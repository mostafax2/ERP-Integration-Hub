<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ t('syncProfiles.title') }}</h1>
      <router-link to="/sync-profiles/create" class="btn btn--primary">+ {{ t('syncProfiles.new') }}</router-link>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <input v-model="search" type="text" class="form-input form-input--sm" :placeholder="t('common.search')" />
      <select v-model="statusFilter" class="form-select form-select--sm">
        <option value="">All Statuses</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="paused">Paused</option>
      </select>
    </div>

    <div v-if="store.loading" class="loading-state">{{ t('common.loading') }}</div>

    <div v-else-if="!store.profiles.length" class="empty-state">
      <div class="empty-state__icon">↔</div>
      <h3>No sync profiles yet</h3>
      <p>Create a sync profile to map your Laravel models to ERP entities.</p>
      <router-link to="/sync-profiles/create" class="btn btn--primary">Create Profile</router-link>
    </div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Profile</th>
            <th>Source → Destination</th>
            <th>Mode</th>
            <th>Status</th>
            <th>Last Sync</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="profile in filteredProfiles" :key="profile.id">
            <td>
              <div class="table-name">{{ profile.name }}</div>
              <div class="table-sub">{{ profile.connection?.name }}</div>
            </td>
            <td>
              <div class="mapping-flow">
                <span class="model-chip">{{ shortModel(profile.source_model) }}</span>
                <span class="flow-arrow">→</span>
                <span class="entity-chip">{{ profile.destination_entity }}</span>
              </div>
            </td>
            <td>
              <span :class="['mode-badge', `mode-badge--${profile.sync_mode}`]">
                {{ t(`syncProfiles.modes.${profile.sync_mode}`) }}
              </span>
            </td>
            <td>
              <span :class="['status-badge', `status-badge--${profile.status}`]">
                {{ profile.status }}
              </span>
            </td>
            <td>{{ formatDate(profile.last_synced_at) }}</td>
            <td>
              <div class="action-group">
                <button class="btn btn--sm btn--success" @click="runSync(profile)" :disabled="store.running[profile.id] || profile.is_running">
                  {{ store.running[profile.id] ? '...' : t('syncProfiles.run') }}
                </button>
                <router-link :to="`/sync-profiles/${profile.id}/mapping`" class="btn btn--sm btn--outline">
                  Mapping
                </router-link>
                <router-link :to="`/sync-profiles/${profile.id}`" class="btn btn--sm btn--ghost">Edit</router-link>
                <button class="btn btn--sm btn--danger-ghost" @click="confirmDelete(profile)">×</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Delete Modal -->
    <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
      <div class="modal">
        <h3>Delete Sync Profile</h3>
        <p>Delete <strong>{{ deleteTarget.name }}</strong>? All field mappings and logs will be removed.</p>
        <div class="modal__actions">
          <button class="btn btn--danger" @click="deleteProfile">Delete</button>
          <button class="btn btn--ghost" @click="deleteTarget = null">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSyncProfileStore } from '../stores/syncProfileStore'

const { t }    = useI18n()
const store    = useSyncProfileStore()
const notify   = inject('notify')

const search       = ref('')
const statusFilter = ref('')
const deleteTarget = ref(null)

const filteredProfiles = computed(() => {
  return store.profiles.filter(p => {
    const matchSearch = !search.value || p.name.toLowerCase().includes(search.value.toLowerCase())
    const matchStatus = !statusFilter.value || p.status === statusFilter.value
    return matchSearch && matchStatus
  })
})

async function runSync(profile) {
  try {
    const result = await store.runSync(profile.id, { async: true })
    notify(result.message, result.success ? 'success' : 'error')
  } catch (e) {
    notify(e.message, 'error')
  }
}

function confirmDelete(profile) {
  deleteTarget.value = profile
}

async function deleteProfile() {
  try {
    await store.remove(deleteTarget.value.id)
    notify('Profile deleted.', 'success')
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    deleteTarget.value = null
  }
}

function shortModel(model) {
  return model?.split('\\').pop() ?? model
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString()
}

onMounted(() => store.fetchAll())
</script>
