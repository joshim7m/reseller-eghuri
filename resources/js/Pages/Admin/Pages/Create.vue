<script setup>
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    slug: '',
    content: '',
    status: true,
});

function slugify(value) {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s_]+/g, '-')
        .replace(/-+/g, '-');
}

function generateSlug() {
    if (!form.slug) {
        form.slug = slugify(form.title);
    }
}

function submit() {
    if (!form.slug) {
        form.slug = slugify(form.title);
    }
    form.post(route('admin.pages.store'));
}
</script>

<template>
    <Head title="Add Page" />

    <AdminMaster>
        <div class="mx-auto max-w-2xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        Add Page
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Add a new static content page
                    </p>
                </div>
                <Link
                    :href="route('admin.pages.index')"
                    class="text-sm font-medium text-blue-600 hover:text-blue-500"
                    >&larr; Back</Link
                >
            </div>

            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label
                            for="title"
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Title <span class="text-red-500">*</span></label
                        >
                        <input
                            id="title"
                            v-model="form.title"
                            @blur="generateSlug"
                            type="text"
                            placeholder="e.g. Terms and Conditions"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            required
                        />
                        <p
                            v-if="form.errors.title"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="slug"
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Slug</label
                        >
                        <input
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            placeholder="auto-generated from title"
                            class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Leave empty to auto-generate from the title
                        </p>
                        <p
                            v-if="form.errors.slug"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.slug }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="content"
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Content <span class="text-red-500">*</span></label
                        >
                        <RichTextEditor
                            v-model="form.content"
                            placeholder="Write the page content here..."
                        />
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Supports rich text formatting
                        </p>
                        <p
                            v-if="form.errors.content"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.content }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >Status</label
                        >
                        <label
                            class="inline-flex cursor-pointer items-center gap-2.5"
                        >
                            <input
                                id="status"
                                v-model="form.status"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700"
                            />
                            <span
                                class="text-sm text-gray-600 dark:text-gray-400"
                                >Active</span
                            >
                        </label>
                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Inactive pages are hidden
                        </p>
                        <p
                            v-if="form.errors.status"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.status }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Adding...' : 'Add Page' }}
                        </button>
                        <Link
                            :href="route('admin.pages.index')"
                            class="rounded-lg bg-gray-100 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >Cancel</Link
                        >
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
