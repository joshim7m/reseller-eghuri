<script setup>
import { usePage } from '@inertiajs/vue3';

defineProps({
    instagramImages: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const settings = page.props.settings || {};
</script>

<template>
    <section class="mb-16">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-pink-500 via-purple-500 to-blue-500 shadow-lg shadow-pink-200/60 dark:shadow-pink-900/30 md:h-11 md:w-11"
                >
                    <svg
                        class="h-5 w-5 text-white md:h-6 md:w-6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <rect
                            x="2"
                            y="2"
                            width="20"
                            height="20"
                            rx="5"
                            ry="5"
                        />
                        <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" />
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                    </svg>
                </div>
                <div>
                    <h2
                        class="text-xl font-bold text-gray-800 dark:text-white md:text-2xl"
                    >
                        Follow Us on Instagram
                    </h2>
                    <div
                        class="mt-2 h-1.5 w-16 rounded-full bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500"
                    ></div>
                </div>
            </div>
            <a
                v-if="settings.instagram_handler"
                :href="'https://instagram.com/' + settings.instagram_handler"
                target="_blank"
                class="hidden items-center gap-2 rounded-full bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-pink-200/60 transition-all duration-300 hover:scale-[1.03] hover:opacity-90 active:scale-95 dark:shadow-pink-900/30 sm:inline-flex"
            >
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"
                    />
                </svg>
                @{{ settings.instagram_handler }}
            </a>
        </div>

        <div
            class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6 md:gap-4"
        >
            <template v-if="instagramImages.length">
                <a
                    v-for="img in instagramImages.slice(0, 6)"
                    :key="img.id"
                    :href="
                        settings.instagram_handler
                            ? 'https://instagram.com/' +
                              settings.instagram_handler
                            : '#'
                    "
                    :target="settings.instagram_handler ? '_blank' : '_self'"
                    class="group relative rounded-[1.6rem] border border-gray-200/80 bg-white p-1.5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-pink-200 hover:shadow-2xl hover:shadow-pink-100/70 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600 dark:hover:shadow-gray-900/60"
                >
                    <div
                        class="relative aspect-square overflow-hidden rounded-[1.2rem] bg-gradient-to-br from-pink-50 via-purple-50 to-blue-100 dark:bg-gray-900"
                    >
                        <img
                            :src="'/' + img.image"
                            :alt="img.title"
                            class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-110"
                        />
                        <div
                            class="absolute inset-0 flex items-end justify-center bg-gradient-to-t from-black/40 via-black/0 to-transparent pb-2.5 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                        >
                            <svg
                                class="h-5 w-5 text-white drop-shadow"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"
                                />
                            </svg>
                        </div>
                    </div>
                </a>
            </template>
            <template v-else>
                <div
                    v-for="i in 6"
                    :key="i"
                    class="rounded-[1.6rem] border border-gray-200/80 bg-white p-1.5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div
                        class="flex aspect-square items-center justify-center rounded-[1.2rem] bg-gradient-to-br from-pink-50 via-purple-50 to-blue-100 dark:from-gray-800 dark:to-gray-700"
                    >
                        <svg
                            class="h-8 w-8 text-gray-300 dark:text-gray-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                </div>
            </template>
        </div>

        <div
            v-if="settings.instagram_handler"
            class="mt-5 text-center sm:hidden"
        >
            <a
                :href="'https://instagram.com/' + settings.instagram_handler"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:opacity-90"
            >
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"
                    />
                </svg>
                @{{ settings.instagram_handler }}
            </a>
        </div>
    </section>
</template>
