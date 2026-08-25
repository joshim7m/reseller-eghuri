import { storeToRefs } from 'pinia'
import { useCartStore } from '@/stores/cart'

let loaded = false

export function useCart() {
    const store = useCartStore()

    if (!loaded) {
        store.load()
        loaded = true
    }

    return {
        ...storeToRefs(store),
        addItem: store.addItem,
        updateQuantity: store.updateQuantity,
        removeItem: store.removeItem,
        clear: store.clear,
        openDrawer: store.openDrawer,
        closeDrawer: store.closeDrawer,
    }
}
