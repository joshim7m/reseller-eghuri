<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SearchBox from '@/Components/StoreFront/SearchBox.vue';
import { useWishlist } from '@/composables/useWishlist';

defineEmits(['open-search', 'open-mobile']);

const wishlist = useWishlist();
wishlist.load();
const wishlistCount = computed(() => wishlist.ids.value.length);

const page = usePage();
const settings = page.props.settings || {};
const auth = page.props.auth;

const userMenuOpen = ref(false);
const userMenuEl = ref(null);
const mobileSearchOpen = ref(false);
const isDark = ref(localStorage.getItem('darkMode') === 'true');
const catMegaOpen = ref(false);
const catMegaEl = ref(null);
let catMegaTimer = null;

function toggleDark() {
    isDark.value = !isDark.value;
    localStorage.setItem('darkMode', isDark.value);
    document.documentElement.classList.toggle('dark', isDark.value);
}

onMounted(() => {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    }
});

function toggleUserMenu() {
    userMenuOpen.value = !userMenuOpen.value;
}

function onClickOutside(e) {
    if (userMenuEl.value && !userMenuEl.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
    if (catMegaEl.value && !catMegaEl.value.contains(e.target)) {
        catMegaOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));

function openCatMega() {
    clearTimeout(catMegaTimer);
    catMegaOpen.value = true;
}

function scheduleCloseCatMega() {
    catMegaTimer = setTimeout(() => { catMegaOpen.value = false; }, 150);
}

const categories = computed(() => page.props.categories || []);

const navLinks = [
    { label: 'New Arrivals', href: () => route('products.new-arrivals'), dropdown: false },
    { label: 'Hot Sale', href: () => route('products.hot-sale'), dropdown: false },
];
</script>

<template>
    <header class="sticky top-0 z-40 w-full bg-white dark:bg-[#171212] border-b border-surface-container-high dark:border-[#3a302e]">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 md:h-16 md:gap-4 md:px-6 md:py-0">
            <!-- Left: Mobile menu button + Logo -->
            <div class="flex shrink-0 items-center gap-2">
                <button
                    @click="$emit('open-mobile')"
                    class="-ml-1.5 p-1.5 text-charcoal transition hover:text-primary dark:text-[#f9eeed] dark:hover:text-[#f6b7b2] md:hidden"
                    aria-label="Open menu"
                >
                    <span class="material-symbols-outlined text-[24px]" aria-hidden="true">menu</span>
                </button>

                <Link :href="route('home')" class="flex shrink-0 items-center gap-2">
                    <img
                        v-if="settings.company_logo"
                        :src="`/${settings.company_logo}`"
                        :alt="settings.company_name || 'Store'"
                        class="h-8 w-auto md:h-9"
                    />
                    <template v-else>
                        <span class="material-symbols-outlined text-2xl font-bold text-primary" aria-hidden="true">auto_awesome</span>
                        <span class="text-xl font-bold tracking-tight text-charcoal dark:text-[#f9eeed] md:text-2xl">{{ settings.company_name || 'Store' }}</span>
                    </template>
                </Link>
            </div>

            <!-- Desktop nav -->
            <nav class="hidden items-center gap-8 md:flex">
                <div
                    ref="catMegaEl"
                    class="relative"
                    @mouseenter="openCatMega"
                    @mouseleave="scheduleCloseCatMega"
                >
                    <button
                        @click="catMegaOpen = !catMegaOpen"
                        class="flex items-center gap-1 text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-charcoal dark:text-[#cbb8b6] dark:hover:text-white"
                    >
                        All Categories
                        <span class="material-symbols-outlined align-middle text-[16px]" aria-hidden="true">expand_more</span>
                    </button>
                </div>

                <Link
                    v-for="link in navLinks"
                    :key="link.label"
                    :href="link.href()"
                    class="text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-charcoal dark:text-[#cbb8b6] dark:hover:text-white"
                >
                    {{ link.label }}
                </Link>
            </nav>

            <!-- Desktop search -->
            <div class="hidden min-w-0 max-w-md flex-1 px-2 md:block lg:px-8">
                <div class="relative">
                    <SearchBox />
                </div>
            </div>

            <!-- Right actions -->
            <div class="flex shrink-0 items-center gap-1.5 md:gap-5">
                <!-- Mobile search toggle -->
                <button
                    @click="mobileSearchOpen = !mobileSearchOpen"
                    class="p-1.5 text-charcoal transition hover:text-primary dark:text-[#f9eeed] dark:hover:text-[#f6b7b2] md:hidden"
                    aria-label="Search"
                >
                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">{{ mobileSearchOpen ? 'close' : 'search' }}</span>
                </button>

                <button @click="toggleDark" class="p-1.5 text-charcoal transition hover:text-primary dark:text-[#f9eeed] dark:hover:text-[#f6b7b2]" aria-label="Toggle dark mode">
                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">{{ isDark ? 'light_mode' : 'dark_mode' }}</span>
                </button>

                <Link
                    :href="route('wishlist.index')"
                    class="relative hidden items-center gap-1 p-1.5 text-sm font-medium text-charcoal transition-colors duration-200 hover:text-primary dark:text-[#f9eeed] dark:hover:text-[#f6b7b2] sm:flex"
                >
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">favorite</span>
                    <span class="hidden lg:inline">Favorites</span>
                    <span v-if="wishlistCount" class="absolute right-0 top-0 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-sale-price px-1 text-[10px] font-bold leading-none text-white">{{ wishlistCount }}</span>
                </Link>

                <!-- Auth area -->
                <template v-if="auth?.user">
                    <div ref="userMenuEl" class="relative">
                        <button
                            @click="toggleUserMenu"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-container text-sm font-bold text-on-primary-container transition hover:brightness-95 dark:bg-[#4a2e2c] dark:text-[#f6b7b2]"
                            aria-label="User menu"
                        >
                            {{ (auth.user.name || 'U').charAt(0).toUpperCase() }}
                        </button>
                        <div
                            v-show="userMenuOpen"
                            class="absolute right-0 top-full z-50 mt-2 w-48 rounded-xl border border-surface-container-high bg-white py-2 shadow-lg dark:border-[#3a302e] dark:bg-[#241d1c]"
                        >
                            <Link :href="route('profile')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-charcoal transition hover:bg-surface-container-low dark:text-[#f9eeed] dark:hover:bg-[#2e2523]">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">person</span>
                                Profile
                            </Link>
                            <Link :href="route('orders.index')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-charcoal transition hover:bg-surface-container-low dark:text-[#f9eeed] dark:hover:bg-[#2e2523]">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">receipt_long</span>
                                Orders
                            </Link>
                            <Link
                                v-if="auth.user.user_type === 'reseller'"
                                :href="route('reseller-orders.index')"
                                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-charcoal transition hover:bg-surface-container-low dark:text-[#f9eeed] dark:hover:bg-[#2e2523]"
                            >
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">inventory_2</span>
                                Reseller Orders
                            </Link>
                            <Link :href="route('wishlist.index')" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-charcoal transition hover:bg-surface-container-low dark:text-[#f9eeed] dark:hover:bg-[#2e2523]">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">favorite</span>
                                Wishlist
                            </Link>
                            <hr class="my-1.5 border-surface-container-high dark:border-[#3a302e]" />
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-sale-price transition hover:bg-surface-container-low dark:hover:bg-[#2e2523]"
                            >
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">logout</span>
                                Logout
                            </Link>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="rounded-full bg-charcoal px-4 py-1.5 text-sm font-medium text-white transition-colors hover:bg-primary dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]"
                    >Sign In</Link>
                </template>
            </div>
        </div>

        <!-- Categories Mega Menu -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-show="catMegaOpen"
                ref="catMegaEl"
                @mouseenter="openCatMega"
                @mouseleave="scheduleCloseCatMega"
                class="mx-auto max-w-7xl border-t border-surface-container-high bg-white px-4 py-6 dark:bg-[#241d1c] dark:border-[#3a302e] md:px-6"
            >
                    <div class="grid grid-cols-4 gap-4 sm:grid-cols-6 lg:grid-cols-8">
                        <Link
                            v-for="cat in categories"
                            :key="cat.id"
                            :href="route('category.show', cat.slug || cat.id)"
                            class="group flex flex-col items-center gap-2 rounded-xl p-2 transition hover:bg-surface-container-low dark:hover:bg-[#2e2523]"
                            @click="catMegaOpen = false"
                        >
                            <div class="h-14 w-14 overflow-hidden rounded-full bg-surface-container dark:bg-[#2e2523] lg:h-16 lg:w-16">
                                <img
                                    v-if="cat.image_url"
                                    :src="cat.image_url"
                                    :alt="cat.name"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    loading="lazy"
                                />
                                <div v-else class="flex h-full items-center justify-center">
                                    <span class="material-symbols-outlined text-xl text-outline-variant dark:text-[#3a302e]" aria-hidden="true">category</span>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold text-center text-charcoal dark:text-[#f9eeed] group-hover:text-primary dark:group-hover:text-[#f6b7b2] transition-colors leading-tight">{{ cat.name }}</span>
                        </Link>
                    </div>
                    <Link
                        :href="route('categories.index')"
                        class="mt-4 flex w-full items-center justify-center gap-1 rounded-lg border border-surface-container-high py-2 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container-low dark:border-[#3a302e] dark:text-[#cbb8b6] dark:hover:bg-[#2e2523]"
                        @click="catMegaOpen = false"
                    >
                        View All Categories
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">arrow_forward</span>
                    </Link>
            </div>
        </Transition>

        <!-- Mobile search row -->
        <div v-show="mobileSearchOpen" class="border-t border-surface-container-high px-4 py-3 dark:border-[#3a302e] md:hidden">
            <SearchBox :autofocus="mobileSearchOpen" />
        </div>
    </header>
</template>
