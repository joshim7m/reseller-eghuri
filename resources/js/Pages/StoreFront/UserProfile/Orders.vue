<script setup>
import { Head, Link } from '@inertiajs/vue3'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

defineProps({
    orders: { type: Object, default: () => ({ data: [] }) }
})

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    shipped: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    delivered: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
}
</script>

<template>
    <Head title="My Orders" />

    <FrontEndMaster>
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">My Orders</h1>
        </div>

        <div v-if="orders.data && orders.data.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 text-left">
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Order #</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Date</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Items</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Total</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">#{{ order.order_number || order.id }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ order.created_at }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ order.items_count || order.items?.length || 0 }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">৳{{ order.total }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="statusColors[order.status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'">
                                    {{ order.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="route('orders.show', order.id)" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium text-sm">View</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div v-else class="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            <p class="mt-4 text-gray-500 dark:text-gray-400">No orders yet</p>
            <Link :href="route('products.index')" class="inline-block mt-4 px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">Start Shopping</Link>
        </div>

        <div v-if="orders.links && orders.links.length > 3" class="mt-6 flex justify-center gap-1">
            <Link v-for="(link, i) in orders.links" :key="i" :href="link.url || '#'" v-html="link.label" class="px-3 py-1.5 text-sm rounded-lg transition"
                :class="link.active ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                :preserve-scroll="true"
            />
        </div>
    </FrontEndMaster>
</template>
