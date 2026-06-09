<template>
  <div class="erp-bridge-layout" :class="{ dark: isDark, rtl: isRTL }">
    <!-- Sidebar -->
    <aside class="sidebar" :class="{ 'sidebar--collapsed': sidebarCollapsed }">
      <div class="sidebar__header">
        <div class="sidebar__logo">
          <svg class="sidebar__icon" viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="10" fill="#3b82f6"/>
            <path d="M10 20h8M22 20h8M20 10v8M20 22v8" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="20" cy="20" r="3" fill="white"/>
          </svg>
          <span class="sidebar__title" v-if="!sidebarCollapsed">ERP Bridge</span>
        </div>
        <button class="sidebar__toggle" @click="sidebarCollapsed = !sidebarCollapsed">
          <span>{{ sidebarCollapsed ? '→' : '←' }}</span>
        </button>
      </div>

      <nav class="sidebar__nav">
        <router-link v-for="item in navItems" :key="item.to" :to="item.to" class="nav-item" :title="sidebarCollapsed ? t(item.labelKey) : ''">
          <span class="nav-item__icon" v-html="item.icon"></span>
          <span class="nav-item__label" v-if="!sidebarCollapsed">{{ t(item.labelKey) }}</span>
          <span v-if="item.badge && !sidebarCollapsed" class="nav-item__badge">{{ item.badge }}</span>
        </router-link>
      </nav>

      <div class="sidebar__footer" v-if="!sidebarCollapsed">
        <button class="btn-icon" @click="toggleTheme" :title="isDark ? 'Light Mode' : 'Dark Mode'">
          <span>{{ isDark ? '☀️' : '🌙' }}</span>
        </button>
        <button class="btn-icon" @click="toggleLocale" :title="currentLocale === 'en' ? 'العربية' : 'English'">
          {{ currentLocale === 'en' ? 'ع' : 'EN' }}
        </button>
        <span class="sidebar__version">v1.0.0</span>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <header class="top-bar">
        <div class="top-bar__breadcrumb">
          <router-link to="/" class="breadcrumb-home">ERP Bridge</router-link>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">{{ currentPageTitle }}</span>
        </div>
        <div class="top-bar__actions">
          <div v-if="healthStatus" class="health-indicator" :class="`health-indicator--${overallHealth}`">
            <span class="health-dot"></span>
            <span class="health-label">{{ overallHealth === 'healthy' ? 'All Systems OK' : 'Warning' }}</span>
          </div>
        </div>
      </header>

      <main class="page-content">
        <router-view v-slot="{ Component }">
          <transition name="page-fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>

    <!-- Global Notifications -->
    <div class="notifications-container">
      <transition-group name="notif">
        <div v-for="n in notifications" :key="n.id" class="notification" :class="`notification--${n.type}`">
          <span class="notification__message">{{ n.message }}</span>
          <button class="notification__close" @click="removeNotification(n.id)">×</button>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, provide } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { monitoringApi } from '../../utils/api'

const { t, locale } = useI18n()
const route         = useRoute()

const isDark            = ref(localStorage.getItem('erp-bridge-theme') !== 'light')
const sidebarCollapsed  = ref(false)
const healthStatus      = ref(null)
const notifications     = ref([])
let   notifCounter      = 0

const currentLocale = computed(() => locale.value)
const isRTL         = computed(() => locale.value === 'ar')

const navItems = [
  { to: '/',              labelKey: 'nav.dashboard',    icon: '<svg viewBox="0 0 20 20" fill="currentColor" width="18"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/></svg>' },
  { to: '/connections',   labelKey: 'nav.connections',  icon: '<svg viewBox="0 0 20 20" fill="currentColor" width="18"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>' },
  { to: '/sync-profiles', labelKey: 'nav.syncProfiles', icon: '<svg viewBox="0 0 20 20" fill="currentColor" width="18"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1h3.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1v-5.586a1 1 0 00-.293-.707L16 3.293A1 1 0 0015.293 3H4a1 1 0 00-1 1z"/></svg>' },
  { to: '/scheduler',     labelKey: 'nav.scheduler',    icon: '<svg viewBox="0 0 20 20" fill="currentColor" width="18"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>' },
  { to: '/monitoring',    labelKey: 'nav.monitoring',   icon: '<svg viewBox="0 0 20 20" fill="currentColor" width="18"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>' },
  { to: '/logs',          labelKey: 'nav.logs',         icon: '<svg viewBox="0 0 20 20" fill="currentColor" width="18"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>' },
  { to: '/failed-jobs',   labelKey: 'nav.failedJobs',   icon: '<svg viewBox="0 0 20 20" fill="currentColor" width="18"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>' },
  { to: '/settings',      labelKey: 'nav.settings',     icon: '<svg viewBox="0 0 20 20" fill="currentColor" width="18"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>' },
]

const routeTitles = {
  '/':              'nav.dashboard',
  '/connections':   'nav.connections',
  '/sync-profiles': 'nav.syncProfiles',
  '/scheduler':     'nav.scheduler',
  '/monitoring':    'nav.monitoring',
  '/logs':          'nav.logs',
  '/failed-jobs':   'nav.failedJobs',
  '/settings':      'nav.settings',
}

const currentPageTitle = computed(() => {
  const key = routeTitles[route.path] ?? 'nav.dashboard'
  return t(key)
})

const overallHealth = computed(() => {
  if (! healthStatus.value) return 'healthy'
  const checks = Object.values(healthStatus.value).filter(v => typeof v === 'object' && v.status)
  return checks.every(c => c.status === 'healthy') ? 'healthy' : 'warning'
})

function toggleTheme() {
  isDark.value = !isDark.value
  localStorage.setItem('erp-bridge-theme', isDark.value ? 'dark' : 'light')
  document.documentElement.classList.toggle('dark', isDark.value)
}

function toggleLocale() {
  locale.value = locale.value === 'en' ? 'ar' : 'en'
  const isRtl  = locale.value === 'ar'
  document.documentElement.setAttribute('dir', isRtl ? 'rtl' : 'ltr')
  document.documentElement.setAttribute('lang', locale.value)
}

function notify(message, type = 'success') {
  const id = ++notifCounter
  notifications.value.push({ id, message, type })
  setTimeout(() => removeNotification(id), 4000)
}

function removeNotification(id) {
  notifications.value = notifications.value.filter(n => n.id !== id)
}

provide('notify', notify)

onMounted(async () => {
  try {
    const res = await monitoringApi.health()
    healthStatus.value = res.data?.data
  } catch {}
})
</script>
