<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps({
    notices: { type: Array, required: true },
})

function destroyNotice(id) {
    if (confirm('Delete this notice?')) {
        router.delete(route('admin.notices.destroy', id))
    }
}
</script>

<template>
    <Head title="Notices" />

    <AdminMaster>
        <div class="space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notices</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage notices and announcements</p>
                </div>
                <Link :href="route('admin.notices.create')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Notice
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto admin-scrollbar">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                <th class="text-left px-4 py-3 font-medium">Image</th>
                                <th class="text-left px-4 py-3 font-medium">Title</th>
                                <th class="text-left px-4 py-3 font-medium">Content</th>
                                <th class="text-left px-4 py-3 font-medium">Status</th>
                                <th class="text-right px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="notice in notices" :key="notice.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-3">
                                    <img v-if="notice.image" :src="`/${notice.image}`" alt="" class="w-14 h-14 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                    <span v-else class="inline-flex items-center justify-center w-14 h-14 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white max-w-[260px]">
                                    <div class="line-clamp-2">{{ notice.title }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-[320px]">
                                    <div class="line-clamp-2">{{ notice.content }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="notice.status ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ notice.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link :href="route('admin.notices.edit', notice.id)" class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-600 hover:text-white bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-600 dark:hover:bg-blue-600 transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </Link>
                                        <button @click="destroyNotice(notice.id)" class="w-8 h-8 rounded-lg flex items-center justify-center text-red-600 hover:text-white bg-red-50 dark:bg-red-900/20 hover:bg-red-600 dark:hover:bg-red-600 transition" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="notices.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No notices found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
