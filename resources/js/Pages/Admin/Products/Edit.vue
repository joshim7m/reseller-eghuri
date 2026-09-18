<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import CategoriesAndVisibilitySection from './partials/CategoriesAndVisibilitySection.vue'
import PricingAndStockSection from './partials/PricingAndStockSection.vue'
import ProductGallerySection from './partials/ProductGallerySection.vue'
import ProductSection from './partials/ProductSection.vue'
import SeoSection from './partials/SeoSection.vue'
import VariantGenerator from './partials/VariantGenerator.vue'

const { product, categories } = defineProps({
    product: { type: Object, required: true },
    categories: { type: Array, required: true },
})

const form = useForm({
    category_ids: product.categories ? product.categories.map(c => c.id) : [],
    title: product.title,
    slug: product.slug,
    description: product.description,
    specification: product.specification || '',
    sku: product.sku || '',
    quantity: product.quantity ?? '',
    unit_price: product.unit_price,
    sale_price: product.sale_price,
    status: product.status,
    featured: product.featured,
    meta_title: product.meta_title || '',
    meta_description: product.meta_description || '',
    youtube_url: product.youtube_url || '',
    images: null,
    variants: [],
})

function submit() {
    form.put(route('admin.products.update', product.id))
}
</script>

<template>
    <Head title="Edit Product" />

    <AdminMaster>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Product</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ product.title }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('product.show', product.slug)" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        Store View
                    </a>
                    <Link :href="route('admin.products.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-5">
                    <ProductSection :form="form" :product="product" />
                    <CategoriesAndVisibilitySection :form="form" :categories="categories" mode="multi" />
                    <PricingAndStockSection :form="form" />
                    <ProductGallerySection :form="form" :product="product" />
                    <SeoSection v-model="form" />
                    <VariantGenerator :form="form" :product="product" />

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Updating...' : 'Update Product' }}
                        </button>
                        <Link :href="route('admin.products.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
