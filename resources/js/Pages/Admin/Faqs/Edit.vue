<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const { faq } = defineProps({
    faq: { type: Object, required: true },
})

const form = useForm({
    question: faq.question,
    answer: faq.answer,
    sort_order: faq.sort_order,
    status: faq.status,
})

function submit() {
    form.put(route('admin.faqs.update', faq.id))
}
</script>

<template>
    <Head title="Edit FAQ" />

    <AdminMaster>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit FAQ</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update the frequently asked question</p>
                </div>
                <Link :href="route('admin.faqs.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="question" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Question <span class="text-red-500">*</span></label>
                        <input id="question" v-model="form.question" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        <p v-if="form.errors.question" class="mt-1 text-sm text-red-600">{{ form.errors.question }}</p>
                    </div>

                    <div>
                        <label for="answer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Answer <span class="text-red-500">*</span></label>
                        <textarea id="answer" v-model="form.answer" rows="5" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required></textarea>
                        <p v-if="form.errors.answer" class="mt-1 text-sm text-red-600">{{ form.errors.answer }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                            <input id="sort_order" v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lower numbers appear first</p>
                            <p v-if="form.errors.sort_order" class="mt-1 text-sm text-red-600">{{ form.errors.sort_order }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <label class="inline-flex items-center gap-2.5 cursor-pointer">
                                <input id="status" v-model="form.status" type="checkbox" class="w-4 h-4 rounded border-gray-300 dark:border-gray-700 text-blue-600 focus:ring-blue-500" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Active</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Inactive FAQs are hidden from the storefront</p>
                            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Updating...' : 'Update FAQ' }}
                        </button>
                        <Link :href="route('admin.faqs.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
