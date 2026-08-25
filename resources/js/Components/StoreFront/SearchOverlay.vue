<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close'])

const page = usePage()
const isGuest = computed(() => !page.props.auth?.user)

const query = ref('')
const results = ref([])
const loading = ref(false)
const inputEl = ref(null)

let debounceTimer = null

const isSearching = computed(() => query.value.trim() !== '')

async function fetchResults() {
    loading.value = true

    try {
        const res = await fetch(`/api/search?q=${encodeURIComponent(query.value.trim())}`)

        if (!res.ok) {
            throw new Error('Search failed')
        }

        results.value = await res.json()
    } catch {
        results.value = []
    } finally {
        loading.value = false
    }
}

function onInput() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(fetchResults, 250)
}

function clearQuery() {
    query.value = ''
    fetchResults()
    inputEl.value?.focus()
}

function close() {
    clearTimeout(debounceTimer)
    emit('close')
}

function onKeydown(e) {
    if (e.key === 'Escape' && props.open) {
        close()
    }
}

watch(() => props.open, (val) => {
    if (val) {
        query.value = ''
        results.value = []
        document.body.style.overflow = 'hidden'
        fetchResults()
        setTimeout(() => inputEl.value?.focus(), 60)
    } else {
        document.body.style.overflow = ''
    }
})

onMounted(() => document.addEventListener('keydown', onKeydown))

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})

const formatPrice = (price) => '৳' + Number(price).toLocaleString('en-IN')
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
            <div v-if="open" class="fixed inset-0 z-[60] bg-black/50" @click="close">
                <div
                    class="absolute inset-x-0 top-0 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 sm:top-6 sm:w-full sm:max-w-lg w-full h-full sm:h-auto sm:max-h-[85vh] sm:rounded-2xl bg-white dark:bg-gray-900 shadow-2xl flex flex-col sm:overflow-hidden"
                    @click.stop
                >
                    <!-- search bar -->
                    <div class="flex items-center gap-2 px-2 sm:px-3 h-14 sm:h-12 border-b border-gray-200 dark:border-gray-700 shrink-0">
                        <button type="button" @click="close" class="sm:hidden text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1.5 -ml-1.5" aria-label="Close search">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" @click="close" class="hidden sm:flex text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 p-1" aria-label="Close search">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>

                        <div class="flex-1 flex items-center gap-2 bg-gray-100 dark:bg-gray-800 rounded-full px-3.5 h-10">
                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input
                                ref="inputEl"
                                v-model="query"
                                type="text"
                                enterkeyhint="search"
                                placeholder="Search products..."
                                class="flex-1 min-w-0 bg-transparent outline-none text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                                @input="onInput"
                            >
                            <button v-if="query" type="button" @click="clearQuery" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 p-0.5 shrink-0" aria-label="Clear search">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- results -->
                    <div class="flex-1 overflow-y-auto overscroll-contain">
                        <div class="px-4 pt-3 pb-1 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ isSearching ? 'Results' : 'Featured Products' }}
                        </div>

                        <!-- skeleton -->
                        <div v-if="loading" class="px-4 py-2 space-y-3">
                            <div v-for="i in 4" :key="i" class="flex items-center gap-3 animate-pulse">
                                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-gray-100 dark:bg-gray-800 shrink-0"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="h-3.5 bg-gray-100 dark:bg-gray-800 rounded w-4/5"></div>
                                    <div class="h-3 bg-gray-100 dark:bg-gray-800 rounded w-1/3"></div>
                                </div>
                            </div>
                        </div>

                        <!-- results -->
                        <template v-else-if="results.length">
                            <div class="pb-2">
                                <Link
                                    v-for="product in results" :key="product.id"
                                    :href="route('product.show', product.slug || product.id)"
                                    @click="close"
                                    class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition"
                                >
                                    <img
                                        v-if="product.image" :src="product.image" :alt="product.title"
                                        class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl object-cover bg-gray-100 dark:bg-gray-800 shrink-0"
                                        loading="lazy"
                                    >
                                    <div v-else class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-300 dark:text-gray-600 shrink-0">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white leading-snug line-clamp-2">{{ product.title }}</p>
                                        <p v-if="product.sku" class="mt-0.5 text-xs font-medium text-gray-400 dark:text-gray-500">SKU: {{ product.sku }}</p>
                                        <div v-if="!isGuest" class="mt-1 flex items-center gap-2">
                                            <span class="text-sm font-bold text-[#0B132A] dark:text-white">৳{{ formatPrice(product.sale_price) }}</span>
                                            <span v-if="product.unit_price > product.sale_price" class="text-xs text-red-300 dark:text-red-500 line-through">৳{{ formatPrice(product.unit_price) }}</span>
                                        </div>
                                        <Link v-else :href="route('login')" @click="close" class="mt-1 inline-block text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline">প্রাইস দেখতে লগইন করুন</Link>
                                    </div>

                                    <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </Link>

                                <Link
                                    :href="isSearching ? route('products.index', { q: query.trim() }) : route('products.index')"
                                    @click="close"
                                    class="block mx-4 my-2 py-2.5 text-center text-sm font-semibold text-blue-600 dark:text-blue-400 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
                                >
                                    {{ isSearching ? `View all results for "${query.trim()}"` : 'View all products' }}
                                </Link>
                            </div>
                        </template>

                        <!-- empty -->
                        <div v-else class="px-4 py-12 text-center">
                            <svg class="w-14 h-14 mx-auto text-gray-200 dark:text-gray-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                No products found for "{{ query }}"
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
