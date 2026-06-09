<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ t('fieldMapping.title') }}: {{ profile?.name }}</h1>
      <div class="header-actions">
        <button class="btn btn--outline btn--sm" @click="autoMap" :disabled="autoMapping">
          {{ autoMapping ? 'Mapping...' : t('fieldMapping.autoMap') }}
        </button>
        <button class="btn btn--outline btn--sm" @click="showPreview = !showPreview">
          {{ t('fieldMapping.preview') }}
        </button>
        <button class="btn btn--primary" @click="saveMappings" :disabled="saving">
          {{ saving ? 'Saving...' : t('common.save') }}
        </button>
      </div>
    </div>

    <!-- Field columns header -->
    <div class="mapping-header">
      <div class="mapping-col mapping-col--source">
        <strong>Source ({{ sourceModelName }})</strong>
        <span class="field-count">{{ sourceFields.length }} fields</span>
      </div>
      <div class="mapping-col mapping-col--transform">Transformation</div>
      <div class="mapping-col mapping-col--dest">
        <strong>Destination ({{ profile?.destination_entity }})</strong>
        <span class="field-count">{{ destFields.length }} fields</span>
      </div>
      <div class="mapping-col mapping-col--options">Options</div>
    </div>

    <!-- Mapping Rows -->
    <div class="mapping-rows">
      <div v-for="(row, idx) in mappings" :key="idx" :class="['mapping-row', { 'mapping-row--ignored': row.is_ignored, 'mapping-row--key': row.is_key_field }]">
        <!-- Source Field -->
        <div class="mapping-col mapping-col--source">
          <select v-model="row.source_field" class="form-select form-select--sm" :disabled="row.is_ignored">
            <option value="">— Select source field —</option>
            <option v-for="f in sourceFields" :key="f" :value="f">{{ f }}</option>
          </select>
        </div>

        <!-- Transformation -->
        <div class="mapping-col mapping-col--transform">
          <select v-model="row.transformation" class="form-select form-select--sm" :disabled="row.is_ignored">
            <option v-for="tr in transformers" :key="tr.value" :value="tr.value">{{ tr.label }}</option>
          </select>
        </div>

        <!-- Destination Field -->
        <div class="mapping-col mapping-col--dest">
          <select v-model="row.destination_field" class="form-select form-select--sm" :disabled="row.is_ignored">
            <option value="">— Select destination field —</option>
            <option v-for="f in destFields" :key="f.fieldname ?? f" :value="f.fieldname ?? f">
              {{ f.label ?? f.fieldname ?? f }}
            </option>
          </select>
        </div>

        <!-- Options -->
        <div class="mapping-col mapping-col--options">
          <label class="checkbox-label" :title="'Key field for upsert matching'">
            <input type="checkbox" v-model="row.is_key_field" class="checkbox" />
            <span class="checkbox-text">Key</span>
          </label>
          <label class="checkbox-label" :title="'Ignore this mapping'">
            <input type="checkbox" v-model="row.is_ignored" class="checkbox" />
            <span class="checkbox-text">Skip</span>
          </label>
          <button class="btn-icon btn-icon--danger" @click="removeRow(idx)" title="Remove">×</button>
        </div>
      </div>
    </div>

    <!-- Add Row Button -->
    <button class="btn btn--dashed btn--full" @click="addRow">
      + {{ t('fieldMapping.addRow') }}
    </button>

    <!-- Preview Panel -->
    <div v-if="showPreview" class="preview-panel">
      <h3>Mapping Preview</h3>
      <div class="form-group">
        <label>Sample JSON Record</label>
        <textarea v-model="previewJson" class="form-textarea font-mono" rows="6" placeholder='{"id":1,"name":"Ahmed Ali","email":"ahmed@example.com"}'></textarea>
      </div>
      <button class="btn btn--outline" @click="runPreview">Preview Mapping</button>
      <div v-if="previewResult" class="preview-result">
        <div :class="['preview-badge', previewResult.success ? 'preview-badge--ok' : 'preview-badge--error']">
          {{ previewResult.success ? 'Mapping Successful' : 'Mapping Error' }}
        </div>
        <pre class="preview-json">{{ JSON.stringify(previewResult.mapped ?? previewResult.error, null, 2) }}</pre>
      </div>
    </div>

    <div v-if="saved" class="success-banner">✓ Field mappings saved successfully.</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { mappingApi, profileApi } from '../utils/api'
import { connectionApi } from '../utils/api'

const { t }    = useI18n()
const route    = useRoute()
const notify   = inject('notify')

const profile      = ref(null)
const mappings     = ref([])
const sourceFields = ref([])
const destFields   = ref([])
const transformers = ref([])
const saving       = ref(false)
const autoMapping  = ref(false)
const saved        = ref(false)
const showPreview  = ref(false)
const previewJson  = ref('')
const previewResult = ref(null)

const profileId       = route.params.id
const sourceModelName = computed(() => profile.value?.source_model?.split('\\').pop() ?? '')

function addRow() {
  mappings.value.push({ source_field: '', destination_field: '', transformation: 'none', is_key_field: false, is_ignored: false })
}

function removeRow(idx) {
  mappings.value.splice(idx, 1)
}

async function saveMappings() {
  saving.value = true
  saved.value  = false
  try {
    await mappingApi.save(profileId, mappings.value)
    saved.value = true
    notify('Field mappings saved.', 'success')
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    saving.value = false
  }
}

async function autoMap() {
  autoMapping.value = true
  try {
    const res = await mappingApi.autoMap(
      profileId,
      sourceFields.value,
      destFields.value.map(f => f.fieldname ?? f)
    )
    const suggestions = res.data?.data ?? []
    mappings.value = suggestions.map(s => ({
      source_field:      s.source_field,
      destination_field: s.destination_field,
      transformation:    'none',
      is_key_field:      false,
      is_ignored:        false,
    }))
    notify(`${suggestions.length} fields auto-mapped.`, 'success')
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    autoMapping.value = false
  }
}

async function runPreview() {
  try {
    const sample = JSON.parse(previewJson.value || '{}')
    const res    = await mappingApi.preview(profileId, sample)
    previewResult.value = res.data?.data
  } catch (e) {
    previewResult.value = { success: false, error: e.message }
  }
}

onMounted(async () => {
  const [profileRes, mappingsRes, transformersRes] = await Promise.all([
    profileApi.get(profileId),
    mappingApi.list(profileId),
    mappingApi.transformers(),
  ])

  profile.value    = profileRes.data?.data
  mappings.value   = mappingsRes.data?.data ?? []
  transformers.value = transformersRes.data?.data ?? []

  if (!mappings.value.length) addRow()

  // Load source model fields
  if (profile.value?.source_model) {
    try {
      const modelRes = await profileApi.analyzeModel(profile.value.source_model)
      sourceFields.value = modelRes.data?.data?.columns ?? []
    } catch {}
  }

  // Load destination entity fields
  if (profile.value?.connection_id && profile.value?.destination_entity) {
    try {
      const fieldsRes = await connectionApi.entityFields(profile.value.connection_id, profile.value.destination_entity)
      destFields.value = fieldsRes.data?.data ?? []
    } catch {}
  }
})
</script>
