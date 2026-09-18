<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, onMounted, onUnmounted } from 'vue'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const props = defineProps({
    orders: { type: Array, required: true },
    user: { type: Object, required: true },
    date: { type: String, required: true },
})

const activeDropdown = ref(null)
const copiedOrderId = ref(null)

function toggleDropdown(orderId) {
    activeDropdown.value = activeDropdown.value === orderId ? null : orderId
}

function handleClickOutside(e) {
    if (!e.target.closest('.status-dropdown')) {
        activeDropdown.value = null
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

function changeStatus(order, status) {
    activeDropdown.value = null
    router.patch(route('admin.reseller-orders.update-status', order.id), { status }, {
        preserveScroll: true,
    })
}

function copyMobile(mobile) {
    navigator.clipboard.writeText(mobile)
}

function formatPrice(price) {
    return '৳' + Number(price).toLocaleString('en-IN')
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

function statusBadge(status) {
    const map = { pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', returned: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' }

    return map[status] || 'bg-gray-100 text-gray-800'
}

function copyOrder(order) {
    const deliveryCharge = Number(order.delivery_charge || 0)
    const lines = order.items.map(item => {
        const variantOpts = item.options?.length ? item.options.map(o => o.value).join(' / ') : ''
        const productName = [item.product_name, variantOpts, item.variant?.sku].filter(Boolean).join(' ')
        const itemAmount = Number(item.sale_price ?? item.unit_price) * item.quantity

        return [
            productName,
            order.customer_name || '',
            order.shipping_address || '',
            order.mobile || '',
            itemAmount + deliveryCharge,
            order.notes || '',
            '',
            'eghuri.com',
            '01729200455',
        ].join('\t')
    })
    navigator.clipboard.writeText(lines.join('\n'))
    copiedOrderId.value = order.id
    setTimeout(() => {
 copiedOrderId.value = null 
}, 2000)
}
</script>

<template>

    <Head :title="`Reseller Orders - ${user.name}`" />

    <AdminMaster>
        <div class="space-y-4">
            <div class="mb-6">
                <Link :href="route('admin.reseller-orders.index')"
                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline mb-1 inline-block">&larr; Back to
                    all orders</Link>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ user.name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ formatDate(date) }} &middot; {{
                    orders.length }} order{{ orders.length !== 1 ? 's' : '' }}</p>
            </div>

            <div v-for="order in orders" :key="order.id"
                class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3 flex-wrap">
                        <Link :href="route('admin.reseller-orders.edit', order.id)"><span
                                class="font-mono text-sm font-medium text-gray-900 dark:text-white underline">{{
                                order.order_number }}</span></Link>

                        <div class="relative">
                            <button @click.stop="toggleDropdown(order.id)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize cursor-pointer hover:opacity-80 transition"
                                :class="statusBadge(order.status)">
                                {{ order.status }}
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-if="activeDropdown === order.id"
                                class="absolute z-10 mt-1 w-36 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg py-1">
                                <button @click="changeStatus(order, 'pending')"
                                    class="w-full text-left px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition capitalize">Pending</button>
                                <button @click="changeStatus(order, 'processing')"
                                    class="w-full text-left px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition capitalize">Processing</button>
                                <button @click="changeStatus(order, 'completed')"
                                    class="w-full text-left px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition capitalize">Completed</button>
                                <button @click="changeStatus(order, 'cancelled')"
                                    class="w-full text-left px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition capitalize">Cancelled</button>
                                <button @click="changeStatus(order, 'returned')"
                                    class="w-full text-left px-3 py-1.5 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition capitalize">Returned</button>
                            </div>
                        </div>
                    </div>

                    <button @click="copyOrder(order)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition"
                        :class="copiedOrderId === order.id ? 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30' : 'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700'">
                        <svg v-if="copiedOrderId === order.id" class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        {{ copiedOrderId === order.id ? 'Copied!' : 'Copy Order' }}
                    </button>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3 py-2.5">
                        <div class="shrink-0 w-10 h-10 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800">
                            <img v-if="item.product?.image_url" :src="item.product.image_url"
                                :alt="item.product_name" class="w-full h-full object-cover" />
                            <div v-else
                                class="w-full h-full flex items-center justify-center text-xs text-gray-400 dark:text-gray-500">
                                N/A</div>
                        </div>
                        <div v-if="item.purchase_image_path" class="shrink-0 w-10 h-10 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800 ring-1 ring-gray-200 dark:ring-gray-700">
                            <img :src="item.purchase_image_path" :alt="item.product_name + ' purchase'" class="w-full h-full object-cover" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.product_name
                                }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Qty: <span class="font-mono">{{ item.quantity }}</span>
                                <span class="mx-1.5 text-gray-300 dark:text-gray-600">|</span>
                                <span class="font-mono">Unit Price: {{ item.unit_price }}</span>
                                <template v-if="item.sale_price">
                                    <span class="mx-1.5 text-gray-300 dark:text-gray-600">|</span>
                                    <span class="font-mono text-green-600 dark:text-green-400">Sale Price: {{
                                        item.sale_price }}</span>
                                </template>
                                <template v-if="item.options?.length">
                                    <span class="mx-1.5 text-gray-300 dark:text-gray-600">|</span>
                                    <span class="font-mono">{{ item.options.map(o => o.value).join(' / ') }}</span>
                                </template>
                            </p>
                        </div>
                        <div class="text-right shrink-0 flex gap-2 text-sm">
                            <p v-if="item.sale_price" class="font-mono text-green-600 dark:text-green-400">Price: {{
                                item.sale_price *
                                item.quantity }}</p>
                            <p v-else class="font-mono text-gray-400">&mdash;</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between border-t border-dashed border-gray-200 dark:border-gray-700">
                    
                    <div class="flex flex-col gap-1.5 mt-3 text-xs text-gray-600 dark:text-gray-400">
                        <p v-if="order.customer_name" class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>{{ order.customer_name }}</span>
                            <template v-if="order.mobile">
                                <span class="text-gray-400 dark:text-gray-500">({{ order.mobile }})</span>
                            </template>
                        </p>
                        <p class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ order.shipping_address }}</span>
                        </p>
                        <p class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1m4-1l2 1 4-1 2 1M5 18h14a2 2 0 002-2V8a2 2 0 00-2-2h-2.5M5 18a2 2 0 01-2-2m2 2a2 2 0 002 2h10a2 2 0 002-2" />
                            </svg>
                            <span>Delivery Charge: {{ order.delivery_charge }}</span>
                        </p>
                        <p v-if="order.notes" class="flex items-start gap-1.5 italic">
                            <svg class="w-3.5 h-3.5 text-purple-400 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>{{ order.notes }}</span>
                        </p>
                    </div>

                    <div
                        class="flex flex-col font-bold items-end gap-2 mt-3 pt-3 text-sm text-gray-500 dark:text-gray-400">
                        <span>Price: {{ Number(order.items.reduce((s, i) => s + (i.sale_price ?? i.unit_price) * i.quantity, 0)).toLocaleString('en-IN') }}</span>
                        <span>Delivery Charge: {{ Number(order.delivery_charge).toLocaleString('en-IN') }}</span>
                        <span class="text-gray-900 dark:text-white">Total: {{ Number(order.items.reduce((s, i) => s + (i.sale_price ?? i.unit_price) * i.quantity, 0) + Number(order.delivery_charge)).toLocaleString('en-IN') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </AdminMaster>
</template>
