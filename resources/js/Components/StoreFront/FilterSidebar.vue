<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { isColorDimension } from '@/composables/useVariantOptions'

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({})
    },
    showCategories: {
        type: Boolean,
        default: true
    }
})

const dimensions = computed(() => props.filters.dimensions || [])
const prices = computed(() => props.filters.prices || null)
const topCategories = computed(() => props.filters.categories || [])

const catTints = [
    'bg-[#e3ecff] hover:ring-[#a5b4fc]/50 text-[#2563eb] dark:bg-[#24314d] dark:text-[#93c5fd] dark:hover:ring-[#5a539a]',
    'bg-[#fbe7f2] hover:ring-[#f9a8d4]/50 text-[#db2777] dark:bg-[#42223a] dark:text-[#f9a8d4] dark:hover:ring-[#8a4463]',
    'bg-[#e8f8ef] hover:ring-[#86efac]/50 text-[#16a34a] dark:bg-[#1e3d2c] dark:text-[#86efac] dark:hover:ring-[#4d7a5c]',
    'bg-[#fff3e0] hover:ring-[#fdba74]/50 text-[#ea580c] dark:bg-[#422c14] dark:text-[#fdba74] dark:hover:ring-[#8a5a34]',
    'bg-[#efebff] hover:ring-[#c4b5fd]/50 text-[#7c3aed] dark:bg-[#2e2a4d] dark:text-[#c4b5fd] dark:hover:ring-[#6b62a8]',
]
const catTint = (i) => catTints[i % catTints.length]

const urlParams = computed(() => new URL(window.location.href).searchParams)

const selectedCats = computed(() => urlParams.value.getAll('categories'))
const minPriceParam = computed(() => urlParams.value.get('min_price'))
const maxPriceParam = computed(() => urlParams.value.get('max_price'))
const qParam = computed(() => urlParams.value.get('q'))

function selectedDimValues(dimName) {
    return urlParams.value.getAll(`options[${dimName}]`)
}

const minPrice = ref(Number(minPriceParam.value) || 200)
const maxPrice = ref(Number(maxPriceParam.value) || 2000)

const activeFilterCount = computed(() => {
    let count = 0
    count += selectedCats.value.length

    if (minPriceParam.value) {
count++
}

    if (maxPriceParam.value) {
count++
}

    for (const dim of dimensions.value) {
        count += selectedDimValues(dim.name).length
    }

    return count
})

function submitForm() {
    const params = new URLSearchParams()

    if (qParam.value) {
params.set('q', qParam.value)
}

    for (const cat of selectedCats.value) {
params.append('categories', cat)
}

    for (const dim of dimensions.value) {
        for (const val of selectedDimValues(dim.name)) {
            params.append(`options[${dim.name}]`, val)
        }
    }

    if (Number(minPrice.value) > 200) {
params.set('min_price', minPrice.value)
}

    if (Number(maxPrice.value) < 2000) {
params.set('max_price', maxPrice.value)
}

    const qs = params.toString()
    router.get(window.location.pathname + (qs ? '?' + qs : ''), {}, { preserveScroll: true, preserveState: true })
}

function clearFilters() {
    router.get(window.location.pathname, {}, { preserveScroll: true, preserveState: true })
}

function toggleDimValue(dimName, value) {
    toggleParam(`options[${dimName}]`, value)
}

function toggleParam(key, value) {
    const params = new URLSearchParams(window.location.search)
    const current = params.getAll(key)

    if (current.includes(value)) {
        params.delete(key)
        current.filter(v => v !== value).forEach(v => params.append(key, v))
    } else {
        params.append(key, value)
    }

    const qs = params.toString()
    router.get(window.location.pathname + (qs ? '?' + qs : ''), {}, { preserveScroll: true, preserveState: true })
}

function applyPrice() {
 submitForm() 
}

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

        <div v-if="showCategories && topCategories.length" class="rounded-2xl border border-outline-variant bg-[#f8f9ff] p-4 dark:border-[#3a302e] dark:bg-[#241d1c]">
            <h3 class="mb-4 text-xs font-bold uppercase tracking-widest text-secondary dark:text-[#cbb8b6]">Categories</h3>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="(cat, i) in topCategories"
                    :key="cat.id"
                    :href="route('category.show', cat.slug || cat.id)"
                    :class="catTint(i)"
                    class="flex items-center gap-1 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-transparent transition-all duration-200 hover:bg-white hover:ring-1 dark:hover:bg-white/10"
                >
                    {{ cat.name }}
                    <span v-if="cat.products_count" class="opacity-60">{{ cat.products_count }}</span>
                </Link>
            </div>
        </div>

        <div v-if="dimensions.length || prices" class="border-t border-outline-variant dark:border-[#3a302e] pt-8">
            <h3 class="text-xs font-bold uppercase tracking-widest text-secondary dark:text-[#cbb8b6] mb-6">Filters</h3>
            <div class="flex flex-col gap-6">
                <div v-for="dim in dimensions" :key="dim.name">
                    <p class="text-xs font-bold uppercase tracking-widest text-on-surface dark:text-[#f9eeed] mb-3 capitalize">{{ dim.name }}</p>
                    <div v-if="isColorDimension(dim.name)" class="flex flex-wrap gap-2">
                        <label v-for="value in dim.values" :key="value" class="cursor-pointer" :title="value">
                            <input type="checkbox" :checked="selectedDimValues(dim.name).includes(value)" @change="toggleDimValue(dim.name, value)" class="sr-only peer">
                            <span class="block w-6 h-6 rounded-full border border-outline-variant dark:border-[#3a302e] transition-all duration-200 peer-checked:ring-2 peer-checked:ring-primary dark:peer-checked:ring-[#f6b7b2] peer-checked:ring-offset-2 dark:peer-checked:ring-offset-[#171212]"
                                :style="{ backgroundColor: colorHex(value), borderColor: ['white','beige','champagne'].includes(value.toLowerCase()) ? '#e5e7eb' : undefined }"></span>
                        </label>
                    </div>
                    <div v-else class="grid grid-cols-4 gap-2">
                        <label v-for="value in dim.values" :key="value" class="cursor-pointer">
                            <input type="checkbox" :checked="selectedDimValues(dim.name).includes(value)" @change="toggleDimValue(dim.name, value)" class="sr-only peer">
                            <span class="flex items-center justify-center py-2 border text-[10px] font-medium transition-all border-outline-variant dark:border-[#3a302e] text-on-surface-variant dark:text-[#cbb8b6] peer-checked:bg-on-surface dark:peer-checked:bg-[#f9eeed] peer-checked:text-surface dark:peer-checked:text-charcoal peer-checked:border-on-surface dark:peer-checked:border-[#f9eeed] hover:border-primary dark:hover:border-[#f6b7b2]">{{ value }}</span>
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
