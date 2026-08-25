<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

const form = useForm({
    email: '',
    password: '',
    remember: '0'
})

function submit() {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password')
            form.remember = '0'
        },
        preserveState: (page) => Object.keys(page.props.errors).length > 0
    })
}
</script>

<template>
    <Head title="Log in" />

    <FrontEndMaster>
        <div class="max-w-md mx-auto py-12">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-8">
                <div class="text-center mb-8">
                    <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Welcome Back</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sign in to your account</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                        <input
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                            placeholder="you@example.com"
                        >
                        <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                        <input
                            type="password"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                            placeholder="Enter your password"
                        >
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                :true-value="'1'"
                                :false-value="'0'"
                                class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 w-4 h-4 dark:bg-gray-700"
                            >
                            <span class="text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Signing in...' : 'Sign In' }}
                    </button>

                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                        Don't have an account?
                        <Link :href="route('register')" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">Register as Wholeseller</Link>
                    </p>
                </form>
            </div>
        </div>
    </FrontEndMaster>
</template>
