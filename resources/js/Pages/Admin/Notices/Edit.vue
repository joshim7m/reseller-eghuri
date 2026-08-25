<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const { notice } = defineProps({
    notice: { type: Object, required: true },
})

const form = useForm({
    title: notice.title,
    content: notice.content,
    image: null,
    status: notice.status,
})

function submit() {
    form.put(route('admin.notices.update', notice.id))
}

function onImageChange(e) {
    form.image = e.target.files[0]
}
</script>

<template>
    <Head title="Edit Notice" />

    <AdminMaster>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Notice</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update the notice</p>
                </div>
                <Link :href="route('admin.notices.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title <span class="text-red-500">*</span></label>
                        <input id="title" v-model="form.title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content <span class="text-red-500">*</span></label>
                        <textarea id="content" v-model="form.content" rows="6" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required></textarea>
                        <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
                    </div>

                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Image</label>
                        <div v-if="notice.image" class="mb-2">
                            <img :src="`/${notice.image}`" alt="" class="w-24 h-24 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload a new image to replace it</p>
                        </div>
                        <input id="image" type="file" accept="image/*" @change="onImageChange" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-600 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/40" />
                        <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <label class="inline-flex items-center gap-2.5 cursor-pointer">
                            <input id="status" v-model="form.status" type="checkbox" class="w-4 h-4 rounded border-gray-300 dark:border-gray-700 text-blue-600 focus:ring-blue-500" />
                            <span class="text-sm text-gray-600 dark:text-gray-400">Active</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Inactive notices are hidden</p>
                        <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Updating...' : 'Update Notice' }}
                        </button>
                        <Link :href="route('admin.notices.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
