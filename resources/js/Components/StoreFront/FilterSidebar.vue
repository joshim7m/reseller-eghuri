<script setup>
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({})
    }
})

const categories = computed(() => props.filters.categories || [])
const sizes = computed(() => props.filters.sizes || [])
const colors = computed(() => props.filters.colors || [])
const prices = computed(() => props.filters.prices || null)

const urlParams = computed(() => new URL(window.location.href).searchParams)

const selectedCats = computed(() => urlParams.value.getAll('categories'))
const selectedSizes = computed(() => urlParams.value.getAll('sizes'))
const selectedColors = computed(() => urlParams.value.getAll('colors'))
const minPriceParam = computed(() => urlParams.value.get('min_price'))
const maxPriceParam = computed(() => urlParams.value.get('max_price'))
const qParam = computed(() => urlParams.value.get('q'))

const minPrice = ref(Number(minPriceParam.value) || 200)
const maxPrice = ref(Number(maxPriceParam.value) || 2000)

const activeFilterCount = computed(() => {
    let count = 0
    count += selectedCats.value.length
    count += selectedSizes.value.length
    count += selectedColors.value.length
    if (minPriceParam.value) count++
    if (maxPriceParam.value) count++
    return count
})

function submitForm() {
    const params = new URLSearchParams()
    if (qParam.value) params.set('q', qParam.value)
    for (const cat of selectedCats.value) params.append('categories', cat)
    for (const size of selectedSizes.value) params.append('sizes', size)
    for (const color of selectedColors.value) params.append('colors', color)
    if (Number(minPrice.value) > 200) params.set('min_price', minPrice.value)
    if (Number(maxPrice.value) < 2000) params.set('max_price', maxPrice.value)
    const qs = params.toString()
    router.get(window.location.pathname + (qs ? '?' + qs : ''), {}, { preserveScroll: true, preserveState: true })
}

function clearFilters() {
    router.get(window.location.pathname, {}, { preserveScroll: true, preserveState: true })
}

function toggleCategory(id) {
    const idx = selectedCats.value.indexOf(String(id))
    if (idx > -1) selectedCats.value.splice(idx, 1)
    else selectedCats.value.push(String(id))
    submitForm()
}

function toggleSize(size) {
    const idx = selectedSizes.value.indexOf(size)
    if (idx > -1) selectedSizes.value.splice(idx, 1)
    else selectedSizes.value.push(size)
    submitForm()
}

function toggleColor(color) {
    const idx = selectedColors.value.indexOf(color)
    if (idx > -1) selectedColors.value.splice(idx, 1)
    else selectedColors.value.push(color)
    submitForm()
}

function applyPrice() { submitForm() }

const colorHex = (color) => {
    const map = {
        red: '#ef4444', black: '#1a1a1a', white: '#ffffff', blue: '#3b82f6',
        green: '#22c55e', pink: '#ec4899', purple: '#a855f7', grey: '#6b7280',
        gray: '#6b7280', navy: '#1e3a5f', brown: '#8b4513', beige: '#f5f5dc',
        champagne: '#f7e7ce', 'dusty pink': '#d8a7b0', 'army green': '#4b5320',
        olive: '#808000', 'hot pink': '#ff69b4', bronze: '#cd7f32',
        'multi colour': '#d1d5db', multicolor: '#d1d5db', multicolour: '#d1d5db',
        multi: '#d1d5db', 'baby pink': '#f4c2c2', 'mint blue': '#a2d8d8',
    }
    return map[color.toLowerCase()] || '#d1d5db'
}
</script>

<template>
    <div class="flex flex-col gap-8">
        <div v-if="activeFilterCount" class="flex items-center justify-between">
            <span class="text-xs font-bold text-primary dark:text-[#f6b7b2]">{{ activeFilterCount }} active</span>
            <button @click="clearFilters" class="text-xs text-on-surface-variant dark:text-[#cbb8b6] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors">Clear all</button>
        </div>

        <div v-if="categories.length">
            <h3 class="text-xs font-bold uppercase tracking-widest text-secondary dark:text-[#cbb8b6] mb-6">Categories</h3>
            <nav class="flex flex-col gap-4">
                <Link
                    v-for="cat in categories"
                    :key="cat.id"
                    :href="route('category.show', cat.slug || cat.id)"
                    class="text-sm transition-colors"
                    :class="$page.url.includes(cat.slug) ? 'font-bold text-primary dark:text-[#f6b7b2]' : 'text-on-surface-variant dark:text-[#cbb8b6] hover:text-primary dark:hover:text-[#f6b7b2]'"
                >{{ cat.name }}</Link>
            </nav>
        </div>

        <div v-if="colors.length || sizes.length || prices" class="border-t border-outline-variant dark:border-[#3a302e] pt-8">
            <h3 class="text-xs font-bold uppercase tracking-widest text-secondary dark:text-[#cbb8b6] mb-6">Filters</h3>
            <div class="flex flex-col gap-6">
                <div v-if="colors.length">
                    <p class="text-xs font-bold uppercase tracking-widest text-on-surface dark:text-[#f9eeed] mb-3">Color</p>
                    <div class="flex flex-wrap gap-2">
                        <label v-for="color in colors" :key="color" class="cursor-pointer" :title="color">
                            <input type="checkbox" :checked="selectedColors.includes(color)" @change="toggleColor(color)" class="sr-only peer">
                            <span class="block w-6 h-6 rounded-full border border-outline-variant dark:border-[#3a302e] transition-all duration-200 peer-checked:ring-2 peer-checked:ring-primary dark:peer-checked:ring-[#f6b7b2] peer-checked:ring-offset-2 dark:peer-checked:ring-offset-[#171212]"
                                :style="{ backgroundColor: colorHex(color), borderColor: ['white','beige','champagne'].includes(color.toLowerCase()) ? '#e5e7eb' : undefined }"></span>
                        </label>
                    </div>
                </div>

                <div v-if="sizes.length">
                    <p class="text-xs font-bold uppercase tracking-widest text-on-surface dark:text-[#f9eeed] mb-3">Size</p>
                    <div class="grid grid-cols-4 gap-2">
                        <label v-for="size in sizes" :key="size" class="cursor-pointer">
                            <input type="checkbox" :checked="selectedSizes.includes(size)" @change="toggleSize(size)" class="sr-only peer">
                            <span class="flex items-center justify-center py-2 border text-[10px] font-medium transition-all border-outline-variant dark:border-[#3a302e] text-on-surface-variant dark:text-[#cbb8b6] peer-checked:bg-on-surface dark:peer-checked:bg-[#f9eeed] peer-checked:text-surface dark:peer-checked:text-charcoal peer-checked:border-on-surface dark:peer-checked:border-[#f9eeed] hover:border-primary dark:hover:border-[#f6b7b2]">{{ size }}</span>
                        </label>
                    </div>
                </div>

                <div v-if="prices">
                    <p class="text-xs font-bold uppercase tracking-widest text-on-surface dark:text-[#f9eeed] mb-3">Price Range</p>
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <input v-model.number="minPrice" type="number" :min="prices.min_price || 0" :max="maxPrice"
                                class="w-full border border-outline-variant dark:border-[#3a302e] rounded-lg px-3 py-2 text-sm text-charcoal dark:text-[#f9eeed] bg-white dark:bg-[#241d1c] focus:outline-none focus:border-primary transition-all">
                        </div>
                        <span class="text-outline dark:text-[#3a302e] text-sm">&mdash;</span>
                        <div class="flex-1">
                            <input v-model.number="maxPrice" type="number" :min="minPrice" :max="prices.max_price || 100000"
                                class="w-full border border-outline-variant dark:border-[#3a302e] rounded-lg px-3 py-2 text-sm text-charcoal dark:text-[#f9eeed] bg-white dark:bg-[#241d1c] focus:outline-none focus:border-primary transition-all">
                        </div>
                    </div>
                    <button @click="applyPrice" class="mt-3 w-full text-xs font-semibold bg-charcoal dark:bg-[#f9eeed] text-white dark:text-charcoal py-2.5 rounded-lg hover:bg-primary dark:hover:bg-[#f6b7b2] transition-all">Apply Price</button>
                </div>
            </div>
        </div>
    </div>
</template>
