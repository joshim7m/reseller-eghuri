<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const props = defineProps({
    backups: { type: Array, required: true },
})

const creating = ref(false)
const confirmDelete = ref(null)
const form = useForm({})

function createBackup() {
    creating.value = true
    router.post(route('admin.settings.backup.store'), {}, {
        onFinish: () => {
            creating.value = false
        },
    })
}

function destroyBackup(name) {
    router.delete(route('admin.settings.backup.destroy', name), {
        preserveScroll: true,
        onStart: () => {
            confirmDelete.value = null
        },
    })
}

function formatSize(bytes) {
    if (!bytes) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB']
    let i = 0
    let size = bytes
    while (size >= 1024 && i < units.length - 1) {
        size /= 1024
        i++
    }
    return size.toFixed(i === 0 ? 0 : 1) + ' ' + units[i]
}

function formatDate(timestamp) {
    return timestamp ? new Date(timestamp * 1000).toLocaleString() : '—'
}
</script>

<template>
    <Head title="Database Backup" />

    <AdminMaster>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Database Backup</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Create and download compressed database backups (.sql.gz)
                    </p>
                </div>
                <button
                    @click="createBackup"
                    :disabled="creating"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ creating ? 'Creating...' : 'Create Backup' }}
                </button>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto admin-scrollbar">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                <th class="text-left px-4 py-3 font-medium">Filename</th>
                                <th class="text-left px-4 py-3 font-medium">Size</th>
                                <th class="text-left px-4 py-3 font-medium">Created</th>
                                <th class="text-right px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr
                                v-for="backup in backups"
                                :key="backup.name"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30"
                            >
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white font-mono text-xs">
                                    {{ backup.name }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                    {{ formatSize(backup.size) }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ formatDate(backup.created_at) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            :href="route('admin.settings.backup.download', backup.name)"
                                            class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-700 dark:text-blue-400 text-sm font-medium"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            Download
                                        </a>
                                        <button
                                            @click="confirmDelete = backup.name"
                                            class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700 dark:text-red-400 text-sm font-medium"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="backups.length === 0">
                                <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No backups yet. Click "Create Backup" to make one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="confirmDelete"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="confirmDelete = null"
            >
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Backup</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Are you sure you want to delete <strong class="font-mono">{{ confirmDelete }}</strong>? This cannot be undone.
                    </p>
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button
                            @click="confirmDelete = null"
                            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="destroyBackup(confirmDelete)"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition shadow-sm"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminMaster>
</template>
