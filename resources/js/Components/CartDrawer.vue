<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { useCart } from '@/composables/useCart'

const enter = ref(false)

const { items: cart, count, total, updateQuantity, removeItem, drawerOpen, closeDrawer } = useCart()

watch(drawerOpen, (val) => {
    if (val) {
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                enter.value = true
            })
        })
    } else {
        enter.value = false
    }
})

function close() {
    enter.value = false
    setTimeout(() => closeDrawer(), 300)
}

function formatPrice(price) {
    return '৳' + Number(price).toLocaleString('en-IN')
}
</script>

<template>
    <Teleport to="body">
            <div v-if="drawerOpen" class="fixed inset-0 z-50">
            <div class="fixed inset-0 bg-black/40 backdrop" @click="close" />

            <div class="fixed inset-x-0 bottom-0 md:inset-y-0 md:right-0 md:left-auto w-full md:max-w-md bg-white dark:bg-gray-900 shadow-xl flex flex-col rounded-t-2xl md:rounded-none overflow-hidden md:translate-x-0 transition-transform duration-300 ease-out"
                 :class="enter ? 'translate-y-0 md:translate-x-0' : 'translate-y-full md:translate-x-full'">
                <div class="flex items-center justify-between px-4 h-14 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <span class="font-semibold text-gray-800 dark:text-white">
                        Cart
                        <span v-if="count" class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-1">({{ count }} items)</span>
                    </span>
                    <button @click="close" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-2">
                    <div v-if="!cart.length" class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                        <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        <p class="text-sm">Your cart is empty</p>
                    </div>

                    <div v-for="(item, i) in cart" :key="i" class="flex items-start gap-3 py-4 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <div class="w-16 h-16 rounded-lg bg-gray-100 dark:bg-gray-800 shrink-0 overflow-hidden flex items-center justify-center">
                            <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                            <svg v-else class="w-6 h-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ item.name }}</p>
                            <p v-if="item.variant_name" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ item.variant_name }}</p>
                            <p v-if="item.sku" class="text-xs text-gray-400 dark:text-gray-500">SKU: {{ item.sku }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ formatPrice(item.price) }} each</p>
                            <div class="flex items-center gap-2 mt-2">
                                <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
                                    <button @click="updateQuantity(i, -1)" class="px-2 py-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition text-sm leading-none">&minus;</button>
                                    <span class="px-2.5 py-1 text-sm font-medium text-gray-800 dark:text-gray-100 min-w-[24px] text-center">{{ item.qty }}</span>
                                    <button @click="updateQuantity(i, 1)" class="px-2 py-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition text-sm leading-none">+</button>
                                </div>
                                <button @click="removeItem(i)" class="text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 transition p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/30" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 shrink-0">{{ formatPrice(item.price * item.qty) }}</p>
                    </div>
                </div>

                <div v-if="cart.length" class="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900 shrink-0 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ formatPrice(total) }}</span>
                    </div>
                    <Link :href="route('cart.index')" @click="close" class="block w-full text-center px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-lg text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        View Cart
                    </Link>
                    <Link :href="route('checkout')" @click="close" class="block w-full text-center px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                        Checkout
                    </Link>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.backdrop {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
