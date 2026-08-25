<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
    exports: { type: Array, required: true },
    imports: { type: Array, required: true },
})

const jobs = ref({ exports: props.exports, imports: props.imports })
const startingExport = ref(false)
const errorsVisible = ref({})
const importForm = useForm({ file: null })

let pollTimer = null

const isActive = (job) => ['queued', 'processing'].includes(job.status)

const hasActiveJob = computed(() =>
    [...jobs.value.exports, ...jobs.value.imports].some(isActive),
)

const allJobs = computed(() => {
    const exports = jobs.value.exports.map((job) => ({ ...job, kind: 'export' }))
    const imports = jobs.value.imports.map((job) => ({ ...job, kind: 'import' }))

    return [...exports, ...imports].sort(
        (a, b) => new Date(b.created_at) - new Date(a.created_at),
    )
})

function progress(job) {
    const total = job.total || job.total_rows || 0

    if (!total) {
        return 0
    }

    return Math.min(100, Math.round(((job.processed || 0) / total) * 100))
}

function statusBadge(status) {
    const map = {
        queued: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    }

    return map[status] || 'bg-gray-100 text-gray-800'
}

function refresh() {
    window.axios
        .get(route('admin.settings.catalog.status'))
        .then(({ data }) => {
            jobs.value = data
        })
}

function startExport() {
    startingExport.value = true

    router.post(route('admin.settings.catalog.export'), {}, {
        onFinish: () => {
            startingExport.value = false
            refresh()
        },
    })
}

function onFileChange(e) {
    importForm.file = e.target.files[0] || null
}

function submitImport() {
    importForm.post(route('admin.settings.catalog.import'), {
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset('file')
            refresh()
        },
    })
}

function toggleErrors(key) {
    errorsVisible.value[key] = !errorsVisible.value[key]
}

function formatDate(value) {
    return value ? new Date(value).toLocaleString() : '—'
}

onMounted(() => {
    pollTimer = setInterval(() => {
        if (hasActiveJob.value) {
            refresh()
        }
    }, 2500)
})

onBeforeUnmount(() => {
    clearInterval(pollTimer)
})
</script>

<template>
    <Head title="Catalog Import/Export" />

    <AdminMaster>
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Catalog Import/Export</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Export your catalog to a ZIP (CSV + images) or import one back. Jobs run in the background, processed 100 rows at a time.
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Export Catalog</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Download the whole catalog as a ZIP containing a single products.csv (categories included as columns) and the actual image files.
                    </p>
                    <div class="flex flex-wrap gap-3 pt-4">
                        <button
                            @click="startExport"
                            :disabled="startingExport"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            {{ startingExport ? 'Starting...' : 'Export Catalog' }}
                        </button>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Import Catalog</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Upload a ZIP (single products.csv + images), or a single CSV/XLSX whose columns tell us whether it holds products or categories. Rows with an existing SKU or slug are skipped.
                    </p>
                    <div class="pt-4 space-y-3">
                        <input
                            type="file"
                            accept=".zip,.csv,.xlsx,.xls"
                            @change="onFileChange"
                            class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-600 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/40"
                        />
                        <p v-if="importForm.errors.file" class="text-sm text-red-600">{{ importForm.errors.file }}</p>
                        <button
                            @click="submitImport"
                            :disabled="importForm.processing || !importForm.file"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            {{ importForm.processing ? 'Uploading...' : 'Start Import' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Jobs</h2>
                </div>

                <div class="overflow-x-auto admin-scrollbar">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                <th class="text-left px-4 py-3 font-medium">Job</th>
                                <th class="text-left px-4 py-3 font-medium">Status</th>
                                <th class="text-left px-4 py-3 font-medium">Progress</th>
                                <th class="text-left px-4 py-3 font-medium">Result</th>
                                <th class="text-left px-4 py-3 font-medium">Errors</th>
                                <th class="text-left px-4 py-3 font-medium">Started</th>
                                <th class="text-right px-4 py-3 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <template v-for="job in allJobs" :key="job.kind + job.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white capitalize">
                                        {{ job.kind === 'export' ? 'Catalog Export' : 'Catalog Import' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusBadge(job.status)">{{ job.status }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="w-40">
                                            <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                                <div class="h-full bg-blue-600 rounded-full transition-all" :style="{ width: progress(job) + '%' }"></div>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ job.processed || 0 }} / {{ job.total || job.total_rows || 0 }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        <template v-if="job.kind === 'import'">
                                            <span v-if="job.imported" class="text-green-600 dark:text-green-400">{{ job.imported }} imported</span>
                                            <span v-if="job.skipped" class="text-amber-600 dark:text-amber-400 ml-2">{{ job.skipped }} skipped</span>
                                        </template>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <template v-if="job.status === 'failed'">
                                            <span class="text-xs text-red-600 block max-w-[220px] truncate" :title="job.error">{{ job.error }}</span>
                                        </template>
                                        <button v-else-if="job.errors?.length" @click="toggleErrors(job.kind + job.id)" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 text-xs font-medium">
                                            {{ errorsVisible[job.kind + job.id] ? 'Hide' : 'View' }} ({{ job.errors.length }})
                                        </button>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(job.created_at) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a
                                            v-if="job.kind === 'export' && job.status === 'completed'"
                                            :href="route('admin.settings.catalog.download', job.id)"
                                            class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-700 dark:text-blue-400 text-sm font-medium"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            Download ZIP
                                        </a>
                                        <span v-else class="text-gray-400 text-sm">—</span>
                                    </td>
                                </tr>
                                <tr v-if="errorsVisible[job.kind + job.id] && job.errors?.length">
                                    <td colspan="7" class="px-4 py-3">
                                        <ul class="space-y-1 bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-lg p-3 text-xs text-red-700 dark:text-red-300">
                                            <li v-for="(err, i) in job.errors" :key="i">
                                                <strong>Row {{ err.row }}:</strong> {{ err.message }}
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="allJobs.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No jobs yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
