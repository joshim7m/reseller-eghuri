<script setup>
import { Link } from '@inertiajs/vue3';
import HomeProductCard from '@/Components/StoreFront/HomeProductCard.vue';

defineProps({
    products: { type: Array, default: () => [] },
});
</script>

<template>
    <section v-if="products.length" class="mb-16">
        <div class="mb-8 text-center">
            <h2 class="text-xl font-bold text-charcoal dark:text-[#f9eeed] md:text-2xl">Best Sellers Products</h2>
            <div class="mx-auto mt-3 h-1 w-12 rounded-full bg-primary"></div>
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
