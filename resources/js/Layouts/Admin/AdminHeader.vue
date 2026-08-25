<script setup>
import { ref, onMounted } from 'vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { Link } from '@inertiajs/vue3'

defineEmits(['toggle-sidebar'])

const isDark = ref(localStorage.getItem('darkMode') === 'true')

function toggleDark() {
    isDark.value = !isDark.value
    localStorage.setItem('darkMode', isDark.value)
    document.documentElement.classList.toggle('dark', isDark.value)
}

onMounted(() => {
    if (isDark.value) {
        document.documentElement.classList.add('dark')
    }
})
</script>

<template>
    <header class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 h-16 flex items-center justify-between px-4 md:px-6 sticky top-0 z-40">
        <div class="flex items-center gap-4 min-w-0">
            <button @click="$emit('toggle-sidebar')" class="lg:hidden text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1 -ml-1" aria-label="Open sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <nav class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 font-inter min-w-0">
                <Link :href="route('admin.dashboard')" class="hover:text-blue-600 transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                </Link>
                <span class="text-gray-300 dark:text-gray-600">/</span>
                <span class="text-gray-700 dark:text-gray-200 font-medium truncate">Dashboard</span>
            </nav>
            <span class="sm:hidden font-bold text-base text-gray-800 dark:text-gray-100 truncate">Dashboard</span>
        </div>

        <div class="flex items-center gap-3">
            <button @click="toggleDark" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition p-1.5" aria-label="Toggle dark mode">
                <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>
            <Dropdown align="right" width="48">
                <template #trigger>
                    <span class="inline-flex rounded-md">
                        <button type="button" class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm hover:bg-blue-200 dark:hover:bg-blue-900/60 transition">
                            {{ ($page.props.auth?.user?.name || 'A').charAt(0).toUpperCase() }}
                        </button>
                    </span>
                </template>
                <template #content>
                    <DropdownLink :href="route('profile')">Profile</DropdownLink>
                    <DropdownLink :href="route('admin.logout')" method="post" as="button">Log Out</DropdownLink>
                </template>
            </Dropdown>
        </div>
    </header>
</template>
