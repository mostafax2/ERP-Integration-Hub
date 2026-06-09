<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ t('monitoring.title') }}</h1>
      <div class="header-actions">
        <span class="refresh-label">Auto-refresh: 5s</span>
        <button class="btn btn--sm btn--outline" @click="refresh">↻ Refresh</button>
      </div>
    </div>

    <!-- Health Status Row -->
    <div class="health-row" v-if="health">
      <div v-for="(check, key) in healthChecks" :key="key" :class="['health-card', `health-card--${check.status}`]">
        <span class="health-card__icon">{{ check.status === 'healthy' ? '✓' : '⚠' }}</span>
        <div>
          <div class="health-card__name">{{ key }}</div>
          <div class="health-card__msg">{{ check.message }}</div>
        </div>
      </div>
    </div>

    <!-- KPI Row -->
    <div class="kpi-grid" v-if="stats">
      <div class="kpi-card kpi-card--blue">
        <div class="kpi-card__label">Active Jobs</div>
        <div class="kpi-card__value">{{ stats.sync_profiles?.running ?? 0 }}</div>
      </div>
      <div class="kpi-card kpi-card--yellow">
        <div class="kpi-card__label">Queue Size</div>
        <div class="kpi-card__value">{{ stats.queue_size ?? 0 }}</div>
      </div>
      <div class="kpi-card kpi-card--green">
        <div class="kpi-card__label">Success Rate (30d)</div>
        <div class="kpi-card__value">{{ stats.success_rate ?? 0 }}%</div>
      </div>
      <div class="kpi-card kpi-card--red">
        <div class="kpi-card__label">Failed Jobs</div>
        <div class="kpi-card__value">{{ stats.failed_jobs ?? 0 }}</div>
        <router-link v-if="stats.failed_jobs" to="/failed-jobs" class="kpi-card__link">Retry All →</router-link>
      </div>
      <div class="kpi-card kpi-card--purple">
        <div class="kpi-card__label">Avg Duration</div>
        <div class="kpi-card__value">{{ formatDuration(stats.avg_duration_ms) }}</div>
      </div>
      <div class="kpi-card kpi-card--teal">
        <div class="kpi-card__label">Records (30d)</div>
        <div class="kpi-card__value">{{ formatNumber(stats.records_30d?.total_success ?? 0) }}</div>
      </div>
    </div>

    <!-- Chart -->
    <div class="card chart-card" v-if="chartData.length">
      <h2 class="card__title">Sync Activity — Last 14 Days</h2>
      <div class="bar-chart">
        <div v-for="day in chartData.slice(-14)" :key="day.date" class="bar-chart__column">
          <div class="bar-chart__bars">
            <div class="bar-chart__bar bar-chart__bar--success" :style="{ height: pct(day.completed) + '%' }" :title="`${day.completed} completed`"></div>
            <div class="bar-chart__bar bar-chart__bar--failed"  :style="{ height: pct(day.failed) + '%' }"    :title="`${day.failed} failed`"></div>
          </div>
          <div class="bar-chart__label">{{ shortDate(day.date) }}</div>
        </div>
      </div>
      <div class="chart-legend">
        <span class="legend-dot legend-dot--success"></span> Completed
        <span class="legend-dot legend-dot--failed"></span> Failed
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useMonitoringStore } from '../stores/monitoringStore'

const { t }   = useI18n()
const store   = useMonitoringStore()
const stats   = computed(() => store.stats)
const health  = computed(() => store.health)
const chartData = computed(() => store.chartData)

const healthChecks = computed(() => {
  const h = health.value ?? {}
  return Object.fromEntries(
    Object.entries(h).filter(([, v]) => typeof v === 'object' && v.status)
  )
})

const maxVal = computed(() => {
  if (!chartData.value.length) return 1
  return Math.max(...chartData.value.map(d => d.completed + d.failed), 1)
})

function pct(val) {
  return Math.round((val / maxVal.value) * 100)
}

function shortDate(d) {
  return new Date(d).toLocaleDateString('en', { month: 'short', day: 'numeric' })
}

function formatDuration(ms) {
  if (!ms) return '—'
  return ms < 1000 ? `${ms}ms` : `${Math.round(ms / 1000)}s`
}

function formatNumber(n) {
  return new Intl.NumberFormat().format(n)
}

async function refresh() {
  await Promise.all([store.fetchDashboard(), store.fetchHealth(), store.fetchChartData()])
}

onMounted(() => {
  refresh()
  store.startAutoRefresh(5000)
})

onUnmounted(() => store.stopAutoRefresh())
</script>
