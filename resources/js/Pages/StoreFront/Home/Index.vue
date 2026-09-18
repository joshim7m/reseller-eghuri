<script setup>
import { usePage } from '@inertiajs/vue3'
import FaqSection from '@/Components/StoreFront/FaqSection.vue'
import InstagramGallery from '@/Components/StoreFront/InstagramGallery.vue'
import NoticeSection from '@/Components/StoreFront/NoticeSection.vue'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'
import BestSellers from '@/Pages/StoreFront/Home/Partials/BestSellers.vue'
import BoutiqueBanner from '@/Pages/StoreFront/Home/Partials/BoutiqueBanner.vue'
import HeroGrid from '@/Pages/StoreFront/Home/Partials/HeroGrid.vue'
import NewArrivalsRow from '@/Pages/StoreFront/Home/Partials/NewArrivalsRow.vue'
import TopCategories from '@/Pages/StoreFront/Home/Partials/TopCategories.vue'

defineProps({
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    newArrivals: { type: Array, default: () => [] },
    instagramImages: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
    notices: { type: Array, default: () => [] }
})

const page = usePage()
const settings = page.props.settings || {}
</script>

<template>
    <FrontEndMaster>
        <HeroGrid :categories="categories" :products="products" />

        <TopCategories :categories="categories" />

        <BestSellers :products="products" />

        <NewArrivalsRow :products="newArrivals" />

        <BoutiqueBanner :categories="categories" />

        <NoticeSection :notices="notices" />

        <FaqSection :faqs="faqs" /> 
        
    </FrontEndMaster>
</template>
