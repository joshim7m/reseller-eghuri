<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const topCategories = computed(() => {
    const shuffled = [...props.categories].sort(() => Math.random() - 0.5);

    return shuffled.slice(0, 3);
});

const randomProduct = computed(() => {
    if (!props.products.length) {
return null;
}

    return props.products[Math.floor(Math.random() * props.products.length)];
});

const productImage = computed(() => {
    if (!randomProduct.value?.images?.length) {
return null;
}

    const img = randomProduct.value.images[0];

    if (typeof img === 'string') {
return img;
}

    return img.image_url || img.image_path || img.url || img.src || null;
});

const categoryImage = (cat) => cat.image_url || null;

const categoryLink = (cat) => route('category.show', cat.slug || cat.id);
</script>

<template>
    <section class="mb-16 rounded-3xl bg-gradient-to-r from-[#e3ecff] via-[#eae5ff] to-[#ffe7f0] p-4 sm:p-8 dark:bg-none dark:bg-transparent" role="region" aria-label="Hero grid">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4 md:auto-rows-[180px] lg:auto-rows-[220px]">
            <!-- Tile A: Large hero — first category -->
            <Link
                :href="topCategories[0] ? categoryLink(topCategories[0]) : route('products.new-arrivals')"
                class="group relative md:col-span-2 md:row-span-2 overflow-hidden rounded-2xl bg-surface-container dark:bg-[#241d1c]"
            >
                <img
                    v-if="topCategories[0] && categoryImage(topCategories[0])"
                    :src="categoryImage(topCategories[0])"
                    :alt="topCategories[0].name"
                    class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    loading="lazy"
                />
                <div v-else class="absolute inset-0 bg-gradient-to-br from-primary-container/40 via-surface to-surface-variant dark:from-[#4a2e2c]/40 dark:via-[#241d1c] dark:to-[#2e2523]"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/30 to-transparent"></div>
                <div class="relative z-10 flex h-full flex-col justify-center p-6 md:p-10">
                    <h2 class="mt-2 text-2xl font-bold text-white md:text-3xl lg:text-4xl">{{ topCategories[0]?.name || 'New Arrivals' }}</h2>
                    <span class="mt-6 inline-flex w-fit items-center gap-2 rounded-full bg-charcoal px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary dark:bg-[#f9eeed] dark:text-charcoal">
                        View Collection
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">arrow_forward</span>
                    </span>
                </div>
            </Link>

            <!-- Tile B: Second category -->
            <Link
                v-if="topCategories[1]"
                :href="categoryLink(topCategories[1])"
                class="group relative overflow-hidden rounded-2xl bg-surface-container dark:bg-[#241d1c]"
            >
                <img
                    v-if="categoryImage(topCategories[1])"
                    :src="categoryImage(topCategories[1])"
                    :alt="topCategories[1].name"
                    class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    loading="lazy"
                />
                <div v-else class="absolute inset-0 bg-gradient-to-br from-surface-variant to-surface-container-high dark:from-[#2e2523] dark:to-[#3a302e]"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                
            </Link>
            <div v-else class="flex items-center justify-center rounded-2xl bg-surface-container dark:bg-[#241d1c]">
                <span class="material-symbols-outlined text-4xl text-outline-variant dark:text-[#3a302e]" aria-hidden="true">storefront</span>
            </div>

            <!-- Tile C: Third category -->
            <Link
                v-if="topCategories[2]"
                :href="categoryLink(topCategories[2])"
                class="group relative overflow-hidden rounded-2xl bg-surface-container dark:bg-[#241d1c]"
            >
                <img
                    v-if="categoryImage(topCategories[2])"
                    :src="categoryImage(topCategories[2])"
                    :alt="topCategories[2].name"
                    class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    loading="lazy"
                />
                <div v-else class="absolute inset-0 bg-gradient-to-br from-surface-variant to-surface-container-high dark:from-[#2e2523] dark:to-[#3a302e]"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                
            </Link>
            <div v-else class="flex items-center justify-center rounded-2xl bg-surface-container dark:bg-[#241d1c]">
                <span class="material-symbols-outlined text-4xl text-outline-variant dark:text-[#3a302e]" aria-hidden="true">storefront</span>
            </div>

            <!-- Tile D: Wide call-to-action -->
            <Link
                :href="route('products.index')"
                class="group relative md:col-span-2 overflow-hidden rounded-2xl bg-charcoal dark:bg-[#2e2523]"
            >
                <img
                    v-if="productImage"
                    :src="productImage"
                    :alt="randomProduct?.title || 'Explore'"
                    class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    loading="lazy"
                />
                <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-transparent"></div>
                
            </Link>
        </div>
    </section>
</template>
