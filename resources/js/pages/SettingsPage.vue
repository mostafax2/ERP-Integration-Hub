<template>
  <div class="page">
    <div class="page-header">
      <h1 class="page-title">{{ t('nav.settings') }}</h1>
      <button class="btn btn--primary" @click="saveSettings" :disabled="saving">
        {{ saving ? 'Saving...' : t('common.save') }}
      </button>
    </div>

    <div v-if="loading" class="loading-state">{{ t('common.loading') }}</div>

    <div v-else class="settings-grid">
      <div v-for="(groupSettings, group) in settingsByGroup" :key="group" class="settings-section">
        <h2 class="settings-section-title">{{ capitalize(group) }}</h2>
        <div v-for="setting in groupSettings" :key="setting.key" class="form-group">
          <label>{{ formatKey(setting.key) }}</label>
          <p class="form-hint" v-if="setting.description">{{ setting.description }}</p>
          <input v-if="setting.type === 'int' || setting.type === 'float'"
            v-model.number="formValues[setting.key]" type="number" class="form-input" />
          <input v-else-if="setting.type === 'bool'"
            v-model="formValues[setting.key]" type="checkbox" class="checkbox" />
          <textarea v-else-if="setting.type === 'json'"
            v-model="formValues[setting.key]" class="form-textarea font-mono" rows="3" />
          <input v-else v-model="formValues[setting.key]" type="text" class="form-input" />
        </div>
      </div>
    </div>

    <div v-if="saved" class="success-banner">✓ Settings saved.</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { settingsApi } from '../utils/api'

const { t }  = useI18n()
const notify = inject('notify')

const loading   = ref(false)
const saving    = ref(false)
const saved     = ref(false)
const allSettings = ref({})
const formValues  = ref({})

const settingsByGroup = computed(() => allSettings.value)

async function load() {
  loading.value = true
  const res = await settingsApi.list()
  allSettings.value = res.data?.data ?? {}
  for (const [, settings] of Object.entries(allSettings.value)) {
    for (const s of settings) {
      formValues.value[s.key] = s.value
    }
  }
  loading.value = false
}

async function saveSettings() {
  saving.value = true
  saved.value  = false
  try {
    await settingsApi.update(formValues.value)
    saved.value = true
    notify('Settings saved.', 'success')
  } catch (e) {
    notify(e.message, 'error')
  } finally {
    saving.value = false
  }
}

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function formatKey(key) {
  return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

onMounted(load)
</script>
