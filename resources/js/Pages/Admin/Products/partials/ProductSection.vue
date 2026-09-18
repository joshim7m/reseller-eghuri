<script setup>
import { ref } from 'vue'
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue'

const props = defineProps({
    form: { type: Object, required: true },
    product: { type: Object, default: null },
})

const showSpecification = ref(Boolean(props.form.specification))

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
    props.form.slug = generateSlug(props.form.title)
}
</script>

<template>
    <div>
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

        <div>
            <label for="youtube_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">YouTube Video URL</label>
            <input id="youtube_url" v-model="form.youtube_url" type="url"
                placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/..."
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            <p class="mt-1 text-xs text-gray-400">Paste a YouTube video URL. Supports youtube.com/watch?v=, youtu.be, shorts, and embed links.</p>
            <p v-if="form.errors.youtube_url" class="mt-1 text-sm text-red-600">{{ form.errors.youtube_url }}</p>
        </div>
    </div>
</template>
