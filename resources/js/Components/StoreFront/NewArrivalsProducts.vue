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
        title="New Arrivals"
        :view-all-href="route('products.new-arrivals')"
        :products="products"
    />
</template>
