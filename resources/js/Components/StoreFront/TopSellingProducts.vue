<script setup>
import ProductGridSlider from '@/Components/StoreFront/ProductGridSlider.vue';

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <ProductGridSlider
        title="Top Selling Products"
        :view-all-href="route('products.index')"
        :products="products"
    />
</template>
