<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import HomeProductCard from '@/Components/StoreFront/HomeProductCard.vue';
import { useWishlist } from '@/composables/useWishlist';
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});

const wishlist = useWishlist();

const saved = computed(() =>
    props.products.filter((p) => wishlist.ids.value.includes(p.id)),
);

const exporting = ref(false);

async function exportExcel() {
    if (!saved.value.length || exporting.value) {
return;
}

    exporting.value = true;

    try {
        const response = await window.axios.post(
            route('wishlist.export'),
            { ids: saved.value.map((p) => p.id) },
            { responseType: 'blob' },
        );
        const url = URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'wishlist.xlsx';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    } finally {
        exporting.value = false;
    }
}

const showClearModal = ref(false);
function confirmClearAll() {
 showClearModal.value = true; 
}
function executeClear() {
 showClearModal.value = false; wishlist.clear(); 
}
</script>

<template>
    <Head title="My Wishlist" />

    <FrontEndMaster>
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-charcoal dark:text-[#f9eeed]">My Wishlist</h1>
                <p v-if="saved.length" class="mt-2 text-sm text-on-surface-variant dark:text-[#cbb8b6]">
                    {{ saved.length }} {{ saved.length === 1 ? 'item' : 'items' }} saved
                </p>
            </div>
            <div v-if="saved.length" class="flex items-center gap-3">
                <button
                    type="button"
                    :disabled="exporting"
                    @click="exportExcel"
                    class="inline-flex items-center gap-2 rounded-lg border border-outline-variant dark:border-[#3a302e] px-4 py-2.5 text-sm font-medium text-charcoal dark:text-[#f9eeed] transition hover:bg-surface-container dark:hover:bg-[#241d1c] disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">download</span>
                    {{ exporting ? 'Exporting...' : 'Export Excel' }}
                </button>
                <button
                    type="button"
                    @click="confirmClearAll"
                    class="inline-flex items-center gap-2 rounded-lg border border-sale-price/30 px-4 py-2.5 text-sm font-medium text-sale-price transition hover:bg-sale-price/5"
                >
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">delete_outline</span>
                    Clear All
                </button>
            </div>
        </div>

        <div v-if="saved.length" class="grid grid-cols-2 gap-4 md:grid-cols-6">
            <HomeProductCard
                v-for="product in saved"
                :key="product.id"
                :product="product"
            />
        </div>

        <div v-else class="py-24 text-center">
            <span class="material-symbols-outlined text-7xl text-outline-variant dark:text-[#3a302e]" aria-hidden="true">favorite</span>
            <h2 class="mt-6 text-xl font-bold text-charcoal dark:text-[#f9eeed]">Your wishlist is empty</h2>
            <p class="mt-2 text-sm text-on-surface-variant dark:text-[#cbb8b6]">Tap the heart on any product to save it here.</p>
            <Link
                :href="route('products.index')"
                class="mt-8 inline-flex items-center gap-2 rounded-full bg-charcoal px-6 py-3 text-sm font-medium text-white transition hover:bg-primary dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]"
            >
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">shopping_bag</span>
                Explore Products
            </Link>
        </div>

        <Teleport to="body">
            <Transition enter-active-class="duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showClearModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showClearModal = false">
                    <div class="absolute inset-0 bg-black/50" @click="showClearModal = false"></div>
                    <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl dark:bg-[#241d1c]">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-sale-price/10 mx-auto">
                            <span class="material-symbols-outlined text-2xl text-sale-price" aria-hidden="true">warning</span>
                        </div>
                        <h3 class="mt-4 text-center text-lg font-bold text-charcoal dark:text-[#f9eeed]">Clear Wishlist?</h3>
                        <p class="mt-2 text-center text-sm text-on-surface-variant dark:text-[#cbb8b6]">This will remove all {{ saved.length }} {{ saved.length === 1 ? 'item' : 'items' }} from your wishlist.</p>
                        <div class="mt-6 flex gap-3">
                            <button @click="showClearModal = false" class="flex-1 rounded-xl border border-outline-variant dark:border-[#3a302e] px-4 py-2.5 text-sm font-semibold text-charcoal dark:text-[#f9eeed] transition hover:bg-surface-container dark:hover:bg-[#2e2523]">Cancel</button>
                            <button @click="executeClear" class="flex-1 rounded-xl bg-sale-price px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">Clear All</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </FrontEndMaster>
</template>
