<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ t('connections.title') }}</h1>
      <router-link to="/connections/create" class="btn btn--primary">
        + {{ t('connections.new') }}
      </router-link>
    </div>

    <div v-if="store.loading" class="loading-state">{{ t('common.loading') }}</div>

    <div v-else-if="!store.connections.length" class="empty-state">
      <div class="empty-state__icon">🔌</div>
      <h3>No ERP connections yet</h3>
      <p>Connect your first ERP system to start synchronizing data.</p>
      <router-link to="/connections/create" class="btn btn--primary">Add Connection</router-link>
    </div>

    <div v-else class="cards-grid">
      <div v-for="conn in store.connections" :key="conn.id" class="connection-card">
        <div class="connection-card__header">
          <div class="connection-card__info">
            <h3 class="connection-card__name">{{ conn.name }}</h3>
            <span class="badge badge--driver">{{ conn.driver_label }}</span>
          </div>
          <div :class="['status-badge', `status-badge--${conn.status}`]">
            <span class="status-dot"></span>
            {{ t(`connections.status.${conn.status}`) }}
          </div>
        </div>

        <div class="connection-card__meta">
          <div class="meta-item">
            <span class="meta-label">Environment</span>
            <span class="meta-value">{{ conn.environment_name || '—' }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Profiles</span>
            <span class="meta-value">{{ conn.sync_profiles_count }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Last Connected</span>
            <span class="meta-value">{{ formatDate(conn.last_connected_at) }}</span>
          </div>
        </div>

        <div class="connection-card__actions">
          <button class="btn btn--sm btn--outline" @click="testConnection(conn)" :disabled="testing === conn.id">
            {{ testing === conn.id ? t('connections.testing') : t('connections.test') }}
          </button>
          <router-link :to="`/connections/${conn.id}/edit`" class="btn btn--sm btn--ghost">Edit</router-link>
          <button class="btn btn--sm btn--danger-ghost" @click="confirmDelete(conn)">Delete</button>
        </div>

        <div v-if="testResults[conn.id]" :class="['test-result', testResults[conn.id].success ? 'test-result--ok' : 'test-result--error']">
          {{ testResults[conn.id].message }}
        </div>
      </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
      <div class="modal">
        <h3>Delete Connection</h3>
        <p>Are you sure you want to delete <strong>{{ deleteTarget.name }}</strong>? This will also delete all associated sync profiles.</p>
        <div class="modal__actions">
          <button class="btn btn--danger" @click="deleteConnection">Delete</button>
          <button class="btn btn--ghost" @click="deleteTarget = null">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { inject } from 'vue'
import { useConnectionStore } from '../stores/connectionStore'

const { t }   = useI18n()
const store   = useConnectionStore()
const notify  = inject('notify')

const testing     = ref(null)
const testResults = ref({})
const deleteTarget = ref(null)

async function testConnection(conn) {
  testing.value = conn.id
  testResults.value = { ...testResults.value, [conn.id]: null }
  try {
    const result = await store.test(conn.id)
    testResults.value = { ...testResults.value, [conn.id]: result }
    notify(result.message, result.success ? 'success' : 'error')
  } catch (e) {
    testResults.value = { ...testResults.value, [conn.id]: { success: false, message: e.message } }
    notify(e.message, 'error')
  } finally {
    testing.value = null
  }
}

function confirmDelete(conn) {
  deleteTarget.value = conn
}

async function deleteConnection() {
  try {
    await store.remove(deleteTarget.value.id)
    notify('Connection deleted.', 'success')
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    deleteTarget.value = null
  }
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString()
}

onMounted(() => store.fetchAll())
</script>
