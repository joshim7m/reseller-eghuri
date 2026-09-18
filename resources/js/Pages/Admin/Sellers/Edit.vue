<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const { user } = defineProps({
    user: { type: Object, required: true },
})

const form = useForm({
    name: user.name,
    email: user.email,
    mobile: user.user_detail?.mobile || '',
    company: user.user_detail?.company || '',
    address: user.user_detail?.address || '',
    user_type: user.user_type || 'reseller',
    status: user.status ? true : false,
    password: '',
    password_confirmation: '',
})

function submit() {
    form.put(route('admin.sellers.update', user.id))
}
</script>

<template>
    <Head title="Edit Seller" />

    <AdminMaster>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Seller</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ user.name }}</p>
                </div>
                <Link :href="route('admin.sellers.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name <span class="text-red-500">*</span></label>
                        <input id="name" v-model="form.name" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                        <input id="email" v-model="form.email" type="email" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="mobile" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mobile</label>
                        <input id="mobile" v-model="form.mobile" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.mobile" class="mt-1 text-sm text-red-600">{{ form.errors.mobile }}</p>
                    </div>

                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company</label>
                        <input id="company" v-model="form.company" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">{{ form.errors.company }}</p>
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                        <textarea id="address" v-model="form.address" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                    </div>

                    <div>
                        <label for="user_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type <span class="text-red-500">*</span></label>
                        <select id="user_type" v-model="form.user_type" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="reseller">Reseller</option>
                            <option value="wholeseller">Wholeseller</option>
                        </select>
                        <p v-if="form.errors.user_type" class="mt-1 text-sm text-red-600">{{ form.errors.user_type }}</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="status" v-model="form.status" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                        <label for="status" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                    </div>
                    <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Leave password blank to keep current</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                                <input id="password" v-model="form.password" type="password" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Updating...' : 'Update Seller' }}
                        </button>
                        <Link :href="route('admin.sellers.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
