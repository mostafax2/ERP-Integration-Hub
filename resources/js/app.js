import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import routes from './routes'
import i18n from './i18n'
import './css/app.css'

const router = createRouter({
    history: createWebHistory(window.__BRIDGE_CONFIG__?.route_prefix ?? '/erp-integration-hub'),
    routes,
    scrollBehavior: () => ({ top: 0 }),
})

const pinia = createPinia()

const app = createApp(App)
app.use(pinia)
app.use(router)
app.use(i18n)
app.mount('#erp-integration-hub-app')
