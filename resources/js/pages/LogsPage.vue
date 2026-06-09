<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ t('nav.logs') }}</h1>
      <button class="btn btn--outline btn--sm" @click="exportLogs">Export CSV</button>
    </div>

    <div class="filter-bar">
      <select v-model="filters.status" class="form-select form-select--sm">
        <option value="">All Statuses</option>
        <option value="completed">Completed</option>
        <option value="failed">Failed</option>
        <option value="running">Running</option>
        <option value="cancelled">Cancelled</option>
        <option value="partial">Partial</option>
      </select>
      <input v-model="filters.from" type="date" class="form-input form-input--sm" />
      <input v-model="filters.to" type="date" class="form-input form-input--sm" />
      <button class="btn btn--sm btn--outline" @click="loadLogs">Apply</button>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar" v-if="logStats">
      <div class="stat-pill stat-pill--total">Total: {{ logStats.total }}</div>
      <div class="stat-pill stat-pill--success">Completed: {{ logStats.completed }}</div>
      <div class="stat-pill stat-pill--failed">Failed: {{ logStats.failed }}</div>
      <div class="stat-pill stat-pill--rate">Success Rate: {{ logStats.success_rate }}%</div>
      <div class="stat-pill stat-pill--records">Records: {{ formatNumber(logStats.total_records_processed) }}</div>
    </div>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Profile</th>
            <th>Trigger</th>
            <th>Status</th>
            <th>Records</th>
            <th>Duration</th>
            <th>Started</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id">
            <td class="text-muted">#{{ log.id }}</td>
            <td>{{ log.sync_profile?.name ?? '—' }}</td>
            <td><span class="trigger-badge">{{ log.trigger }}</span></td>
            <td><span :class="['status-badge', `status-badge--${log.status}`]">{{ log.status }}</span></td>
            <td>
              <span class="text-success">{{ log.success_records }}</span> /
              <span class="text-muted">{{ log.total_records }}</span>
              <span v-if="log.failed_records" class="text-danger"> ({{ log.failed_records }} failed)</span>
            </td>
            <td>{{ log.duration }}</td>
            <td>{{ formatDate(log.started_at) }}</td>
            <td>
              <router-link :to="`/logs/${log.id}`" class="btn btn--sm btn--ghost">View</router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!logs.length && !loading" class="empty-state">
      <p>{{ t('common.noData') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useMonitoringStore } from '../stores/monitoringStore'
import { logApi } from '../utils/api'

const { t }  = useI18n()
const store  = useMonitoringStore()
const logs   = ref([])
const logStats = ref(null)
const loading  = ref(false)

const filters = ref({ status: '', from: '', to: '' })

async function loadLogs() {
  loading.value = true
  try {
    const res = await store.fetchLogs(filters.value)
    logs.value = store.logs
    const statsRes = await store.fetchLogStats()
    logStats.value = store.logStats
  } finally {
    loading.value = false
  }
}

async function exportLogs() {
  const res = await logApi.export(filters.value)
  const url = URL.createObjectURL(res.data)
  const a   = document.createElement('a')
  a.href = url
  a.download = 'sync_logs.csv'
  a.click()
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString()
}

function formatNumber(n) {
  return n ? new Intl.NumberFormat().format(n) : 0
}

onMounted(loadLogs)
</script>
