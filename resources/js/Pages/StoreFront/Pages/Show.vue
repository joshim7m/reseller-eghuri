<script setup>
import { Link } from '@inertiajs/vue3';
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue';

const { page } = defineProps({
    page: { type: Object, required: true },
});
</script>

<template>
    <FrontEndMaster>
        <div class="max-w-3xl mx-auto">
            <Link :href="route('home')" class="inline-flex items-center gap-1.5 text-sm font-medium text-on-surface-variant transition hover:text-primary dark:text-[#cbb8b6] dark:hover:text-[#f6b7b2] mb-6">
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                Back to Home
            </Link>

            <header class="mb-8">
                <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-charcoal dark:text-[#f9eeed] tracking-tight">{{ page.title }}</h1>
                <div class="mt-3 h-1 w-16 rounded-full bg-primary"></div>
            </header>

            <div class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-6 md:p-8">
                <div class="rich-text-content leading-relaxed text-on-surface-variant dark:text-[#cbb8b6]" v-html="page.content"></div>
            </div>
        </div>
    </FrontEndMaster>
</template>
