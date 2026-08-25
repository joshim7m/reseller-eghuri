<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useWishlist } from '@/composables/useWishlist';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const isGuest = computed(() => !page.props.auth?.user);

const productUrl = computed(() =>
    route('product.show', props.product.slug || props.product.id),
);

const salePrice = computed(() =>
    Number(props.product.sale_price || props.product.unit_price || 0),
);
const originalPrice = computed(() =>
    Number(props.product.unit_price || props.product.original_price || 0),
);
const hasDiscount = computed(
    () => originalPrice.value > salePrice.value && originalPrice.value > 0,
);
const discountPct = computed(() =>
    hasDiscount.value
        ? Math.round((1 - salePrice.value / originalPrice.value) * 100)
        : 0,
);

const stock = computed(() =>
    Number(props.product.total_stock ?? props.product.quantity ?? 0),
);
const outOfStock = computed(() => stock.value <= 0);

const images = computed(() => (props.product.images || []).filter(Boolean));

const imageUrl = (img) => {
    if (!img) {
        return null;
    }

    if (typeof img === 'string') {
        return img;
    }

    return img.image_url || img.image_path || img.url || img.src || null;
};

const coverImage = computed(() => imageUrl(images.value[0]) || null);

const wishlist = useWishlist()
const isWished = computed(() => wishlist.has(props.product.id))
function toggleWishlist() { wishlist.toggle(props.product.id) }

const colors = computed(() => {
    if (props.product.variants) {
        return [
            ...new Set(
                props.product.variants.map((v) => v.color).filter(Boolean),
            ),
        ];
    }

    return props.product.colors || [];
});

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
        'multi colour':
            'linear-gradient(90deg, #ef4444, #3b82f6, #22c55e, #eab308)',
        multicolor:
            'linear-gradient(90deg, #ef4444, #3b82f6, #22c55e, #eab308)',
        multicolour:
            'linear-gradient(90deg, #ef4444, #3b82f6, #22c55e, #eab308)',
        multi: 'linear-gradient(90deg, #ef4444, #3b82f6, #22c55e, #eab308)',
        'baby pink': '#f4c2c2',
        'mint blue': '#a2d8d8',
    };

    return map[color.toLowerCase()] || '#d1d5db';
};
</script>

<template>
    <Link
        :href="productUrl"
        class="group relative flex flex-col rounded-xl border border-surface-container-high/50 bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/5 dark:border-[#3a302e] dark:bg-[#171212] dark:hover:shadow-black/20"
    >
        <!-- Image -->
        <div class="relative overflow-hidden rounded-t-xl bg-surface-container aspect-[16/14] dark:bg-[#241d1c]">
            <img
                v-if="coverImage"
                :src="coverImage"
                :alt="product.title"
                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                loading="lazy"
            />
            <div v-else class="flex h-full items-center justify-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant dark:text-[#3a302e]" aria-hidden="true">image</span>
            </div>

            <button
                v-if="!isGuest"
                type="button"
                @click.prevent="toggleWishlist"
                class="absolute right-2 top-2 z-10 w-8 h-8 rounded-full border flex items-center justify-center transition"
                :class="isWished ? 'text-red-500 border-red-200 bg-red-50 dark:border-red-500/40 dark:bg-red-500/10' : 'text-gray-400 border-gray-200 hover:text-red-500 hover:border-red-200 bg-white/80 dark:border-gray-700 dark:text-gray-500 dark:hover:text-red-400'"
                :aria-label="isWished ? 'Remove from wishlist' : 'Add to wishlist'"
            >
                <svg class="w-4 h-4" :fill="isWished ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                </svg>
            </button>
        </div>

        <!-- Meta -->
        <div class="flex flex-1 flex-col gap-2 p-3">
            <!-- Color swatches -->
            <div v-if="colors.length" class="flex items-center gap-1.5">
                <span
                    v-for="color in colors.slice(0, 5)"
                    :key="color"
                    class="inline-block h-5 w-5 rounded-full border border-outline-variant dark:border-[#3a302e]"
                    :style="{
                        backgroundColor: colorHex(color),
                        backgroundImage: colorHex(color).startsWith('linear')
                            ? colorHex(color)
                            : 'none',
                    }"
                    :title="color"
                ></span>
                <span
                    v-if="colors.length > 5"
                    class="text-[10px] font-medium text-outline dark:text-[#cbb8b6]"
                >+{{ colors.length - 5 }}</span>
            </div>

            <!-- Title -->
            <h3 class="line-clamp-2 text-sm font-bold leading-snug text-charcoal dark:text-[#f9eeed]">
                {{ product.title }}
            </h3>

            <!-- Price -->
            <div class="mt-auto">
                <div v-if="!isGuest" class="flex items-baseline gap-1.5">
                    <span class="text-sm font-bold text-sale-price">
                        ৳{{ salePrice.toLocaleString() }}
                    </span>
                    <span
                        v-if="hasDiscount"
                        class="text-[11px] font-medium text-outline line-through"
                    >৳{{ originalPrice.toLocaleString() }}</span>
                </div>
                <Link
                    v-else
                    :href="route('login')"
                    class="text-[11px] font-medium text-primary hover:underline dark:text-[#f6b7b2]"
                >প্রাইস দেখতে লগইন করুন</Link>

            </div>
        </div>
    </Link>
</template>
