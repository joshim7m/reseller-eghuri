<script setup>
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({
    orders: { type: Object, required: true },
    wallet: { type: Object, default: null },
    pendingBalance: { type: Number, default: 0 },
    cancelledBalance: { type: Number, default: 0 },
})

const search = ref(new URLSearchParams(window.location.search).get('search') || '')
let timeout = null

function onSearch() {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        const params = new URLSearchParams(window.location.search)
        if (search.value) {
            params.set('search', search.value)
        } else {
            params.delete('search')
        }
        router.get(route('reseller-orders.index') + '?' + params.toString(), {}, { preserveScroll: true, preserveState: true })
    }, 400)
}

function formatPrice(price) {
    return '৳' + Number(price ?? 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function statusColor(status) {
    const map = {
        pending: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        processing: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        cancelled: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    }
    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}

function statusDot(status) {
    const map = {
        pending: 'bg-amber-500',
        processing: 'bg-blue-500',
        completed: 'bg-emerald-500',
        cancelled: 'bg-red-500',
    }
    return map[status] || 'bg-gray-400'
}

function formatDateTime(date) {
    return new Date(date).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true })
}
</script>

<template>
    <Head title="Reseller Orders" />

    <FrontEndMaster>
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-8 flex-wrap gap-3">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-charcoal dark:text-[#f9eeed] mb-1">Reseller Orders</h1>
                    <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">Track and manage your orders.</p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <Link :href="route('reseller-transactions')"
                        class="border border-primary text-primary font-semibold px-5 py-2.5 rounded-xl text-sm hover:bg-primary hover:text-white transition">
                        Transactions
                    </Link>
                    <Link :href="route('reseller-orders.create')"
                        class="bg-charcoal text-white font-semibold px-5 py-2.5 rounded-xl text-sm hover:bg-primary transition dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]">
                        + New Order
                    </Link>
                </div>
            </div>

            <div class="mb-6 relative max-w-sm">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-outline dark:text-[#cbb8b6]" aria-hidden="true">search</span>
                <input v-model="search" @input="onSearch" type="text" placeholder="Search by order number..."
                    class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] pl-10 pr-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
            </div>

            <div v-if="wallet" class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-5 md:p-6 mb-6">
                <h2 class="text-xs font-bold uppercase tracking-widest text-on-surface-variant dark:text-[#cbb8b6] mb-4">Wallet Summary</h2>
                <div class="flex items-center gap-6 flex-wrap">
                    <div>
                        <p class="font-mono text-2xl font-bold text-charcoal dark:text-[#f9eeed]">{{ formatPrice(wallet?.balance) }}</p>
                        <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6] mt-0.5">Available Balance</p>
                    </div>
                    <div class="h-10 w-px bg-outline-variant dark:bg-[#3a302e]"></div>
                    <div>
                        <p class="font-mono text-sm font-semibold text-amber-600 dark:text-amber-400">{{ formatPrice(pendingBalance) }}</p>
                        <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6]">Pending</p>
                    </div>
                    <div class="h-10 w-px bg-outline-variant dark:bg-[#3a302e]"></div>
                    <div>
                        <p class="font-mono text-sm font-semibold text-sale-price">{{ formatPrice(cancelledBalance) }}</p>
                        <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6]">Cancelled</p>
                    </div>
                </div>
            </div>

            <div v-if="orders.data.length" class="space-y-3">
                <Link v-for="order in orders.data" :key="order.id" :href="route('reseller-orders.show', order.id)"
                    class="block bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-5 transition hover:shadow-md hover:border-primary/30 dark:hover:border-[#f6b7b2]/30">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm font-medium text-charcoal dark:text-[#f9eeed]">{{ order.order_number }}</span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusColor(order.status)">
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(order.status)"></span>
                                {{ order.status }}
                            </span>
                        </div>
                        <div class="font-mono text-charcoal dark:text-[#f9eeed] font-bold">{{ formatPrice(order.total_amount) }}</div>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between text-xs text-on-surface-variant dark:text-[#cbb8b6]">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">schedule</span>
                            {{ formatDateTime(order.created_at) }}
                        </span>
                        <span>{{ order.items?.length || 0 }} item(s)</span>
                    </div>
                </Link>

                <div v-if="orders.links && orders.links.length > 3" class="mt-8 pt-6 border-t border-outline-variant dark:border-[#3a302e] flex justify-between items-center">
                    <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">Page {{ orders.current_page }} of {{ orders.last_page }}</p>
                    <div class="flex gap-2">
                        <button v-if="orders.prev_page_url" @click="router.get(orders.prev_page_url, {}, { preserveScroll: true, preserveState: true })"
                            class="flex items-center gap-2 text-sm font-medium text-charcoal dark:text-[#f9eeed] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                            Previous
                        </button>
                        <button v-if="orders.next_page_url" @click="router.get(orders.next_page_url, {}, { preserveScroll: true, preserveState: true })"
                            class="flex items-center gap-2 text-sm font-medium text-charcoal dark:text-[#f9eeed] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors">
                            Next
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-20">
                <span class="material-symbols-outlined text-6xl text-outline-variant dark:text-[#3a302e] mb-4" aria-hidden="true">receipt_long</span>
                <p class="text-on-surface-variant dark:text-[#cbb8b6] text-sm mb-4">No reseller orders yet.</p>
                <Link :href="route('reseller-orders.create')"
                    class="inline-block bg-charcoal text-white font-semibold px-6 py-2.5 rounded-xl text-sm hover:bg-primary transition dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]">
                    Create Your First Order
                </Link>
            </div>
        </div>
    </FrontEndMaster>
</template>
