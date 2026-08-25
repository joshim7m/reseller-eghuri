<script setup>
import { ref } from 'vue'

const email = ref('')

function subscribe() {
}
</script>

<template>
    <section class="mb-16">
        <div class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 rounded-2xl px-6 md:px-10 py-10 md:py-14 text-white text-center">
            <h2 class="text-2xl md:text-3xl font-bold">Stay in the Loop</h2>
            <p class="mt-2 text-gray-400 max-w-md mx-auto text-sm">Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.</p>
            <form class="mt-6 max-w-md mx-auto flex flex-col sm:flex-row gap-2" @submit.prevent="subscribe">
                <input
                    v-model="email"
                    type="email"
                    placeholder="Enter your email"
                    class="flex-1 rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400 transition"
                >
                <button type="submit" class="bg-blue-600 text-white font-semibold px-6 py-2.5 rounded-lg text-sm hover:opacity-90 transition shrink-0">Subscribe</button>
            </form>
        </div>
    </section>
</template>
