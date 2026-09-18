<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

defineProps({
    paymentMethods: { type: Object, required: true },
})

const showDeleteDialog = ref(false)
const deletingId = ref(null)

function destroyMethod(id) {
    deletingId.value = id
    showDeleteDialog.value = true
}

function confirmDelete() {
    router.delete(route('admin.payment-methods.destroy', deletingId.value), {
        onFinish: () => {
            showDeleteDialog.value = false
            deletingId.value = null
        },
    })
}

function toggleStatus(id) {
    router.patch(route('admin.payment-methods.toggle-status', id), {}, { preserveScroll: true })
}

function statusBadge(status) {
    return status === 'active'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}
</script>

<template>
    <Head title="Payment Methods" />

    <AdminMaster>
        <div class="space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Payment Methods</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage payment accounts and providers</p>
                </div>
                <Link :href="route('admin.payment-methods.create')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Method
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto admin-scrollbar">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                <th class="text-left px-4 py-3 font-medium w-12">SN</th>
                                <th class="text-left px-4 py-3 font-medium">Method</th>
                                <th class="text-left px-4 py-3 font-medium">Provider</th>
                                <th class="text-left px-4 py-3 font-medium">Account Number</th>
                                <th class="text-left px-4 py-3 font-medium">Status</th>
                                <th class="text-right px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="(method, index) in paymentMethods.data" :key="method.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ paymentMethods.from + index }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img v-if="method.image" :src="'/' + method.image" :alt="method.name" class="w-10 h-10 rounded-lg object-cover" />
                                        <span v-else class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V6a3 3 0 00-3-3H6a3 3 0 00-3 3v10a3 3 0 003 3z"/></svg>
                                        </span>
                                        <Link :href="route('admin.payment-methods.edit', method.id)" class="text-gray-900 dark:text-white font-medium hover:text-blue-600 dark:hover:text-blue-400 transition">{{ method.name }}</Link>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ method.provider || '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">{{ method.account_number }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="statusBadge(method.status)">
                                        {{ method.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="toggleStatus(method.id)" class="w-8 h-8 rounded-lg inline-flex items-center justify-center transition" :class="method.status === 'active' ? 'text-amber-600 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-600 hover:text-white' : 'text-green-600 bg-green-50 dark:bg-green-900/20 hover:bg-green-600 hover:text-white'" :title="method.status === 'active' ? 'Deactivate' : 'Activate'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        </button>
                                        <Link :href="route('admin.payment-methods.edit', method.id)" class="w-8 h-8 rounded-lg inline-flex items-center justify-center text-blue-600 hover:text-white bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-600 dark:hover:bg-blue-600 transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </Link>
                                        <button @click="destroyMethod(method.id)" class="w-8 h-8 rounded-lg flex items-center justify-center text-red-600 hover:text-white bg-red-50 dark:bg-red-900/20 hover:bg-red-600 dark:hover:bg-red-600 transition" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="paymentMethods.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No payment methods yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="paymentMethods.last_page > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Showing {{ paymentMethods.from }} to {{ paymentMethods.to }} of {{ paymentMethods.total }} entries</p>
                    <div class="flex gap-1">
                        <Link v-for="link in paymentMethods.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="px-3 py-1.5 text-sm rounded-lg transition" :class="link.active ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'" :disabled="!link.url" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmDialog
            :show="showDeleteDialog"
            title="Delete Payment Method"
            message="Are you sure you want to delete this payment method?"
            @confirm="confirmDelete"
            @cancel="showDeleteDialog = false"
        />
    </AdminMaster>
</template>
