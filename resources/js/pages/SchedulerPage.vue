<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ t('nav.scheduler') }}</h1>
    </div>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Profile</th>
            <th>Frequency</th>
            <th>Cron Expression</th>
            <th>Timezone</th>
            <th>Next Run</th>
            <th>Last Run</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in schedules" :key="s.id">
            <td>{{ s.sync_profile?.name ?? '—' }}</td>
            <td>{{ s.frequency }}</td>
            <td><code class="cron">{{ s.cron_expression }}</code></td>
            <td>{{ s.timezone }}</td>
            <td>{{ formatDate(s.next_run_at) }}</td>
            <td>{{ formatDate(s.last_run_at) }}</td>
            <td>
              <span :class="['status-badge', s.is_active ? 'status-badge--active' : 'status-badge--inactive']">
                {{ s.is_active ? 'Active' : 'Paused' }}
              </span>
            </td>
            <td>
              <button class="btn btn--sm btn--outline" @click="toggle(s)">
                {{ s.is_active ? 'Pause' : 'Resume' }}
              </button>
              <button class="btn btn--sm btn--danger-ghost" @click="remove(s)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!schedules.length" class="empty-state">
      <div class="empty-state__icon">📅</div>
      <h3>No schedules configured</h3>
      <p>Open a Sync Profile and add a schedule to automate synchronization.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { schedulerApi } from '../utils/api'

const { t }  = useI18n()
const notify = inject('notify')

const schedules = ref([])

async function load() {
  const res   = await schedulerApi.list()
  schedules.value = res.data?.data ?? []
}

async function toggle(s) {
  await schedulerApi.toggle(s.sync_profile_id, s.id)
  await load()
}

async function remove(s) {
  if (!confirm('Delete this schedule?')) return
  await schedulerApi.delete(s.sync_profile_id, s.id)
  await load()
  notify('Schedule deleted.', 'success')
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString()
}

onMounted(load)
</script>
