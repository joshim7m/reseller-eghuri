<script setup>
import { Link } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { computed, ref } from 'vue';
import 'swiper/css';
import HomeProductCard from '@/Components/StoreFront/HomeProductCard.vue';

const props = defineProps({
    title: { type: String, required: true },
    products: { type: Array, default: () => [] },
    viewAllHref: { type: String, default: '' },
});

const columns = computed(() => {
    const cols = [];

    props.products.forEach((product, i) => {
        const col = Math.floor(i / 2);

        if (!cols[col]) {
            cols[col] = [];
        }

        cols[col].push({ product, index: i });
    });

    return cols;
});

const swiper = ref(null);
const isBeginning = ref(true);
const isEnd = ref(true);

function syncState() {
    const s = swiper.value;

    if (!s) {
        return;
    }

    isBeginning.value = s.isBeginning;
    isEnd.value = s.isEnd;
}

function onSwiper(instance) {
    swiper.value = instance;
    syncState();
}

function prev() {
    swiper.value?.slidePrev();
}

function next() {
    swiper.value?.slideNext();
}
</script>

<template>
    <section v-if="products.length" class="mb-16">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h2
                    class="text-xl font-bold text-gray-800 dark:text-white md:text-2xl"
                >
                    {{ title }}
                </h2>
                <div
                    class="mt-2 h-1.5 w-16 rounded-full bg-gradient-to-r from-[#D9531E] via-amber-400 to-emerald-400"
                ></div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden items-center gap-2 md:flex">
                    <button
                        @click="prev"
                        :disabled="isBeginning"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition-all duration-300 hover:border-orange-200 hover:text-[#D9531E] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-gray-200 disabled:hover:text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-orange-500/40 dark:hover:text-orange-400 dark:disabled:hover:border-gray-700 dark:disabled:hover:text-gray-300 md:h-10 md:w-10"
                        :aria-label="'Previous ' + title.toLowerCase()"
                    >
                        <svg
                            class="h-4 w-4 md:h-5 md:w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                    </button>
                    <button
                        @click="next"
                        :disabled="isEnd"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition-all duration-300 hover:border-orange-200 hover:text-[#D9531E] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-gray-200 disabled:hover:text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-orange-500/40 dark:hover:text-orange-400 dark:disabled:hover:border-gray-700 dark:disabled:hover:text-gray-300 md:h-10 md:w-10"
                        :aria-label="'Next ' + title.toLowerCase()"
                    >
                        <svg
                            class="h-4 w-4 md:h-5 md:w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>
                </div>

                <Link
                    v-if="viewAllHref"
                    :href="viewAllHref"
                    class="group/link inline-flex items-center gap-1.5 text-sm font-semibold text-[#D9531E] transition-all duration-300 hover:gap-2.5 dark:text-orange-400"
                >
                    View All
                    <svg
                        class="h-4 w-4 transition-transform duration-300 group-hover/link:translate-x-0.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M13 7l5 5m0 0l-5 5m5-5H6"
                        />
                    </svg>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:hidden">
            <HomeProductCard
                v-for="(product, i) in products"
                :key="product.id"
                :product="product"
                :index="i"
            />
        </div>

        <div class="hidden md:block">
            <Swiper
                :slides-per-view="5"
                :space-between="20"
                :slides-per-group="1"
                class="pb-5"
                @swiper="onSwiper"
                @init="syncState"
                @slide-change="syncState"
                @resize="syncState"
            >
                <SwiperSlide
                    v-for="col in columns"
                    :key="col[0].product.id"
                    class="!h-auto"
                >
                    <div class="flex h-full flex-col gap-y-5">
                        <HomeProductCard
                            v-for="item in col"
                            :key="item.product.id"
                            :product="item.product"
                            :index="item.index"
                            class="!w-full"
                        />

                        <div v-if="col.length === 1" class="flex-1"></div>
                    </div>
                </SwiperSlide>
            </Swiper>
        </div>
    </section>
</template>
