<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

const props = defineProps({
    categories: { type: Array, default: () => [] }
})

const randomCategory = computed(() => {
    const withImage = props.categories.filter(c => c.image_url)
    if (!withImage.length) return null
    return withImage[Math.floor(Math.random() * withImage.length)]
})
</script>

<template>
    <Head title="Categories" />

    <FrontEndMaster>
        <section class="mb-12 border-b border-outline-variant dark:border-[#3a302e]">
            <div class="relative w-full h-[300px] md:h-[400px] overflow-hidden rounded-xl mb-8 bg-gradient-to-br from-primary-container/40 via-surface to-surface-variant dark:from-[#4a2e2c]/40 dark:via-[#241d1c] dark:to-[#2e2523]">
                <img v-if="randomCategory" :src="randomCategory.image_url" :alt="randomCategory.name" class="w-full h-full object-cover" loading="lazy" />
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
            </div>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-charcoal dark:text-[#f9eeed] uppercase tracking-tight mb-2">Categories</h1>
            <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">Browse our product categories</p>
        </section>

        <div v-if="categories.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4">
            <Link
                v-for="cat in categories" :key="cat.id"
                :href="route('category.show', cat.slug || cat.id)"
                class="group relative rounded-2xl overflow-hidden bg-white dark:bg-[#1e1917] border border-outline-variant dark:border-[#3a302e] shadow-sm hover:shadow-md transition-all duration-300"
            >
                <div class="aspect-[4/3] flex items-center justify-center overflow-hidden bg-surface-container dark:bg-[#241d1c]">
                    <img
                        v-if="cat.image_url"
                        :src="cat.image_url"
                        :alt="cat.name"
                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                    >
                    <span v-else class="material-symbols-outlined text-4xl text-outline-variant dark:text-[#3a302e]" aria-hidden="true">category</span>
                </div>
                <div class="p-3 md:p-4">
                    <h3 class="font-bold text-charcoal dark:text-[#f9eeed] text-sm md:text-base truncate group-hover:text-primary dark:group-hover:text-[#f6b7b2] transition-colors">{{ cat.name }}</h3>
                    <p v-if="cat.products_count !== undefined" class="mt-1.5">
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-surface-container dark:bg-[#2e2523] text-on-surface-variant dark:text-[#cbb8b6]">
                            {{ cat.products_count }} {{ cat.products_count === 1 ? 'product' : 'products' }}
                        </span>
                    </p>
                </div>
            </Link>
        </div>

        <div v-else class="text-center py-20">
            <span class="material-symbols-outlined text-6xl text-outline-variant dark:text-[#3a302e] mb-4" aria-hidden="true">category</span>
            <p class="text-on-surface-variant dark:text-[#cbb8b6] text-sm">No categories found.</p>
        </div>
    </FrontEndMaster>
</template>
