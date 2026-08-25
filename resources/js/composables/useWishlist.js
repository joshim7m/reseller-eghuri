import { storeToRefs } from 'pinia'
import { useWishlistStore } from '@/stores/wishlist'

let loaded = false

export function useWishlist() {
    const store = useWishlistStore()

    if (!loaded) {
        store.load()
        loaded = true
    }

    return {
        ...storeToRefs(store),
        load: store.load,
        toggle: store.toggle,
        has: store.has,
        clear: store.clear,
    }
}
