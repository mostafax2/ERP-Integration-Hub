<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ t('nav.failedJobs') }}</h1>
      <div class="header-actions">
        <button class="btn btn--warning" @click="retryAll" :disabled="retrying">
          {{ retrying ? 'Retrying...' : '↻ Retry All' }}
        </button>
      </div>
    </div>

    <div class="filter-bar">
      <select v-model="statusFilter" class="form-select form-select--sm">
        <option value="">All</option>
        <option value="pending_retry">Pending Retry</option>
        <option value="abandoned">Abandoned</option>
        <option value="resolved">Resolved</option>
      </select>
    </div>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Profile</th>
            <th>Record ID</th>
            <th>Error</th>
            <th>Attempts</th>
            <th>Status</th>
            <th>Last Attempted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="job in failedJobs" :key="job.id">
            <td>{{ job.sync_profile?.name ?? '—' }}</td>
            <td class="font-mono text-sm">{{ job.record_id ?? '—' }}</td>
            <td class="text-danger text-sm">{{ truncate(job.error_message, 80) }}</td>
            <td>{{ job.attempt_count }} / {{ job.max_attempts }}</td>
            <td><span :class="['status-badge', `status-badge--${job.status}`]">{{ job.status }}</span></td>
            <td>{{ formatDate(job.last_attempted_at) }}</td>
            <td>
              <button class="btn btn--sm btn--outline"
                @click="retryOne(job)"
                :disabled="job.status === 'abandoned' || job.status === 'resolved'">
                Retry
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!failedJobs.length" class="empty-state">
      <div class="empty-state__icon">✓</div>
      <h3>No failed jobs</h3>
      <p>All sync operations completed successfully.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useMonitoringStore } from '../stores/monitoringStore'
import { syncApi } from '../utils/api'

const { t }    = useI18n()
const store    = useMonitoringStore()
const notify   = inject('notify')

const statusFilter = ref('')
const retrying     = ref(false)

const failedJobs = computed(() => {
  return store.failedJobs.filter(j => !statusFilter.value || j.status === statusFilter.value)
})

async function retryOne(job) {
  try {
    await syncApi.retryOne(job.id)
    notify('Retry queued.', 'success')
    await store.fetchFailedJobs()
  } catch (e) {
    notify(e.message, 'error')
  }
}

async function retryAll() {
  retrying.value = true
  try {
    const res = await store.retryAll()
    notify(res.message, 'success')
    await store.fetchFailedJobs()
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    retrying.value = false
  }
}

function truncate(str, len) {
  return str?.length > len ? str.slice(0, len) + '…' : str
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString()
}

onMounted(() => store.fetchFailedJobs())
</script>
