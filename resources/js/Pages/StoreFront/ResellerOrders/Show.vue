<script setup>
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    resellerOrder: { type: Object, required: true },
})

const totalProfit = computed(() =>
    props.resellerOrder.items.reduce((sum, item) => sum + ((item.sale_price ?? item.unit_price) - item.unit_price) * item.quantity, 0)
)

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

function paymentColor(status) {
    const map = {
        paid: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        unpaid: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    }
    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}

function transactionColor(status) {
    const map = {
        completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        pending: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        cancelled: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    }
    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}

function methodLabel(method) {
    return (method || '').replaceAll('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function formatDateTime(date) {
    return new Date(date).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true })
}
</script>

<template>
    <Head :title="`Order ${resellerOrder.order_number}`" />

    <FrontEndMaster>
        <div class="max-w-4xl mx-auto">
            <Link :href="route('reseller-orders.index')" class="inline-flex items-center gap-1.5 text-sm font-medium text-on-surface-variant transition hover:text-primary dark:text-[#cbb8b6] dark:hover:text-[#f6b7b2] mb-6">
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                Back to Orders
            </Link>

            <div class="flex items-center justify-between mb-8 flex-wrap gap-3">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-charcoal dark:text-[#f9eeed] mb-1">{{ resellerOrder.order_number }}</h1>
                    <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">{{ formatDateTime(resellerOrder.created_at) }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium capitalize" :class="statusColor(resellerOrder.status)">
                    <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(resellerOrder.status)"></span>
                    {{ resellerOrder.status }}
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-6">
                        <h2 class="text-lg font-bold text-charcoal dark:text-[#f9eeed] mb-4">Order Items</h2>
                        <div class="divide-y divide-outline-variant dark:divide-[#3a302e]">
                            <div v-for="item in resellerOrder.items" :key="item.id" class="flex items-center gap-3 py-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-charcoal dark:text-[#f9eeed]">{{ item.product_name }}</p>
                                    <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6] mt-0.5">
                                        Qty: <span class="font-mono">{{ item.quantity }}</span>
                                        <span class="mx-1.5 text-outline-variant dark:text-[#3a302e]">|</span>
                                        <span class="font-mono">Cost: {{ formatPrice(item.unit_price) }}</span>
                                        <template v-if="item.sale_price">
                                            <span class="mx-1.5 text-outline-variant dark:text-[#3a302e]">|</span>
                                            <span class="font-mono">Sale: {{ formatPrice(item.sale_price) }}</span>
                                        </template>
                                        <template v-if="(item.sale_price ?? item.unit_price) > item.unit_price">
                                            <span class="mx-1.5 text-outline-variant dark:text-[#3a302e]">|</span>
                                            <span class="font-mono text-emerald-600 dark:text-emerald-400">Profit: {{ formatPrice(((item.sale_price ?? item.unit_price) - item.unit_price) * item.quantity) }}</span>
                                        </template>
                                        <template v-if="item.size || item.color">
                                            <span class="mx-1.5 text-outline-variant dark:text-[#3a302e]">|</span>
                                            <span v-if="item.size" class="font-mono">{{ item.size }}</span><span v-if="item.size && item.color" class="mx-0.5">/</span><span v-if="item.color" class="font-mono">{{ item.color }}</span>
                                        </template>
                                    </p>
                                </div>
                                <p class="font-mono text-sm text-charcoal dark:text-[#f9eeed] shrink-0 font-medium">{{ formatPrice(item.total) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-5">
                        <h2 class="text-lg font-bold text-charcoal dark:text-[#f9eeed] mb-3">Order Summary</h2>
                        <div class="space-y-2.5 text-sm">
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant dark:text-[#cbb8b6]">Delivery Charge</span>
                                <span class="font-mono text-charcoal dark:text-[#f9eeed]">{{ formatPrice(resellerOrder.delivery_charge) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant dark:text-[#cbb8b6]">Total Amount</span>
                                <span class="font-mono font-bold text-charcoal dark:text-[#f9eeed]">{{ formatPrice(resellerOrder.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant dark:text-[#cbb8b6]">Your Profit</span>
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ formatPrice(totalProfit) }}</span>
                            </div>

                            <hr class="border-outline-variant dark:border-[#3a302e]" />

                            <div class="flex justify-between items-center">
                                <span class="text-on-surface-variant dark:text-[#cbb8b6]">Payment</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="paymentColor(resellerOrder.payment_status)">
                                    {{ resellerOrder.payment_status }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-on-surface-variant dark:text-[#cbb8b6]">Method</span>
                                <span class="text-charcoal dark:text-[#f9eeed] capitalize">{{ methodLabel(resellerOrder.payment_method) }}</span>
                            </div>

                            <template v-if="resellerOrder.transaction">
                                <hr class="border-outline-variant dark:border-[#3a302e]" />
                                <div class="flex justify-between items-center">
                                    <span class="text-on-surface-variant dark:text-[#cbb8b6]">Wallet Transaction</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="transactionColor(resellerOrder.transaction.status)">
                                        {{ resellerOrder.transaction.status }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-on-surface-variant dark:text-[#cbb8b6]">Transaction Amount</span>
                                    <span class="font-mono text-sm text-emerald-600 dark:text-emerald-400">{{ formatPrice(resellerOrder.transaction.amount) }}</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-5">
                        <h2 class="text-lg font-bold text-charcoal dark:text-[#f9eeed] mb-3">Customer</h2>
                        <p class="text-sm text-charcoal dark:text-[#f9eeed]">{{ resellerOrder.customer_name || '—' }}</p>
                        <p class="text-xs font-mono text-on-surface-variant dark:text-[#cbb8b6]">{{ resellerOrder.mobile || '' }}</p>
                        <hr class="my-2.5 border-outline-variant dark:border-[#3a302e]" />
                        <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6]">Shipping Address</p>
                        <p class="text-sm text-charcoal dark:text-[#f9eeed] mt-1">{{ resellerOrder.shipping_address }}</p>
                    </div>

                    <div v-if="resellerOrder.notes" class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-5">
                        <h2 class="text-lg font-bold text-charcoal dark:text-[#f9eeed] mb-2">Notes</h2>
                        <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">{{ resellerOrder.notes }}</p>
                    </div>

                    <div class="text-center pt-2">
                        <Link :href="route('reseller-orders.index')" class="text-sm text-primary hover:underline">Back to Orders</Link>
                    </div>
                </div>
            </div>
        </div>
    </FrontEndMaster>
</template>
