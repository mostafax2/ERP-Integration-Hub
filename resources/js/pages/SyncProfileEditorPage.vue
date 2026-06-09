<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ isEdit ? 'Edit Sync Profile' : 'New Sync Profile' }}</h1>
    </div>

    <form @submit.prevent="save" class="editor-form">
      <div class="form-grid">
        <div class="form-section">
          <h2 class="form-section-title">Basic Info</h2>
          <div class="form-group">
            <label>Profile Name *</label>
            <input v-model="form.name" class="form-input" placeholder="e.g. Students → Customers" required />
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="form.description" class="form-textarea" rows="2" />
          </div>
          <div class="form-group">
            <label>ERP Connection *</label>
            <select v-model="form.connection_id" class="form-select" required>
              <option value="">— Select Connection —</option>
              <option v-for="c in connections" :key="c.id" :value="c.id">{{ c.name }} ({{ c.driver_label }})</option>
            </select>
          </div>
        </div>

        <div class="form-section">
          <h2 class="form-section-title">Source (Laravel)</h2>
          <div class="form-group">
            <label>Source Model *</label>
            <select v-model="form.source_model" class="form-select" required>
              <option value="">— Detect & Select Model —</option>
              <option v-for="m in models" :key="m.class" :value="m.class">{{ m.name }} ({{ m.table }})</option>
            </select>
          </div>
          <div class="form-group">
            <label>Source Primary Key</label>
            <input v-model="form.source_key" class="form-input" placeholder="id" />
          </div>
        </div>

        <div class="form-section">
          <h2 class="form-section-title">Destination (ERP)</h2>
          <div class="form-group">
            <label>ERP Entity *</label>
            <select v-model="form.destination_entity" class="form-select" required>
              <option value="">— Select Entity —</option>
              <option v-for="e in entities" :key="e.name ?? e" :value="e.name ?? e">{{ e.name ?? e }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Entity Key Field</label>
            <input v-model="form.destination_key" class="form-input" placeholder="No" />
          </div>
        </div>

        <div class="form-section">
          <h2 class="form-section-title">Sync Settings</h2>
          <div class="form-group">
            <label>Sync Mode *</label>
            <select v-model="form.sync_mode" class="form-select">
              <option value="manual">Manual</option>
              <option value="scheduled">Scheduled</option>
              <option value="realtime">Real-Time</option>
              <option value="incremental">Incremental</option>
              <option value="full">Full Sync</option>
              <option value="event_driven">Event Driven</option>
            </select>
          </div>
          <div class="form-group">
            <label>Direction</label>
            <select v-model="form.direction" class="form-select">
              <option value="push">Push (Laravel → ERP)</option>
              <option value="pull">Pull (ERP → Laravel)</option>
              <option value="bidirectional">Bidirectional</option>
            </select>
          </div>
          <div class="form-group">
            <label>Conflict Resolution</label>
            <select v-model="form.conflict_resolution" class="form-select">
              <option value="source_wins">Source Wins</option>
              <option value="destination_wins">Destination Wins</option>
              <option value="manual">Manual</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Chunk Size</label>
              <input v-model.number="form.chunk_size" type="number" class="form-input" placeholder="500" />
            </div>
            <div class="form-group">
              <label>Retry Limit</label>
              <input v-model.number="form.retry_limit" type="number" class="form-input" placeholder="3" />
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <router-link to="/sync-profiles" class="btn btn--ghost">Cancel</router-link>
        <button type="submit" class="btn btn--primary" :disabled="saving">
          {{ saving ? 'Saving...' : t('common.save') }}
        </button>
      </div>

      <div v-if="errorMsg" class="error-banner">{{ errorMsg }}</div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, inject } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useSyncProfileStore } from '../stores/syncProfileStore'
import { useConnectionStore } from '../stores/connectionStore'
import { connectionApi } from '../utils/api'

const { t }   = useI18n()
const router  = useRouter()
const route   = useRoute()
const store   = useSyncProfileStore()
const connStore = useConnectionStore()
const notify  = inject('notify')

const isEdit    = computed(() => !!route.params.id)
const saving    = ref(false)
const errorMsg  = ref(null)
const entities  = ref([])
const models    = computed(() => store.models)
const connections = computed(() => connStore.connections)

const form = ref({
  name: '', description: '', connection_id: '', source_model: '', source_key: 'id',
  destination_entity: '', destination_key: 'No', destination_company: '',
  sync_mode: 'manual', direction: 'push', conflict_resolution: 'source_wins',
  status: 'active', chunk_size: 500, retry_limit: 3, priority: 50,
})

watch(() => form.value.connection_id, async (newId) => {
  if (newId) {
    try {
      const res = await connectionApi.entities(newId)
      entities.value = res.data?.data ?? []
    } catch { entities.value = [] }
  }
})

async function save() {
  saving.value  = true
  errorMsg.value = null
  try {
    if (isEdit.value) {
      await store.update(route.params.id, form.value)
    } else {
      await store.create(form.value)
    }
    notify('Profile saved.', 'success')
    router.push('/sync-profiles')
  } catch (e) {
    errorMsg.value = e.message
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([connStore.fetchAll(), store.detectModels()])
  if (isEdit.value) {
    const profile = await store.fetchOne(route.params.id)
    if (profile) Object.assign(form.value, profile)
  }
})
</script>
