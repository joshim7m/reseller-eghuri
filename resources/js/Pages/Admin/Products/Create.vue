<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

defineProps({
    categories: { type: Array, required: true },
})

const dropzoneRef = ref(null)
const isDragging = ref(false)
const newImagePreviews = ref([])
const showSpecification = ref(false)

const form = useForm({
    category_id: '',
    title: '',
    slug: '',
    description: '',
    specification: '',
    sku: '',
    quantity: '',
    unit_price: '',
    sale_price: '',
    status: 'active',
    featured: false,
    images: null,
})

const hasNewImages = computed(() => newImagePreviews.value.length > 0)

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
    form.slug = generateSlug(form.title)
}

function generateSku() {
    let sku = ''
    for (let i = 0; i < 5; i++) {
        sku += Math.floor(Math.random() * 10)
    }
    form.sku = sku
}

function submit() {
    form.post(route('admin.products.store'))
}

function formatPrice(price) {
    return price.toLocaleString('en-IN')
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
    handleFiles(e.dataTransfer.files)
}

function onFileSelect(e) {
    handleFiles(e.target.files)
}

function handleFiles(files) {
    const validFiles = []
    for (const file of files) {
        if (file.type.startsWith('image/')) {
            validFiles.push(file)
        }
    }
    if (!validFiles.length) return

    const dt = new DataTransfer()
    const existing = form.images ? Array.from(form.images) : []
    for (const f of [...existing, ...validFiles]) {
        dt.items.add(f)
    }
    form.images = dt.files

    for (const file of validFiles) {
        const reader = new FileReader()
        reader.onload = (e) => {
            newImagePreviews.value.push({ id: Date.now() + Math.random(), src: e.target.result, name: file.name })
        }
        reader.readAsDataURL(file)
    }
}

function removeNewImage(id) {
    const idx = newImagePreviews.value.findIndex(i => i.id === id)
    if (idx === -1) return
    newImagePreviews.value.splice(idx, 1)

    const dt = new DataTransfer()
    const remaining = Array.from(form.images).filter((_, i) => i !== idx)
    for (const f of remaining) {
        dt.items.add(f)
    }
    form.images = dt.files
}

function openFilePicker() {
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/*'
    input.multiple = true
    input.onchange = onFileSelect
    input.click()
}
</script>

<template>
    <Head title="Create Product" />

    <AdminMaster>
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Product</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add a new product to your catalog</p>
                </div>
                <Link :href="route('admin.products.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category <span class="text-red-500">*</span></label>
                        <select id="category_id" v-model="form.category_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Select Category</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</p>
                    </div>

                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title <span class="text-red-500">*</span></label>
                        <input id="title" v-model="form.title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                        <div class="flex gap-2">
                            <input id="slug" v-model="form.slug" type="text" class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Auto-generated from title" />
                            <button type="button" @click="autoGenerateSlug" class="shrink-0 px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition whitespace-nowrap">Auto Generate</button>
                        </div>
                        <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description <span class="text-red-500">*</span></label>
                        <RichTextEditor v-model="form.description" placeholder="Write a detailed description..." :min-height="'200px'" />
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="show_specification" v-model="showSpecification" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                        <label for="show_specification" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">Add Specification</label>
                    </div>

                    <div v-if="showSpecification">
                        <label for="specification" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Specification</label>
                        <RichTextEditor v-model="form.specification" placeholder="Add specifications (fabric, size guide, care instructions, etc.)..." :min-height="'200px'" />
                        <p v-if="form.errors.specification" class="mt-1 text-sm text-red-600">{{ form.errors.specification }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                            <div class="flex gap-2">
                                <input id="sku" v-model="form.sku" type="text" class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Unique code" />
                                <button type="button" @click="generateSku" class="shrink-0 px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition whitespace-nowrap">Generate</button>
                            </div>
                            <p v-if="form.errors.sku" class="mt-1 text-sm text-red-600">{{ form.errors.sku }}</p>
                        </div>
                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity <span class="text-red-500">*</span></label>
                            <input id="quantity" v-model="form.quantity" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.quantity" class="mt-1 text-sm text-red-600">{{ form.errors.quantity }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="unit_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit Price (BDT) <span class="text-red-500">*</span></label>
                            <input id="unit_price" v-model="form.unit_price" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                            <p v-if="form.errors.unit_price" class="mt-1 text-sm text-red-600">{{ form.errors.unit_price }}</p>
                        </div>
                        <div>
                            <label for="sale_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sale Price (BDT) <span class="text-red-500">*</span></label>
                            <input id="sale_price" v-model="form.sale_price" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                            <p v-if="form.errors.sale_price" class="mt-1 text-sm text-red-600">{{ form.errors.sale_price }}</p>
                        </div>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status <span class="text-red-500">*</span></label>
                        <select id="status" v-model="form.status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="draft">Draft</option>
                        </select>
                        <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="featured" v-model="form.featured" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                        <label for="featured" class="text-sm font-medium text-gray-700 dark:text-gray-300">Featured</label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Images</label>
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
                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Drop images here or click to browse</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">JPG, PNG, WebP &bull; Max 2MB each</p>
                        </div>
                        <p v-if="form.errors.images" class="mt-1 text-sm text-red-600">{{ form.errors.images }}</p>

                        <div v-if="hasNewImages" class="mt-3 flex flex-wrap gap-3">
                            <div v-for="preview in newImagePreviews" :key="preview.id" class="relative group">
                                <img :src="preview.src" :alt="preview.name" class="w-20 h-20 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                                <button type="button" @click="removeNewImage(preview.id)" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">&times;</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Creating...' : 'Create Product' }}
                        </button>
                        <Link :href="route('admin.products.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
