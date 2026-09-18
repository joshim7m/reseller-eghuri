<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useWishlist } from '@/composables/useWishlist';

const page = usePage();
const settings = page.props.settings || {};
const socialMedias = page.props.socialMedias || [];
const pages = page.props.pages || [];
const auth = page.props.auth;

const wishlist = useWishlist();
wishlist.load();
const wishlistCount = computed(() => wishlist.ids.value.length);

const companyName = settings.company_name || 'Store';
const companyDescription = settings.company_description || '';
const companyEmail = settings.company_email || '';
const companyMobile = settings.company_mobile || '';
const companyAddress = settings.company_address || '';
const companyWorkingHours = settings.company_working_hours || '';
const companyLogo = settings.company_logo;

const year = new Date().getFullYear();
const openSection = ref(null);
const isDesktop = ref(false);

function toggle(section) {
    openSection.value = openSection.value === section ? null : section;
}

const mql = typeof window !== 'undefined' ? window.matchMedia('(min-width: 768px)') : null;
function checkDesktop() {
 isDesktop.value = mql ? mql.matches : false; 
}

onMounted(() => {
 checkDesktop();

 if (mql) {
mql.addEventListener('change', checkDesktop);
} 
});
onUnmounted(() => {
 if (mql) {
mql.removeEventListener('change', checkDesktop);
} 
});
</script>

<template>
    <div class="dark">
    <footer class="bg-[#19283d] border-t border-white/10">
        <div class="mx-auto max-w-7xl px-4 py-16 md:px-6">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-12 lg:gap-8">
                <div class="lg:col-span-4">
                    <Link :href="route('home')" class="inline-flex items-center gap-3">
                        <img v-if="companyLogo" :src="`/${companyLogo}`" :alt="companyName" class="h-9 w-auto dark:brightness-0 dark:invert" />
                        <span v-else class="text-xl font-bold tracking-tight text-charcoal dark:text-[#f9eeed]">{{ companyName }}</span>
                    </Link>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-on-surface-variant dark:text-[#cbb8b6]">{{ companyDescription }}</p>
                    <div v-if="socialMedias.length" class="mt-5 flex items-center gap-2.5">
                        <a
                            v-for="s in socialMedias"
                            :key="s.id"
                            :href="s.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            :title="s.name"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant transition-all duration-200 hover:bg-primary hover:text-white hover:scale-110 dark:bg-[#2e2523] dark:text-[#cbb8b6] dark:hover:bg-primary dark:hover:text-white"
                        >
                            <span v-if="s.icon_svg" v-html="s.icon_svg" class="h-4 w-4 [&>svg]:h-4 [&>svg]:w-4 [&>svg]:fill-current"></span>
                            <i v-else-if="s.icon" :class="s.icon" class="text-[16px]"></i>
                            <span v-else class="material-symbols-outlined text-[18px]" aria-hidden="true">link</span>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <button @click="toggle('information')" class="flex w-full items-center justify-between py-2 text-left md:cursor-default">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-charcoal dark:text-[#f9eeed]">Information</h3>
                        <span class="material-symbols-outlined text-[18px] text-outline transition-transform md:hidden" :class="{ 'rotate-180': openSection === 'information' }" aria-hidden="true">expand_more</span>
                    </button>
                    <div v-show="isDesktop || openSection === 'information'" class="mt-3 pb-4 md:pb-0">
                        <ul class="space-y-2.5">
                            <li v-for="p in pages" :key="p.id">
                                <Link :href="route('pages.show', p.slug)" class="text-sm text-on-surface-variant transition hover:text-charcoal hover:pl-1 dark:text-[#cbb8b6] dark:hover:text-[#f9eeed]">{{ p.title }}</Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <button @click="toggle('support')" class="flex w-full items-center justify-between py-2 text-left md:cursor-default">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-charcoal dark:text-[#f9eeed]">Support</h3>
                        <span class="material-symbols-outlined text-[18px] text-outline transition-transform md:hidden" :class="{ 'rotate-180': openSection === 'support' }" aria-hidden="true">expand_more</span>
                    </button>
                    <div v-show="isDesktop || openSection === 'support'" class="mt-3 pb-4 md:pb-0">
                        <ul class="space-y-2.5">
                            <li><a href="#" class="text-sm text-on-surface-variant transition hover:text-charcoal hover:pl-1 dark:text-[#cbb8b6] dark:hover:text-[#f9eeed]">Contact Us</a></li>
                            <li><a href="#" class="text-sm text-on-surface-variant transition hover:text-charcoal hover:pl-1 dark:text-[#cbb8b6] dark:hover:text-[#f9eeed]">Shipping &amp; Returns</a></li>
                            <li><a href="#" class="text-sm text-on-surface-variant transition hover:text-charcoal hover:pl-1 dark:text-[#cbb8b6] dark:hover:text-[#f9eeed]">FAQs</a></li>
                            <li><a href="#" class="text-sm text-on-surface-variant transition hover:text-charcoal hover:pl-1 dark:text-[#cbb8b6] dark:hover:text-[#f9eeed]">Size Guide</a></li>
                        </ul>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <h3 class="mb-5 text-sm font-semibold uppercase tracking-wider text-charcoal dark:text-[#f9eeed]">Get in Touch</h3>
                    <ul class="">
                        <li v-if="companyEmail" class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface-container dark:bg-[#2e2523] text-outline">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">email</span>
                            </span>
                            <span class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">{{ companyEmail }}</span>
                        </li>
                        <li v-if="companyMobile" class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface-container dark:bg-[#2e2523] text-outline">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">phone</span>
                            </span>
                            <span class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">{{ companyMobile }}</span>
                        </li>
                        <li v-if="companyAddress" class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-surface-container dark:bg-[#2e2523] text-outline">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">location_on</span>
                            </span>
                            <span class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">{{ companyAddress }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-5 sm:flex-row md:px-6">
                <p class="text-xs text-outline dark:text-[#cbb8b6]">&copy; {{ year }} {{ companyName }}. All rights reserved.</p>
                <div class="flex items-center gap-4 text-xs text-outline dark:text-[#cbb8b6]">
                    <template v-for="(p, i) in pages" :key="p.id">
                        <Link :href="route('pages.show', p.slug)" class="transition hover:text-charcoal dark:hover:text-[#f9eeed]">{{ p.title }}</Link>
                        <span v-if="i < pages.length - 1" class="text-outline-variant dark:text-[#3a302e]">|</span>
                    </template>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile bottom navigation -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 flex h-16 items-center justify-around border-t border-surface-container-high bg-surface-container dark:bg-[#241d1c] dark:border-[#3a302e] shadow-[0_-2px_10px_rgba(0,0,0,0.05)] md:hidden">
        <Link :href="route('home')" class="relative flex flex-col items-center gap-0.5 text-on-surface-variant transition hover:text-primary dark:text-[#cbb8b6] dark:hover:text-[#f6b7b2]">
            <span class="material-symbols-outlined text-[22px]" aria-hidden="true">home</span>
            <span class="text-[10px] font-medium">Home</span>
        </Link>
        <Link :href="route('categories.index')" class="flex flex-col items-center gap-0.5 text-on-surface-variant transition hover:text-primary dark:text-[#cbb8b6] dark:hover:text-[#f6b7b2]">
            <span class="material-symbols-outlined text-[22px]" aria-hidden="true">category</span>
            <span class="text-[10px] font-medium">Categories</span>
        </Link>
        <Link :href="route('wishlist.index')" class="relative flex flex-col items-center gap-0.5 text-on-surface-variant transition hover:text-primary dark:text-[#cbb8b6] dark:hover:text-[#f6b7b2]" aria-label="Wishlist">
            <span class="material-symbols-outlined text-[22px]" aria-hidden="true">favorite</span>
            <span class="text-[10px] font-medium">Wishlist</span>
            <span v-if="wishlistCount" class="absolute right-[calc(50%-22px)] top-0 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-sale-price px-1 text-[10px] font-bold leading-none text-white">{{ wishlistCount }}</span>
        </Link>
        <Link v-if="auth?.user" :href="route('orders.index')" class="flex flex-col items-center gap-0.5 text-on-surface-variant transition hover:text-primary dark:text-[#cbb8b6] dark:hover:text-[#f6b7b2]">
            <span class="material-symbols-outlined text-[22px]" aria-hidden="true">person</span>
            <span class="text-[10px] font-medium">Profile</span>
        </Link>
        <Link v-else :href="route('login')" class="flex flex-col items-center gap-0.5 text-on-surface-variant transition hover:text-primary dark:text-[#cbb8b6] dark:hover:text-[#f6b7b2]">
            <span class="material-symbols-outlined text-[22px]" aria-hidden="true">person</span>
            <span class="text-[10px] font-medium">Account</span>
        </Link>
    </nav>

    <div class="h-16 md:hidden"></div>
    </div>
</template>
