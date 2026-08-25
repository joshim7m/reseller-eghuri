<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'
import { useCart } from '@/composables/useCart'

const { items: cart, total, updateQuantity, removeItem } = useCart()
</script>

<template>
    <Head title="Shopping Cart" />

    <FrontEndMaster>
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Shopping Cart</h1>
        </div>

        <div v-if="cart.length" class="flex flex-col lg:flex-row gap-6 lg:gap-8">
            <div class="flex-1 space-y-3 md:space-y-4">
                <div v-for="(item, i) in cart" :key="i" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 md:p-4">
                    <div class="flex gap-3 md:gap-4">
                        <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden shrink-0 flex items-center justify-center text-gray-300 dark:text-gray-600">
                            <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                            <svg v-else class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm md:text-base font-medium text-gray-800 dark:text-gray-100 line-clamp-2">{{ item.name }}</h3>
                            <p v-if="item.variant_name" class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ item.variant_name }}</p>
                            <p v-if="item.sku" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">SKU: {{ item.sku }}</p>
                            <p class="text-sm md:text-base font-bold text-gray-900 dark:text-gray-100 mt-1">৳{{ item.price }}</p>
                        </div>
                        <button @click="removeItem(i)" class="shrink-0 text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 transition p-1 -mr-1 -mt-1">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg">
                            <button @click="updateQuantity(i, -1)" class="px-2.5 md:px-3 py-1.5 md:py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition text-sm leading-none">&minus;</button>
                            <span class="px-3 md:px-4 py-1.5 md:py-2 text-sm font-medium text-gray-800 dark:text-gray-100 border-x border-gray-300 dark:border-gray-600 min-w-[2.5rem] text-center">{{ item.qty }}</span>
                            <button @click="updateQuantity(i, 1)" class="px-2.5 md:px-3 py-1.5 md:py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition text-sm leading-none">+</button>
                        </div>
                        <p class="text-sm md:text-base font-bold text-gray-900 dark:text-gray-100">৳{{ (item.price * item.qty).toLocaleString() }}</p>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-80 xl:w-96 shrink-0">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 md:p-6 lg:sticky lg:top-24">
                    <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-white mb-4">Order Summary</h3>
                    <div class="space-y-2 text-sm md:text-base">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Subtotal</span>
                            <span>৳{{ total.toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Shipping</span>
                            <span class="text-xs md:text-sm">Calculated at checkout</span>
                        </div>
                        <hr class="my-2 border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between font-bold text-gray-900 dark:text-white text-base md:text-lg">
                            <span>Total</span>
                            <span>৳{{ total.toLocaleString() }}</span>
                        </div>
                    </div>
                    <Link :href="route('checkout')" class="block w-full mt-5 md:mt-6 px-4 py-2.5 md:py-3 bg-blue-600 text-white rounded-lg text-sm md:text-base font-semibold text-center hover:bg-blue-700 transition">
                        Proceed to Checkout
                    </Link>
                    <Link :href="route('products.index')" class="block w-full mt-2 px-4 py-2 text-sm text-center text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
                        Continue Shopping
                    </Link>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
            <p class="mt-4 text-gray-500 dark:text-gray-400">Your cart is empty</p>
            <Link :href="route('products.index')" class="inline-block mt-4 px-6 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">Continue Shopping</Link>
        </div>
    </FrontEndMaster>
</template>
