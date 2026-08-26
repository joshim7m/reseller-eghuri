<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue';
import HomeProductCard from '@/Components/StoreFront/HomeProductCard.vue';
import { useWishlist } from '@/composables/useWishlist';

const page = usePage();

function renderContent(html) {
    if (!html) return '';
    if (/<[a-z][\s\S]*>/i.test(html)) return html;
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
const selectedSize = ref('');
const selectedColor = ref('');
const images = computed(() => {
    return (props.product.images || []).map(
        (img) => img.image_url || img.image_path || img.url,
    );
});

const variants = computed(() => props.product.variants || []);

const uniqueSizes = computed(() => {
    return [...new Set(variants.value.map((v) => v.size).filter(Boolean))];
});

const uniqueColors = computed(() => {
    return [...new Set(variants.value.map((v) => v.color).filter(Boolean))];
});

const selectedVariant = computed(() => {
    return variants.value.find(
        (v) =>
            (v.size === selectedSize.value ||
                (!v.size && !selectedSize.value)) &&
            (v.color === selectedColor.value ||
                (!v.color && !selectedColor.value)),
    );
});

const availableQty = computed(() => {
    return selectedVariant.value ? selectedVariant.value.quantity : 0;
});

const salePrice = computed(() => {
    if (selectedVariant.value?.sale_price)
        return Number(selectedVariant.value.sale_price);
    return Number(props.product.sale_price || props.product.unit_price || 0);
});

const originalPrice = computed(() => {
    if (selectedVariant.value?.unit_price)
        return Number(selectedVariant.value.unit_price);
    return Number(
        props.product.unit_price || props.product.original_price || 0,
    );
});

function prevImage() {
    activeImage.value = activeImage.value > 0 ? activeImage.value - 1 : 0;
}

function nextImage() {
    activeImage.value =
        activeImage.value < images.value.length - 1
            ? activeImage.value + 1
            : images.value.length - 1;
}

function selectSize(size) {
    selectedSize.value = size;
}

function selectColor(color) {
    selectedColor.value = color;
}

watch(selectedVariant, (variant) => {
    if (variant?.image?.image_url || variant?.image?.image_path) {
        const url = variant.image.image_url || variant.image.image_path;
        const idx = images.value.indexOf(url);
        if (idx !== -1) activeImage.value = idx;
    }
});

const wishlist = useWishlist();
const isWished = computed(() => wishlist.has(props.product.id));

function toggleWishlist() {
    wishlist.toggle(props.product.id);
}

const hasOptions = computed(
    () => uniqueSizes.value.length + uniqueColors.value.length > 0,
);

const whatsappUrl = computed(() => {
    const number = page.props.settings?.whatsapp_number || '';
    const lines = [`Hi! I'm interested in: ${props.product.title}`];
    if (selectedSize.value) lines.push(`Size: ${selectedSize.value}`);
    if (selectedColor.value) lines.push(`Color: ${selectedColor.value}`);
    lines.push(`Price: ৳${salePrice.value.toLocaleString()}`);
    const currentImage = images.value[activeImage.value];
    if (currentImage) lines.push(`Image: ${currentImage}`);
    return `https://wa.me/${number}?text=${encodeURIComponent(lines.join('\n'))}`;
});
</script>

<template>
    <Head :title="product.name || product.title || 'Product'" />

    <FrontEndMaster>
        <nav
            class="mb-4 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
        >
            <Link
                :href="route('home')"
                class="transition hover:text-blue-600 dark:hover:text-blue-400"
                >Home</Link
            >
            <svg
                class="h-3 w-3 text-gray-300 dark:text-gray-600"
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
            <Link
                v-if="product.category"
                :href="
                    route(
                        'category.show',
                        product.category.slug || product.category.id,
                    )
                "
                class="transition hover:text-blue-600 dark:hover:text-blue-400"
                >{{ product.category.name }}</Link
            >
            <svg
                v-if="product.category"
                class="h-3 w-3 text-gray-300 dark:text-gray-600"
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
            <span class="font-medium text-gray-800 dark:text-gray-200">{{
                product.title
            }}</span>
        </nav>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
            <div class="space-y-3">
                <div
                    class="group relative flex aspect-square items-center justify-center overflow-hidden rounded-xl bg-gray-100 text-sm text-gray-400 dark:bg-gray-800 dark:text-gray-500"
                >
                    <img
                        v-for="(img, i) in images"
                        :key="i"
                        :src="img"
                        :class="{
                            'opacity-100': activeImage === i,
                            'opacity-0': activeImage !== i,
                        }"
                        class="absolute inset-0 h-full w-full object-cover transition-opacity duration-300"
                    />
                    <span v-if="!images.length">No Image</span>
                    <button
                        v-if="images.length > 1"
                        @click="prevImage"
                        class="absolute left-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 opacity-0 shadow-sm transition hover:bg-white group-hover:opacity-100 dark:bg-gray-900/80 dark:text-gray-300 dark:hover:bg-gray-900"
                    >
                        &lsaquo;
                    </button>
                    <button
                        v-if="images.length > 1"
                        @click="nextImage"
                        class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-gray-700 opacity-0 shadow-sm transition hover:bg-white group-hover:opacity-100 dark:bg-gray-900/80 dark:text-gray-300 dark:hover:bg-gray-900"
                    >
                        &rsaquo;
                    </button>
                    <div
                        v-if="images.length > 1"
                        class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5"
                    >
                        <button
                            v-for="(img, i) in images"
                            :key="i"
                            @click="activeImage = i"
                            class="h-2 w-2 rounded-full transition"
                            :class="
                                activeImage === i
                                    ? 'bg-white shadow-sm dark:bg-white'
                                    : 'bg-white/40 dark:bg-white/40'
                            "
                        ></button>
                    </div>
                </div>
                <div
                    v-if="images.length > 1"
                    class="flex gap-2 overflow-x-auto pb-1"
                >
                    <button
                        v-for="(img, i) in images"
                        :key="i"
                        @click="activeImage = i"
                        class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2 transition"
                        :class="{
                            'border-blue-600 dark:border-blue-400':
                                activeImage === i,
                            'border-transparent': activeImage !== i,
                        }"
                    >
                        <img
                            :src="img"
                            alt=""
                            class="h-full w-full object-cover"
                        />
                    </button>
                </div>
            </div>

            <div class="space-y-5 border-l border-gray-200 pl-8 dark:border-gray-700">
                <h1
                    class="text-xl font-bold text-gray-800 dark:text-white md:text-2xl"
                >
                    {{ product.title }}
                </h1>

                <div class="flex items-center gap-3">
                    <span
                        class="text-xl font-bold text-blue-600 dark:text-blue-400"
                        >৳{{ salePrice.toLocaleString() }}</span
                    >
                    <span
                        class="text-base text-gray-400 line-through dark:text-gray-500"
                        >৳{{ originalPrice.toLocaleString() }}</span
                    >
                    <span
                        v-if="originalPrice > salePrice"
                        class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-600 dark:bg-green-900/30 dark:text-green-400"
                        >Save ৳{{
                            (originalPrice - salePrice).toLocaleString()
                        }}</span
                    >
                </div>
                <div
                    class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm"
                >
                    <div class="flex items-center gap-1.5">
                        <svg
                            class="h-4 w-4 text-gray-400 dark:text-gray-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                            />
                        </svg>
                        <span class="text-gray-500 dark:text-gray-400"
                            >SKU:</span
                        >
                        <span
                            class="font-mono font-medium text-gray-800 dark:text-gray-200"
                            >{{
                                selectedVariant?.sku || product.sku || '—'
                            }}</span
                        >
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span
                            v-if="!hasOptions || selectedVariant"
                            class="inline-flex items-center gap-1"
                            :class="
                                (selectedVariant
                                    ? availableQty
                                    : product.quantity) > 0
                                    ? 'text-green-600 dark:text-green-400'
                                    : 'text-red-500 dark:text-red-400'
                            "
                        >
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="
                                    (selectedVariant
                                        ? availableQty
                                        : product.quantity) > 0
                                        ? 'bg-green-500 dark:bg-green-400'
                                        : 'bg-red-500 dark:bg-red-400'
                                "
                            ></span>
                            {{
                                (selectedVariant
                                    ? availableQty
                                    : product.quantity) > 0
                                    ? 'In Stock'
                                    : 'Out of Stock'
                            }}
                        </span>
                        <span
                            v-else
                            class="italic text-gray-400 dark:text-gray-500"
                            >Select options to check stock</span
                        >
                    </div>
                </div>

                <div
                    v-if="product.description"
                    class="rich-text-content text-sm text-gray-600 dark:text-gray-400"
                    v-html="renderContent(product.description)"
                ></div>

                <div v-if="uniqueSizes.length">
                    <p
                        class="mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Size
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="size in uniqueSizes"
                            :key="size"
                            @click="selectSize(size)"
                            class="rounded-lg border px-4 py-1.5 text-sm transition"
                            :class="
                                selectedSize === size
                                    ? 'border-blue-600 bg-blue-600 text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-blue-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-blue-400'
                            "
                        >
                            {{ size }}
                        </button>
                    </div>
                </div>

                <div v-if="uniqueColors.length">
                    <p
                        class="mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Color
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="color in uniqueColors"
                            :key="color"
                            @click="selectColor(color)"
                            class="rounded-lg border px-4 py-1.5 text-sm transition"
                            :class="
                                selectedColor === color
                                    ? 'border-blue-600 bg-blue-600 text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-blue-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-blue-400'
                            "
                        >
                            {{ color }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-[#25D366] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1da851]"
                        :class="
                            hasOptions && !selectedVariant
                                ? 'pointer-events-none cursor-not-allowed opacity-40'
                                : ''
                        "
                        :disabled="hasOptions && !selectedVariant"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        Order on WhatsApp
                    </a>
                    <button
                        type="button"
                        @click="toggleWishlist"
                        :aria-label="isWished ? 'Remove from wishlist' : 'Add to wishlist'"
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border transition-all duration-200"
                        :class="
                            isWished
                                ? 'border-red-200 bg-red-50 text-red-500 dark:border-red-500/40 dark:bg-red-500/10'
                                : 'border-gray-300 bg-white text-gray-400 hover:border-red-200 hover:text-red-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-500 dark:hover:border-red-500/40 dark:hover:text-red-400'
                        "
                    >
                        <svg class="h-5 w-5" :fill="isWished ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="product.specification" class="mt-10">
            <h2
                class="mb-3 text-lg font-bold text-gray-800 dark:text-white sm:text-xl"
            >
                Specification
            </h2>
            <div
                class="rich-text-content rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 sm:p-6"
                v-html="renderContent(product.specification)"
            ></div>
        </div>

        <section v-if="related && related.length" class="mt-12">
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <h2
                        class="text-xl font-bold text-gray-800 dark:text-white md:text-2xl"
                    >
                        Related Products
                    </h2>
                    <div
                        class="mt-2 h-1.5 w-16 rounded-full bg-gradient-to-r from-[#D9531E] via-amber-400 to-emerald-400"
                    ></div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 md:gap-5 lg:grid-cols-6">
                <HomeProductCard
                    v-for="(rel, i) in related"
                    :key="rel.id"
                    :product="rel"
                    :index="i"
                />
            </div>
        </section>
    </FrontEndMaster>
</template>
