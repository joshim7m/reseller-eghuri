<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useNotifications } from '@/composables/useNotifications';

const page = usePage();
const auth = page.props.auth;

const { notifications, unreadCount, loading, open, toggle, close, fetchNotifications } = useNotifications();

const dropdownEl = ref(null);

function isPositive(notification) {
    const status = notification.data?.status

    return ['accepted', 'completed', 'in_stock'].includes(status)
}

function timeAgo(dateStr) {    const now = new Date();
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

function onClickOutside(e) {
    if (dropdownEl.value && !dropdownEl.value.contains(e.target)) {
        close();
    }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));

onMounted(() => {
    if (auth?.user) {
        fetchNotifications();
    }
});
</script>

<template>
    <div v-if="auth?.user" ref="dropdownEl" class="relative">
        <button
            @click.stop="toggle"
            class="relative p-1.5 text-charcoal transition hover:text-primary dark:text-[#f9eeed] dark:hover:text-[#f6b7b2]"
            aria-label="Notifications"
        >
            <span class="material-symbols-outlined text-[20px]" aria-hidden="true">notifications</span>
            <span
                v-if="unreadCount > 0"
                class="absolute right-0 top-0 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-sale-price px-1 text-[10px] font-bold leading-none text-white"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute right-0 top-full z-50 mt-2 w-80 rounded-xl border border-surface-container-high bg-white shadow-lg dark:border-[#3a302e] dark:bg-[#241d1c]"
            >
                <div class="flex items-center justify-between border-b border-surface-container-high px-4 py-3 dark:border-[#3a302e]">
                    <h3 class="text-sm font-semibold text-charcoal dark:text-[#f9eeed]">Notifications</h3>
                    <button
                        v-if="unreadCount > 0"
                        @click.stop="useNotifications().markAllRead()"
                        class="text-xs font-medium text-primary hover:underline dark:text-[#f6b7b2]"
                    >
                        Mark all read
                    </button>
                </div>

                <div class="notification-scrollbar max-h-80 overflow-y-auto overscroll-contain">
                    <div v-if="loading" class="space-y-3 px-4 py-4">
                        <div v-for="i in 3" :key="i" class="flex animate-pulse items-center gap-3">
                            <div class="h-10 w-10 shrink-0 rounded-lg bg-surface-container dark:bg-[#2e2523]"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-4/5 rounded bg-surface-container dark:bg-[#2e2523]"></div>
                                <div class="h-3 w-1/3 rounded bg-surface-container dark:bg-[#2e2523]"></div>
                            </div>
                        </div>
                    </div>

                    <template v-else-if="notifications.length">
                        <Link
                            v-for="notification in notifications"
                            :key="notification.id"
                            :href="notification.data.link"
                            class="flex items-start gap-3 px-4 py-3 transition hover:bg-surface-container-low dark:hover:bg-[#2e2523]"
                            @click="close"
                        >
                            <div class="relative shrink-0">
                                <img
                                    v-if="notification.data.product_image"
                                    :src="notification.data.product_image"
                                    :alt="notification.data.product_title"
                                    class="h-10 w-10 rounded-lg object-cover"
                                    loading="lazy"
                                />
                                <div
                                    v-else
                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-container text-outline-variant dark:bg-[#2e2523] dark:text-[#3a302e]"
                                >
                                    <span class="material-symbols-outlined text-lg" aria-hidden="true">
                                        {{ notification.data.icon || 'inventory_2' }}
                                    </span>
                                </div>
                                <span
                                    v-if="!notification.read_at"
                                    class="absolute -left-1 -top-1 h-2.5 w-2.5 rounded-full border-2 border-white dark:border-[#241d1c]"
                                    :class="isPositive(notification) ? 'bg-green-500' : 'bg-red-500'"
                                ></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-1 text-sm font-medium text-charcoal dark:text-[#f9eeed]">
                                    {{ notification.data.title || notification.data.product_title }}
                                </p>
                                <p
                                    v-if="notification.data.message"
                                    class="mt-0.5 line-clamp-2 text-xs text-outline dark:text-[#cbb8b6]"
                                >
                                    {{ notification.data.message }}
                                </p>
                                <p
                                    v-else
                                    class="mt-0.5 text-xs"
                                    :class="isPositive(notification) ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'"
                                >
                                    {{ notification.data.status === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                                </p>
                                <p class="mt-0.5 text-[11px] text-outline dark:text-[#cbb8b6]">
                                    {{ timeAgo(notification.created_at) }}
                                </p>
                            </div>
                        </Link>
                    </template>

                    <div v-else class="px-4 py-8 text-center">
                        <span class="material-symbols-outlined text-3xl text-outline-variant dark:text-[#3a302e]" aria-hidden="true">notifications_none</span>
                        <p class="mt-2 text-sm text-outline dark:text-[#cbb8b6]">No notifications yet</p>
                    </div>
                </div>

                <Link
                    :href="route('notifications.page')"
                    class="block border-t border-surface-container-high px-4 py-2.5 text-center text-sm font-medium text-primary transition hover:bg-surface-container-low dark:border-[#3a302e] dark:text-[#f6b7b2] dark:hover:bg-[#2e2523]"
                    @click="close"
                >
                    View All
                </Link>
            </div>
        </Transition>
    </div>
</template>
