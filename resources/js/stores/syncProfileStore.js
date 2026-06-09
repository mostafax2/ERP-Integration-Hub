import { defineStore } from 'pinia'
import { ref } from 'vue'
import { profileApi, syncApi } from '../utils/api'

export const useSyncProfileStore = defineStore('syncProfiles', () => {
    const profiles  = ref([])
    const current   = ref(null)
    const loading   = ref(false)
    const running   = ref({}) // { [profileId]: true }
    const models    = ref([])

    async function fetchAll(params = {}) {
        loading.value = true
        try {
            const res = await profileApi.list(params)
            profiles.value = res.data?.data ?? res.data?.data?.data ?? []
        } finally {
            loading.value = false
        }
    }

    async function fetchOne(id) {
        const res = await profileApi.get(id)
        current.value = res.data?.data
        return current.value
    }

    async function create(data) {
        const res = await profileApi.create(data)
        await fetchAll()
        return res.data?.data
    }

    async function update(id, data) {
        const res = await profileApi.update(id, data)
        await fetchAll()
        return res.data?.data
    }

    async function remove(id) {
        await profileApi.delete(id)
        profiles.value = profiles.value.filter(p => p.id !== id)
    }

    async function runSync(profileId, opts = {}) {
        running.value = { ...running.value, [profileId]: true }
        try {
            const res = await syncApi.run(profileId, opts)
            return res.data
        } finally {
            running.value = { ...running.value, [profileId]: false }
        }
    }

    async function detectModels() {
        const res = await profileApi.detectModels()
        models.value = Object.values(res.data?.data ?? {})
        return models.value
    }

    return { profiles, current, loading, running, models, fetchAll, fetchOne, create, update, remove, runSync, detectModels }
})
