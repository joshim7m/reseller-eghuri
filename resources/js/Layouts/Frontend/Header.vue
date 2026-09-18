<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import NotificationDropdown from '@/Components/StoreFront/NotificationDropdown.vue';
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
    catMegaTimer = setTimeout(() => {
 catMegaOpen.value = false; 
}, 150);
}

const categories = computed(() => page.props.headerCategories || page.props.categories || []);

const catTints = [
    { bg: 'bg-[#e3ecff] dark:bg-[#24314d]', text: 'text-[#2563eb] dark:text-[#93c5fd]' },
    { bg: 'bg-[#fbe7f2] dark:bg-[#42223a]', text: 'text-[#db2777] dark:text-[#f9a8d4]' },
    { bg: 'bg-[#e8f8ef] dark:bg-[#1e3d2c]', text: 'text-[#16a34a] dark:text-[#86efac]' },
    { bg: 'bg-[#fff3e0] dark:bg-[#422c14]', text: 'text-[#ea580c] dark:text-[#fdba74]' },
    { bg: 'bg-[#efebff] dark:bg-[#2e2a4d]', text: 'text-[#7c3aed] dark:text-[#c4b5fd]' },
    { bg: 'bg-[#e0f5fa] dark:bg-[#173a42]', text: 'text-[#0891b2] dark:text-[#67e8f9]' },
    { bg: 'bg-[#fde9df] dark:bg-[#42221c]', text: 'text-[#e11d48] dark:text-[#fda4af]' },
    { bg: 'bg-[#f7f5d9] dark:bg-[#3b3a12]', text: 'text-[#ca8a04] dark:text-[#fde047]' },
];
const catTint = (i) => catTints[i % catTints.length];

const navLinks = [
    { label: 'New Arrivals', href: () => route('products.new-arrivals'), dropdown: false },
    { label: 'Hot Sale', href: () => route('products.hot-sale'), dropdown: false },
];
</script>

<template>
    <header class="sticky top-0 z-40 w-full bg-gradient-to-r from-[#e3ecff] via-[#eae5ff] to-[#ffe7f0] dark:bg-[#171212] dark:bg-none border-b border-surface-container-high dark:border-[#3a302e]">
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
              
                    <span v-if="wishlistCount" class="absolute right-0 top-0 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-sale-price px-1 text-[10px] font-bold leading-none text-white">{{ wishlistCount }}</span>
                </Link>

                <NotificationDropdown />
                
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
                class="absolute left-1/2 top-full z-50 w-full max-w-7xl -translate-x-1/2 border-t border-surface-container-high bg-[#f8f9ff] px-4 py-6 shadow-lg shadow-black/5 hover:ring-1 hover:ring-[#a5b4fc]/40 dark:border-[#3a302e] dark:bg-[#241d1c] dark:hover:ring-[#5a539a]/50 md:px-6"
            >
                <div>
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-6">
                        <Link
                            v-for="(cat, i) in categories"
                            :key="cat.id"
                            :href="route('category.show', cat.slug || cat.id)"
                            class="group flex items-center gap-3 rounded-xl border border-transparent bg-white/60 p-2.5 transition-all duration-200 hover:-translate-y-0.5 hover:border-[#a5b4fc]/50 hover:bg-white hover:ring-1 hover:ring-[#a5b4fc]/40 hover:shadow-md hover:shadow-black/5 dark:bg-white/5 dark:hover:border-[#5a539a] dark:hover:bg-white/10 dark:hover:ring-[#5a539a]/50"
                            @click="catMegaOpen = false"
                        >
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl lg:h-12 lg:w-12"
                                :class="catTint(i).bg"
                            >
                                <img
                                    v-if="cat.image_url"
                                    :src="cat.image_url"
                                    :alt="cat.name"
                                    class="h-full w-full rounded-xl object-cover transition-transform duration-300 group-hover:scale-110"
                                    loading="lazy"
                                />
                                <span v-else class="material-symbols-outlined text-xl" :class="catTint(i).text" aria-hidden="true">category</span>
                            </div>
                            <span
                                class="min-w-0 truncate text-[13px] font-semibold leading-tight transition-colors"
                                :class="catTint(i).text"
                            >{{ cat.name }}</span>
                        </Link>
                    </div>
                    <Link
                        :href="route('categories.index')"
                        class="mt-4 flex w-full items-center justify-center gap-1 rounded-xl bg-gradient-to-r from-[#e3ecff] via-[#eae5ff] to-[#ffe7f0] py-2.5 text-sm font-semibold text-charcoal transition hover:brightness-97 dark:border dark:border-[#3a302e] dark:bg-none dark:text-[#cbb8b6] dark:hover:bg-[#2e2523]"
                        @click="catMegaOpen = false"
                    >
                        View All Categories
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">arrow_forward</span>
                    </Link>
                </div>
            </div>
        </Transition>

        <!-- Mobile search row -->
        <div v-show="mobileSearchOpen" class="border-t border-surface-container-high px-4 py-3 dark:border-[#3a302e] md:hidden">
            <SearchBox :autofocus="mobileSearchOpen" />
        </div>
    </header>
</template>
