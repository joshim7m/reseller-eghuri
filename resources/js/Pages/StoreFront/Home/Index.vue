<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'
import HeroGrid from '@/Pages/StoreFront/Home/Partials/HeroGrid.vue'
import TopCategories from '@/Pages/StoreFront/Home/Partials/TopCategories.vue'
import BestSellers from '@/Pages/StoreFront/Home/Partials/BestSellers.vue'
import NewArrivalsRow from '@/Pages/StoreFront/Home/Partials/NewArrivalsRow.vue'
import BoutiqueBanner from '@/Pages/StoreFront/Home/Partials/BoutiqueBanner.vue'
import InstagramGallery from '@/Components/StoreFront/InstagramGallery.vue'
import NoticeSection from '@/Components/StoreFront/NoticeSection.vue'
import FaqSection from '@/Components/StoreFront/FaqSection.vue'

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
    <Head title="Home" />

    <FrontEndMaster>
        <HeroGrid :categories="categories" :products="products" />

        <TopCategories :categories="categories" />

        <BestSellers :products="products" />

        <NewArrivalsRow :products="newArrivals" />

        <BoutiqueBanner :categories="categories" />

        <InstagramGallery :instagramImages="instagramImages" />

        <!-- <NoticeSection :notices="notices" />

        <FaqSection :faqs="faqs" /> -->
    </FrontEndMaster>
</template>
