<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()
const cfg = window.__BRIDGE_CONFIG__ ?? {}

onMounted(() => {
  // Apply RTL direction for Arabic
  if (cfg.rtl_locales?.includes(locale.value)) {
    document.documentElement.setAttribute('dir', 'rtl')
    document.documentElement.setAttribute('lang', locale.value)
  }

  // Apply saved theme
  const savedTheme = localStorage.getItem('erp-bridge-theme') ?? cfg.default_theme ?? 'dark'
  document.documentElement.classList.toggle('dark', savedTheme === 'dark')
})
</script>
