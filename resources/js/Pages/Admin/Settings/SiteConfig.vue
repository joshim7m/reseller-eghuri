<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const settings = usePage().props.settings || {}

function parseDeliveryAreas() {
    const raw = settings.delivery_areas

    if (!raw) {
        return [{ name: 'Inside Dhaka', charge: 50 }, { name: 'Outside Dhaka', charge: 120 }]
    }

    try {
        const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw

        return Array.isArray(parsed) && parsed.length
            ? parsed.map((area) => ({ name: area.name, charge: area.charge }))
            : [{ name: 'Inside Dhaka', charge: 50 }, { name: 'Outside Dhaka', charge: 120 }]
    } catch {
        return [{ name: 'Inside Dhaka', charge: 50 }, { name: 'Outside Dhaka', charge: 120 }]
    }
}

const form = useForm({
    whatsapp_number: settings.whatsapp_number || '',
    telegram_number: settings.telegram_number || '',
    delivery_areas: parseDeliveryAreas(),
})

const confirmingDelete = ref(null)

function addArea() {
    form.delivery_areas.push({ name: '', charge: '' })
}

function removeArea(index) {
    confirmingDelete.value = null
    form.delivery_areas.splice(index, 1)
}

function submit() {
    form.transform((data) => ({
        ...data,
        delivery_areas: data.delivery_areas.filter((area) => area.name.trim() !== ''),
    })).post(route('admin.settings.update'))
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

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Delivery Areas</label>
                            <button type="button" @click="addArea" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                Add Area
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">These areas appear as delivery options on the reseller order form.</p>

                        <div class="space-y-3">
                            <div v-for="(area, index) in form.delivery_areas" :key="index" class="flex items-center gap-3">
                                <template v-if="confirmingDelete === index">
                                    <div class="flex-1 flex items-center justify-between gap-3 rounded-lg border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-900/10 px-3 py-2">
                                        <p class="text-xs text-red-700 dark:text-red-400">Remove {{ area.name || 'this area' }} from delivery options?</p>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button" @click="removeArea(index)" class="px-2.5 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 transition">Remove</button>
                                            <button type="button" @click="confirmingDelete = null" class="px-2.5 py-1.5 text-xs font-medium rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">Cancel</button>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex-1">
                                        <input v-model="area.name" type="text" placeholder="Area name (e.g. Inside Dhaka)" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                        <p v-if="form.errors[`delivery_areas.${index}.name`]" class="mt-1 text-xs text-red-600">{{ form.errors[`delivery_areas.${index}.name`] }}</p>
                                    </div>
                                    <div class="w-28 shrink-0 relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 text-sm">৳</span>
                                        <input v-model.number="area.charge" type="number" min="0" placeholder="60" class="w-full pl-7 pr-3 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                        <p v-if="form.errors[`delivery_areas.${index}.charge`]" class="mt-1 text-xs text-red-600">{{ form.errors[`delivery_areas.${index}.charge`] }}</p>
                                    </div>
                                    <button type="button" @click="confirmingDelete = index" class="p-2 rounded-lg text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition shrink-0" aria-label="Remove area">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p v-if="form.errors.delivery_areas" class="mt-1 text-sm text-red-600">{{ form.errors.delivery_areas }}</p>
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
