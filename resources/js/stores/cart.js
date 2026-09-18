import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCartStore = defineStore('cart', () => {
    const items = ref([])
    const drawerOpen = ref(false)

    function openDrawer() {
        drawerOpen.value = true
    }

    function closeDrawer() {
        drawerOpen.value = false
    }

    function load() {
        const stored = sessionStorage.getItem('cart')

        if (stored) {
items.value = JSON.parse(stored)
}
    }

    function save() {
        sessionStorage.setItem('cart', JSON.stringify(items.value))
    }

    const count = computed(() => items.value.reduce((sum, item) => sum + (item.qty || 0), 0))

    const total = computed(() => items.value.reduce((sum, item) => sum + (item.price || 0) * (item.qty || 0), 0))

    function addItem(item) {
        const existing = items.value.find(
            i => i.product_id === item.product_id && i.variant_id === item.variant_id
        )

        if (existing) {
            existing.qty = (existing.qty || 0) + (item.qty || 1)
        } else {
            items.value.push({ ...item, qty: item.qty || 1 })
        }

        save()
    }

    function updateQuantity(index, delta) {
        const newQty = (items.value[index]?.qty || 0) + delta

        if (newQty < 1) {
            items.value.splice(index, 1)
        } else {
            items.value[index].qty = newQty
        }

        save()
    }

    function removeItem(index) {
        items.value.splice(index, 1)
        save()
    }

    function clear() {
        items.value = []
        save()
    }

    return { items, count, total, drawerOpen, load, addItem, updateQuantity, removeItem, clear, openDrawer, closeDrawer }
})
