import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useWishlistStore = defineStore('wishlist', () => {
    const STORAGE_KEY = 'wishlist'

    const ids = ref([])

    function load() {
        const stored = localStorage.getItem(STORAGE_KEY)
        ids.value = stored ? JSON.parse(stored) : []
    }

    function save() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(ids.value))
    }

    function toggle(productId) {
        const num = Number(productId)
        const idx = ids.value.indexOf(num)
        if (idx === -1) {
            ids.value.push(num)
        } else {
            ids.value.splice(idx, 1)
        }
        save()
    }

    function has(productId) {
        return ids.value.includes(Number(productId))
    }

    function clear() {
        ids.value = []
        save()
    }

    return { ids, load, toggle, has, clear }
})
