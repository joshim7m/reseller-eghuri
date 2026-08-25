<script setup>
import { ref } from 'vue';

const { faqs } = defineProps({
    faqs: { type: Array, default: () => [] },
});

const openIndex = ref(null);

function toggle(index) {
    openIndex.value = openIndex.value === index ? null : index;
}
</script>

<template>
    <section class="mb-16">
        <div class="mb-6 flex items-end justify-center gap-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 shadow-lg shadow-sky-200/60 dark:shadow-sky-900/30 md:h-11 md:w-11"
                >
                    <svg
                        class="h-5 w-5 text-white md:h-6 md:w-6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                        />
                    </svg>
                </div>
                <div>
                    <h2
                        class="font-bengali text-xl font-bold text-gray-800 dark:text-white md:text-2xl"
                    >
                        সচরাচর জিজ্ঞাসা
                    </h2>
                    <p
                        class="mt-0.5 font-bengali text-xs text-gray-400 dark:text-gray-500 md:text-sm"
                    >
                        আপনার প্রশ্নের উত্তর খুঁজুন
                    </p>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-2xl space-y-2.5 px-0 md:space-y-3">
            <div
                v-for="(faq, i) in faqs"
                :key="i"
                class="group relative rounded-[2rem] border border-gray-200/80 bg-white p-1.5 shadow-sm transition-all duration-300 hover:border-sky-200 hover:shadow-xl hover:shadow-sky-100/70 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600 dark:hover:shadow-gray-900/50 md:p-2"
                :class="{
                    'border-sky-200 shadow-lg dark:border-gray-600 dark:shadow-gray-900/40':
                        openIndex === i,
                }"
            >
                <span
                    class="absolute inset-x-10 top-0 h-1 rounded-b-full bg-gradient-to-r from-sky-500 to-indigo-600 opacity-70"
                ></span>

                <button
                    @click="toggle(i)"
                    class="flex w-full items-center justify-between gap-3 rounded-[1.6rem] px-3.5 py-3 text-left transition md:px-4"
                    :class="
                        openIndex === i
                            ? 'bg-sky-50 dark:bg-sky-900/20'
                            : 'hover:bg-gray-50 dark:hover:bg-gray-700/40'
                    "
                >
                    <span
                        class="pr-2 font-bengali text-sm font-medium leading-relaxed text-gray-800 dark:text-gray-100 md:text-base"
                        >{{ faq.question }}</span
                    >
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full transition-all duration-300 md:h-8 md:w-8"
                        :class="
                            openIndex === i
                                ? 'rotate-180 bg-gradient-to-br from-sky-500 to-indigo-600 text-white'
                                : 'bg-gray-100 text-gray-400 group-hover:bg-sky-100 group-hover:text-sky-600 dark:bg-gray-700/60 dark:text-gray-500 dark:group-hover:bg-sky-900/30 dark:group-hover:text-sky-400'
                        "
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </span>
                </button>
                <div
                    class="overflow-hidden transition-all duration-300 ease-in-out"
                    :class="
                        openIndex === i
                            ? 'max-h-96 opacity-100'
                            : 'max-h-0 opacity-0'
                    "
                >
                    <p
                        class="px-4 pb-3 font-bengali text-xs leading-relaxed text-gray-600 dark:text-gray-400 md:px-5 md:pb-4 md:text-sm"
                    >
                        {{ faq.answer }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
