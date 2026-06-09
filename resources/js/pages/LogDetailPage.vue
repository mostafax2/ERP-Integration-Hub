<template>
  <div class="page">
    <div class="page-header">
      <router-link to="/logs" class="btn btn--ghost btn--sm">← Back to Logs</router-link>
    </div>

    <div v-if="log" class="log-detail">
      <div class="log-detail__header">
        <h1 class="page-title">Sync Log #{{ log.id }}</h1>
        <span :class="['status-badge', `status-badge--${log.status}`]">{{ log.status }}</span>
      </div>

      <div class="detail-grid">
        <div class="detail-card">
          <h3>Overview</h3>
          <div class="detail-row"><span>Profile</span><strong>{{ log.sync_profile?.name }}</strong></div>
          <div class="detail-row"><span>Connection</span><strong>{{ log.connection?.name }}</strong></div>
          <div class="detail-row"><span>Trigger</span><strong>{{ log.trigger }}</strong></div>
          <div class="detail-row"><span>Started</span><strong>{{ formatDate(log.started_at) }}</strong></div>
          <div class="detail-row"><span>Completed</span><strong>{{ formatDate(log.completed_at) }}</strong></div>
          <div class="detail-row"><span>Duration</span><strong>{{ log.duration }}</strong></div>
        </div>

        <div class="detail-card">
          <h3>Statistics</h3>
          <div class="detail-row"><span>Total Records</span><strong>{{ log.total_records }}</strong></div>
          <div class="detail-row detail-row--success"><span>Succeeded</span><strong>{{ log.success_records }}</strong></div>
          <div class="detail-row detail-row--error"><span>Failed</span><strong>{{ log.failed_records }}</strong></div>
          <div class="detail-row"><span>Skipped</span><strong>{{ log.skipped_records }}</strong></div>
        </div>
      </div>

      <div v-if="log.message" class="error-box">
        <h3>Error Message</h3>
        <pre>{{ log.message }}</pre>
      </div>

      <!-- Failed Syncs Table -->
      <div v-if="log.failed_syncs?.length" class="card">
        <div class="card-header-row">
          <h3>Failed Records ({{ log.failed_syncs.length }})</h3>
          <button class="btn btn--sm btn--warning" @click="retryAllFailed">Retry All Failed</button>
        </div>
        <table class="data-table">
          <thead>
            <tr><th>Record ID</th><th>Error</th><th>Attempts</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
            <tr v-for="f in log.failed_syncs" :key="f.id">
              <td>{{ f.record_id ?? '—' }}</td>
              <td class="text-danger">{{ f.error_message }}</td>
              <td>{{ f.attempt_count }} / {{ f.max_attempts }}</td>
              <td><span :class="['status-badge', `status-badge--${f.status}`]">{{ f.status }}</span></td>
              <td>
                <button class="btn btn--sm btn--outline" @click="retryOne(f)" :disabled="f.status === 'abandoned'">
                  Retry
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="loading-state">{{ t('common.loading') }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { logApi } from '../utils/api'
import { syncApi } from '../utils/api'

const { t }  = useI18n()
const route  = useRoute()
const notify = inject('notify')
const log    = ref(null)

async function load() {
  const res = await logApi.get(route.params.id)
  log.value = res.data?.data
}

async function retryOne(failedSync) {
  try {
    await syncApi.retryOne(failedSync.id)
    notify('Retry queued.', 'success')
    await load()
  } catch (e) {
    notify(e.message, 'error')
  }
}

async function retryAllFailed() {
  const promises = (log.value?.failed_syncs ?? []).filter(f => f.status !== 'abandoned').map(f => retryOne(f))
  await Promise.allSettled(promises)
  notify('All retryable records queued.', 'success')
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString()
}

onMounted(load)
</script>
