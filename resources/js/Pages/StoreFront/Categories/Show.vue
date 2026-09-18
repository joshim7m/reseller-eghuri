<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import FilterSidebar from '@/Components/StoreFront/FilterSidebar.vue'
import HomeProductCard from '@/Components/StoreFront/HomeProductCard.vue'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

const props = defineProps({
    category: { type: Object, default: () => ({}) },
    products: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) }
})

const filterOpen = ref(false)
const urlParams = new URL(window.location.href).searchParams

const subcategories = computed(() => props.category.children || [])
const activeSubcategory = computed(() => {
    const catId = urlParams.get('categories')

    if (!catId) {
return null
}

    return subcategories.value.find(c => String(c.id) === catId) || null
})

function changeSort(event) {
    const params = new URLSearchParams(window.location.search)
    params.set('sort', event.target.value)
    router.get(window.location.pathname + '?' + params.toString(), {}, { preserveScroll: true, preserveState: true })
}

function goToPage(url) {
    if (url) {
router.get(url, {}, { preserveScroll: true, preserveState: true })
}
}
</script>

<template>
    <FrontEndMaster>
        <h1 class="sr-only">{{ category.name }}</h1>
        <!-- Mobile filter toggle -->
        <div class="flex lg:hidden items-center gap-2 mb-4">
            <button
                type="button"
                @click="filterOpen = true"
                class="flex items-center gap-2 bg-white dark:bg-[#241d1c] border border-outline-variant dark:border-[#3a302e] rounded-lg px-4 py-2 text-sm text-charcoal dark:text-[#f9eeed] transition"
            >
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">filter_list</span>
                Filters
            </button>
        </div>

        <!-- Mobile filter drawer -->
        <div v-show="filterOpen" class="fixed inset-0 z-50 lg:hidden" @keydown.escape.window="filterOpen = false">
            <div class="absolute inset-0 bg-black/50" @click="filterOpen = false"></div>
            <div class="absolute inset-y-0 left-0 w-80 max-w-[85vw] bg-white dark:bg-[#171212] shadow-xl overflow-y-auto">
                <div class="sticky top-0 bg-white dark:bg-[#171212] border-b border-outline-variant dark:border-[#3a302e] px-4 py-3 flex items-center justify-between z-10">
                    <h2 class="font-bold text-charcoal dark:text-[#f9eeed]">Filters</h2>
                    <button type="button" @click="filterOpen = false" class="p-1 rounded-lg hover:bg-surface-container dark:hover:bg-[#241d1c] transition">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
                    </button>
                </div>
                <div class="p-4">
                    <FilterSidebar :filters="filters" />
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex gap-6">
            <!-- Sidebar -->
            <aside class="w-64 flex-shrink-0 hidden lg:block">
                <div class="sticky top-24 flex flex-col gap-8">
                    <!-- Subcategories nav -->
                    <div v-if="subcategories.length">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-on-surface-variant dark:text-[#cbb8b6] mb-6">Categories</h3>
                        <nav class="flex flex-col gap-4">
                            <Link
                                :href="route('category.show', category.slug || category.id)"
                                class="text-sm transition-colors"
                                :class="!activeSubcategory ? 'font-bold text-primary dark:text-[#f6b7b2]' : 'text-on-surface-variant dark:text-[#cbb8b6] hover:text-primary dark:hover:text-[#f6b7b2]'"
                            >
                                All {{ category.name }}
                            </Link>
                            <Link
                                v-for="sub in subcategories"
                                :key="sub.id"
                                :href="route('category.show', sub.slug || sub.id)"
                                class="text-sm transition-colors"
                                :class="activeSubcategory?.id === sub.id ? 'font-bold text-primary dark:text-[#f6b7b2]' : 'text-on-surface-variant dark:text-[#cbb8b6] hover:text-primary dark:hover:text-[#f6b7b2]'"
                            >
                                {{ sub.name }}
                            </Link>
                        </nav>
                    </div>

                    <!-- Filters -->
                    <div class="border-t border-outline-variant dark:border-[#3a302e] pt-8">
                        <FilterSidebar :filters="filters" :show-categories="false" />
                    </div>
                </div>
            </aside>

            <!-- Product grid -->
            <div class="flex-1 min-w-0">
                <!-- Sort & count -->
                <div class="hidden lg:flex items-center justify-between mb-6">
                    <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">{{ products.total || products.data?.length || 0 }} products</p>
                    <select @change="changeSort" class="bg-white dark:bg-[#241d1c] border border-outline-variant dark:border-[#3a302e] rounded-lg px-3 py-2 text-sm text-charcoal dark:text-[#f9eeed] focus:outline-none focus:border-primary">
                        <option value="latest" :selected="!urlParams.get('sort') || urlParams.get('sort') === 'latest'">Sort: Latest</option>
                        <option value="price_asc" :selected="urlParams.get('sort') === 'price_asc'">Price: Low to High</option>
                        <option value="price_desc" :selected="urlParams.get('sort') === 'price_desc'">Price: High to Low</option>
                        <option value="name_asc" :selected="urlParams.get('sort') === 'name_asc'">Name: A-Z</option>
                        <option value="name_desc" :selected="urlParams.get('sort') === 'name_desc'">Name: Z-A</option>
                    </select>
                </div>

                <!-- Products grid -->
                <div v-if="products.data && products.data.length" class="grid grid-cols-2 gap-4 md:gap-5 lg:grid-cols-5">
                    <HomeProductCard
                        v-for="product in products.data"
                        :key="product.id"
                        :product="product"
                    />
                </div>
                <div v-else class="text-center py-20">
                    <span class="material-symbols-outlined text-6xl text-outline-variant dark:text-[#3a302e] mb-4" aria-hidden="true">inventory_2</span>
                    <p class="text-on-surface-variant dark:text-[#cbb8b6] text-sm">No products found in this category.</p>
                </div>

                <!-- Pagination -->
                <div v-if="products.links && products.links.length > 3" class="mt-12 pt-8 border-t border-outline-variant dark:border-[#3a302e] flex justify-between items-center">
                    <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">
                        Showing {{ products.from }}-{{ products.to }} of {{ products.total }}
                    </p>
                    <div class="flex gap-2">
                        <button
                            v-if="products.prev_page_url"
                            @click="goToPage(products.prev_page_url)"
                            class="flex items-center gap-2 text-sm font-medium text-charcoal dark:text-[#f9eeed] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                            Previous
                        </button>
                        <button
                            v-if="products.next_page_url"
                            @click="goToPage(products.next_page_url)"
                            class="flex items-center gap-2 text-sm font-medium text-charcoal dark:text-[#f9eeed] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors"
                        >
                            Next
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </FrontEndMaster>
</template>
