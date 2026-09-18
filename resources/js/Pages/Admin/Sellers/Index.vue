<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

defineProps({
    sellers: { type: Object, required: true },
    sortBy: { type: String, default: 'created_at' },
    sortOrder: { type: String, default: 'desc' },
})

const search = ref('')
const statusFilter = ref('')

let debounceTimer
watch([search, statusFilter], () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        router.get(route('admin.sellers.index'), { search: search.value, status: statusFilter.value }, { preserveState: true, replace: true })
    }, 300)
})


</script>

<template>
    <Head title="Sellers" />

    <AdminMaster>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sellers</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage resellers and wholesellers</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex flex-wrap gap-3">
                    <input v-model="search" type="text" placeholder="Search by name or mobile..." class="flex-1 min-w-[200px] rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    <select v-model="statusFilter" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="overflow-x-auto admin-scrollbar">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                <th class="text-left px-4 py-3 font-medium">Name</th>
                                <th class="text-left px-4 py-3 font-medium">Mobile</th>
                                <th class="text-left px-4 py-3 font-medium">Company</th>
                                <th class="text-left px-4 py-3 font-medium">Type</th>
                                <th class="text-center px-4 py-3 font-medium">Status</th>
                                <th class="text-right px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="seller in sellers.data" :key="seller.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ seller.name }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ seller.user_detail?.mobile || '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ seller.user_detail?.company || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="seller.user_type === 'reseller' ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400'">
                                        {{ seller.user_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium" :class="seller.status ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'">
                                        {{ seller.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link :href="route('admin.sellers.edit', seller.id)" class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-600 hover:text-white bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-600 dark:hover:bg-blue-600 transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="sellers.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No sellers found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="sellers.last_page > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Showing {{ sellers.from }} to {{ sellers.to }} of {{ sellers.total }} entries</p>
                    <div class="flex gap-1">
                        <Link v-for="link in sellers.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="px-3 py-1.5 text-sm rounded-lg transition" :class="link.active ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'" />
                    </div>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
