<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

defineProps({
    withdrawals: { type: Object, required: true },
    pendingCount: { type: Number, default: 0 },
})

const search = ref(new URLSearchParams(window.location.search).get('search') || '')
const urlParams = new URLSearchParams(window.location.search)
const statusFilter = ref(urlParams.get('status') || '')
const from = ref(urlParams.get('from') || '')
const to = ref(urlParams.get('to') || '')
let timeout = null

function buildParams() {
    const params = {}

    if (search.value) {
        params.search = search.value
    }
    if (statusFilter.value) {
        params.status = statusFilter.value
    }
    if (from.value) {
        params.from = from.value
    }
    if (to.value) {
        params.to = to.value
    }

    return params
}

function onSearch() {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        router.get(route('admin.withdrawals.index'), buildParams(), { preserveScroll: true, preserveState: true })
    }, 400)
}

function applyFilters() {
    router.get(route('admin.withdrawals.index'), buildParams(), { preserveScroll: true, preserveState: true })
}

function clearFilters() {
    search.value = ''
    statusFilter.value = ''
    from.value = ''
    to.value = ''
    router.get(route('admin.withdrawals.index'), {}, { preserveScroll: true, preserveState: true })
}

function formatPrice(price) {
    return '৳' + Number(price ?? 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDateTime(date) {
    return new Date(date).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true })
}

const statusLabels = { pending: 'Pending', completed: 'Accepted', cancelled: 'Rejected' }

function statusColor(status) {
    const map = {
        pending: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        cancelled: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    }

    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}

function statusDot(status) {
    const map = {
        pending: 'bg-amber-500',
        completed: 'bg-emerald-500',
        cancelled: 'bg-red-500',
    }

    return map[status] || 'bg-gray-400'
}

const confirming = ref(null)
const processing = ref(false)

function askAccept(withdrawal) {
    confirming.value = { id: withdrawal.id, action: 'accept' }
}

function askReject(withdrawal) {
    confirming.value = { id: withdrawal.id, action: 'reject' }
}

function cancelConfirm() {
    confirming.value = null
}

function confirmAction() {
    if (! confirming.value) {
        return
    }

    const url = route(confirming.value.action === 'accept' ? 'admin.withdrawals.accept' : 'admin.withdrawals.reject', { transaction: confirming.value.id })

    processing.value = true

    router.post(url, {}, {
        preserveScroll: true,
        onFinish: () => { processing.value = false },
        onSuccess: () => { confirming.value = null },
    })
}

function isConfirming(withdrawal, action) {
    return confirming.value?.id === withdrawal.id && confirming.value.action === action
}
</script>

<template>
    <Head title="Withdrawals" />

    <AdminMaster>
        <div class="space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Withdrawals</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Review reseller withdrawal requests</p>
                </div>
                <span v-if="pendingCount" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    {{ pendingCount }} pending request(s)
                </span>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative w-full sm:max-w-xs">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input
                        v-model="search"
                        @input="onSearch"
                        type="text"
                        placeholder="Search by reseller name or mobile number..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-10 pr-10 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                    />
                    <button v-if="search" @click="search = ''; applyFilters()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <select v-model="statusFilter" @change="applyFilters"
                    class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 pl-3 pr-8 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Accepted</option>
                    <option value="cancelled">Rejected</option>
                </select>

                <div class="flex items-center gap-2">
                    <input v-model="from" @change="applyFilters" type="date" title="From date"
                        class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 px-3 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" />
                    <span class="text-sm text-gray-400 dark:text-gray-500">–</span>
                    <input v-model="to" @change="applyFilters" type="date" title="To date"
                        class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 py-2 px-3 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" />
                </div>

                <button v-if="search || statusFilter || from || to" @click="clearFilters"
                    class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Clear Filters
                </button>
            </div>

            <div v-if="withdrawals.data.length" class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 text-left text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-3 font-medium whitespace-nowrap">Reseller</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap">Payout Method</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap text-right">Amount</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap">Status</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap">Requested</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="withdrawal in withdrawals.data" :key="withdrawal.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-900 dark:text-white">{{ withdrawal.user?.name || 'N/A' }}</span>
                                <span class="block text-xs text-gray-400 dark:text-gray-500">{{ withdrawal.user?.email }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 capitalize">
                                    {{ withdrawal.paymentmethod_name }}
                                </span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">{{ withdrawal.note }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-medium text-gray-900 dark:text-white">{{ formatPrice(withdrawal.amount) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusColor(withdrawal.status)">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(withdrawal.status)"></span>
                                    {{ statusLabels[withdrawal.status] }}
                                </span>
                                <span v-if="withdrawal.action_by" class="block text-xs text-gray-400 dark:text-gray-500 mt-1">by {{ withdrawal.action_by }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ formatDateTime(withdrawal.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <template v-if="withdrawal.status === 'pending'">
                                    <div v-if="isConfirming(withdrawal, 'accept')" class="inline-flex items-center gap-2">
                                        <span class="text-xs text-gray-600 dark:text-gray-300">Accept {{ formatPrice(withdrawal.amount) }} via {{ withdrawal.paymentmethod_name }}?</span>
                                        <button @click="confirmAction" :disabled="processing"
                                            class="rounded-lg bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 px-3 py-1.5 text-xs font-semibold transition">Yes, Accept</button>
                                        <button @click="cancelConfirm" class="rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 px-3 py-1.5 text-xs font-semibold transition">Cancel</button>
                                    </div>
                                    <div v-else-if="isConfirming(withdrawal, 'reject')" class="inline-flex items-center gap-2">
                                        <span class="text-xs text-gray-600 dark:text-gray-300">Reject {{ formatPrice(withdrawal.amount) }}? Amount returns to wallet.</span>
                                        <button @click="confirmAction" :disabled="processing"
                                            class="rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 px-3 py-1.5 text-xs font-semibold transition">Yes, Reject</button>
                                        <button @click="cancelConfirm" class="rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 px-3 py-1.5 text-xs font-semibold transition">Cancel</button>
                                    </div>
                                    <template v-else>
                                        <button @click="askAccept(withdrawal)" class="rounded-lg bg-green-50 text-green-700 hover:bg-green-600 hover:text-white px-3 py-1.5 text-xs font-semibold transition dark:bg-green-900/30 dark:text-green-400">Accept</button>
                                        <button @click="askReject(withdrawal)" class="ml-2 rounded-lg bg-red-50 text-red-700 hover:bg-red-600 hover:text-white px-3 py-1.5 text-xs font-semibold transition dark:bg-red-900/30 dark:text-red-400">Reject</button>
                                    </template>
                                </template>
                                <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="withdrawals.data.length && withdrawals.last_page > 1" class="flex justify-between items-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Page {{ withdrawals.current_page }} of {{ withdrawals.last_page }}</p>
                <div class="flex gap-2">
                    <button v-if="withdrawals.prev_page_url" @click="router.get(withdrawals.prev_page_url, {}, { preserveScroll: true, preserveState: true })" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Previous</button>
                    <button v-if="withdrawals.next_page_url" @click="router.get(withdrawals.next_page_url, {}, { preserveScroll: true, preserveState: true })" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">Next</button>
                </div>
            </div>

            <p v-if="! withdrawals.data.length" class="text-gray-500 dark:text-gray-400 text-sm py-12 text-center">No withdrawal requests found.</p>
        </div>
    </AdminMaster>
</template>
