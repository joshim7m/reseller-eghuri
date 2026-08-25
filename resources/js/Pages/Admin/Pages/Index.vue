<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    pages: { type: Array, required: true },
});

function destroyPage(id) {
    if (confirm('Delete this page?')) {
        router.delete(route('admin.pages.destroy', id));
    }
}
</script>

<template>
    <Head title="Pages" />

    <AdminMaster>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Pages
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Manage static content pages such as terms, privacy, and
                        about us
                    </p>
                </div>
                <Link
                    :href="route('admin.pages.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Add Page
                </Link>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="admin-scrollbar overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="bg-gray-50 text-gray-500 dark:bg-gray-800/50 dark:text-gray-400"
                            >
                                <th class="px-4 py-3 text-left font-medium">
                                    #
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Title
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Slug
                                </th>
                                <th class="px-4 py-3 text-left font-medium">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-800"
                        >
                            <tr
                                v-for="page in pages"
                                :key="page.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/30"
                            >
                                <td
                                    class="px-4 py-3 text-gray-500 dark:text-gray-400"
                                >
                                    {{ page.id }}
                                </td>
                                <td
                                    class="max-w-[260px] px-4 py-3 font-medium text-gray-900 dark:text-white"
                                >
                                    <div class="line-clamp-2">
                                        {{ page.title }}
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-3 text-gray-500 dark:text-gray-400"
                                >
                                    <code
                                        class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-800"
                                        >{{ page.slug }}</code
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="
                                            page.status
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                        "
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    >
                                        {{
                                            page.status ? 'Active' : 'Inactive'
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div
                                        class="flex items-center justify-end gap-1.5"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'admin.pages.edit',
                                                    page.id,
                                                )
                                            "
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 transition hover:bg-blue-600 hover:text-white dark:bg-blue-900/20 dark:hover:bg-blue-600"
                                            title="Edit"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </Link>
                                        <button
                                            @click="destroyPage(page.id)"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-600 hover:text-white dark:bg-red-900/20 dark:hover:bg-red-600"
                                            title="Delete"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="pages.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-8 text-center text-gray-500 dark:text-gray-400"
                                >
                                    No pages found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
