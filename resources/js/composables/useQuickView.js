import { ref } from 'vue'

const open = ref(false)
const product = ref(null)

export function useQuickView() {
    function show(item) {
        product.value = item
        open.value = true
    }

    function close() {
        open.value = false
        product.value = null
    }

    return { open, product, show, close }
}
