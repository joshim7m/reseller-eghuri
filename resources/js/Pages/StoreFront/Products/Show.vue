<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick, onBeforeUnmount } from 'vue';
import HomeProductCard from '@/Components/StoreFront/HomeProductCard.vue';
import { dimensionNames, dimensionValues, findVariantByOptions, isColorDimension } from '@/composables/useVariantOptions';
import { useWishlist } from '@/composables/useWishlist';
import { youtubeVideoId, loadYoutubeIframeApi } from '@/composables/useYoutubeVideo';
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue';

function renderContent(html) {
    if (!html) {
        return '';
    }

    if (/<[a-z][\s\S]*>/i.test(html)) {
        return html;
    }

    return html
        .split(/\n+/)
        .map((p) => `<p>${p.replace(/</g, '&lt;')}</p>`)
        .join('');
}

const props = defineProps({
    product: { type: Object, default: () => ({}) },
    related: { type: Array, default: () => [] },
});

const activeImage = ref(0);
const selectedOptions = ref({});
const descriptionExpanded = ref(false);

const images = computed(() => {
    return (props.product.images || []).map(
        (img) => img.image_url || img.image_path || img.url,
    );
});

const videoId = computed(() => youtubeVideoId(props.product.youtube_url));

const hasVideo = computed(() => Boolean(videoId.value));

const videoIndex = computed(() => images.value.length);

const slideCount = computed(() => images.value.length + (hasVideo.value ? 1 : 0));

const videoStarted = ref(false);
const videoThumb = ref('');
const player = ref(null);
const playerHost = ref(null);
const isPlaying = ref(false);
const isMuted = ref(false);
const volume = ref(100);

const videoThumbUrl = computed(() => videoThumb.value || `https://img.youtube.com/vi/${videoId.value}/maxresdefault.jpg`);

function hydrateThumb() {
    if (hasVideo.value) {
        videoThumb.value = `https://img.youtube.com/vi/${videoId.value}/maxresdefault.jpg`;
    }
}

hydrateThumb();

async function playVideo() {
    if (!hasVideo.value || videoStarted.value) {
        return;
    }

    videoStarted.value = true;

    await nextTick();

    if (!playerHost.value) {
        return;
    }

    const YT = await loadYoutubeIframeApi();

    player.value = new YT.Player(playerHost.value, {
        width: '100%',
        height: '100%',
        videoId: videoId.value,
        playerVars: {
            autoplay: 1,
            controls: 0,
            rel: 0,
            iv_load_policy: 3,
            modestbranding: 1,
            playsinline: 1,
            fs: 0,
            disablekb: 1,
        },
        events: {
            onStateChange: (event) => {
                isPlaying.value = event.data === YT.PlayerState.PLAYING;

                if (event.data === YT.PlayerState.ENDED) {
                    isPlaying.value = false;
                }
            },
        },
    });
}

function togglePlay() {
    if (!player.value) {
        return;
    }

    if (isPlaying.value) {
        player.value.pauseVideo();
        isPlaying.value = false;
    } else {
        player.value.playVideo();
    }
}

function changeVolume() {
    if (!player.value) {
        return;
    }

    player.value.setVolume(volume.value);

    if (volume.value === 0) {
        player.value.mute();
        isMuted.value = true;
    } else {
        player.value.unMute();
        isMuted.value = false;
    }
}

function toggleMute() {
    if (!player.value) {
        return;
    }

    if (isMuted.value) {
        player.value.unMute();
        isMuted.value = false;
        volume.value = volume.value || 100;
        player.value.setVolume(volume.value);
    } else {
        player.value.mute();
        isMuted.value = true;
    }
}

watch(activeImage, (slide) => {
    if (player.value && slide !== videoIndex.value) {
        player.value.pauseVideo();
    }
});

onBeforeUnmount(() => {
    player.value?.destroy();
    player.value = null;
});

const variants = computed(() => props.product.variants || []);

const category = computed(
    () => props.product.category || props.product.categories?.[0] || null,
);

const dimensions = computed(() =>
    dimensionNames(variants.value).map((name) => ({
        name,
        values: dimensionValues(variants.value, name),
    })),
);

const selectedVariant = computed(() =>
    findVariantByOptions(variants.value, selectedOptions.value),
);

const availableQty = computed(() => {
    return selectedVariant.value ? selectedVariant.value.quantity : 0;
});

const stockQty = computed(() =>
    selectedVariant.value ? availableQty.value : Number(props.product.quantity || 0),
);

const isInStock = computed(() => stockQty.value > 0);

const salePrice = computed(() => {
    if (selectedVariant.value?.unit_price) {
        return Number(selectedVariant.value.unit_price);
    }

    return Number(props.product.unit_price || props.product.sale_price || 0);
});

const originalPrice = computed(() => {
    if (selectedVariant.value?.sale_price) {
        return Number(selectedVariant.value.sale_price);
    }

    return Number(
        props.product.sale_price || props.product.original_price || 0,
    );
});

const descriptionIsLong = computed(() => {
    const text = String(props.product.description || '')
        .replace(/<[^>]*>/g, '')
        .trim();

    return text.length > 180;
});

function prevImage() {
    activeImage.value = activeImage.value > 0 ? activeImage.value - 1 : 0;
}

function nextImage() {
    activeImage.value =
        activeImage.value < slideCount.value - 1
            ? activeImage.value + 1
            : slideCount.value - 1;
}

function selectOption(name, value) {
    selectedOptions.value = { ...selectedOptions.value, [name]: value };
}

const colorHex = (color) => {
    const map = {
        red: '#ef4444',
        black: '#1a1a1a',
        white: '#ffffff',
        blue: '#3b82f6',
        green: '#22c55e',
        pink: '#ec4899',
        purple: '#a855f7',
        grey: '#6b7280',
        gray: '#6b7280',
        navy: '#1e3a5f',
        brown: '#8b4513',
        beige: '#f5f5dc',
        champagne: '#f7e7ce',
        'dusty pink': '#d8a7b0',
        'army green': '#4b5320',
        olive: '#808000',
        'hot pink': '#ff69b4',
        bronze: '#cd7f32',
        'multi colour': 'linear-gradient(90deg, #ef4444, #3b82f6, #22c55e, #eab308)',
        multicolor: 'linear-gradient(90deg, #ef4444, #3b82f6, #22c55e, #eab308)',
        multicolour: 'linear-gradient(90deg, #ef4444, #3b82f6, #22c55e, #eab308)',
        multi: 'linear-gradient(90deg, #ef4444, #3b82f6, #22c55e, #eab308)',
        'baby pink': '#f4c2c2',
        'mint blue': '#a2d8d8',
    };

    return map[color.toLowerCase()] || '#d1d5db';
};

const isLightColor = (color) => {
    return ['white', 'beige', 'champagne', 'silver', 'light', 'ivory', 'cream']
        .some((c) => color.toLowerCase().includes(c));
};

watch(selectedVariant, (variant) => {
    if (variant?.image?.image_url || variant?.image?.image_path) {
        const url = variant.image.image_url || variant.image.image_path;
        const idx = images.value.indexOf(url);

        if (idx !== -1) {
            activeImage.value = idx;
        }
    }
});

const wishlist = useWishlist();
const isWished = computed(() => wishlist.has(props.product.id));

function toggleWishlist() {
    wishlist.toggle(props.product.id);
}

const hasOptions = computed(() => dimensions.value.length > 0);
</script>

<template>
    <FrontEndMaster>
        <nav class="mb-4 hidden sm:flex flex-wrap items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            <Link :href="route('home')" class="transition hover:text-blue-600 dark:hover:text-blue-400">Home</Link>
            <svg class="h-3 w-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <Link v-if="category" :href="route('category.show', category.slug || category.id)"
                class="transition hover:text-blue-600 dark:hover:text-blue-400">{{ category.name }}</Link>
            <svg v-if="category" class="h-3 w-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor"
                stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-800 dark:text-gray-200">{{
                product.title
                }}</span>
        </nav>

        <div class="grid grid-cols-1 gap-6 md:gap-8 lg:grid-cols-2">
            <div class="space-y-3">
                <div
                    class="group relative flex aspect-square items-center justify-center overflow-hidden rounded-2xl bg-gray-100 text-sm text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                    <img v-for="(img, i) in images" :key="i" :src="img" :class="{
                        'opacity-100': activeImage === i,
                        'opacity-0': activeImage !== i,
                    }" class="absolute inset-0 h-full w-full object-cover transition-opacity duration-300" />
                    <div v-if="hasVideo && activeImage === videoIndex"
                        class="absolute inset-0 h-full w-full bg-black">
                        <img v-if="!videoStarted" :src="videoThumbUrl" alt="" class="h-full w-full object-cover"
                            @error="videoThumb = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`" />
                        <template v-else>
                            <div ref="playerHost" class="absolute inset-0 h-full w-full"></div>
                            <button type="button" @click="togglePlay" :aria-label="isPlaying ? 'Pause video' : 'Play video'"
                                class="absolute inset-0 z-0 cursor-pointer select-none"></button>
                            <div
                                class="absolute bottom-3 left-1/2 z-10 flex -translate-x-1/2 items-center gap-2 rounded-full bg-black/70 px-3 py-1.5 text-white shadow-lg backdrop-blur-sm">
                                <button type="button" @click="togglePlay" :aria-label="isPlaying ? 'Pause video' : 'Play video'"
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 transition hover:bg-white/30">
                                    <svg v-if="isPlaying" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M6 5h4v14H6zM14 5h4v14h-4z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </button>
                                <button type="button" @click="toggleMute" :aria-label="isMuted ? 'Unmute video' : 'Mute video'"
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white/15 transition hover:bg-white/30">
                                    <svg v-if="isMuted || volume === 0" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728M15.536 8.464a5 5 0 010 7.072M11 5L6 9H2v6h4l5 4V5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 2l20 20" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5L6 9H2v6h4l5 4V5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072" />
                                    </svg>
                                </button>
                                <input v-model.number="volume" type="range" min="0" max="100" step="1" @input="changeVolume"
                                    aria-label="Volume"
                                    class="h-1.5 w-16 cursor-pointer appearance-none rounded-full bg-white/25 accent-white sm:w-20" />
                            </div>
                        </template>

                        <button v-if="!videoStarted" type="button" @click="playVideo" aria-label="Play video"
                            class="absolute inset-0 flex items-center justify-center bg-black/40 transition hover:bg-black/30">
                            <span
                                class="flex h-16 w-16 items-center justify-center rounded-full bg-white/95 text-red-600 shadow-2xl transition hover:scale-110 sm:h-20 sm:w-20">
                                <svg class="h-7 w-7 sm:h-9 sm:w-9" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </span>
                        </button>
                    </div>
                    <span v-if="!images.length && !hasVideo">No Image</span>

                    <span v-if="originalPrice > salePrice"
                        class="absolute left-3 top-3 rounded-full bg-red-500 px-2.5 py-1 text-xs font-bold text-white shadow-md">
                        Save ৳{{ (originalPrice - salePrice).toLocaleString() }}
                    </span>

                    <button v-if="slideCount > 1" @click="prevImage" aria-label="Previous image"
                        class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 opacity-0 shadow-sm transition hover:bg-white group-hover:opacity-100 dark:bg-gray-900/80 dark:text-gray-300 dark:hover:bg-gray-900">
                        &lsaquo;
                    </button>
                    <button v-if="slideCount > 1" @click="nextImage" aria-label="Next image"
                        class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 opacity-0 shadow-sm transition hover:bg-white group-hover:opacity-100 dark:bg-gray-900/80 dark:text-gray-300 dark:hover:bg-gray-900">
                        &rsaquo;
                    </button>
                    <div v-if="slideCount > 1" class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5">
                        <button v-for="i in slideCount" :key="i" @click="activeImage = i - 1"
                            :aria-label="`Slide ${i}`" class="h-2 w-2 rounded-full transition" :class="activeImage === i - 1
                                    ? 'bg-white shadow-sm dark:bg-white'
                                    : 'bg-white/40 dark:bg-white/40'
                                "></button>
                    </div>
                </div>
                <div v-if="slideCount > 1" class="flex gap-2 overflow-x-auto pb-1">
                    <button v-for="(img, i) in images" :key="i" @click="activeImage = i" :aria-label="`Image ${i + 1}`"
                        class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 transition sm:h-20 sm:w-20"
                        :class="{
                            'border-blue-600 dark:border-blue-400':
                                activeImage === i,
                            'border-transparent': activeImage !== i,
                        }">
                        <img :src="img" alt="" class="h-full w-full object-cover" />
                    </button>
                    <button v-if="hasVideo" @click="activeImage = videoIndex" aria-label="Video"
                        class="relative h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 transition sm:h-20 sm:w-20"
                        :class="{
                            'border-blue-600 dark:border-blue-400':
                                activeImage === videoIndex,
                            'border-transparent': activeImage !== videoIndex,
                        }">
                        <img :src="`https://img.youtube.com/vi/${videoId}/mqdefault.jpg`" alt=""
                            class="h-full w-full object-cover" />
                        <span
                            class="absolute inset-0 flex items-center justify-center bg-black/30 text-white">
                            <svg class="h-6 w-6 drop-shadow" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <div class="space-y-5 lg:border-l lg:border-gray-100 lg:pl-10 dark:lg:border-gray-800">


                <h1 class="text-lg md:text-2xl font-bold leading-snug text-gray-900 dark:text-white">
                    {{ product.title }}
                </h1>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    <span class="text-xl font-extrabold text-blue-600 sm:text-[28px] dark:text-blue-400">৳{{
                        salePrice.toLocaleString() }}</span>
                    <span v-if="originalPrice > salePrice"
                        class="text-base font-medium text-gray-400 line-through dark:text-gray-500">৳{{
                            originalPrice.toLocaleString() }}</span>
                    <span v-if="originalPrice > salePrice"
                        class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">Save
                        ৳{{
                            (originalPrice - salePrice).toLocaleString()
                        }}</span>
                </div>

                <div class="border rounded-xl border-gray-100 p-3 dark:border-gray-800">
                    <div class="flex items-center justify-between gap-3 ">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-500 dark:text-gray-400">SKU:</span>
                            <span
                                class="rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">{{
                                    selectedVariant?.sku || product.sku || '—' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span v-if="!hasOptions || selectedVariant"
                                class="inline-flex items-center gap-1.5 font-medium" :class="isInStock
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-red-500 dark:text-red-400'
                                    ">
                                <span class="h-2 w-2 rounded-full" :class="isInStock
                                        ? 'bg-green-500 dark:bg-green-400'
                                        : 'bg-red-500 dark:bg-red-400'
                                    "></span>
                                {{ isInStock ? 'In Stock' : 'Out of Stock' }}
                            </span>
                            <span v-else class="italic text-gray-400 dark:text-gray-500">Select options to check
                                stock</span>
                        </div>
                    </div>

                    <button type="button" @click="toggleWishlist"
                        :aria-label="isWished ? 'Remove from wishlist' : 'Add to wishlist'"
                        :title="isWished ? 'Remove from wishlist' : 'Add to wishlist'"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 transition-colors dark:border-gray-700"
                        :class="isWished
                                ? 'border-red-200 bg-red-50 text-red-500 dark:border-red-900 dark:bg-red-950'
                                : 'text-gray-400 hover:border-red-300 hover:text-red-500 dark:text-gray-400 dark:hover:border-red-700 dark:hover:text-red-400'
                            ">
                        <svg class="h-4 w-4" :fill="isWished ? 'currentColor' : 'none'" stroke="currentColor"
                            stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </button>
                    </div>

                    <div>
                        <Link v-if="category" :href="route(
                            'category.show',
                            category.slug || category.id,
                        )
                            " class="inline-flex gap-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:underline">
                            <svg class="h-3.5 w-3.5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 6A2.25 2.25 0 016 3.75h2.25c.597 0 1.17.237 1.591.659l1.5 1.5c.42.421.994.659 1.591.659H18A2.25 2.25 0 0120.25 8.25v9A2.25 2.25 0 0118 19.5H6a2.25 2.25 0 01-2.25-2.25V6z" />
                            </svg>
                            {{ category.name }}
                        </Link>
                    </div>
                </div>

                <div v-if="dimensions.length"
                    class="space-y-4 rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                    <div v-for="dim in dimensions" :key="dim.name" class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-800 capitalize dark:text-gray-200">
                                {{ isColorDimension(dim.name) ? 'Color' : dim.name }}
                            </p>
                            <span v-if="selectedOptions[dim.name]"
                                class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                {{ selectedOptions[dim.name] }}
                            </span>
                        </div>

                        <div v-if="isColorDimension(dim.name)" class="flex flex-wrap items-center gap-2">
                            <button v-for="value in dim.values" :key="value" type="button" :title="value"
                                :aria-label="value" @click="selectOption(dim.name, value)"
                                class="relative flex h-8 w-8 items-center justify-center rounded-full border transition-all duration-200"
                                :class="selectedOptions[dim.name] === value
                                        ? 'scale-105 border-blue-600 ring-2 ring-blue-600/40 ring-offset-1 ring-offset-white dark:ring-offset-gray-900'
                                        : 'border-gray-300 hover:scale-105 hover:border-blue-400 dark:border-gray-600 dark:hover:border-blue-400'
                                    " :style="{
                                    backgroundColor: colorHex(value),
                                    backgroundImage: colorHex(value).startsWith('linear') ? colorHex(value) : 'none',
                                    borderColor: isLightColor(value) ? '#e5e7eb' : undefined,
                                }">
                                <svg v-if="selectedOptions[dim.name] === value" class="h-3.5 w-3.5 drop-shadow"
                                    :class="isLightColor(value) ? 'text-gray-700' : 'text-white'" fill="none"
                                    stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </button>
                        </div>

                        <div v-else class="flex flex-wrap gap-1.5">
                            <button v-for="value in dim.values" :key="value" type="button"
                                @click="selectOption(dim.name, value)"
                                class="rounded-md border px-2 py-1 text-xs font-medium capitalize transition-all duration-150"
                                :class="selectedOptions[dim.name] === value
                                        ? 'border-blue-600 bg-blue-600 text-white shadow-sm'
                                        : 'border-gray-300 bg-white text-gray-700 hover:border-blue-400 hover:text-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-blue-300'
                                    ">
                                {{ value }}
                            </button>
                        </div>
                    </div>
                </div>

                <section v-if="product.description" class="pt-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                            Description
                        </h2>
                        <div class="h-px flex-1 bg-gray-100 dark:bg-gray-800"></div>
                    </div>

                    <div
                        class="mt-3 rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 sm:p-5">
                        <div class="rich-text-content text-sm leading-relaxed text-gray-600 dark:text-gray-400" :class="{
                            'line-clamp-4': descriptionIsLong && !descriptionExpanded,
                        }" v-html="renderContent(product.description)"></div>

                        <button v-if="descriptionIsLong" type="button"
                            @click="descriptionExpanded = !descriptionExpanded"
                            class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 transition hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                            {{ descriptionExpanded ? 'Show less' : 'Show more' }}
                            <svg class="h-4 w-4 transition-transform duration-200"
                                :class="{ 'rotate-180': descriptionExpanded }" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </section>
            </div>
        </div>

        <div v-if="product.specification" class="mt-10">
            <h2 class="mb-3 text-lg font-bold text-gray-800 dark:text-white sm:text-xl">
                Specification
            </h2>
            <div class="rich-text-content rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 sm:p-6"
                v-html="renderContent(product.specification)"></div>
        </div>

        <section v-if="related && related.length" class="mt-12">
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white md:text-2xl">
                        Related Products
                    </h2>
                    <div
                        class="mt-2 h-1.5 w-16 rounded-full bg-gradient-to-r from-[#D9531E] via-amber-400 to-emerald-400">
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-6">
                <HomeProductCard v-for="(rel, i) in related" :key="rel.id" :product="rel" :index="i" />
            </div>
        </section>
    </FrontEndMaster>
</template>