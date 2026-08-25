<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip, Legend } from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const { salesData, recentOrders, topSellers, weeklyCategorySales } = defineProps({
    salesData: { type: Object, required: true },
    recentOrders: { type: Array, required: true },
    topSellers: { type: Array, required: true },
    weeklyCategorySales: { type: Array, required: true },
})

const isDark = ref(false)

onMounted(() => {
    const syncDark = () => {
        isDark.value = document.documentElement.classList.contains('dark')
    }

    syncDark()

    const observer = new MutationObserver(syncDark)
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

const palette = ['#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#84cc16']

const chartData = computed(() => ({
    labels: weeklyCategorySales.map(c => c.category),
    datasets: [
        {
            label: 'Sales',
            data: weeklyCategorySales.map(c => Number(c.total)),
            backgroundColor: weeklyCategorySales.map((_, i) => palette[i % palette.length]),
            borderRadius: 6,
            maxBarThickness: 48,
        },
    ],
}))

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: ctx => ' ৳' + Number(ctx.parsed.y).toLocaleString('en-IN'),
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: isDark.value ? '#9ca3af' : '#6b7280' },
        },
        y: {
            beginAtZero: true,
            grid: { color: isDark.value ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)' },
            ticks: {
                color: isDark.value ? '#9ca3af' : '#6b7280',
                callback: value => '৳' + Number(value).toLocaleString('en-IN'),
            },
        },
    },
}))

function formatPrice(price) {
    return '৳' + Number(price).toLocaleString('en-IN')
}

function statusBadgeClass(status) {
    const map = { pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }
    return map[status] || 'bg-gray-100 text-gray-800'
}

function visitOrder(id) {
    router.visit(route('admin.orders.show', id))
}
</script>

<template>
    <Head title="Dashboard" />

    <AdminMaster>
        <div class="space-y-4 sm:space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-4 sm:p-5 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs sm:text-sm font-medium text-blue-100">Total Sales</p>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-lg sm:text-2xl font-bold truncate">{{ formatPrice(salesData.total_sales) }}</p>
                </div>
                <div class="rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 p-4 sm:p-5 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs sm:text-sm font-medium text-amber-100">Pending Orders</p>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-lg sm:text-2xl font-bold">{{ salesData.pending_orders }}</p>
                </div>
                <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-4 sm:p-5 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs sm:text-sm font-medium text-emerald-100">Products</p>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                    </div>
                    <p class="text-lg sm:text-2xl font-bold">{{ salesData.total_products }}</p>
                </div>
                <div class="rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 p-4 sm:p-5 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs sm:text-sm font-medium text-purple-100">Customers</p>
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <p class="text-lg sm:text-2xl font-bold">{{ salesData.total_customers }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="px-4 sm:px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <h2 class="text-sm sm:text-lg font-semibold text-gray-900 dark:text-white">Category Sales (Weekly)</h2>
                    </div>
                    <div class="p-4 sm:p-5">
                        <div v-if="weeklyCategorySales.length" class="relative h-64 sm:h-72">
                            <Bar :data="chartData" :options="chartOptions" />
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400 text-center py-6">No sales data this week.</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="px-4 sm:px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                        <h2 class="text-sm sm:text-lg font-semibold text-gray-900 dark:text-white">Top Sellers</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div v-for="topSeller in topSellers" :key="topSeller.id" class="flex items-center gap-3">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xs sm:text-sm font-bold shrink-0">
                                {{ topSeller.name?.charAt(0)?.toUpperCase() || '?' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs sm:text-sm font-medium text-gray-900 dark:text-white truncate">{{ topSeller.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ topSeller.user_detail?.mobile || 'No mobile' }}</p>
                            </div>
                        </div>
                        <div v-if="topSellers.length === 0" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">No sellers yet.</div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="px-4 sm:px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <h2 class="text-sm sm:text-lg font-semibold text-gray-900 dark:text-white">Recent Orders</h2>
                    <Link :href="route('admin.orders.index')" class="text-xs sm:text-sm font-medium text-blue-600 hover:text-blue-500">View All</Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs sm:text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                <th class="text-left px-3 sm:px-4 py-3 font-medium">Order</th>
                                <th class="text-left px-3 sm:px-4 py-3 font-medium">Customer</th>
                                <th class="text-left px-3 sm:px-4 py-3 font-medium hidden sm:table-cell">Status</th>
                                <th class="text-right px-3 sm:px-4 py-3 font-medium">Total</th>
                                <th class="text-right px-3 sm:px-4 py-3 font-medium hidden sm:table-cell">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30 cursor-pointer" @click="visitOrder(order.id)">
                                <td class="px-3 sm:px-4 py-3 font-medium text-blue-600 hover:text-blue-500">#{{ order.order_number }}</td>
                                <td class="px-3 sm:px-4 py-3 text-gray-600 dark:text-gray-400 truncate max-w-[100px] sm:max-w-none">{{ order.user?.name }}</td>
                                <td class="px-3 sm:px-4 py-3 hidden sm:table-cell"><span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusBadgeClass(order.status)">{{ order.status }}</span></td>
                                <td class="px-3 sm:px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ formatPrice(order.total_amount) }}</td>
                                <td class="px-3 sm:px-4 py-3 text-right text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                            </tr>
                            <tr v-if="recentOrders.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No orders yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
