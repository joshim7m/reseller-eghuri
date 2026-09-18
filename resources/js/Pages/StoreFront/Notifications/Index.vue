<script setup>
import { Head, Link } from '@inertiajs/vue3';
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue';

defineProps({
    notifications: { type: Object, default: () => ({ data: [] }) },
});

function timeAgo(dateStr) {
    const now = new Date();
    const date = new Date(dateStr);
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) {
return 'just now';
}

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
return `${minutes}m ago`;
}

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
return `${hours}h ago`;
}

    const days = Math.floor(hours / 24);

    return `${days}d ago`;
}
</script>

<template>
    <Head title="Notifications" />

    <FrontEndMaster>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white md:text-3xl">Notifications</h1>
        </div>

        <div v-if="notifications.data && notifications.data.length" class="space-y-3">
            <Link
                v-for="notification in notifications.data"
                :key="notification.id"
                :href="notification.data.link"
                class="flex items-start gap-4 rounded-xl border border-gray-200 bg-white p-4 transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="relative shrink-0">
                    <img
                        v-if="notification.data.product_image"
                        :src="notification.data.product_image"
                        :alt="notification.data.product_title"
                        class="h-12 w-12 rounded-lg object-cover"
                        loading="lazy"
                    />
                    <div
                        v-else
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 text-gray-300 dark:bg-gray-700 dark:text-gray-600"
                    >
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">{{ notification.data.icon || 'inventory_2' }}</span>
                    </div>
                    <span
                        class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white dark:border-gray-800"
                        :class="['accepted', 'completed', 'in_stock'].includes(notification.data.status) ? 'bg-green-500' : 'bg-red-500'"
                    >
                        <span v-if="['accepted', 'completed', 'in_stock'].includes(notification.data.status)" class="material-symbols-outlined text-[12px] text-white" aria-hidden="true">check</span>
                        <span v-else class="material-symbols-outlined text-[12px] text-white" aria-hidden="true">close</span>
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ notification.data.title || notification.data.product_title }}
                    </p>
                    <p
                        v-if="notification.data.message"
                        class="mt-1 text-sm text-gray-600 dark:text-gray-300"
                    >
                        {{ notification.data.message }}
                    </p>
                    <p
                        v-else
                        class="mt-1 text-sm font-medium"
                        :class="notification.data.status === 'in_stock' ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'"
                    >
                        {{ notification.data.status === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        {{ timeAgo(notification.created_at) }}
                    </p>
                </div>
            </Link>
        </div>

        <div v-else class="py-16 text-center">
            <span class="material-symbols-outlined text-5xl text-gray-300 dark:text-gray-600" aria-hidden="true">notifications_none</span>
            <p class="mt-4 text-gray-500 dark:text-gray-400">No notifications in the last week</p>
            <Link :href="route('products.index')" class="mt-4 inline-block rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                Browse Products
            </Link>
        </div>

        <div v-if="notifications.links && notifications.links.length > 3" class="mt-6 flex justify-center gap-1">
            <Link
                v-for="(link, i) in notifications.links"
                :key="i"
                :href="link.url || '#'"
                v-html="link.label"
                class="rounded-lg px-3 py-1.5 text-sm transition"
                :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
                :preserve-scroll="true"
            />
        </div>
    </FrontEndMaster>
</template>
