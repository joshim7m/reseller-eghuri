<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const form = useForm({
    name: '',
    provider: '',
    account_number: '',
    type: 'withdrawal',
    image: null,
    status: 'active',
})

function submit() {
    form.post(route('admin.payment-methods.store'))
}

function onImageChange(e) {
    form.image = e.target.files[0]
}
</script>

<template>
    <Head title="Add Payment Method" />

    <AdminMaster>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Payment Method</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add a payment account or provider</p>
                </div>
                <Link :href="route('admin.payment-methods.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                            <input id="name" v-model="form.name" type="text" placeholder="bKash / Nagad / Bank" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label for="provider" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Provider</label>
                            <input id="provider" v-model="form.provider" type="text" placeholder="bKash / SSLCOMMERZ / Bank" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.provider" class="mt-1 text-sm text-red-600">{{ form.errors.provider }}</p>
                        </div>
                    </div>

                    <div>
                        <label for="account_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Account Number <span class="text-red-500">*</span></label>
                        <input id="account_number" v-model="form.account_number" type="text" placeholder="0XXXXXXXXXX or bank account number" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.account_number" class="mt-1 text-sm text-red-600">{{ form.errors.account_number }}</p>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type <span class="text-red-500">*</span></label>
                        <select id="type" v-model="form.type" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="withdrawal">Withdrawal</option>
                            <option value="ecommerce">Ecommerce</option>
                        </select>
                        <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status <span class="text-red-500">*</span></label>
                        <select id="status" v-model="form.status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                    </div>

                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo</label>
                        <input id="image" type="file" accept="image/*" @change="onImageChange" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-600 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/40" />
                        <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Saving...' : 'Save Payment Method' }}
                        </button>
                        <Link :href="route('admin.payment-methods.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
