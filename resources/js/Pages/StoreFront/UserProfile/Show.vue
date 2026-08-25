<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

const props = defineProps({
    order: { type: Object, default: () => ({}) }
})

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
}

const paymentStatusColors = {
    unpaid: 'bg-red-100 text-red-800',
    paid: 'bg-green-100 text-green-800',
    partial: 'bg-orange-100 text-orange-800'
}

const subtotal = computed(() => {
    if (!props.order.items?.length) return 0
    return props.order.items.reduce((sum, item) => sum + Number(item.total || item.price_at_purchase * item.quantity), 0)
})

function formatPrice(price) {
    return '৳' + Number(price).toLocaleString('en-IN')
}

function formatDate(date) {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-BD', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    })
}

function itemImage(item) {
    return item.variant?.image?.image_url || item.variant?.product?.images?.[0]?.image_url || null
}
</script>

<template>
    <Head :title="'Order #' + (order.order_number || order.id)" />

    <FrontEndMaster>
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <Link :href="route('home')" class="hover:text-blue-600 transition">Home</Link>
            <span>/</span>
            <Link :href="route('orders.index')" class="hover:text-blue-600 transition">My Orders</Link>
            <span>/</span>
            <span class="text-gray-800 font-medium">#{{ order.order_number || order.id }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Order Items</h2>
                    <div v-if="order.items?.length" class="divide-y divide-gray-100">
                        <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4 py-3">
                            <div class="w-16 h-16 rounded-lg bg-gray-100 shrink-0 overflow-hidden flex items-center justify-center">
                                <img v-if="itemImage(item)" :src="itemImage(item)" :alt="item.item_name" class="w-full h-full object-cover" />
                                <svg v-else class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800">{{ item.item_name }}</p>
                                <div v-if="item.size || item.color" class="flex flex-wrap gap-1.5 mt-1">
                                    <span v-if="item.size" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">Size: {{ item.size }}</span>
                                    <span v-if="item.color" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">Color: {{ item.color }}</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">{{ formatPrice(item.price_at_purchase) }} each &times; {{ item.quantity }}</p>
                            </div>
                            <p class="text-sm font-bold text-gray-900">{{ formatPrice(item.total || item.price_at_purchase * item.quantity) }}</p>
                        </div>
                    </div>
                    <div v-else class="text-gray-500 text-sm py-4">No items found.</div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 mb-3">Shipping Address</h3>
                    <p class="text-sm text-gray-600">{{ order.shipping_address }}</p>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-900 mb-3">Order Details</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Order #</span>
                            <span class="font-medium text-gray-900">#{{ order.order_number || order.id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Date</span>
                            <span class="font-medium text-gray-900">{{ formatDate(order.created_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusColors[order.status] || 'bg-gray-100 text-gray-800'">{{ order.status }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Payment</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="paymentStatusColors[order.payment_status] || 'bg-gray-100 text-gray-800'">{{ order.payment_status }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Method</span>
                            <span class="font-medium text-gray-900 capitalize">{{ order.payment_method }}</span>
                        </div>
                        <hr>
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>{{ formatPrice(subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Delivery</span>
                            <span>{{ formatPrice(order.delivery_charge) }}</span>
                        </div>
                        <hr>
                        <div class="flex justify-between font-bold text-gray-900 text-base">
                            <span>Total</span>
                            <span>{{ formatPrice(order.total_amount) }}</span>
                        </div>
                    </div>
                </div>

                <a v-if="order.invoice"
                   :href="route('orders.invoice', order.id)"
                   target="_blank"
                   class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-blue-600 text-blue-600 rounded-lg text-sm font-semibold hover:bg-blue-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Invoice
                </a>
            </div>
        </div>
    </FrontEndMaster>
</template>