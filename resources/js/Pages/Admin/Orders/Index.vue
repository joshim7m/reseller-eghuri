<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

defineProps({
    orders: { type: Object, required: true },
})

const page = usePage()
const search = ref(page.url.split('search=')[1]?.split('&')[0] || '')
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

        router.get(route('admin.orders.index') + '?' + params.toString(), {}, { preserveScroll: true, preserveState: true })
    }, 400)
}

function formatPrice(price) {
    return '৳' + price.toLocaleString('en-IN')
}

function statusBadge(status) {
    const map = { pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }

    return map[status] || 'bg-gray-100 text-gray-800'
}

function paymentBadge(status) {
    const map = { paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', unpaid: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', partial: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }

    return map[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <Head title="Orders" />

    <AdminMaster>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Orders</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View and manage customer orders</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                    <div class="relative max-w-md">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        <input
                            v-model="search"
                            @input="onSearch"
                            type="text"
                            placeholder="Search by order #, phone, or SKU..."
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-10 pr-4 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        />
                        <button v-if="search" @click="search = ''; onSearch()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto admin-scrollbar">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                <th class="text-left px-4 py-3 font-medium">Order #</th>
                                <th class="text-left px-4 py-3 font-medium">Customer</th>
                                <th class="text-right px-4 py-3 font-medium">Total</th>
                                <th class="text-center px-4 py-3 font-medium">Status</th>
                                <th class="text-center px-4 py-3 font-medium">Payment</th>
                                <th class="text-left px-4 py-3 font-medium">Date</th>
                                <th class="text-right px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-3 font-medium"><Link :href="route('admin.orders.show', order.id)" class="text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition">#{{ order.order_number }}</Link></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ order.user?.name }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ formatPrice(order.total_amount) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusBadge(order.status)">{{ order.status }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="paymentBadge(order.payment_status)">{{ order.payment_status }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('admin.orders.show', order.id)" class="w-8 h-8 rounded-lg inline-flex items-center justify-center text-blue-600 hover:text-white bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-600 dark:hover:bg-blue-600 transition" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="orders.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No orders found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="orders.last_page > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} entries</p>
                    <div class="flex gap-1">
                        <Link v-for="link in orders.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="px-3 py-1.5 text-sm rounded-lg transition" :class="link.active ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'" />
                    </div>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
