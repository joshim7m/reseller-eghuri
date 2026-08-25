<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    categories: {
        type: Array,
        default: () => []
    }
})
</script>

<template>
    <section v-if="categories.length" class="mb-16">
        <h2 class="text-xl md:text-2xl font-bold text-gray-800 dark:text-white mb-4">Top Categories</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <Link
                v-for="category in categories" :key="category.id"
                :href="route('category.show', category.slug || category.id)"
                class="group relative rounded-xl overflow-hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md dark:hover:shadow-gray-900/50 transition"
            >
                <div class="aspect-[4/3] bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400">
                    <img
                        v-if="category.image_url"
                        :src="category.image_url"
                        :alt="category.name"
                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                    >
                    <svg v-else class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div class="p-3 text-center">
                    <h3 class="font-bold text-gray-800 dark:text-white text-sm truncate">{{ category.name }}</h3>
                    <p v-if="category.children_count" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ category.children_count }} items</p>
                </div>
            </Link>
        </div>
    </section>
</template>
