import { defineStore } from 'pinia'
import { ref } from 'vue'
import { monitoringApi, logApi, syncApi } from '../utils/api'

export const useMonitoringStore = defineStore('monitoring', () => {
    const stats     = ref(null)
    const health    = ref(null)
    const chartData = ref([])
    const logs      = ref([])
    const logStats  = ref(null)
    const failedJobs = ref([])
    const loading   = ref(false)
    let   refreshTimer = null

    async function fetchDashboard() {
        loading.value = true
        try {
            const res   = await monitoringApi.dashboard()
            stats.value = res.data?.data
        } finally {
            loading.value = false
        }
    }

    async function fetchHealth() {
        const res   = await monitoringApi.health()
        health.value = res.data?.data
    }

    async function fetchChartData() {
        const res      = await monitoringApi.chartData()
        chartData.value = res.data?.data ?? []
    }

    async function fetchLogs(params = {}) {
        const res = await logApi.list(params)
        logs.value = res.data?.data ?? res.data?.data?.data ?? []
        return res.data
    }

    async function fetchLogStats() {
        const res     = await logApi.statistics()
        logStats.value = res.data?.data
    }

    async function fetchFailedJobs(params = {}) {
        const res      = await logApi.failedJobs(params)
        failedJobs.value = res.data?.data ?? []
        return res.data
    }

    async function retryAll() {
        return (await syncApi.retryAll()).data
    }

    function startAutoRefresh(intervalMs = 5000) {
        stopAutoRefresh()
        refreshTimer = setInterval(() => { fetchDashboard() }, intervalMs)
    }

    function stopAutoRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer)
            refreshTimer = null
        }
    }

    return {
        stats, health, chartData, logs, logStats, failedJobs, loading,
        fetchDashboard, fetchHealth, fetchChartData, fetchLogs, fetchLogStats,
        fetchFailedJobs, retryAll, startAutoRefresh, stopAutoRefresh,
    }
})
