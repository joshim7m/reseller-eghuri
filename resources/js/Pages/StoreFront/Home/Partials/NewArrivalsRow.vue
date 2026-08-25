<script setup>
import { Link } from '@inertiajs/vue3';
import HomeProductCard from '@/Components/StoreFront/HomeProductCard.vue';

defineProps({
    products: { type: Array, default: () => [] },
});
</script>

<template>
    <section v-if="products.length" class="mb-16">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-charcoal dark:text-[#f9eeed] md:text-2xl">New Arrivals</h2>
                <div class="mt-3 h-1 w-12 rounded-full bg-primary"></div>
            </div>
            <Link
                :href="route('products.new-arrivals')"
                class="group/link inline-flex items-center gap-1.5 text-sm font-semibold text-primary transition-colors hover:text-on-primary-container dark:text-[#f6b7b2] dark:hover:text-white"
            >
                View All
                <span class="material-symbols-outlined text-[16px] transition-transform group-hover/link:translate-x-0.5" aria-hidden="true">arrow_forward</span>
            </Link>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
            <HomeProductCard
                v-for="(product, index) in products"
                :key="product.id"
                :product="product"
                :index="index"
            />
        </div>
    </section>
</template>
