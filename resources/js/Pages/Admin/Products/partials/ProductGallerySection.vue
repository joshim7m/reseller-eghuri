<script setup>
import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    form: { type: Object, required: true },
    product: { type: Object, default: null },
})

const dropzoneRef = ref(null)
const isDragging = ref(false)
const newImagePreviews = ref([])
const confirmDialog = ref({ show: false, title: '', message: '', onConfirm: null })

const hasNewImages = computed(() => newImagePreviews.value.length > 0)

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

    if (! validFiles.length) {
return
}

    const dt = new DataTransfer()
    const existing = props.form.images ? Array.from(props.form.images) : []

    for (const f of [...existing, ...validFiles]) {
        dt.items.add(f)
    }

    props.form.images = dt.files

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

    if (idx === -1) {
return
}

    newImagePreviews.value.splice(idx, 1)

    const dt = new DataTransfer()
    const remaining = Array.from(props.form.images).filter((_, i) => i !== idx)

    for (const f of remaining) {
        dt.items.add(f)
    }

    props.form.images = dt.files
}

function openFilePicker() {
    const input = document.createElement('input')
    input.type = 'file'
    input.accept = 'image/*'
    input.multiple = true
    input.onchange = onFileSelect
    input.click()
}

function destroyImage(imageId) {
    if (! props.product) {
return
}

    confirmDialog.value = {
        show: true,
        title: 'Delete Image',
        message: 'Delete this image permanently?',
        onConfirm: () => {
            router.delete(route('admin.products.images.destroy', { product: props.product.id, image: imageId }))
            confirmDialog.value.show = false
        },
    }
}

function closeConfirm() {
    confirmDialog.value = { show: false, title: '', message: '', onConfirm: null }
}
</script>

<template>
    <!-- Current images (edit mode only) -->
    <div v-if="product?.images?.length" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Current Images</h3>
        <div class="flex flex-wrap gap-3">
            <div v-for="img in product.images" :key="img.id" class="relative group">
                <img :src="img.image_url || '/' + img.image_path" :alt="product?.title" class="w-20 h-20 rounded-lg object-cover border border-gray-200 dark:border-gray-700" />
                <button @click="destroyImage(img.id)" type="button" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs font-bold">&times;</button>
            </div>
        </div>
    </div>

    <!-- Upload dropzone -->
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

    <ConfirmDialog
        :show="confirmDialog.show"
        :title="confirmDialog.title"
        :message="confirmDialog.message"
        @confirm="confirmDialog.onConfirm"
        @cancel="closeConfirm"
    />
</template>
