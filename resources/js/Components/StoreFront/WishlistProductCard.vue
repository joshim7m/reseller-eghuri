<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useWishlist } from '@/composables/useWishlist';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const wishlist = useWishlist();

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

function remove() {
    wishlist.toggle(props.product.id);
}
</script>

<template>
    <div class="group flex gap-4 rounded-xl border border-surface-container-high/50 bg-white p-3 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/5 dark:border-[#3a302e] dark:bg-[#171212] dark:hover:shadow-black/20">
        <Link :href="productUrl" class="relative h-40 w-48 flex-shrink-0 overflow-hidden rounded-lg bg-surface-container dark:bg-[#241d1c]">
            <img
                v-if="coverImage"
                :src="coverImage"
                :alt="product.title"
                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                loading="lazy"
            />
            <div v-else class="flex h-full items-center justify-center">
                <span class="material-symbols-outlined text-3xl text-outline-variant dark:text-[#3a302e]" aria-hidden="true">image</span>
            </div>
        </Link>

        <div class="flex flex-1 flex-col justify-between py-1">
            <div>
                <Link :href="productUrl" class="line-clamp-2 text-sm font-bold leading-snug text-charcoal dark:text-[#f9eeed] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors">
                    {{ product.title }}
                </Link>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-sm font-bold text-sale-price">
                        ৳{{ salePrice.toLocaleString() }}
                    </span>
                    <span
                        v-if="hasDiscount"
                        class="text-[11px] font-medium text-outline line-through"
                    >৳{{ originalPrice.toLocaleString() }}</span>
                </div>
                <button
                    type="button"
                    @click.prevent="remove"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-outline-variant/50 text-outline transition hover:border-sale-price hover:bg-sale-price/5 hover:text-sale-price dark:border-[#3a302e] dark:hover:border-sale-price dark:hover:text-sale-price"
                    aria-label="Remove from wishlist"
                >
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">close</span>
                </button>
            </div>
        </div>
    </div>
</template>
