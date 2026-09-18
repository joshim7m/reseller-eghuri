<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const form = useForm({
    name: '',
    icon: '',
    icon_svg: '',
    url: '',
})

function submit() {
    form.post(route('admin.social-media.store'))
}
</script>

<template>
    <Head title="Add Social Media Link" />

    <AdminMaster>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Social Media Link</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add a new social media link</p>
                </div>
                <Link :href="route('admin.social-media.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input id="name" v-model="form.name" type="text" placeholder="e.g. Facebook" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icon Class</label>
                        <input id="icon" v-model="form.icon" type="text" placeholder="e.g. fab fa-facebook" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Font Awesome or other icon class name</p>
                        <p v-if="form.errors.icon" class="mt-1 text-sm text-red-600">{{ form.errors.icon }}</p>
                    </div>

                    <div>
                        <label for="icon_svg" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icon SVG</label>
                        <textarea id="icon_svg" v-model="form.icon_svg" rows="3" placeholder="Paste SVG markup here..." class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Alternatively, paste raw SVG markup</p>
                        <p v-if="form.errors.icon_svg" class="mt-1 text-sm text-red-600">{{ form.errors.icon_svg }}</p>
                    </div>

                    <div>
                        <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL <span class="text-red-500">*</span></label>
                        <input id="url" v-model="form.url" type="url" placeholder="https://facebook.com/yourpage" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.url" class="mt-1 text-sm text-red-600">{{ form.errors.url }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Adding...' : 'Add Link' }}
                        </button>
                        <Link :href="route('admin.social-media.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
