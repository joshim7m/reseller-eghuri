<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, onUnmounted, ref, watch } from 'vue'
import { useCart } from '@/composables/useCart'
import { useQuickView } from '@/composables/useQuickView'
import { dimensionNames, dimensionValues, findVariantByOptions, variantOptions, isColorDimension } from '@/composables/useVariantOptions'

const { open, product, close } = useQuickView()
const cart = useCart()

const page = usePage()
const isGuest = computed(() => !page.props.auth?.user)

// ---------- images ----------
const images = computed(() => (product.value?.images || []).filter(Boolean))

const imageUrl = (img) => {
    if (!img) {
        return null
    }

    if (typeof img === 'string') {
        return img
    }

    return img.image_url || img.image_path || img.url || img.src || null
}

const totalSlides = computed(() => Math.max(1, images.value.length))

const current = ref(0)
const touchX = ref(null)

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

// ---------- variants ----------
const variants = computed(() => product.value?.variants || [])

const dimensions = computed(() =>
    dimensionNames(variants.value).map((name) => ({
        name,
        values: dimensionValues(variants.value, name),
    })),
)

const selectedOptions = ref({})

const selectedVariant = computed(
    () =>
        findVariantByOptions(variants.value, selectedOptions.value)
        || variants.value[0] || null,
)

const variantName = computed(() => {
    const opts = variantOptions(selectedVariant.value || {})

    return opts.map(o => o.value).join(' / ') || null
})

const variantImageIndex = computed(() => {
    const v = selectedVariant.value

    if (!v?.product_image_id) {
        return null
    }

    const idx = images.value.findIndex(img => {
        if (typeof img === 'string') {
            return false
        }

        return Number(img.id) === Number(v.product_image_id)
    })

    return idx >= 0 ? idx : null
})

watch(variantImageIndex, (idx) => {
    if (idx !== null && idx !== current.value) {
        current.value = idx
    }
})

// ---------- price / stock ----------
const salePrice = computed(() =>
    Number(selectedVariant.value?.sale_price ?? product.value?.sale_price ?? product.value?.unit_price ?? 0)
)

const originalPrice = computed(() =>
    Number(selectedVariant.value?.unit_price ?? product.value?.unit_price ?? product.value?.original_price ?? 0)
)

const hasDiscount = computed(() => originalPrice.value > salePrice.value && originalPrice.value > 0)

const discountPct = computed(() =>
    hasDiscount.value ? Math.round((1 - salePrice.value / originalPrice.value) * 100) : 0
)

const stock = computed(() =>
    Number(selectedVariant.value?.quantity ?? product.value?.total_stock ?? product.value?.quantity ?? 0)
)

const outOfStock = computed(() => stock.value <= 0)

// ---------- quantity ----------
const qty = ref(1)

function changeQty(delta) {
    const max = Math.max(1, stock.value || 99)
    qty.value = Math.min(max, Math.max(1, qty.value + delta))
}

function selectOption(name, value) {
    selectedOptions.value = { ...selectedOptions.value, [name]: value }
}

// ---------- add to cart ----------
const added = ref(false)
let addedTimer = null

function addToCart() {
    if (outOfStock.value) {
        return
    }

    cart.addItem({
        product_id: product.value.id,
        name: product.value.title,
        sku: selectedVariant.value?.sku || product.value.sku,
        image: imageUrl(images.value[variantImageIndex.value ?? 0]),
        price: salePrice.value,
        qty: qty.value,
        variant_id: selectedVariant.value?.id ?? null,
        options: variantOptions(selectedVariant.value || {}),
        variant_name: variantName.value,
    })
    added.value = true
    clearTimeout(addedTimer)
    addedTimer = setTimeout(() => {
        added.value = false
    }, 1800)
}

// ---------- rich text ----------
const descriptionHtml = computed(() => {
    const raw = product.value?.description || ''

    if (!raw) {
        return ''
    }

    if (/<[a-z][\s\S]*>/i.test(raw)) {
        return raw
    }

    return raw.split(/\n+/).map(p => `<p>${p.replace(/</g, '&lt;')}</p>`).join('')
})

// ---------- colors ----------
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

// ---------- lifecycle ----------
watch(open, (val) => {
    if (val) {
        current.value = 0
        qty.value = 1
        selectedOptions.value = Object.fromEntries(
            dimensions.value.map(d => [d.name, d.values[0] ?? null]),
        )
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})

function onKeydown(e) {
    if (e.key === 'Escape' && open.value) {
        close()
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown))

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})

onBeforeUnmount(() => clearTimeout(addedTimer))
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-[60] overflow-y-auto">
                <div class="fixed inset-0 bg-black/60" @click="close" />

                <div class="relative min-h-full flex items-center justify-center p-2 sm:p-6" @click.self="close">
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[92vh] lg:h-[600px] lg:max-h-[92vh]">
                        <div class="flex items-center justify-between px-4 sm:px-6 h-12 sm:h-14 border-b border-gray-200 dark:border-gray-700 shrink-0">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Quick View</h3>
                            <button type="button" @click="close" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition" aria-label="Close quick view">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="flex-1 min-h-0 flex flex-col md:flex-row overflow-hidden">
                            <!-- images -->
                            <div class="relative shrink-0 w-full md:w-[42%] lg:w-[40%] bg-gray-100 dark:bg-gray-800 md:border-r border-gray-200 dark:border-gray-700">
                                <div class="relative aspect-square md:h-full md:aspect-auto overflow-hidden">
                                    <div
                                        class="h-full w-full flex transition-transform duration-500 ease-out"
                                        :style="{ transform: `translateX(-${current * 100}%)` }"
                                        @touchstart.passive="onTouchStart"
                                        @touchend="onTouchEnd"
                                    >
                                        <template v-if="images.length">
                                            <div v-for="(img, i) in images" :key="i" class="h-full w-full shrink-0 flex items-center justify-center">
                                                <img :src="imageUrl(img)" :alt="product?.title" class="w-full h-full object-cover" loading="lazy" draggable="false">
                                            </div>
                                        </template>
                                        <div v-else class="h-full w-full shrink-0 flex items-center justify-center text-gray-300 dark:text-gray-600">
                                            <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                                <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    </div>

                                    <button
                                        v-if="images.length > 1"
                                        type="button"
                                        @click="go(current - 1)"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white/90 hover:bg-white text-gray-700 shadow flex items-center justify-center transition"
                                        aria-label="Previous image"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <button
                                        v-if="images.length > 1"
                                        type="button"
                                        @click="go(current + 1)"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-8 h-8 rounded-full bg-white/90 hover:bg-white text-gray-700 shadow flex items-center justify-center transition"
                                        aria-label="Next image"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </button>

                                    <div v-if="images.length > 1" class="absolute bottom-2 inset-x-0 z-10 flex items-center justify-center gap-1.5">
                                        <button
                                            v-for="(img, i) in images" :key="i"
                                            type="button"
                                            @click="go(i)"
                                            :class="i === current ? 'w-5 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'"
                                            class="h-1.5 rounded-full transition-all duration-300"
                                            :aria-label="`Go to image ${i + 1}`"
                                        ></button>
                                    </div>

                                    <span v-if="hasDiscount" class="absolute top-2 left-2 z-10 bg-[#D9531E] text-white text-xs font-bold px-2 py-0.5 rounded">-{{ discountPct }}%</span>
                                    <span v-if="outOfStock" class="absolute top-2 left-2 z-10 bg-gray-900/80 text-white text-xs font-semibold px-2 py-0.5 rounded">OUT OF STOCK</span>
                                </div>
                            </div>

                            <!-- details -->
                            <div class="flex-1 min-w-0 min-h-0 flex flex-col p-4 sm:p-6 lg:px-8">
                                <div class="flex-1 min-h-0 overflow-y-auto pr-1 qv-scroll">
                                    <Link :href="route('product.show', product?.slug || product?.id)" @click="close">
                                        <h2 class="text-base sm:text-lg lg:text-xl font-bold text-[#0B132A] dark:text-white leading-snug line-clamp-2 hover:text-[#D9531E] dark:hover:text-orange-400 transition-colors">{{ product?.title }}</h2>
                                    </Link>

                                    <div v-if="selectedVariant?.sku || product?.sku" class="mt-1 text-xs text-gray-400 dark:text-gray-500">SKU: {{ selectedVariant?.sku || product?.sku }}</div>

                                    <div v-if="!isGuest" class="mt-3">
                                        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 tracking-wider uppercase">Wholesale Price</span>
                                        <div class="flex items-baseline gap-2 mt-0.5">
                                            <span class="text-xl sm:text-2xl font-extrabold text-[#0B132A] dark:text-white tracking-tight">৳{{ salePrice.toLocaleString() }}</span>
                                            <span v-if="hasDiscount" class="text-sm text-red-300 dark:text-red-500 line-through font-medium">৳{{ originalPrice.toLocaleString() }}</span>
                                        </div>
                                        <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 mt-1">
                                            {{ outOfStock ? 'Out of Stock' : 'In Stock' }}
                                            <span v-if="!outOfStock" class="text-[#D9531E] dark:text-orange-400">{{ stock }} units</span>
                                        </p>
                                    </div>
                                    <div v-else class="mt-3">
                                        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 tracking-wider uppercase">Wholesale Price</span>
                                        <Link :href="route('login')" @click="close" class="block mt-1 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">প্রাইস দেখতে লগইন করুন</Link>
                                    </div>

                                    <div v-if="dimensions.length" class="mt-4 space-y-3">
                                        <div v-for="dim in dimensions" :key="dim.name">
                                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 capitalize">{{ dim.name }}</span>
                                            <div v-if="isColorDimension(dim.name)" class="flex flex-wrap items-center gap-2 mt-1.5">
                                                <button
                                                    v-for="color in dim.values" :key="color"
                                                    type="button"
                                                    @click="selectOption(dim.name, color)"
                                                    :class="selectedOptions[dim.name] === color ? 'ring-2 ring-[#D9531E] dark:ring-orange-500 ring-offset-2 ring-offset-white dark:ring-offset-gray-900' : 'hover:ring-2 hover:ring-gray-300 dark:hover:ring-gray-500 ring-offset-2 ring-offset-white dark:ring-offset-gray-900'"
                                                    class="w-6 h-6 rounded-full border border-gray-300 dark:border-gray-600 transition"
                                                    :style="{ backgroundColor: colorHex(color), backgroundImage: colorHex(color).startsWith('linear') ? colorHex(color) : 'none' }"
                                                    :title="color"
                                                ></button>
                                            </div>
                                            <div v-else class="flex flex-wrap gap-2 mt-1.5">
                                                <button
                                                    v-for="value in dim.values" :key="value"
                                                    type="button"
                                                    @click="selectOption(dim.name, value)"
                                                    :class="selectedOptions[dim.name] === value ? 'bg-[#0B132A] dark:bg-gray-900 text-white border-[#D9531E] dark:border-orange-500' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-[#D9531E] dark:hover:border-orange-500'"
                                                    class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition"
                                                >{{ value }}</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="descriptionHtml" class="mt-4 rich-text-content text-gray-600 dark:text-gray-400 text-sm leading-relaxed" v-html="descriptionHtml"></div>
                                </div>

                                <div class="shrink-0 pt-4 mt-4 border-t border-gray-100 dark:border-gray-800 flex items-center gap-3">
                                    <div v-if="!isGuest && !outOfStock" class="flex items-center border border-gray-300 dark:border-gray-600 rounded-xl shrink-0">
                                        <button type="button" @click="changeQty(-1)" class="px-3 py-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition text-sm leading-none" aria-label="Decrease quantity">&minus;</button>
                                        <span class="px-3 py-2.5 text-sm font-semibold text-gray-900 dark:text-white min-w-[24px] text-center">{{ qty }}</span>
                                        <button type="button" @click="changeQty(1)" class="px-3 py-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition text-sm leading-none" aria-label="Increase quantity">+</button>
                                    </div>

                                    <button
                                        v-if="!isGuest"
                                        type="button"
                                        :disabled="outOfStock"
                                        @click="addToCart"
                                        class="flex-1 px-4 py-2.5 rounded-xl bg-[#0B132A] dark:bg-gray-900 text-white text-sm font-bold border border-[#D9531E] dark:border-orange-500 hover:bg-slate-800 dark:hover:bg-gray-800 transition active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="outOfStock">Out of Stock</span>
                                        <span v-else-if="added" class="inline-flex items-center gap-1.5 text-green-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            Added
                                        </span>
                                        <span v-else>Add to Order</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.qv-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(217, 83, 30, 0.5) transparent;
}
.qv-scroll::-webkit-scrollbar {
    width: 6px;
}
.qv-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.qv-scroll::-webkit-scrollbar-thumb {
    background: rgba(217, 83, 30, 0.5);
    border-radius: 9999px;
}
.qv-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(217, 83, 30, 0.75);
}
</style>
