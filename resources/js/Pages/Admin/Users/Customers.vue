<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    customers: { type: Object, required: true },
    sortBy: { type: String, default: 'created_at' },
    sortOrder: { type: String, default: 'desc' },
})
</script>

<template>
    <Head title="Customers" />

    <AdminMaster>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Customers</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View registered customers</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                <th class="text-left px-4 py-3 font-medium">Name</th>
                                <th class="text-left px-4 py-3 font-medium">Email</th>
                                <th class="text-left px-4 py-3 font-medium">Mobile</th>
                                <th class="text-left px-4 py-3 font-medium">Address</th>
                                <th class="text-center px-4 py-3 font-medium">Status</th>
                                <th class="text-left px-4 py-3 font-medium">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ customer.name }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ customer.email }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ customer.user_detail?.mobile || '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[200px] truncate">{{ customer.user_detail?.address || '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium" :class="customer.status ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'">
                                        {{ customer.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">{{ new Date(customer.created_at).toLocaleDateString() }}</td>
                            </tr>
                            <tr v-if="customers.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No customers found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="customers.last_page > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Showing {{ customers.from }} to {{ customers.to }} of {{ customers.total }} entries</p>
                    <div class="flex gap-1">
                        <Link v-for="link in customers.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="px-3 py-1.5 text-sm rounded-lg transition" :class="link.active ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'" />
                    </div>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
