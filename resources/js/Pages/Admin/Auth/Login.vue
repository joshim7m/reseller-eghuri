<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post(route('admin.login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Admin Login" />

    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white">Admin Panel</h1>
                <p class="text-gray-400 mt-2 text-sm">Sign in to manage your store</p>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-2xl p-8 space-y-6">
                <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    {{ $page.props.flash.error }}
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input id="email" type="email" v-model="form.email"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="admin@example.com" required autofocus autocomplete="username">
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                    <input id="password" type="password" v-model="form.password"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        placeholder="Enter your password" required autocomplete="current-password">
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.remember"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <button type="submit" :disabled="form.processing"
                    class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg text-sm font-semibold transition disabled:opacity-50 shadow-lg shadow-blue-600/25">
                    {{ form.processing ? 'Signing in...' : 'Sign In' }}
                </button>

                <p class="text-center text-xs text-gray-500">
                    <Link :href="route('home')" class="text-blue-600 hover:text-blue-700 hover:underline">&larr; Back to Store</Link>
                </p>
            </form>
        </div>
    </div>
</template>
