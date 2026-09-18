<script setup>
import { Link } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { computed, ref } from 'vue';
import 'swiper/css';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const displayCategories = computed(() => props.categories);

const categoryImage = (cat) => cat.image_url || null;

const swiper = ref(null);

function onSwiper(instance) {
    swiper.value = instance;
}

function prev() {
    swiper.value?.slidePrev();
}
function next() {
    swiper.value?.slideNext();
}
</script>

<template>
    <section v-if="displayCategories.length" class="mb-16 rounded-3xl bg-gradient-to-r from-[#e3ecff] via-[#eae5ff] to-[#ffe7f0] px-4 py-10 sm:px-8 dark:bg-none dark:bg-transparent">
        <div class="mb-2 flex items-end justify-center gap-4">
            <div class="mb-3 text-center">
            <h2 class="text-xl font-bold text-charcoal dark:text-[#f9eeed] md:text-2xl">Top Categories</h2>
            <div class="mx-auto mt-3 h-1 w-12 rounded-full bg-primary"></div>
        </div>
        </div>

        <Swiper
            :slides-per-view="3"
            :slides-per-group="3"
            :space-between="20"
            :speed="650"
            :breakpoints="{ 640: { slidesPerView: 4, slidesPerGroup: 4 }, 768: { slidesPerView: 6, slidesPerGroup: 6 }, 1024: { slidesPerView: 8, slidesPerGroup: 8 } }"
            @swiper="onSwiper"
        >
            <SwiperSlide v-for="cat in displayCategories" :key="cat.id" class="pt-5">
                <Link
                    :href="route('category.show', cat.slug || cat.id)"
                    class="group relative flex flex-col items-center"
                >
                    <div class="relative w-full overflow-hidden rounded-full bg-gray-50 aspect-square shadow-md transition-all duration-300 group-hover:ring-primary/80 group-hover:scale-105 dark:bg-[#241d1c]">
                        <img
                            v-if="categoryImage(cat)"
                            :src="categoryImage(cat)"
                            :alt="cat.name"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            loading="lazy"
                        />
                        <div v-else class="flex h-full items-center justify-center">
                            <span class="material-symbols-outlined text-5xl text-outline-variant" aria-hidden="true">category</span>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                    </div>
                    <span class="mt-3 text-sm text-charcoal dark:text-[#f9eeed] group-hover:text-primary dark:group-hover:text-[#f6b7b2] transition-colors">{{ cat.name }}</span>
                </Link>
            </SwiperSlide>
        </Swiper>

        <div class="mt-8 flex items-center justify-center gap-3">
            <button
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-surface-container-high bg-white text-on-surface transition hover:border-primary hover:text-primary dark:bg-[#241d1c] dark:text-[#f9eeed] dark:hover:border-[#f6b7b2] dark:hover:text-[#f6b7b2]"
                aria-label="Previous categories"
                @click="prev"
            >
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">arrow_back</span>
            </button>
            <button
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-surface-container-high bg-white text-on-surface transition hover:border-primary hover:text-primary dark:bg-[#241d1c] dark:text-[#f9eeed] dark:hover:border-[#f6b7b2] dark:hover:text-[#f6b7b2]"
                aria-label="Next categories"
                @click="next"
            >
                <span class="material-symbols-outlined text-[20px]" aria-hidden="true">arrow_forward</span>
            </button>
        </div>
    </section>
</template>

<style scoped>
:deep(.swiper-wrapper) {
    transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1) !important;
}
</style>
