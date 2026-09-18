<script setup>
import { ref } from 'vue';

const { faqs } = defineProps({
    faqs: { type: Array, default: () => [] },
});

const openIndex = ref(0);

function toggle(index) {
    openIndex.value = openIndex.value === index ? null : index;
}
</script>

<template>
    <section v-if="faqs.length" role="region" aria-label="FAQs">
        <div class="mb-2 text-center">
            <h2 class="text-xl font-bold text-charcoal dark:text-[#f9eeed] md:text-2xl">FAQ</h2>
            <div class="mx-auto mt-3 flex h-1 w-12 items-center justify-center gap-1">
                <span class="h-1 w-12 rounded-full bg-primary"></span>
            </div>
            <p class="mt-4 text-sm text-on-surface-variant dark:text-[#cbb8b6]">
                Answers to your common questions
            </p>
        </div>

        <div class="mx-auto max-w-3xl space-y-3 px-2 py-10 sm:px-0">
            <div
                v-for="(faq, i) in faqs"
                :key="i"
                class="overflow-hidden rounded-2xl border transition-all duration-200"
                :class="
                    openIndex === i
                        ? 'border-primary/60 bg-[#fff7f6] dark:border-[#f6b7b2]/50 dark:bg-[#2a211f]'
                        : 'border-surface-container-high bg-white hover:border-primary/40 dark:border-[#3a302e] dark:bg-[#241d1c] dark:hover:border-[#f6b7b2]/30'
                "
            >
                <button
                    @click="toggle(i)"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                    aria-expanded="openIndex === i"
                >
                    <span class="flex min-w-0 items-center gap-3">
                        <span
                            class="hidden h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold sm:flex"
                            :class="
                                openIndex === i
                                    ? 'bg-primary text-white'
                                    : 'bg-surface-container-high text-on-surface-variant dark:bg-[#3a302e] dark:text-[#cbb8b6]'
                            "
                        >{{ i + 1 }}</span>
                        <span
                            class="font-bengali text-sm font-semibold leading-relaxed text-charcoal dark:text-[#f9eeed] md:text-base"
                        >{{ faq.question }}</span>
                    </span>
                    <span
                        class="material-symbols-outlined shrink-0 text-[22px] transition-transform duration-300"
                        :class="
                            openIndex === i
                                ? 'rotate-180 text-primary dark:text-[#f6b7b2]'
                                : 'text-outline-variant dark:text-[#3a302e]'
                        "
                        aria-hidden="true"
                    >expand_more</span>
                </button>

                <div
                    class="overflow-hidden transition-all duration-300 ease-in-out"
                    :class="openIndex === i ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'"
                >
                    <p
                        class="border-t border-outline-variant/60 px-5 pb-4 pt-3 font-bengali text-xs leading-relaxed text-on-surface-variant dark:border-[#3a302e] dark:text-[#cbb8b6] md:text-sm"
                    >
                        {{ faq.answer }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
