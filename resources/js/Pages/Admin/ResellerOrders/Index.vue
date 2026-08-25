<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

defineProps({
    orders: { type: Array, required: true },
})

const page = usePage()
const search = ref(page.url.split('search=')[1]?.split('&')[0]?.replace(/%20/g, ' ') || '')
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
        router.get(route('admin.reseller-orders.index') + '?' + params.toString(), {}, { preserveScroll: true, preserveState: true })
    }, 400)
}

function formatPrice(price) {
    return '৳' + Number(price).toLocaleString('en-IN')
}

function dateLabel(date) {
    const d = new Date(date + 'T00:00:00')
    const today = new Date()
    const yesterday = new Date()
    yesterday.setDate(today.getDate() - 1)
    const sameDay = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
    if (sameDay(d, today)) return 'Today'
    if (sameDay(d, yesterday)) return 'Yesterday'
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
    <Head title="Reseller Orders" />

    <AdminMaster>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reseller Orders</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Orders grouped by reseller and day</p>
            </div>

            <div class="relative max-w-md">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input
                    v-model="search"
                    @input="onSearch"
                    type="text"
                    placeholder="Search by name or mobile..."
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-10 pr-4 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                />
                <button v-if="search" @click="search = ''; onSearch()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div v-if="orders.length" class="space-y-6">
                <div v-for="dayGroup in orders" :key="dayGroup.date">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ dateLabel(dayGroup.date) }}</p>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/50 text-left text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-3 font-medium whitespace-nowrap">Reseller</th>
                                    <th class="px-4 py-3 font-medium whitespace-nowrap text-right">Orders</th>
                                    <th class="px-4 py-3 font-medium whitespace-nowrap text-right">Total</th>
                                    <th class="px-4 py-3 font-medium whitespace-nowrap text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-for="group in dayGroup.groups" :key="group.user_id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-4 py-3">
                                        <Link :href="route('admin.reseller-orders.by-user', { user: group.user_id, date: dayGroup.date })" class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition">
                                            {{ group.user_name }}
                                        </Link>
                                        <span v-if="group.company" class="ml-2 text-xs text-gray-400 dark:text-gray-500">({{ group.company }})</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400">{{ group.count }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">{{ formatPrice(group.total) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <Link :href="route('admin.reseller-orders.by-user', { user: group.user_id, date: dayGroup.date })" class="w-8 h-8 rounded-lg inline-flex items-center justify-center text-blue-600 hover:text-white bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-600 dark:hover:bg-blue-600 transition" title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <p v-else class="text-gray-500 dark:text-gray-400 text-sm py-12 text-center">No reseller orders yet.</p>
        </div>
    </AdminMaster>
</template>
