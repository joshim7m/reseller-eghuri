<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import CategoriesAndVisibilitySection from './partials/CategoriesAndVisibilitySection.vue'
import PricingAndStockSection from './partials/PricingAndStockSection.vue'
import ProductGallerySection from './partials/ProductGallerySection.vue'
import ProductSection from './partials/ProductSection.vue'
import SeoSection from './partials/SeoSection.vue'
import VariantGenerator from './partials/VariantGenerator.vue'

defineProps({
    categories: { type: Array, required: true },
})

const form = useForm({
    category_id: '',
    title: '',
    slug: '',
    description: '',
    specification: '',
    sku: '',
    quantity: '',
    unit_price: '',
    sale_price: '',
    status: 'active',
    featured: false,
    meta_title: '',
    meta_description: '',
    youtube_url: '',
    images: null,
    variants: [],
})

function submit() {
    form.post(route('admin.products.store'))
}
</script>

<template>
    <Head title="Create Product" />

    <AdminMaster>
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Product</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add a new product to your catalog</p>
                </div>
                <Link :href="route('admin.products.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <CategoriesAndVisibilitySection :form="form" :categories="categories" mode="single" />
                    <ProductSection :form="form" />
                    <PricingAndStockSection :form="form" />
                    <ProductGallerySection :form="form" />
                    <SeoSection v-model="form" />
                    <VariantGenerator :form="form" />

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Creating...' : 'Create Product' }}
                        </button>
                        <Link :href="route('admin.products.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>