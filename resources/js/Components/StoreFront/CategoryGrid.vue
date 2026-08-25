<script setup>
import { Link } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { computed, ref } from 'vue';
import 'swiper/css';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const backdrops = [
    'bg-gradient-to-br from-orange-100 via-rose-50 to-indigo-100',
    'bg-gradient-to-br from-indigo-100 via-sky-50 to-emerald-100',
    'bg-gradient-to-br from-rose-100 via-amber-50 to-lime-100',
    'bg-gradient-to-br from-violet-100 via-fuchsia-50 to-orange-100',
    'bg-gradient-to-br from-emerald-100 via-teal-50 to-blue-100',
];

const accents = [
    'from-[#D9531E] via-amber-400 to-emerald-400',
    'from-indigo-500 via-sky-400 to-emerald-400',
    'from-rose-500 via-fuchsia-400 to-indigo-400',
    'from-violet-500 via-purple-400 to-pink-400',
    'from-teal-500 via-cyan-400 to-blue-400',
];

const backdropFor = (i) => backdrops[i % backdrops.length];
const accentFor = (i) => accents[i % accents.length];

const columns = computed(() => {
    const cols = [];

    props.categories.forEach((cat, i) => {
        const col = Math.floor(i / 2);

        if (!cols[col]) {
            cols[col] = [];
        }

        cols[col].push({ cat, index: i });
    });

    return cols;
});

const swiper = ref(null);
const snaps = ref([0]);
const activeSnap = ref(0);
const isBeginning = ref(true);
const isEnd = ref(true);

function syncState() {
    const s = swiper.value;

    if (!s) {
        return;
    }

    snaps.value = s.snapGrid;
    activeSnap.value = s.activeIndex;
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

function goTo(i) {
    swiper.value?.slideTo(snaps.value[i]);
}
</script>

<template>
    <section v-if="categories.length" class="mb-16">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h2
                    class="text-xl font-bold text-gray-800 dark:text-white md:text-2xl"
                >
                    Shop by Category
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
                        :aria-label="'Previous categories'"
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
                        :aria-label="'Next categories'"
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
                    :href="route('categories.index')"
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

        <div class="relative">
            <Swiper
                :slides-per-view="3"
                :space-between="8"
                :slides-per-group="1"
                :breakpoints="{
                    768: { slidesPerView: 5, spaceBetween: 20 },
                }"
                class="pb-5"
                @swiper="onSwiper"
                @init="syncState"
                @slide-change="syncState"
                @resize="syncState"
            >
                <SwiperSlide
                    v-for="col in columns"
                    :key="col[0].cat.id"
                    class="!h-auto"
                >
                    <div class="flex h-full flex-col gap-y-5">
                        <Link
                            v-for="item in col"
                            :key="item.cat.id"
                            :href="
                                route(
                                    'category.show',
                                    item.cat.slug || item.cat.id,
                                )
                            "
                            class="group relative flex w-full flex-1 flex-col rounded-[2rem] border border-gray-200/80 bg-white p-2.5 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-orange-200 hover:shadow-2xl hover:shadow-orange-100/70 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600 dark:hover:shadow-gray-900/60"
                        >
                            <span
                                :class="accentFor(item.index)"
                                class="absolute inset-x-10 top-0 h-1 rounded-b-full bg-gradient-to-r opacity-80"
                            ></span>

                            <div
                                :class="backdropFor(item.index)"
                                class="relative flex aspect-[4/3] items-center justify-center overflow-hidden rounded-[1.5rem] text-gray-300 dark:text-gray-600"
                            >
                                <img
                                    v-if="item.cat.image_url"
                                    :src="item.cat.image_url"
                                    :alt="item.cat.name"
                                    class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-110"
                                    loading="lazy"
                                />
                                <svg
                                    v-else
                                    class="h-12 w-12"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>

                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/5 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                ></div>
                            </div>

                            <div
                                class="hidden items-center justify-center gap-2 p-2.5 pt-3 md:flex"
                            >
                                <div class="min-w-0">
                                    <h3
                                        class="truncate text-sm font-bold text-[#0B132A] transition-colors group-hover:text-[#D9531E] dark:text-gray-100 dark:group-hover:text-orange-400 md:text-base"
                                    >
                                        {{ item.cat.name }}
                                    </h3>
                                    <p
                                        v-if="
                                            item.cat.products_count !==
                                            undefined
                                        "
                                        class="mt-1.5"
                                    >
                                        <span
                                            class="hidden items-center gap-1 rounded-full border border-orange-200 bg-orange-50 px-2 py-0.5 text-[11px] font-semibold text-orange-600 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-400 md:inline-flex"
                                        >
                                            <svg
                                                class="h-3 w-3"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                                />
                                            </svg>
                                            {{ item.cat.products_count }}
                                            {{
                                                item.cat.products_count === 1
                                                    ? 'product'
                                                    : 'products'
                                            }}
                                        </span>
                                    </p>
                                </div>

                                <span
                                    class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#D9531E] to-orange-500 text-white shadow-md shadow-orange-200/70 transition-all duration-300 group-hover:rotate-45 group-hover:scale-105 group-hover:from-orange-500 group-hover:to-orange-400 dark:shadow-orange-900/30 md:flex"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M7 17L17 7m0 0H8m9 0v9"
                                        />
                                    </svg>
                                </span>
                            </div>
                        </Link>

                        <div v-if="col.length === 1" class="flex-1"></div>
                    </div>
                </SwiperSlide>
            </Swiper>

            <div
                v-if="snaps.length > 1"
                class="absolute -bottom-5 left-1/2 z-10 flex -translate-x-1/2 items-center gap-1.5 md:hidden"
            >
                <button
                    v-for="(snap, i) in snaps"
                    :key="snap"
                    @click="goTo(i)"
                    class="h-1.5 rounded-full transition-all duration-300"
                    :class="
                        activeSnap === snap
                            ? 'w-5 bg-[#D9531E]'
                            : 'w-1.5 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500'
                    "
                    :aria-label="'Go to slide ' + (i + 1)"
                ></button>
            </div>
        </div>
    </section>
</template>
