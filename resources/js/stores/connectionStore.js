import { defineStore } from 'pinia'
import { ref } from 'vue'
import { connectionApi } from '../utils/api'

export const useConnectionStore = defineStore('connections', () => {
    const connections = ref([])
    const current     = ref(null)
    const loading     = ref(false)
    const error       = ref(null)
    const drivers     = ref([])

    async function fetchAll() {
        loading.value = true
        error.value   = null
        try {
            const res      = await connectionApi.list()
            connections.value = res.data?.data?.data ?? res.data?.data ?? []
        } catch (e) {
            error.value = e.message
        } finally {
            loading.value = false
        }
    }

    async function fetchOne(id) {
        loading.value = true
        try {
            const res  = await connectionApi.get(id)
            current.value = res.data?.data
        } finally {
            loading.value = false
        }
    }

    async function fetchDrivers() {
        const res = await connectionApi.drivers()
        drivers.value = res.data?.data ?? []
    }

    async function create(data) {
        const res = await connectionApi.create(data)
        await fetchAll()
        return res.data?.data
    }

    async function update(id, data) {
        const res = await connectionApi.update(id, data)
        await fetchAll()
        return res.data?.data
    }

    async function remove(id) {
        await connectionApi.delete(id)
        connections.value = connections.value.filter(c => c.id !== id)
    }

    async function test(id) {
        return (await connectionApi.test(id)).data
    }

    return { connections, current, loading, error, drivers, fetchAll, fetchOne, fetchDrivers, create, update, remove, test }
})
