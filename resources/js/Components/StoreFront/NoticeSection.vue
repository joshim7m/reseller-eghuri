<script setup>
defineProps({
    notices: { type: Array, default: () => [] },
});
</script>

<template>
    <section v-if="notices.length > 0" class="mb-16">
        <div class="mb-6 flex items-end justify-center gap-4">
            <div class="flex items-center gap-3 ">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg shadow-amber-200/60 dark:shadow-amber-900/30 md:h-11 md:w-11"
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
                            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                        />
                    </svg>
                </div>
                <div>
                    <h2
                        class="font-bengali text-xl font-bold text-gray-800 dark:text-white md:text-2xl"
                    >
                        নোটিশ
                    </h2>
                    <p
                        class="mt-0.5 font-bengali text-xs text-gray-400 dark:text-gray-500 md:text-sm"
                    >
                        গুরুত্বপূর্ণ ঘোষণা ও তথ্য
                    </p>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-2xl space-y-3 px-0 md:space-y-4">
            <div
                v-for="notice in notices"
                :key="notice.id"
                class="group relative rounded-[2rem] border border-gray-200/80 bg-white p-2.5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-2xl hover:shadow-amber-100/70 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600 dark:hover:shadow-gray-900/60 md:p-3"
            >
                <span
                    class="absolute inset-x-10 top-0 h-1 rounded-b-full bg-gradient-to-r from-amber-400 to-orange-500 opacity-80"
                ></span>

                <div class="flex gap-3 md:gap-4">
                    <div v-if="notice.image" class="shrink-0">
                        <div
                            class="h-20 w-20 overflow-hidden rounded-[1.2rem] border border-gray-200/80 bg-gradient-to-br from-amber-50 to-orange-100 dark:border-gray-700 dark:bg-gray-900 md:h-24 md:w-24"
                        >
                            <img
                                :src="`/${notice.image}`"
                                :alt="notice.title"
                                class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-110"
                            />
                        </div>
                    </div>
                    <div class="min-w-0 p-1">
                        <h3
                            class="font-bengali text-sm font-semibold leading-relaxed text-gray-800 transition-colors group-hover:text-[#D9531E] dark:text-gray-100 dark:group-hover:text-orange-400 md:text-base"
                        >
                            {{ notice.title }}
                        </h3>
                        <p
                            class="mt-1.5 font-bengali text-xs leading-relaxed text-gray-600 dark:text-gray-400 md:text-sm"
                        >
                            {{ notice.content }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
