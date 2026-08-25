<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'

const settings = usePage().props.settings || {}

const form = useForm({
    whatsapp_number: settings.whatsapp_number || '',
    telegram_number: settings.telegram_number || '',
})

function submit() {
    form.post(route('admin.settings.update'))
}
</script>

<template>
    <Head title="Site Config" />

    <AdminMaster>
        <div class="max-w-2xl mx-auto space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Site Config</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your site configuration</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="whatsapp_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">WhatsApp Number</label>
                        <input id="whatsapp_number" v-model="form.whatsapp_number" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="+1234567890" />
                        <p v-if="form.errors.whatsapp_number" class="mt-1 text-sm text-red-600">{{ form.errors.whatsapp_number }}</p>
                    </div>

                    <div>
                        <label for="telegram_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telegram Number</label>
                        <input id="telegram_number" v-model="form.telegram_number" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="+1234567890" />
                        <p v-if="form.errors.telegram_number" class="mt-1 text-sm text-red-600">{{ form.errors.telegram_number }}</p>
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
