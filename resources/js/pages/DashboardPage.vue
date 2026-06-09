<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ t('monitoring.title') }}</h1>
      <button class="btn btn--primary btn--sm" @click="refresh" :disabled="store.loading">
        <span>{{ t('common.refresh') }}</span>
      </button>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid" v-if="stats">
      <div class="kpi-card kpi-card--blue">
        <div class="kpi-card__label">{{ t('monitoring.activeJobs') }}</div>
        <div class="kpi-card__value">{{ stats.sync_profiles?.running ?? 0 }}</div>
        <div class="kpi-card__sub">{{ stats.sync_profiles?.active }} active profiles</div>
      </div>
      <div class="kpi-card kpi-card--yellow">
        <div class="kpi-card__label">{{ t('monitoring.pendingJobs') }}</div>
        <div class="kpi-card__value">{{ stats.jobs_today?.pending ?? 0 }}</div>
        <div class="kpi-card__sub">Queue size: {{ stats.queue_size ?? 0 }}</div>
      </div>
      <div class="kpi-card kpi-card--green">
        <div class="kpi-card__label">{{ t('monitoring.completedJobs') }}</div>
        <div class="kpi-card__value">{{ stats.jobs_today?.completed ?? 0 }}</div>
        <div class="kpi-card__sub">Today</div>
      </div>
      <div class="kpi-card kpi-card--red">
        <div class="kpi-card__label">{{ t('monitoring.failedJobs') }}</div>
        <div class="kpi-card__value">{{ stats.failed_jobs ?? 0 }}</div>
        <div class="kpi-card__sub">Pending retry</div>
      </div>
      <div class="kpi-card kpi-card--purple">
        <div class="kpi-card__label">{{ t('monitoring.successRate') }}</div>
        <div class="kpi-card__value">{{ stats.success_rate ?? 0 }}%</div>
        <div class="kpi-card__sub">Last 30 days</div>
      </div>
      <div class="kpi-card kpi-card--teal">
        <div class="kpi-card__label">{{ t('monitoring.recordsProcessed') }}</div>
        <div class="kpi-card__value">{{ formatNumber(stats.records_30d?.total_success ?? 0) }}</div>
        <div class="kpi-card__sub">Last 30 days</div>
      </div>
    </div>

    <!-- Connections Summary -->
    <div class="section-grid">
      <div class="card">
        <h2 class="card__title">ERP Connections</h2>
        <div class="stat-row" v-if="stats">
          <span class="stat-label">Total</span>
          <span class="stat-value">{{ stats.connections?.total ?? 0 }}</span>
        </div>
        <div class="stat-row stat-row--success" v-if="stats">
          <span class="stat-label">Active</span>
          <span class="stat-value">{{ stats.connections?.active ?? 0 }}</span>
        </div>
        <div class="stat-row stat-row--error" v-if="stats">
          <span class="stat-label">Error</span>
          <span class="stat-value">{{ stats.connections?.error ?? 0 }}</span>
        </div>
        <router-link to="/connections" class="card__link">Manage Connections →</router-link>
      </div>

      <div class="card">
        <h2 class="card__title">Sync Activity (14 days)</h2>
        <div class="mini-chart" v-if="chartData.length">
          <div v-for="day in chartData.slice(-14)" :key="day.date" class="mini-chart__bar-wrap" :title="`${day.date}: ${day.completed} ok, ${day.failed} failed`">
            <div class="mini-chart__bar">
              <div class="mini-chart__bar--success" :style="{ height: barHeight(day.completed) + '%' }"></div>
              <div class="mini-chart__bar--failed" :style="{ height: barHeight(day.failed) + '%' }"></div>
            </div>
          </div>
        </div>
        <p v-else class="text-muted">No sync activity yet</p>
      </div>

      <div class="card">
        <h2 class="card__title">Quick Actions</h2>
        <div class="quick-actions">
          <router-link to="/connections/create" class="quick-action">
            <span class="quick-action__icon">+</span>
            Add Connection
          </router-link>
          <router-link to="/sync-profiles/create" class="quick-action">
            <span class="quick-action__icon">↔</span>
            New Sync Profile
          </router-link>
          <router-link to="/failed-jobs" class="quick-action quick-action--warning">
            <span class="quick-action__icon">⚠</span>
            View Failed Jobs
          </router-link>
          <router-link to="/monitoring" class="quick-action">
            <span class="quick-action__icon">📊</span>
            Full Monitoring
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useMonitoringStore } from '../stores/monitoringStore'

const { t }  = useI18n()
const store  = useMonitoringStore()
const stats  = computed(() => store.stats)
const chartData = computed(() => store.chartData)

const maxChartValue = computed(() => {
  if (!chartData.value.length) return 1
  return Math.max(...chartData.value.map(d => d.completed + d.failed), 1)
})

function barHeight(val) {
  return Math.round((val / maxChartValue.value) * 100)
}

function formatNumber(n) {
  return new Intl.NumberFormat().format(n)
}

async function refresh() {
  await Promise.all([store.fetchDashboard(), store.fetchChartData()])
}

onMounted(() => {
  refresh()
  store.startAutoRefresh(window.__BRIDGE_CONFIG__?.refresh_interval ?? 5000)
})

onUnmounted(() => store.stopAutoRefresh())
</script>
