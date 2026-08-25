<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Navigation } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const displayCategories = computed(() => props.categories.slice(0, 8));

const categoryImage = (cat) => cat.image_url || null;

const swiper = ref(null);
const isBeginning = ref(true);
const isEnd = ref(true);

function onSwiper(instance) {
    swiper.value = instance;
    syncState();
}

function syncState() {
    const s = swiper.value;
    if (!s) return;
    isBeginning.value = s.isBeginning;
    isEnd.value = s.isEnd;
}

function prev() { swiper.value?.slidePrev(); }
function next() { swiper.value?.slideNext(); }
</script>

<template>
    <section v-if="displayCategories.length" class="mb-16">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-charcoal dark:text-[#f9eeed] md:text-2xl">Top Categories</h2>
                <p class="mt-2 text-sm text-on-surface-variant dark:text-[#cbb8b6]">Explore our curated collections</p>
                <div class="mx-auto mt-3 h-1 w-12 rounded-full bg-primary"></div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="prev"
                    :disabled="isBeginning"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-surface-container-high transition hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3a302e] dark:hover:bg-[#2e2523]"
                    aria-label="Previous"
                >
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">chevron_left</span>
                </button>
                <button
                    @click="next"
                    :disabled="isEnd"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-surface-container-high transition hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3a302e] dark:hover:bg-[#2e2523]"
                    aria-label="Next"
                >
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">chevron_right</span>
                </button>
            </div>
        </div>

        <Swiper
            :modules="[Navigation]"
            :slides-per-view="3"
            :space-between="16"
            :breakpoints="{ 640: { slidesPerView: 4 }, 768: { slidesPerView: 5 }, 1024: { slidesPerView: 6 } }"
            @swiper="onSwiper"
            @slide-change="syncState"
        >
            <SwiperSlide v-for="cat in displayCategories" :key="cat.id">
                <Link
                    :href="route('category.show', cat.slug || cat.id)"
                    class="group relative flex flex-col items-center"
                >
                    <div class="relative w-full overflow-hidden rounded-2xl bg-surface-container aspect-square dark:bg-[#241d1c]">
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
                    <span class="mt-3 text-sm font-semibold text-charcoal dark:text-[#f9eeed] group-hover:text-primary dark:group-hover:text-[#f6b7b2] transition-colors">{{ cat.name }}</span>
                </Link>
            </SwiperSlide>
        </Swiper>
    </section>
</template>
