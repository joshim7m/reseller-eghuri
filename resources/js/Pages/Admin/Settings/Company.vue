<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'

const settings = usePage().props.settings || {}

const form = useForm({
    company_name: settings.company_name || '',
    company_email: settings.company_email || '',
    company_phone: settings.company_phone || '',
    company_address: settings.company_address || '',
    company_logo: null,
    company_favicon: null,
})

function submit() {
    form.post(route('admin.settings.update'))
}

function onLogoChange(e) {
    form.company_logo = e.target.files[0]
}

function onFaviconChange(e) {
    form.company_favicon = e.target.files[0]
}
</script>

<template>
    <Head title="Company Settings" />

    <AdminMaster>
        <div class="max-w-2xl mx-auto space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Company Settings</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your company information</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Name</label>
                        <input id="company_name" v-model="form.company_name" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.company_name" class="mt-1 text-sm text-red-600">{{ form.errors.company_name }}</p>
                    </div>

                    <div>
                        <label for="company_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Email</label>
                        <input id="company_email" v-model="form.company_email" type="email" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.company_email" class="mt-1 text-sm text-red-600">{{ form.errors.company_email }}</p>
                    </div>

                    <div>
                        <label for="company_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Phone</label>
                        <input id="company_phone" v-model="form.company_phone" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.company_phone" class="mt-1 text-sm text-red-600">{{ form.errors.company_phone }}</p>
                    </div>

                    <div>
                        <label for="company_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Address</label>
                        <textarea id="company_address" v-model="form.company_address" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <p v-if="form.errors.company_address" class="mt-1 text-sm text-red-600">{{ form.errors.company_address }}</p>
                    </div>

                    <div>
                        <label for="company_logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Logo</label>
                        <div v-if="settings.company_logo" class="mb-2">
                            <img :src="'/' + settings.company_logo" alt="Current logo" class="h-12 w-auto rounded-lg" />
                        </div>
                        <input id="company_logo" type="file" accept="image/*" @change="onLogoChange" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-600 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/40" />
                        <p v-if="form.errors.company_logo" class="mt-1 text-sm text-red-600">{{ form.errors.company_logo }}</p>
                    </div>

                    <div>
                        <label for="company_favicon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Favicon</label>
                        <div v-if="settings.company_favicon" class="mb-2">
                            <img :src="'/' + settings.company_favicon" alt="Current favicon" class="h-8 w-auto rounded" />
                        </div>
                        <input id="company_favicon" type="file" accept="image/*" @change="onFaviconChange" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-600 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/40" />
                        <p v-if="form.errors.company_favicon" class="mt-1 text-sm text-red-600">{{ form.errors.company_favicon }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Saving...' : 'Save Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
