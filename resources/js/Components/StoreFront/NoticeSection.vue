<script setup>
import { computed } from 'vue';

const props = defineProps({
    notices: { type: Array, default: () => [] },
});

const formatDate = (date) => {
    if (!date) return '';

    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const latestNotice = computed(() => props.notices[0] || null);
const restNotices = computed(() => props.notices.slice(1));
</script>

<template>
    <section v-if="notices.length" role="region" aria-label="Notices">
        <div class="mb-2 text-center">
            <h2 class="flex items-center justify-center gap-2 text-xl font-bold text-charcoal dark:text-[#f9eeed] md:text-2xl">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/15 text-primary dark:bg-[#f6b7b2]/15 dark:text-[#f6b7b2]">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">notifications_active</span>
                </span>
                Notice
            </h2>
            <div class="mx-auto mt-3 h-1 w-12 rounded-full bg-primary"></div>
        </div>

        <div class="mx-auto mt-8 flex max-w-3xl flex-col gap-4 px-2 sm:px-0">
            <!-- Latest / featured notice -->
            <div
                v-if="latestNotice"
                class="group flex flex-col gap-4 rounded-2xl border border-primary/30 bg-[#fff7f6] p-5 transition-all duration-200 hover:border-primary/60 hover:shadow-md sm:flex-row sm:items-center dark:border-[#f6b7b2]/40 dark:bg-[#2a211f] dark:hover:border-[#f6b7b2]/60"
            >
                <div v-if="latestNotice.image" class="shrink-0 overflow-hidden rounded-xl">
                    <img
                        :src="`/${latestNotice.image}`"
                        :alt="latestNotice.title"
                        class="h-28 w-full object-cover transition-transform duration-500 group-hover:scale-105 sm:h-24 sm:w-24"
                        loading="lazy"
                    />
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-primary px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Latest</span>
                        <span v-if="formatDate(latestNotice.created_at)" class="text-[11px] text-on-surface-variant dark:text-[#cbb8b6]">{{ formatDate(latestNotice.created_at) }}</span>
                    </div>
                    <h3 class="mt-2 font-bengali text-base font-bold leading-relaxed text-charcoal dark:text-[#f9eeed]">
                        {{ latestNotice.title }}
                    </h3>
                    <p class="mt-1 font-bengali text-xs leading-relaxed text-on-surface-variant dark:text-[#cbb8b6] md:text-sm">
                        {{ latestNotice.content }}
                    </p>
                </div>
            </div>

            <!-- Older notices -->
            <div
                v-for="notice in restNotices"
                :key="notice.id"
                class="group flex items-center gap-4 rounded-2xl border border-surface-container-high bg-white p-4 transition-all duration-200 hover:border-primary/40 dark:border-[#3a302e] dark:bg-[#241d1c] dark:hover:border-[#f6b7b2]/30"
            >
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-surface-container-high text-on-surface-variant dark:bg-[#3a302e]"
                    aria-hidden="true"
                >
                    <span class="material-symbols-outlined text-[20px]">campaign</span>
                </span>
                <div class="min-w-0">
                    <h3 class="font-bengali text-sm font-semibold leading-relaxed text-charcoal transition group-hover:text-primary dark:text-[#f9eeed] dark:group-hover:text-[#f6b7b2]">
                        {{ notice.title }}
                    </h3>
                    <p class="mt-1 font-bengali text-xs leading-relaxed text-on-surface-variant dark:text-[#cbb8b6]">
                        {{ notice.content }}
                    </p>
                    <span v-if="formatDate(notice.created_at)" class="mt-1.5 block text-[11px] text-outline dark:text-[#3a302e]">{{ formatDate(notice.created_at) }}</span>
                </div>
            </div>
        </div>
    </section>
</template>
