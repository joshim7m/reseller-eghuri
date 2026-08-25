<script setup>
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps({
    wallet: { type: Object, default: null },
    transactions: { type: Object, required: true },
    pendingBalance: { type: Number, default: 0 },
    cancelledBalance: { type: Number, default: 0 },
})

function formatPrice(price) {
    return '৳' + Number(price ?? 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(date) {
    const d = new Date(date)
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('en-GB', { hour: 'numeric', minute: '2-digit', hour12: true })
}

function typeColor(type) {
    return type === 'credit'
        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
        : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400'
}

function statusColor(status) {
    const map = {
        completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        pending: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        cancelled: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    }
    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}

function statusDot(status) {
    const map = {
        completed: 'bg-emerald-500',
        pending: 'bg-amber-500',
        cancelled: 'bg-red-500',
    }
    return map[status] || 'bg-gray-400'
}

function goToPage(url) {
    if (url) router.get(url, {}, { preserveScroll: true, preserveState: true })
}
</script>

<template>
    <Head title="Reseller Transactions" />

    <FrontEndMaster>
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-8 flex-wrap gap-3">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-charcoal dark:text-[#f9eeed] mb-1">Transactions</h1>
                    <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">View your wallet transaction history.</p>
                </div>
                <Link :href="route('reseller-orders.create')"
                    class="bg-charcoal text-white font-semibold px-5 py-2.5 rounded-xl text-sm hover:bg-primary transition shrink-0 dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]">
                    + New Order
                </Link>
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

            <div v-if="transactions.data.length" class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c]">
                                <th class="px-5 py-3 text-xs font-bold text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider">Date</th>
                                <th class="px-5 py-3 text-xs font-bold text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider">Order</th>
                                <th class="px-5 py-3 text-xs font-bold text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider">Type</th>
                                <th class="px-5 py-3 text-xs font-bold text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider">Amount</th>
                                <th class="px-5 py-3 text-xs font-bold text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 text-xs font-bold text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant dark:divide-[#3a302e]">
                            <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-surface-container-low/50 dark:hover:bg-[#241d1c]/50 transition">
                                <td class="px-5 py-3.5 text-sm text-on-surface-variant dark:text-[#cbb8b6] whitespace-nowrap">{{ formatDate(tx.created_at) }}</td>
                                <td class="px-5 py-3.5">
                                    <Link v-if="tx.reseller_order" :href="route('reseller-orders.show', tx.reseller_order.id)" class="font-mono text-sm text-primary hover:underline">
                                        {{ tx.reseller_order.order_number }}
                                    </Link>
                                    <span v-else class="font-mono text-sm text-outline dark:text-[#cbb8b6]">—</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="typeColor(tx.type)">
                                        {{ tx.type }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-sm font-bold" :class="tx.type === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-sale-price'">
                                    {{ tx.type === 'credit' ? '+' : '-' }}{{ formatPrice(tx.amount) }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusColor(tx.status)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(tx.status)"></span>
                                        {{ tx.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-on-surface-variant dark:text-[#cbb8b6] max-w-[200px] truncate">
                                    {{ tx.note || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="transactions.links && transactions.links.length > 3" class="px-5 py-4 flex justify-between items-center border-t border-outline-variant dark:border-[#3a302e]">
                    <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">Page {{ transactions.current_page }} of {{ transactions.last_page }}</p>
                    <div class="flex gap-2">
                        <button v-if="transactions.prev_page_url" @click="goToPage(transactions.prev_page_url)"
                            class="flex items-center gap-2 text-sm font-medium text-charcoal dark:text-[#f9eeed] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                            Previous
                        </button>
                        <button v-if="transactions.next_page_url" @click="goToPage(transactions.next_page_url)"
                            class="flex items-center gap-2 text-sm font-medium text-charcoal dark:text-[#f9eeed] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors">
                            Next
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-20">
                <span class="material-symbols-outlined text-6xl text-outline-variant dark:text-[#3a302e] mb-4" aria-hidden="true">receipt_long</span>
                <p class="text-on-surface-variant dark:text-[#cbb8b6] text-sm mb-4">No transactions yet.</p>
                <Link :href="route('reseller-orders.create')"
                    class="inline-block bg-charcoal text-white font-semibold px-6 py-2.5 rounded-xl text-sm hover:bg-primary transition dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]">
                    Place Your First Order
                </Link>
            </div>
        </div>
    </FrontEndMaster>
</template>
