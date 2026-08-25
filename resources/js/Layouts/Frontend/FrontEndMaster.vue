<script setup>
import { ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import FrontendHeader from './Header.vue'
import FrontendFooter from './Footer.vue'
import QuickViewModal from '@/Components/StoreFront/QuickViewModal.vue'
import SearchOverlay from '@/Components/StoreFront/SearchOverlay.vue'
import { useWishlist } from '@/composables/useWishlist'
import { computed } from 'vue'

const page = usePage()
const settings = page.props.settings || {}
const auth = page.props.auth

const wishlist = useWishlist()
wishlist.load()
const wishlistCount = computed(() => wishlist.ids.value.length)

const mobileOpen = ref(false)
const searchOpen = ref(false)
</script>

<template>
    <Head>
        <title>{{ settings.company_name || 'Store' }}</title>
        <meta name="description" :content="settings.company_description || ''" />
        <link v-if="settings.company_favicon" rel="icon" type="image/x-icon" :href="'/' + settings.company_favicon" />
    </Head>

    <div class="min-h-screen flex flex-col bg-white dark:bg-[#171212]">
        <Teleport to="body">
            <div v-show="mobileOpen" class="fixed inset-0 z-50 bg-black/40" @click="mobileOpen = false"></div>
            <div v-show="mobileOpen" class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] bg-white dark:bg-[#171212] shadow-2xl overflow-y-auto border-r border-surface-container-high dark:border-[#3a302e]">
                <div class="relative flex items-center justify-between px-4 h-16 border-b border-surface-container-high dark:border-[#3a302e]">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <img v-if="settings.company_logo" :src="`/${settings.company_logo}`" :alt="settings.company_name || 'Store'" class="h-8 w-auto dark:brightness-0 dark:invert">
                        <span v-else class="font-bold text-lg text-charcoal dark:text-[#f9eeed] truncate">{{ settings.company_name || 'Store' }}</span>
                    </div>
                    <button @click="mobileOpen = false" class="w-8 h-8 rounded-full bg-surface-container dark:bg-[#241d1c] text-on-surface-variant hover:text-primary flex items-center justify-center transition" aria-label="Close menu">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
                    </button>
                </div>
                <nav class="px-3 py-3 space-y-1">
                    <Link :href="route('home')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                        <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-primary flex items-center justify-center shrink-0 transition group-hover:scale-105">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">home</span>
                        </span>
                        Home
                    </Link>
                    <Link :href="route('products.index')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                        <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-primary flex items-center justify-center shrink-0 transition group-hover:scale-105">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">shopping_bag</span>
                        </span>
                        All Products
                    </Link>
                    <Link :href="route('products.new-arrivals')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                        <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-primary flex items-center justify-center shrink-0 transition group-hover:scale-105">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">new_releases</span>
                        </span>
                        New Arrivals
                    </Link>
                    <Link :href="route('products.hot-sale')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-sale-price hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                        <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-sale-price flex items-center justify-center shrink-0 transition group-hover:scale-105">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">local_fire_department</span>
                        </span>
                        Hot Sale
                    </Link>

                    <Link :href="route('categories.index')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                        <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-primary flex items-center justify-center shrink-0 transition group-hover:scale-105">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">category</span>
                        </span>
                        Categories
                    </Link>

                    <div class="mx-1 h-px border-surface-container-high dark:border-[#3a302e]"></div>
                    <Link :href="route('wishlist.index')" class="group flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                        <span class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-sale-price flex items-center justify-center shrink-0 transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">favorite</span>
                            </span>
                            Wishlist
                        </span>
                        <span v-if="wishlistCount" class="flex items-center justify-center rounded-full bg-sale-price text-white text-[10px] font-bold leading-none min-w-[18px] h-[18px] px-1">{{ wishlistCount }}</span>
                    </Link>
                </nav>
                <div class="px-3 pb-4 space-y-1">
                    <div class="mx-1 h-px border-surface-container-high dark:border-[#3a302e]"></div>
                    <template v-if="auth?.user">
                        <Link :href="route('profile')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                            <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-primary flex items-center justify-center shrink-0 transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">person</span>
                            </span>
                            Profile
                        </Link>
                        <Link :href="route('orders.index')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                            <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-primary flex items-center justify-center shrink-0 transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">receipt_long</span>
                            </span>
                            Orders
                        </Link>
                        <Link v-if="auth.user.user_type === 'reseller'" :href="route('reseller-orders.index')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                            <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-primary flex items-center justify-center shrink-0 transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">inventory_2</span>
                            </span>
                            Reseller Orders
                        </Link>
                        <Link :href="route('logout')" method="post" as="button" class="group flex items-center gap-3 w-full text-left px-3 py-2.5 rounded-xl text-sm font-medium text-sale-price hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition">
                            <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-sale-price flex items-center justify-center shrink-0 transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">logout</span>
                            </span>
                            Logout
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                            <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-primary flex items-center justify-center shrink-0 transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">login</span>
                            </span>
                            Login
                        </Link>
                        <Link :href="route('register')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-on-surface dark:text-[#cbb8b6] hover:bg-surface-container-low dark:hover:bg-[#241d1c] transition" @click="mobileOpen = false">
                            <span class="w-8 h-8 rounded-lg bg-surface-container dark:bg-[#2e2523] text-primary flex items-center justify-center shrink-0 transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">person_add</span>
                            </span>
                            Register
                        </Link>
                    </template>
                </div>
            </div>

            <QuickViewModal />

            <SearchOverlay :open="searchOpen" @close="searchOpen = false" />
        </Teleport>

        <FrontendHeader @open-search="searchOpen = true" @open-mobile="mobileOpen = true" />

        <main class="flex-1 max-w-7xl w-full mx-auto px-4 md:px-6 py-6 md:py-8">
            <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm font-inter">{{ $page.props.flash.success }}</div>
            <div v-if="$page.props.flash?.error" class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm font-inter">{{ $page.props.flash.error }}</div>
            <slot />
        </main>

        <FrontendFooter />

        <a
            v-if="settings.whatsapp_number"
            :href="`https://wa.me/${settings.whatsapp_number.replace(/[^0-9]/g, '')}`"
            target="_blank"
            rel="noopener noreferrer"
            class="fixed bottom-20 right-4 md:bottom-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition hover:scale-110 hover:shadow-xl"
            aria-label="Chat on WhatsApp"
        >
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
        </a>
    </div>
</template>
