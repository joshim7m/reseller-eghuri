<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const { category, categories } = defineProps({
    category: { type: Object, required: true },
    categories: { type: Array, required: true },
})

const dropzoneRef = ref(null)
const isDragging = ref(false)
const newImagePreview = ref(null)

const form = useForm({
    parent_id: category.parent_id || '',
    name: category.name,
    slug: category.slug,
    description: category.description || '',
    image_path: null,
    is_active: category.is_active,
})

const hasNewImage = ref(false)

function generateSlug(text) {
    return text
        .toLowerCase()
        .trim()
        .replace(/['']/g, '')
        .replace(/&/g, 'and')
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '')
        .substring(0, 200)
}

function autoGenerateSlug() {
    form.slug = generateSlug(form.name)
}

function submit() {
    form.put(route('admin.categories.update', category.id))
}

function onDragOver(e) {
    e.preventDefault()
    isDragging.value = true
}

function onDragLeave() {
    isDragging.value = false
}

function onDropFiles(e) {
    e.preventDefault()
    isDragging.value = false
    handleFile(e.dataTransfer.files[0])
}

function onFileSelect(e) {
    handleFile(e.target.files[0])
}

function handleFile(file) {
    if (!file || !file.type.startsWith('image/')) return
    form.image_path = file

    const reader = new FileReader()
    reader.onload = (e) => {
        newImagePreview.value = e.target.result
        hasNewImage.value = true
    }
    reader.readAsDataURL(file)
}

function removeNewImage() {
    form.image_path = null
    newImagePreview.value = null
    hasNewImage.value = false
}

function openFilePicker() {
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/*'
    input.onchange = onFileSelect
    input.click()
}
</script>

<template>
    <Head title="Edit Category" />

    <AdminMaster>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Category</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ category.name }}</p>
                </div>
                <Link :href="route('admin.categories.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <div v-if="category.image_url && !hasNewImage" class="mb-5">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Current Image</h3>
                    <img :src="category.image_url" :alt="category.name" class="w-24 h-24 rounded-xl object-cover shadow-sm border border-gray-200 dark:border-gray-700" />
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parent Category</label>
                        <select id="parent_id" v-model="form.parent_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">None (Top Level)</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <p v-if="form.errors.parent_id" class="mt-1 text-sm text-red-600">{{ form.errors.parent_id }}</p>
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input id="name" v-model="form.name" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                        <div class="flex gap-2">
                            <input id="slug" v-model="form.slug" type="text" class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Auto-generated from name" />
                            <button type="button" @click="autoGenerateSlug" class="shrink-0 px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition whitespace-nowrap">Auto Generate</button>
                        </div>
                        <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea id="description" v-model="form.description" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Image</label>
                        <div
                            ref="dropzoneRef"
                            @dragover="onDragOver"
                            @dragleave="onDragLeave"
                            @drop="onDropFiles"
                            class="relative flex flex-col items-center justify-center border-2 border-dashed rounded-xl p-8 transition cursor-pointer max-w-sm"
                            :class="isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500 bg-gray-50 dark:bg-gray-800/50'"
                            @click="openFilePicker"
                        >
                            <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Drop an image here or click to browse</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">JPG, PNG, WebP &bull; Max 2MB</p>
                        </div>
                        <p v-if="form.errors.image_path" class="mt-1 text-sm text-red-600">{{ form.errors.image_path }}</p>

                        <div v-if="hasNewImage" class="mt-3 relative inline-block group">
                            <img :src="newImagePreview" alt="Preview" class="w-24 h-24 rounded-xl object-cover border border-gray-200 dark:border-gray-700 shadow-sm" />
                            <button type="button" @click="removeNewImage" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">&times;</button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_active" v-model="form.is_active" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                        <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                    </div>
                    <p v-if="form.errors.is_active" class="mt-1 text-sm text-red-600">{{ form.errors.is_active }}</p>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Updating...' : 'Update Category' }}
                        </button>
                        <Link :href="route('admin.categories.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
