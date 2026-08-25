<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'

const { product, categories } = defineProps({
    product: { type: Object, required: true },
    categories: { type: Array, required: true },
})

const dropzoneRef = ref(null)
const isDragging = ref(false)
const newImagePreviews = ref([])
const hasVariants = ref(product.variants && product.variants.length > 0)
const sizeValuesText = ref('')
const colorValuesText = ref('')
const imagePickerTarget = ref(null)
const showImagePicker = ref(false)
const confirmDialog = ref({ show: false, title: '', message: '', onConfirm: null })
const showSpecification = ref(Boolean(product.specification))

const hasNewImages = computed(() => newImagePreviews.value.length > 0)

const categorySearch = ref('')
const categoryOpen = ref(false)

const filteredCategories = computed(() =>
    categories.filter(c => c.name.toLowerCase().includes(categorySearch.value.toLowerCase()))
)

const selectedCategoryNames = computed(() =>
    categories.filter(c => form.category_ids.includes(c.id))
)

const form = useForm({
    category_ids: product.categories ? product.categories.map(c => c.id) : [],
    title: product.title,
    slug: product.slug,
    description: product.description,
    specification: product.specification || '',
    sku: product.sku || '',
    quantity: product.quantity ?? '',
    unit_price: product.unit_price,
    sale_price: product.sale_price,
    status: product.status,
    featured: product.featured,
    images: null,
    variants: [],
})

function mapVariants() {
    if (product.variants && product.variants.length > 0) {
        const sizes = [...new Set(product.variants.map(v => v.size).filter(Boolean))]
        const colors = [...new Set(product.variants.map(v => v.color).filter(Boolean))]
        sizeValuesText.value = sizes.join(', ')
        colorValuesText.value = colors.join(', ')

        form.variants = product.variants.map(v => ({
            id: v.id,
            option1: v.size || '',
            option2: v.color || '',
            option3: '',
            sku: v.sku || '',
            unit_price: v.unit_price ?? '',
            sale_price: v.sale_price ?? '',
            quantity: v.quantity,
            product_image_id: v.product_image_id || null,
            image_url: v.image?.image_url || null,
        }))
    } else {
        form.variants = []
    }
}

mapVariants()

function generate5DigitSku() {
    let sku = ''
    for (let i = 0; i < 5; i++) {
        sku += Math.floor(Math.random() * 10)
    }
    return sku
}

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
    form.sku = generate5DigitSku()
}

function generateVariants() {
    const sizes = sizeValuesText.value.split(',').map(v => v.trim()).filter(Boolean)
    const colors = colorValuesText.value.split(',').map(v => v.trim()).filter(Boolean)
    const optionGroups = [sizes, colors].filter(g => g.length > 0)
    if (optionGroups.length === 0) return

    function cartesian(arrays) {
        return arrays.reduce((a, b) => a.flatMap(d => b.map(e => [...d, e])), [[]])
    }

    const combinations = cartesian(optionGroups)
    form.variants = combinations.map(combo => {
        const variant = {
            id: null,
            option1: combo[0] || '',
            option2: combo[1] || '',
            option3: combo[2] || '',
            sku: generate5DigitSku(),
            unit_price: form.unit_price || '',
            sale_price: form.sale_price || '',
            quantity: 10,
            product_image_id: null,
            image_url: null,
        }
        return variant
    })
}

function addVariant() {
    form.variants.push({
        id: null,
        option1: '',
        option2: '',
        option3: '',
        sku: generate5DigitSku(),
        unit_price: form.unit_price || '',
        sale_price: form.sale_price || '',
        quantity: 10,
        product_image_id: null,
        image_url: null,
    })
}

function showConfirm(title, message, onConfirm) {
    confirmDialog.value = { show: true, title, message, onConfirm }
}

function closeConfirm() {
    confirmDialog.value = { show: false, title: '', message: '', onConfirm: null }
}

function removeVariant(index) {
    const name = [form.variants[index].option1, form.variants[index].option2].filter(Boolean).join(' / ') || 'this variant'
    showConfirm('Remove Variant', `Remove "${name}"?`, () => {
        form.variants.splice(index, 1)
        closeConfirm()
    })
}

function variantSku(index) {
    form.variants[index].sku = generate5DigitSku()
}

function submit() {
    form.put(route('admin.products.update', product.id))
}

function destroyImage(imageId) {
    showConfirm('Delete Image', 'Delete this image permanently?', () => {
        router.delete(route('admin.products.images.destroy', { product: product.id, image: imageId }))
        closeConfirm()
    })
}

function toggleCategory(id) {
    const idx = form.category_ids.indexOf(id)
    if (idx === -1) {
        form.category_ids.push(id)
    } else {
        form.category_ids.splice(idx, 1)
    }
}

function formatPrice(price) {
    return '৳' + price.toLocaleString('en-IN')
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
    const remaining = Array.from(form.images).filter((_, i) => {
        let count = 0
        for (const preview of newImagePreviews.value) {
            if (preview.id === id) count++
        }
        return i !== idx
    })
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

function openImagePicker(variantIndex) {
    imagePickerTarget.value = variantIndex
    showImagePicker.value = true
}

function selectImage(image) {
    if (imagePickerTarget.value !== null) {
        form.variants[imagePickerTarget.value].product_image_id = image.id
        form.variants[imagePickerTarget.value].image_url = image.image_url || '/' + image.image_path
    }
    showImagePicker.value = false
    imagePickerTarget.value = null
}

function closeImagePicker() {
    showImagePicker.value = false
    imagePickerTarget.value = null
}

function onClickOutside(e) {
    if (categoryOpen.value && !e.target.closest('.category-select')) {
        categoryOpen.value = false
    }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
    <Head title="Edit Product" />

    <AdminMaster>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Product</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ product.title }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('product.show', product.slug)" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        Store View
                    </a>
                    <Link :href="route('admin.products.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
                </div>
            </div>

            <div v-if="product.images && product.images.length" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Current Images</h3>
                <div class="flex flex-wrap gap-3">
                    <div v-for="img in product.images" :key="img.id" class="relative group">
                        <img :src="img.image_url || '/' + img.image_path" :alt="product.title" class="w-20 h-20 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                        <button @click="destroyImage(img.id)" type="button" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">&times;</button>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
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
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categories <span class="text-red-500">*</span></label>
                            <div class="relative category-select">
                                <button type="button" @click="categoryOpen = !categoryOpen" class="w-full flex items-center gap-1 flex-wrap rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 min-h-[42px] text-sm text-left text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <template v-if="selectedCategoryNames.length">
                                        <span v-for="cat in selectedCategoryNames" :key="cat.id" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 rounded-full">
                                            {{ cat.name }}
                                            <button type="button" @click.stop="toggleCategory(cat.id)" class="hover:text-blue-900 dark:hover:text-blue-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </span>
                                    </template>
                                    <span v-else class="text-gray-400">Select categories</span>
                                    <svg class="w-4 h-4 text-gray-400 ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div v-if="categoryOpen" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-hidden">
                                    <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                                        <input v-model="categorySearch" type="text" placeholder="Search categories..." class="w-full rounded-md border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-2 py-1.5 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div class="overflow-y-auto max-h-44 p-1">
                                        <label v-for="cat in filteredCategories" :key="cat.id" class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-sm">
                                            <input type="checkbox" :checked="form.category_ids.includes(cat.id)" @change="toggleCategory(cat.id)" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" />
                                            <span class="text-gray-900 dark:text-white">{{ cat.name }}</span>
                                        </label>
                                        <p v-if="!filteredCategories.length" class="text-sm text-gray-400 p-2 text-center">No categories found</p>
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.category_ids" class="mt-1 text-sm text-red-600">{{ form.errors.category_ids }}</p>
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
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="featured" v-model="form.featured" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                        <label for="featured" class="text-sm font-medium text-gray-700 dark:text-gray-300">Featured</label>
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="sku_simple" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                            <div class="flex gap-2">
                                <input id="sku_simple" v-model="form.sku" type="text" class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Unique code" />
                                <button type="button" @click="generateSku" class="shrink-0 px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition whitespace-nowrap">Generate</button>
                            </div>
                            <p v-if="form.errors.sku" class="mt-1 text-sm text-red-600">{{ form.errors.sku }}</p>
                        </div>
                        <div>
                            <label for="quantity_simple" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                            <input id="quantity_simple" v-model="form.quantity" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.quantity" class="mt-1 text-sm text-red-600">{{ form.errors.quantity }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Add Images</label>
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

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
                        <div class="flex items-center gap-3 mb-4">
                            <input id="has_variants" v-model="hasVariants" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                            <label for="has_variants" class="text-sm font-medium text-gray-700 dark:text-gray-300">This product has variants (e.g. size, color)</label>
                        </div>

                        <template v-if="hasVariants">
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 space-y-4 mb-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Size</label>
                                        <input v-model="sizeValuesText" type="text" placeholder="e.g: S, M, L" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div class="flex-[2]">
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Color</label>
                                        <input v-model="colorValuesText" type="text" placeholder="e.g: Red, Pink, Yellow" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                </div>

                                <button type="button" @click="generateVariants" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                                    Generate Variants
                                </button>
                            </div>

                            <p v-if="form.errors['variants']" class="text-sm text-red-600 mb-2">{{ form.errors['variants'] }}</p>

                            <div v-if="form.variants.length" class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Image</th>
                                        <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Variant</th>
                                        <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">SKU</th>
                                        <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Unit Price</th>
                                        <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Sale Price</th>
                                        <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Qty</th>
                                        <th class="py-2 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(variant, vi) in form.variants" :key="vi" class="border-b border-gray-100 dark:border-gray-800">
                                            <td class="py-2 pr-3">
                                                <button type="button" @click="openImagePicker(vi)" class="w-10 h-10 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden hover:border-blue-400 dark:hover:border-blue-500 transition">
                                                    <img v-if="variant.image_url" :src="variant.image_url" class="w-full h-full object-cover" />
                                                    <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </button>
                                            </td>
                                            <td class="py-2 pr-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                {{ [variant.option1, variant.option2].filter(Boolean).join(' / ') }}
                                            </td>
                                            <td class="py-2 pr-3">
                                                <div class="flex items-center gap-1">
                                                    <input v-model="variant.sku" type="text" class="w-16 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                                    <button type="button" @click="variantSku(vi)" class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition" title="Generate SKU">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input v-model="variant.unit_price" type="number" min="0" class="w-20 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input v-model="variant.sale_price" type="number" min="0" class="w-20 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                            </td>
                                            <td class="py-2 pr-3">
                                                <input v-model="variant.quantity" type="number" min="0" class="w-16 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                            </td>
                                            <td class="py-2">
                                                <button v-if="form.variants.length > 1" type="button" @click="removeVariant(vi)" class="p-2 text-red-500 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition" title="Remove variant">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Updating...' : 'Update Product' }}
                        </button>
                        <Link :href="route('admin.products.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showImagePicker" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closeImagePicker">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-800 p-6 max-w-lg w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select Image</h3>
                        <button type="button" @click="closeImagePicker" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div v-if="product.images && product.images.length" class="grid grid-cols-4 gap-3">
                        <button v-for="img in product.images" :key="img.id" type="button" @click="selectImage(img)" class="aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-500 transition focus:outline-none focus:border-blue-500">
                            <img :src="img.image_url || '/' + img.image_path" :alt="product.title" class="w-full h-full object-cover" />
                        </button>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400">No images available. Upload images first.</p>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="confirmDialog.show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closeConfirm">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-800 p-6 max-w-sm w-full mx-4">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/20">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white text-center mb-2">{{ confirmDialog.title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">{{ confirmDialog.message }}</p>
                    <div class="flex items-center gap-3 justify-center">
                        <button type="button" @click="closeConfirm" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</button>
                        <button type="button" @click="confirmDialog.onConfirm" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Delete</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminMaster>
</template>
