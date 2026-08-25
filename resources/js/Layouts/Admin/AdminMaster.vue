<script setup>
import { ref, onMounted } from 'vue'
import AdminSidebar from './AdminSidebar.vue'
import AdminHeader from './AdminHeader.vue'
import { Head, usePage } from '@inertiajs/vue3'

const page = usePage()
const sidebarOpen = ref(false)
const dismissed = ref(false)

const settings = page.props.settings || {}

onMounted(() => {
    if (localStorage.getItem('darkMode') === 'true') {
        document.documentElement.classList.add('dark')
    }
})

function dismiss() {
    dismissed.value = true
}
</script>

<template>
    <Head title="Admin">
        <link v-if="settings.company_favicon" rel="icon" type="image/x-icon" :href="'/' + settings.company_favicon" />
    </Head>

    <div class="min-h-screen flex bg-gray-100 dark:bg-gray-950">
        <AdminSidebar
            :settings="settings"
            :sidebar-open="sidebarOpen"
            @close-sidebar="sidebarOpen = false"
        />

        <div class="flex-1 flex flex-col min-h-screen min-w-0">
            <AdminHeader @toggle-sidebar="sidebarOpen = !sidebarOpen" />

            <main class="flex-1 p-4 md:p-6 lg:p-8">
                <div class="flex justify-end">
                    <div v-if="$page.props.flash?.success && !dismissed" class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-sm font-inter flex items-center gap-2 w-auto shadow-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $page.props.flash.success }}
                        <button @click="dismiss" class="ml-2 p-0.5 rounded hover:bg-green-200 dark:hover:bg-green-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex justify-end">
                    <div v-if="$page.props.flash?.error && !dismissed" class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-sm font-inter flex items-center gap-2 w-auto shadow-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $page.props.flash.error }}
                        <button @click="dismiss" class="ml-2 p-0.5 rounded hover:bg-red-200 dark:hover:bg-red-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
