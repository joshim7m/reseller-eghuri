<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineProps({
    images: { type: Array, required: true },
})

function destroyImage(id) {
    if (confirm('Delete this Instagram image?')) {
        router.delete(route('admin.instagram-images.destroy', id))
    }
}
</script>

<template>
    <Head title="Instagram Images" />

    <AdminMaster>
        <div class="space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Instagram Images</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage images displayed on the Instagram section</p>
                </div>
                <Link :href="route('admin.instagram-images.create')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Image
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div v-for="img in images" :key="img.id" class="group relative bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                    <div class="aspect-square overflow-hidden">
                        <img :src="'/' + img.image" :alt="img.title" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" />
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ img.title }}</p>
                    </div>
                    <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                        <Link :href="route('admin.instagram-images.edit', img.id)" class="w-8 h-8 bg-white dark:bg-gray-800 rounded-full shadow flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </Link>
                        <button @click="destroyImage(img.id)" class="w-8 h-8 bg-white dark:bg-gray-800 rounded-full shadow flex items-center justify-center text-red-600 hover:bg-red-600 hover:text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
                <div v-if="images.length === 0" class="col-span-full py-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                    <p>No Instagram images yet.</p>
                    <Link :href="route('admin.instagram-images.create')" class="text-blue-600 hover:text-blue-500 font-medium">Add your first image</Link>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
