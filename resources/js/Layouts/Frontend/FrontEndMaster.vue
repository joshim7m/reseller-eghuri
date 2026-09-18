<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import QuickViewModal from '@/Components/StoreFront/QuickViewModal.vue'
import SearchOverlay from '@/Components/StoreFront/SearchOverlay.vue'
import { useWishlist } from '@/composables/useWishlist'
import FrontendFooter from './Footer.vue'
import FrontendHeader from './Header.vue'

const page = usePage()
const settings = page.props.settings || {}
const auth = page.props.auth
const seo = page.props.seo || {}

const wishlist = useWishlist()
wishlist.load()
const wishlistCount = computed(() => wishlist.ids.value.length)

const mobileOpen = ref(false)
const searchOpen = ref(false)

const toasts = ref([])
let toastId = 0

function dismissToast(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) {
            return
        }

        for (const type of ['success', 'error']) {
            const text = flash[type]

            if (text) {
                const id = ++toastId
                toasts.value.push({ id, type, text })
                window.setTimeout(() => dismissToast(id), 5000)
            }
        }
    },
    { immediate: true },
)

const siteName = settings.company_name || 'Store'
const suffix = settings.seo_title_suffix || ''
const rawTitle = seo.title || siteName
const title = rawTitle.includes(siteName) ? rawTitle : (suffix && rawTitle ? `${rawTitle} ${suffix}` : rawTitle)
const description = seo.description || settings.company_description || ''
const keywords = seo.keywords || settings.seo_keywords || ''
const canonical = seo.canonical || (typeof window !== 'undefined' ? window.location.origin + window.location.pathname : '')
const twitterHandle = seo.twitter_handle || settings.seo_twitter_handle || ''
const robotsMeta = seo.robots || null

function toAbsolute(path) {
    if (!path) {
        return null
    }

    if (/^https?:\/\//i.test(path)) {
        return path
    }

    if (typeof window === 'undefined') {
        return path
    }

    return window.location.origin + (path.startsWith('/') ? '' : '/') + path
}

const ogImage = computed(() => toAbsolute(seo.og_image || settings.company_logo))

function isEnabled(value) {
    return value !== '0' && value !== 0 && value !== false && value !== null && value !== undefined
}

const organizationJsonLd = computed(() => {
    if (!isEnabled(settings.seo_schema_org)) {
        return null
    }

    const org = {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: siteName,
        url: typeof window !== 'undefined' ? window.location.origin : '',
        logo: toAbsolute(settings.company_logo),
        description: settings.company_description || undefined,
    }

    if (settings.company_email) {
        org.email = settings.company_email
    }
    if (settings.company_mobile) {
        org.telephone = settings.company_mobile
    }
    if (settings.company_address) {
        org.address = {
            '@type': 'PostalAddress',
            streetAddress: settings.company_address,
            addressCountry: 'BD',
        }
    }
    if (settings.whatsapp_number) {
        org.contactPoint = {
            '@type': 'ContactPoint',
            telephone: settings.whatsapp_number,
            contactType: 'customer service',
            areaServed: 'BD',
        }
    }

    return JSON.stringify(org)
})

const websiteJsonLd = computed(() => {
    if (!isEnabled(settings.seo_schema_website)) {
        return null
    }

    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: siteName,
        url: typeof window !== 'undefined' ? window.location.origin : '',
        description: settings.company_description || undefined,
    })
})

function productJsonLd(schema) {
    const product = schema.product || {}
    const canonicalUrl = seo.canonical || (typeof window !== 'undefined' ? window.location.origin + window.location.pathname : '')

    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.name,
        image: toAbsolute(product.image),
        description: product.description || undefined,
        sku: product.sku || undefined,
        brand: { '@type': 'Brand', name: siteName },
        offers: {
            '@type': 'Offer',
            priceCurrency: product.currency || 'BDT',
            price: product.price,
            availability: product.availability === 'InStock' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            url: canonicalUrl,
        },
    })
}

function itemListJsonLd(schema) {
    const items = (schema.products || []).slice(0, 30).map((product, index) => ({
        '@type': 'ListItem',
        position: index + 1,
        name: product.title,
        url: (typeof window !== 'undefined' ? window.location.origin : '') + `/product/${product.slug}`,
        image: toAbsolute(product.image_url || product.image),
    }))

    if (!items.length) {
        return null
    }

    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        name: schema.heading || seo.title || rawTitle,
        itemListElement: items,
    })
}

function breadcrumbJsonLd(schema) {
    const crumbs = schema.breadcrumbs || []

    if (!crumbs.length) {
        return null
    }

    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: crumbs.map((crumb, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            name: crumb.name,
            item: crumb.url,
        })),
    })
}

const schemaScripts = computed(() => {
    const scripts = []

    if (organizationJsonLd.value) {
        scripts.push(organizationJsonLd.value)
    }
    if (websiteJsonLd.value) {
        scripts.push(websiteJsonLd.value)
    }

    const pageSchema = seo.schema
    if (pageSchema) {
        if (pageSchema.type === 'product') {
            scripts.push(productJsonLd(pageSchema))

            const breadcrumb = breadcrumbJsonLd(pageSchema)
            if (breadcrumb) {
                scripts.push(breadcrumb)
            }
        } else if (pageSchema.type === 'item_list') {
            const itemList = itemListJsonLd(pageSchema)
            if (itemList) {
                scripts.push(itemList)
            }

            const breadcrumb = breadcrumbJsonLd(pageSchema)
            if (breadcrumb) {
                scripts.push(breadcrumb)
            }
        }
    }

    return scripts
})
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta v-if="description" name="description" :content="description" />
        <meta v-if="keywords" name="keywords" :content="keywords" />
        <link v-if="canonical" rel="canonical" :href="canonical" />
        <meta v-if="robotsMeta" name="robots" :content="robotsMeta" />

        <meta property="og:site_name" :content="siteName" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:url" :content="canonical" />
        <meta property="og:type" :content="seo.og_type || 'website'" />
        <meta v-if="ogImage" property="og:image" :content="ogImage" />
        <meta property="og:locale" content="en_US" />
        <meta property="og:locale:alternate" content="bn_BD" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta v-if="ogImage" name="twitter:image" :content="ogImage" />
        <meta v-if="twitterHandle" name="twitter:site" :content="twitterHandle" />

        <component :is="'script'" v-for="(schema, index) in schemaScripts" :key="index" type="application/ld+json" v-html="schema"></component>

        <link v-if="settings.company_favicon" rel="icon" type="image/x-icon" :href="'/' + settings.company_favicon" />
    </Head>

    <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-[#171212]">
        <Teleport to="body">
            <div v-show="mobileOpen" class="fixed inset-0 z-50 bg-black/40" @click="mobileOpen = false"></div>
            <div v-show="mobileOpen" class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] bg-white dark:bg-[#171212] bg-gradient-to-b from-[#e3ecff] via-white/40 to-[#ffe7f0] dark:bg-none shadow-2xl overflow-y-auto">
                <div class="relative flex items-center justify-between px-4 h-16 border-b border-white/60 dark:border-[#3a302e]">
                    <Link :href="route('home')" class="flex items-center gap-2.5 min-w-0" @click="mobileOpen = false">
                        <img v-if="settings.company_logo" :src="`/${settings.company_logo}`" :alt="settings.company_name || 'Store'" class="h-8 w-auto dark:brightness-0 dark:invert">
                        <span v-else class="font-bold text-lg text-charcoal dark:text-[#f9eeed] truncate">{{ settings.company_name || 'Store' }}</span>
                    </Link>
                    <button @click="mobileOpen = false" class="w-8 h-8 rounded-full bg-white dark:bg-[#241d1c] text-on-surface-variant hover:text-primary flex items-center justify-center transition dark:text-[#cbb8b6]" aria-label="Close menu">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
                    </button>
                </div>

                <nav class="px-3 py-4">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant/70 dark:text-[#cbb8b6]/70">Browse</p>
                    <div class="space-y-1">
                        <Link :href="route('products.index')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-charcoal dark:text-[#f9eeed] transition hover:bg-white/80 dark:hover:bg-[#241d1c]" @click="mobileOpen = false">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#24314d]/10 text-[#2563eb] dark:bg-[#24314d] dark:text-[#93c5fd] transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">shopping_bag</span>
                            </span>
                            All Products
                        </Link>
                        <Link :href="route('products.new-arrivals')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-charcoal dark:text-[#f9eeed] transition hover:bg-white/80 dark:hover:bg-[#241d1c]" @click="mobileOpen = false">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#e0f5fa] text-[#0891b2] dark:bg-[#173a42] dark:text-[#67e8f9] transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">new_releases</span>
                            </span>
                            New Arrivals
                        </Link>
                        <Link :href="route('products.hot-sale')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-charcoal dark:text-[#f9eeed] transition hover:bg-white/80 dark:hover:bg-[#241d1c]" @click="mobileOpen = false">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#fde9df] text-[#e11d48] dark:bg-[#42221c] dark:text-[#fda4af] transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">local_fire_department</span>
                            </span>
                            Hot Sale
                        </Link>
                        <Link :href="route('categories.index')" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-charcoal dark:text-[#f9eeed] transition hover:bg-white/80 dark:hover:bg-[#241d1c]" @click="mobileOpen = false">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#efebff] text-[#7c3aed] dark:bg-[#2e2a4d] dark:text-[#c4b5fd] transition group-hover:scale-105">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">category</span>
                            </span>
                            Categories
                        </Link>
                        <Link :href="route('wishlist.index')" class="group flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-charcoal dark:text-[#f9eeed] transition hover:bg-white/80 dark:hover:bg-[#241d1c]" @click="mobileOpen = false">
                            <span class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#fbe7f2] text-[#db2777] dark:bg-[#42223a] dark:text-[#f9a8d4] transition group-hover:scale-105">
                                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">favorite</span>
                                </span>
                                Wishlist
                            </span>
                            <span v-if="wishlistCount" class="flex items-center justify-center rounded-full bg-sale-price text-white text-[10px] font-bold leading-none min-w-[18px] h-[18px] px-1">{{ wishlistCount }}</span>
                        </Link>
                    </div>
                </nav>

                <div class="px-3 pb-6">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant/70 dark:text-[#cbb8b6]/70">Account</p>
                    <template v-if="auth?.user">
                        <div class="rounded-2xl bg-white dark:bg-[#241d1c] border border-white dark:border-[#3a302e] p-2 space-y-1 shadow-sm">
                            <Link :href="route('profile')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-charcoal dark:text-[#f9eeed] transition hover:bg-surface-container-low dark:hover:bg-[#2e2523]" @click="mobileOpen = false">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">
                                    {{ (auth.user.name || 'U').charAt(0).toUpperCase() }}
                                </span>
                                Profile
                            </Link>
                            <Link :href="route('orders.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-charcoal dark:text-[#f9eeed] transition hover:bg-surface-container-low dark:hover:bg-[#2e2523]" @click="mobileOpen = false">
                                <span class="material-symbols-outlined text-[18px] text-on-surface-variant dark:text-[#cbb8b6]" aria-hidden="true">receipt_long</span>
                                Orders
                            </Link>
                            <Link v-if="auth.user.user_type === 'reseller'" :href="route('reseller-orders.index')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-charcoal dark:text-[#f9eeed] transition hover:bg-surface-container-low dark:hover:bg-[#2e2523]" @click="mobileOpen = false">
                                <span class="material-symbols-outlined text-[18px] text-on-surface-variant dark:text-[#cbb8b6]" aria-hidden="true">inventory_2</span>
                                Reseller Orders
                            </Link>
                            <Link :href="route('logout')" method="post" as="button" class="flex items-center gap-3 w-full text-left px-3 py-2.5 rounded-xl text-sm font-medium text-sale-price transition hover:bg-sale-price/10">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">logout</span>
                                Logout
                            </Link>
                        </div>
                    </template>
                    <template v-else>
                        <div class="rounded-2xl bg-white dark:bg-[#241d1c] border border-white dark:border-[#3a302e] p-2 space-y-1 shadow-sm">
                            <Link :href="route('login')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-charcoal dark:text-[#f9eeed] transition hover:bg-surface-container-low dark:hover:bg-[#2e2523]" @click="mobileOpen = false">
                                <span class="material-symbols-outlined text-[18px] text-on-surface-variant dark:text-[#cbb8b6]" aria-hidden="true">login</span>
                                Login
                            </Link>
                            <Link :href="route('register')" class="flex w-full items-center justify-center rounded-xl bg-charcoal px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-primary dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]" @click="mobileOpen = false">
                                Create Account
                            </Link>
                        </div>
                    </template>
                </div>
            </div>

            <QuickViewModal />

            <SearchOverlay :open="searchOpen" @close="searchOpen = false" />
        </Teleport>

        <FrontendHeader @open-search="searchOpen = true" @open-mobile="mobileOpen = true" />

        <div v-if="toasts.length" class="pointer-events-none fixed inset-x-0 top-20 z-40 px-4 md:px-6">
            <div class="mx-auto flex max-w-7xl flex-col items-end gap-2">
                <TransitionGroup
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div
                        v-for="toast in toasts"
                        :key="toast.id"
                        role="alert"
                        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border p-4 shadow-lg"
                        :class="toast.type === 'success'
                            ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/90 dark:text-green-300'
                            : 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/90 dark:text-red-300'"
                    >
                        <span class="material-symbols-outlined mt-0.5 text-[20px] shrink-0" aria-hidden="true">
                            {{ toast.type === 'success' ? 'check_circle' : 'error' }}
                        </span>
                        <p class="min-w-0 flex-1 text-sm break-words">{{ toast.text }}</p>
                        <button
                            type="button"
                            @click="dismissToast(toast.id)"
                            class="-m-1 shrink-0 rounded-lg p-1 transition hover:bg-black/5 dark:hover:bg-white/10"
                            aria-label="Dismiss notification"
                        >
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">close</span>
                        </button>
                    </div>
                </TransitionGroup>
            </div>
        </div>

        <main class="flex-1 max-w-7xl w-full mx-auto px-4 md:px-6 py-6 md:py-8">
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
