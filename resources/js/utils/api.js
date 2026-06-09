import axios from 'axios'

const config = window.__BRIDGE_CONFIG__ ?? {}
const BASE   = '/' + (config.api_prefix ?? 'api/erp-integration-hub')

const api = axios.create({
    baseURL: BASE,
    headers: {
        'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept':        'application/json',
        'Content-Type':  'application/json',
    },
})

api.interceptors.response.use(
    res => res,
    err => {
        const msg = err.response?.data?.message ?? err.response?.data?.error ?? err.message
        return Promise.reject(new Error(msg))
    }
)

export default api

// ── Connections ────────────────────────────────────────────────────────
export const connectionApi = {
    list:          ()          => api.get('/connections'),
    get:           (id)        => api.get(`/connections/${id}`),
    create:        (data)      => api.post('/connections', data),
    update:        (id, data)  => api.put(`/connections/${id}`, data),
    delete:        (id)        => api.delete(`/connections/${id}`),
    test:          (id)        => api.post(`/connections/${id}/test`),
    entities:      (id)        => api.get(`/connections/${id}/entities`),
    entityFields:  (id, entity) => api.get(`/connections/${id}/entities/${entity}/fields`),
    drivers:       ()          => api.get('/connections/drivers'),
}

// ── Sync Profiles ─────────────────────────────────────────────────────
export const profileApi = {
    list:          (params)    => api.get('/sync-profiles', { params }),
    get:           (id)        => api.get(`/sync-profiles/${id}`),
    create:        (data)      => api.post('/sync-profiles', data),
    update:        (id, data)  => api.put(`/sync-profiles/${id}`, data),
    delete:        (id)        => api.delete(`/sync-profiles/${id}`),
    detectModels:  ()          => api.get('/sync-profiles/detect-models'),
    analyzeModel:  (model)     => api.post('/sync-profiles/analyze-model', { model }),
}

// ── Field Mappings ────────────────────────────────────────────────────
export const mappingApi = {
    list:          (profileId)             => api.get(`/sync-profiles/${profileId}/mappings`),
    save:          (profileId, mappings)   => api.post(`/sync-profiles/${profileId}/mappings`, { mappings }),
    preview:       (profileId, sample)    => api.post(`/sync-profiles/${profileId}/mappings/preview`, { sample }),
    autoMap:       (profileId, src, dst)  => api.post(`/sync-profiles/${profileId}/mappings/auto-map`, {
        source_fields: src, destination_fields: dst,
    }),
    transformers:  ()                     => api.get('/field-mapping/transformers'),
}

// ── Sync Operations ───────────────────────────────────────────────────
export const syncApi = {
    run:           (profileId, opts)  => api.post(`/sync/run/${profileId}`, opts),
    cancel:        (logId)            => api.post(`/sync/cancel/${logId}`),
    status:        (logId)            => api.get(`/sync/status/${logId}`),
    retryOne:      (failedId)         => api.post(`/sync/retry/${failedId}`),
    retryProfile:  (profileId)        => api.post(`/sync/retry-profile/${profileId}`),
    retryAll:      ()                 => api.post('/sync/retry-all'),
}

// ── Scheduler ─────────────────────────────────────────────────────────
export const schedulerApi = {
    list:            ()              => api.get('/scheduler'),
    frequencyOptions: ()             => api.get('/scheduler/frequency-options'),
    create:          (pid, data)     => api.post(`/scheduler/${pid}`, data),
    update:          (pid, sid, d)   => api.put(`/scheduler/${pid}/${sid}`, d),
    delete:          (pid, sid)      => api.delete(`/scheduler/${pid}/${sid}`),
    toggle:          (pid, sid)      => api.post(`/scheduler/${pid}/${sid}/toggle`),
}

// ── Monitoring ────────────────────────────────────────────────────────
export const monitoringApi = {
    dashboard: () => api.get('/monitoring/dashboard'),
    health:    () => api.get('/monitoring/health'),
    chartData: () => api.get('/monitoring/chart-data'),
}

// ── Logs ──────────────────────────────────────────────────────────────
export const logApi = {
    list:        (params)  => api.get('/logs', { params }),
    get:         (id)      => api.get(`/logs/${id}`),
    statistics:  ()        => api.get('/logs/statistics'),
    failedJobs:  (params)  => api.get('/logs/failed-jobs', { params }),
    export:      (params)  => api.get('/logs/export', { params, responseType: 'blob' }),
}

// ── Settings ──────────────────────────────────────────────────────────
export const settingsApi = {
    list:   ()       => api.get('/settings'),
    update: (data)   => api.post('/settings', { settings: data }),
}
