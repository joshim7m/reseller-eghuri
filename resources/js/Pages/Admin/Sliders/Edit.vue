<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const { slider } = defineProps({
    slider: { type: Object, required: true },
})

const form = useForm({
    title: slider.title,
    sub_title: slider.sub_title,
    button_text: slider.button_text,
    button_link: slider.button_link,
    image: null,
})

function submit() {
    form.put(route('admin.sliders.update', slider.id))
}

function onImageChange(e) {
    form.image = e.target.files[0]
}
</script>

<template>
    <Head title="Edit Hero Slider" />

    <AdminMaster>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Hero Slider</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ slider.title }}</p>
                </div>
                <Link :href="route('admin.sliders.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <div class="mb-5">
                    <img :src="'/' + slider.image" :alt="slider.title" class="w-40 h-40 rounded-xl object-cover shadow-sm" />
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title <span class="text-red-500">*</span></label>
                        <input id="title" v-model="form.title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label for="sub_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sub Title</label>
                        <input id="sub_title" v-model="form.sub_title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.sub_title" class="mt-1 text-sm text-red-600">{{ form.errors.sub_title }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="button_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Button Text</label>
                            <input id="button_text" v-model="form.button_text" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.button_text" class="mt-1 text-sm text-red-600">{{ form.errors.button_text }}</p>
                        </div>
                        <div>
                            <label for="button_link" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Button Link</label>
                            <input id="button_link" v-model="form.button_link" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.button_link" class="mt-1 text-sm text-red-600">{{ form.errors.button_link }}</p>
                        </div>
                    </div>

                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Image (leave empty to keep current)</label>
                        <input id="image" type="file" accept="image/*" @change="onImageChange" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-600 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/40" />
                        <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Updating...' : 'Update Slider' }}
                        </button>
                        <Link :href="route('admin.sliders.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>