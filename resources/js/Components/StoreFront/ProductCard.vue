<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'
import { useQuickView } from '@/composables/useQuickView'
import { useWishlist } from '@/composables/useWishlist'

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    hideBadges: {
        type: Boolean,
        default: false,
    },
    compact: {
        type: Boolean,
        default: false,
    },
})

const page = usePage()
const isGuest = computed(() => !page.props.auth?.user)

const wishlist = useWishlist()
const quickView = useQuickView()

const productUrl = computed(() => route('product.show', props.product.slug || props.product.id))

const salePrice = computed(() => Number(props.product.sale_price || props.product.unit_price || 0))
const originalPrice = computed(() => Number(props.product.unit_price || props.product.original_price || 0))
const hasDiscount = computed(() => originalPrice.value > salePrice.value && originalPrice.value > 0)
const discountPct = computed(() => hasDiscount.value ? Math.round((1 - salePrice.value / originalPrice.value) * 100) : 0)

const stock = computed(() => Number(props.product.total_stock ?? props.product.quantity ?? 0))
const outOfStock = computed(() => stock.value <= 0)

const merchantBadge = computed(() => props.product.merchant || props.product.division || null)

// ---------- image slider ----------
const images = computed(() => (props.product.images || []).filter(Boolean))
const imageUrl = (img) => {
    if (!img) {
        return null
    }

    if (typeof img === 'string') {
        return img
    }

    return img.image_url || img.image_path || img.url || img.src || null
}
const current = ref(0)
const touchX = ref(null)

const totalSlides = computed(() => Math.max(1, images.value.length))

function go(i) {
    current.value = (i + totalSlides.value) % totalSlides.value
}

function onTouchStart(e) {
    touchX.value = e.touches[0].clientX
}

function onTouchEnd(e) {
    if (touchX.value === null) {
        return
    }

    const dx = e.changedTouches[0].clientX - touchX.value

    if (Math.abs(dx) > 40) {
        if (dx < 0) {
            go(current.value + 1)
        } else {
            go(current.value - 1)
        }
    }

    touchX.value = null
}

watch(totalSlides, (n) => {
    if (current.value >= n) {
        current.value = 0
    }
})

onMounted(() => wishlist.load())

// ---------- wishlist ----------
const isWished = computed(() => wishlist.has(props.product.id))

function toggleWishlist() {
    wishlist.toggle(props.product.id)
}

// ---------- rich text ----------
const descriptionHtml = computed(() => {
    const raw = props.product.description || ''

    if (!raw) {
        return ''
    }

    if (/<[a-z][\s\S]*>/i.test(raw)) {
        return raw
    }

    return raw.split(/\n+/).map(p => `<p>${p.replace(/</g, '&lt;')}</p>`).join('')
})

const hasList = computed(() => descriptionHtml.value.includes('<li>'))

const descriptionParagraphs = computed(() => {
    if (!descriptionHtml.value) {
        return ''
    }

    if (!hasList.value) {
        return descriptionHtml.value
    }

    const doc = new DOMParser().parseFromString(descriptionHtml.value, 'text/html')
    doc.querySelectorAll('ul, ol').forEach(el => el.remove())

    return doc.body.innerHTML.trim()
})

const featureBullets = computed(() => {
    if (!hasList.value) {
        return []
    }

    const doc = new DOMParser().parseFromString(descriptionHtml.value, 'text/html')

    return [...doc.querySelectorAll('li')].map(li => li.textContent.trim()).filter(Boolean)
})

// ---------- colors ----------
const colors = computed(() => {
    if (props.product.variants) {
        return [...new Set(props.product.variants.map(v => v.color).filter(Boolean))]
    }

    return props.product.colors || []
})

const sizes = computed(() => {
    if (props.product.variants) {
        return [...new Set(props.product.variants.map(v => v.size).filter(Boolean))]
    }

    return props.product.sizes || []
})

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
    }

    return map[color.toLowerCase()] || '#d1d5db'
}
</script>

<template>
    <div class="group relative bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm hover:shadow-xl hover:shadow-orange-100/70 hover:border-orange-200 dark:hover:shadow-gray-900/50 dark:hover:border-gray-700 transition-all duration-300 flex flex-row items-stretch overflow-hidden" :class="compact ? 'h-[260px]' : 'h-[210px] sm:h-[250px] lg:h-[260px]'">
        <div class="absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-[#D9531E] via-amber-400 to-emerald-400 opacity-50"></div>
        <div
            class="relative shrink-0 w-1/3 aspect-square max-h-full self-center overflow-hidden bg-gradient-to-br from-orange-50 via-rose-50 to-indigo-100 dark:bg-gray-800"
        >
            <Link :href="productUrl" class="absolute inset-0 block">
                <div
                    class="h-full w-full flex transition-transform duration-500 ease-out"
                    :style="{ transform: `translateX(-${current * 100}%)` }"
                    @touchstart.passive="onTouchStart"
                    @touchend="onTouchEnd"
                >
                    <template v-if="images.length">
                        <div v-for="(img, i) in images" :key="i" class="h-full w-full shrink-0 flex items-center justify-center">
                            <img
                                :src="imageUrl(img)"
                                :alt="product.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                loading="lazy"
                                draggable="false"
                            >
                        </div>
                    </template>
                    <div v-else class="h-full w-full shrink-0 flex items-center justify-center text-orange-200 dark:text-gray-600">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                            <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </Link>

            <span v-if="merchantBadge && isGuest" class="absolute top-2 left-2 z-10 bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded shadow-sm">Merchant</span>
            <span v-if="!hideBadges && hasDiscount" class="absolute top-2 left-2 z-10 bg-gradient-to-r from-[#D9531E] to-rose-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm">-{{ discountPct }}%</span>
            <span v-if="outOfStock" class="absolute top-2 left-2 z-10 bg-gray-900/80 text-white text-[10px] font-semibold px-1.5 py-0.5 rounded shadow-sm">OUT OF STOCK</span>

            <button
                v-if="images.length > 1"
                type="button"
                @click.prevent="go(current - 1)"
                class="absolute left-1.5 top-1/2 -translate-y-1/2 z-10 w-7 h-7 rounded-full bg-white/90 hover:bg-white text-gray-700 shadow-sm flex items-center justify-center transition opacity-0 group-hover:opacity-100"
                aria-label="Previous image"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button
                v-if="images.length > 1"
                type="button"
                @click.prevent="go(current + 1)"
                class="absolute right-1.5 top-1/2 -translate-y-1/2 z-10 w-7 h-7 rounded-full bg-white/90 hover:bg-white text-gray-700 shadow-sm flex items-center justify-center transition opacity-0 group-hover:opacity-100"
                aria-label="Next image"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>

            <div v-if="images.length > 1" class="absolute bottom-1.5 inset-x-0 z-10 flex items-center justify-center gap-1">
                <button
                    v-for="(img, i) in images" :key="i"
                    type="button"
                    @click.prevent="go(i)"
                    :class="i === current ? 'w-4 bg-white' : 'w-1.5 bg-white/50 hover:bg-white/80'"
                    class="h-1.5 rounded-full transition-all duration-300"
                    :aria-label="`Go to image ${i + 1}`"
                ></button>
            </div>
        </div>

        <div class="flex-1 min-w-0 flex flex-col" :class="compact ? 'p-3 sm:p-3.5' : 'p-3.5 sm:p-3.5 lg:p-4'">
            <div class="flex-1 min-h-0 overflow-y-clip product-desc-scroll pr-1.5">
                <div class="flex items-start justify-between gap-2">
                    <Link :href="productUrl" class="min-w-0">
                        <h3 class="text-sm font-bold text-[#0B132A] dark:text-white leading-snug line-clamp-2 group-hover:text-[#D9531E] dark:group-hover:text-orange-400 transition-colors">{{ product.title }}</h3>
                    </Link>
                    <button
                        type="button"
                        @click="toggleWishlist"
                        :aria-label="isWished ? 'Remove from wishlist' : 'Add to wishlist'"
                        :class="isWished ? 'text-red-500 border-red-200 dark:border-red-500/40 bg-red-50 dark:bg-red-500/10' : 'text-gray-400 dark:text-gray-500 border-gray-200 dark:border-gray-700 hover:text-red-500 hover:border-red-200'"
                        class="shrink-0 w-8 h-8 rounded-full border flex items-center justify-center transition mt-0.5"
                    >
                        <svg class="w-4 h-4" :fill="isWished ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/>
                        </svg>
                    </button>
                </div>

                <p v-if="product.sku" class="text-[11px] font-medium text-gray-400 dark:text-gray-500 truncate">SKU: {{ product.sku }}</p>
                <div class="grid grid-cols-2 items-center gap-2">
                    <div v-if="colors.length" class="flex items-center gap-1 ">
                        <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Colors:</span>
                        <span
                            v-for="color in colors.slice(0, 3)" :key="color"
                            class="inline-block w-2.5 h-2.5 rounded-full border border-gray-300 dark:border-gray-600"
                            :style="{ backgroundColor: colorHex(color), backgroundImage: colorHex(color).startsWith('linear') ? colorHex(color) : 'none' }"
                            :title="color"
                        ></span>
                    </div>
                    <div v-if="sizes.length" class="flex items-center flex-wrap gap-1.5">
                        <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide">Sizes:</span>
                        <span
                            v-for="size in sizes" :key="size"
                            class="inline-flex items-center px-1.5 py-0.5 rounded-md border border-orange-200 dark:border-gray-700 bg-orange-50 dark:bg-gray-800 text-[10px] font-semibold text-orange-700 dark:text-gray-300"
                        >{{ size }}</span>
                    </div>
                </div>

                

                <div v-if="descriptionParagraphs" class="rich-text-content text-gray-600 dark:text-gray-400 text-xs leading-relaxed mt-1.5" v-html="descriptionParagraphs"></div>

                <ul v-if="featureBullets.length" class="space-y-1 mt-1.5">
                    <li v-for="(bullet, i) in featureBullets" :key="i" class="flex items-center gap-1.5 text-[11px] text-gray-700 dark:text-gray-300 font-medium">
                        <svg class="w-3.5 h-3.5 text-[#D9531E] dark:text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"></path>
                        </svg>
                        <span>{{ bullet }}</span>
                    </li>
                </ul>
            </div>

            <div class="shrink-0 flex items-end justify-between gap-3 pt-1 mt-3 border-t border-gray-100 dark:border-gray-800">
                <div v-if="!isGuest" class="min-w-0">
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-base font-extrabold text-[#D9531E] dark:text-orange-400 tracking-tight">৳{{ salePrice.toLocaleString() }}</span>
                        <span v-if="hasDiscount" class="text-[11px] text-red-300 dark:text-red-500 line-through font-medium">৳{{ originalPrice.toLocaleString() }}</span>
                    </div>
                    <p class="mt-1.5">
                        <span :class="outOfStock ? 'bg-red-50 text-red-600 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/30' : 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30'" class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full border">
                            <span class="w-1.5 h-1.5 rounded-full" :class="outOfStock ? 'bg-red-500' : 'bg-emerald-500'"></span>
                            {{ outOfStock ? 'Out of Stock' : 'In Stock' }}
                        </span>
                    </p>
                </div>
                <div v-else class="min-w-0">
                    <Link :href="route('login')" class="text-[11px] sm:text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">প্রাইস দেখতে লগইন করুন</Link>
                    <p class="mt-1.5">
                        <span :class="outOfStock ? 'bg-red-50 text-red-600 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/30' : 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/30'" class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full border">
                            <span class="w-1.5 h-1.5 rounded-full" :class="outOfStock ? 'bg-red-500' : 'bg-emerald-500'"></span>
                            {{ outOfStock ? 'Out of Stock' : 'In Stock' }}
                        </span>
                    </p>
                </div>

                <button
                    type="button"
                    @click="quickView.show(props.product)"
                    aria-label="Quick view"
                    title="Quick view"
                    class="shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-[#D9531E] to-orange-500 text-white shadow-md shadow-orange-200/70 dark:shadow-orange-900/30 hover:from-orange-500 hover:to-orange-400 hover:scale-105 flex items-center justify-center transition active:scale-90"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.product-desc-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(217, 83, 30, 0.4) transparent;
}
.product-desc-scroll::-webkit-scrollbar {
    width: 5px;
}
.product-desc-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.product-desc-scroll::-webkit-scrollbar-thumb {
    background: rgba(217, 83, 30, 0.4);
    border-radius: 9999px;
}
.product-desc-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(217, 83, 30, 0.6);
}
</style>
