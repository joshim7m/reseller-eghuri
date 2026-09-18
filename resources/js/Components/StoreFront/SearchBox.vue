<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    autofocus: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const isGuest = computed(() => !page.props.auth?.user);

const query = ref('');
const results = ref([]);
const loading = ref(false);
const open = ref(false);
const inputEl = ref(null);

let debounceTimer = null;
let blurTimer = null;

watch(
    () => props.autofocus,
    (val) => {
        if (val) {
            nextTick(() => {
                inputEl.value?.focus();
            });
        }
    },
);

const isSearching = computed(() => query.value.trim() !== '');

async function fetchResults() {
    const q = query.value.trim();

    if (!q) {
        results.value = [];

        return;
    }

    loading.value = true;

    try {
        const res = await fetch(`/api/search?q=${encodeURIComponent(q)}`);

        if (!res.ok) {
            throw new Error('Search failed');
        }

        results.value = await res.json();
    } catch {
        results.value = [];
    } finally {
        loading.value = false;
    }
}

function onInput() {
    clearTimeout(debounceTimer);

    if (!query.value.trim()) {
        results.value = [];

        return;
    }

    debounceTimer = setTimeout(fetchResults, 250);
}

function onFocus() {
    clearTimeout(blurTimer);
    open.value = true;

    if (query.value.trim() && !results.value.length) {
        fetchResults();
    }
}

function onBlur() {
    blurTimer = setTimeout(() => {
        open.value = false;
    }, 150);
}

function clearQuery() {
    query.value = '';
    results.value = [];
    inputEl.value?.focus();
}

function visit(url) {
    router.visit(url, {
        onFinish: () => {
            open.value = false;
            inputEl.value?.blur();
        },
    });
}

function submit() {
    if (query.value.trim()) {
        visit(route('products.index', { q: query.value.trim() }));
    }
}

function onKeydown(e) {
    if (e.key === 'Escape') {
        open.value = false;
        inputEl.value?.blur();
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown));

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    clearTimeout(debounceTimer);
    clearTimeout(blurTimer);
});

const formatPrice = (price) => '৳' + Number(price).toLocaleString('en-IN');
</script>

<template>
    <div class="relative w-full max-w-xl" @focusin="onFocus" @focusout="onBlur">
        <div
            class="flex h-10 items-center gap-2 rounded-full border border-surface-container-high bg-surface-container-low px-4 transition focus-within:border-outline-variant focus-within:ring-2 focus-within:ring-primary/15 dark:border-[#3a302e] dark:bg-[#241d1c] dark:focus-within:ring-[#f6b7b2]/20"
        >
            <span class="material-symbols-outlined shrink-0 text-[20px] text-outline dark:text-[#cbb8b6]" aria-hidden="true">search</span>
            <input
                ref="inputEl"
                v-model="query"
                type="text"
                enterkeyhint="search"
                placeholder="Search products..."
                class="min-w-0 flex-1 border-0 bg-transparent text-sm text-gray-900 placeholder-gray-400 outline-none focus:border-0 focus:outline-none focus:ring-0 dark:text-white dark:placeholder-gray-500"
                @input="onInput"
                @keydown.enter.prevent="submit"
            />
            <button
                v-if="query"
                type="button"
                @click="clearQuery"
                class="shrink-0 p-0.5 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                aria-label="Clear search"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <Transition
            enter-active-class="ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="open && (loading || results.length || isSearching)"
                class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div v-if="loading" class="space-y-3 px-4 py-3">
                    <div
                        v-for="i in 3"
                        :key="i"
                        class="flex animate-pulse items-center gap-3"
                    >
                        <div
                            class="h-12 w-12 shrink-0 rounded-xl bg-gray-100 dark:bg-gray-800"
                        ></div>
                        <div class="flex-1 space-y-2">
                            <div
                                class="h-3.5 w-4/5 rounded bg-gray-100 dark:bg-gray-800"
                            ></div>
                            <div
                                class="h-3 w-1/3 rounded bg-gray-100 dark:bg-gray-800"
                            ></div>
                        </div>
                    </div>
                </div>

                <template v-else-if="results.length">
                    <div class="max-h-96 overflow-y-auto overscroll-contain">
                        <div
                            class="px-4 pb-1 pt-3 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                        >
                            {{ isSearching ? 'Results' : 'Featured Products' }}
                        </div>
                        <a
                            v-for="product in results"
                            :key="product.id"
                            :href="
                                route(
                                    'product.show',
                                    product.slug || product.id,
                                )
                            "
                            @click.prevent="
                                visit(
                                    route(
                                        'product.show',
                                        product.slug || product.id,
                                    ),
                                )
                            "
                            class="flex items-center gap-3 px-4 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-800/60"
                        >
                            <img
                                v-if="product.image"
                                :src="product.image"
                                :alt="product.title"
                                class="h-12 w-12 shrink-0 rounded-xl bg-gray-100 object-cover dark:bg-gray-800"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-300 dark:bg-gray-800 dark:text-gray-600"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"
                                    />
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="line-clamp-1 text-sm font-semibold leading-snug text-gray-900 dark:text-white"
                                >
                                    {{ product.title }}
                                </p>
                                <p
                                    v-if="product.sku"
                                    class="mt-0.5 text-xs font-medium text-gray-400 dark:text-gray-500"
                                >
                                    SKU: {{ product.sku }}
                                </p>
                                <div
                                    v-if="!isGuest"
                                    class="mt-1 flex items-center gap-2"
                                >
                                    <span
                                        class="text-sm font-bold text-[#D9531E] dark:text-orange-400"
                                        >৳{{
                                            formatPrice(product.sale_price)
                                        }}</span
                                    >
                                    <span
                                        v-if="
                                            product.unit_price >
                                            product.sale_price
                                        "
                                        class="text-xs text-red-300 line-through dark:text-red-500"
                                        >৳{{
                                            formatPrice(product.unit_price)
                                        }}</span
                                    >
                                </div>
                                <span
                                    v-else
                                    class="mt-1 inline-block text-xs font-medium text-blue-600 dark:text-blue-400"
                                    >প্রাইস দেখতে লগইন করুন</span
                                >
                            </div>

                            <svg
                                class="h-4 w-4 shrink-0 text-gray-300 dark:text-gray-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </a>
                    </div>

                    <a
                        :href="
                            isSearching
                                ? route('products.index', { q: query.trim() })
                                : route('products.index')
                        "
                        @click.prevent="
                            visit(
                                isSearching
                                    ? route('products.index', {
                                          q: query.trim(),
                                      })
                                    : route('products.index'),
                            )
                        "
                        class="mx-4 my-2 block rounded-full bg-charcoal py-2.5 text-center text-sm font-semibold text-white transition hover:bg-black dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-white"
                    >
                        {{
                            isSearching
                                ? `View all results for "${query.trim()}"`
                                : 'View all products'
                        }}
                    </a>
                </template>

                <div v-else-if="isSearching" class="px-4 py-10 text-center">
                    <svg
                        class="mx-auto h-10 w-10 text-gray-200 dark:text-gray-700"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        No products found for "{{ query }}"
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>
