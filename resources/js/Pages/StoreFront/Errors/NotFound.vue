<script setup>
import { Head, Link } from '@inertiajs/vue3'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

defineProps({
    status: { type: Number, default: 404 },
})
</script>

<template>
    <Head title="404 - Page Not Found" />

    <FrontEndMaster>
        <div class="relative flex items-center justify-center min-h-[70vh] px-4 py-12 md:py-16 overflow-hidden">
            <div class="absolute -top-24 -left-24 w-64 h-64 md:w-96 md:h-96 rounded-full bg-orange-200/40 dark:bg-orange-500/10 blur-3xl pointer-events-none" aria-hidden="true"></div>
            <div class="absolute -bottom-28 -right-20 w-72 h-72 md:w-[28rem] md:h-[28rem] rounded-full bg-indigo-200/40 dark:bg-indigo-500/10 blur-3xl pointer-events-none" aria-hidden="true"></div>

            <div class="relative text-center max-w-md mx-auto">
                <div class="relative mx-auto w-44 h-44 sm:w-56 sm:h-56 md:w-64 md:h-64 mb-6 sm:mb-8">
                    <svg viewBox="0 0 240 240" class="w-full h-full" fill="none" aria-hidden="true">
                        <circle cx="120" cy="120" r="100" class="fill-orange-100 dark:fill-orange-500/10" />
                        <circle cx="120" cy="120" r="78" stroke-width="2" stroke-dasharray="4 10" class="stroke-amber-300 dark:stroke-amber-500/40" />

                        <g class="animate-pulse">
                            <path d="M46 52l2.8 7.2L56 62l-7.2 2.8L46 72l-2.8-7.2L36 62l7.2-2.8z" class="fill-amber-400 dark:fill-amber-400/80" />
                            <path d="M196 38l2.2 5.8L204 46l-5.8 2.2L196 54l-2.2-5.8L188 46l5.8-2.2z" class="fill-indigo-400 dark:fill-indigo-400/70" />
                            <path d="M206 164l2.2 5.8 5.8 2.2-5.8 2.2L206 180l-2.2-5.8-5.8-2.2 5.8-2.2z" class="fill-rose-400 dark:fill-rose-400/70" />
                        </g>

                        <circle cx="120" cy="120" r="70" stroke-width="1.5" class="stroke-orange-300/60 dark:stroke-orange-400/30" />

                        <g class="origin-[120px_150px] -rotate-6">
                            <rect x="70" y="112" width="100" height="82" rx="14" class="fill-amber-400 dark:fill-amber-500/90" />
                            <rect x="70" y="112" width="100" height="26" rx="14" class="fill-amber-300 dark:fill-amber-400/80" />
                            <rect x="112" y="112" width="16" height="82" class="fill-amber-300/80 dark:fill-amber-300/40" />
                            <path d="M84 150l8 6 10-8 12 10 10-7 12 9" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" class="stroke-white/70 dark:stroke-amber-100/60" fill="none" />
                        </g>

                        <g class="origin-[88px_92px] rotate-12">
                            <circle cx="88" cy="92" r="27" stroke-width="9" class="stroke-gray-700 dark:stroke-gray-200" />
                            <circle cx="88" cy="92" r="18" class="fill-sky-100 dark:fill-sky-900/50" />
                            <line x1="108" y1="112" x2="130" y2="134" stroke-width="11" stroke-linecap="round" class="stroke-gray-700 dark:stroke-gray-200" />
                        </g>

                        <text x="158" y="86" text-anchor="middle" font-weight="800" font-size="44" class="fill-[#D9531E] dark:fill-orange-400">?</text>
                    </svg>
                </div>

                <p class="text-xs sm:text-sm font-semibold uppercase tracking-[0.25em] text-[#D9531E] dark:text-orange-400 mb-3">Error 404</p>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-3">Oops! Page not found</h1>
                <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mb-8 sm:mb-10 leading-relaxed">
                    The page you are looking for doesn't exist or may have been moved. Let's get you back on track.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link
                        :href="route('home')"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-gradient-to-r from-[#D9531E] to-orange-500 text-white text-sm font-semibold shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40 hover:opacity-95 active:scale-[0.98] transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Back to Home
                    </Link>
                    <Link
                        :href="route('products.index')"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-[#D9531E] hover:text-[#D9531E] dark:hover:border-orange-400 dark:hover:text-orange-400 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        Browse Products
                    </Link>
                </div>
            </div>
        </div>
    </FrontEndMaster>
</template>
