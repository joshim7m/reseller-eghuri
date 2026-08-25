<script setup>
import { Head, Link } from '@inertiajs/vue3'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

defineProps({
    order: { type: Object, default: () => ({}) }
})
</script>

<template>
    <Head title="Order Placed!" />

    <FrontEndMaster>
        <div class="max-w-lg mx-auto text-center py-12">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Thank You for Your Order!</h1>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Your order has been placed successfully.</p>

            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Order Number</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">#{{ order.order_number || order.id }}</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <Link :href="route('orders.show', order.id)"
                    class="px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    View Order Details
                </Link>
                <Link :href="route('products.index')"
                    class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Continue Shopping
                </Link>
            </div>
        </div>
    </FrontEndMaster>
</template>
