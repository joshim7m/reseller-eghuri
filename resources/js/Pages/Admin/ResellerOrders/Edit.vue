<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import InputError from '@/Components/InputError.vue'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const props = defineProps({
    resellerOrder: { type: Object, required: true },
})

const isCompleted = computed(() => props.resellerOrder.status === 'completed')
const isCancelled = computed(() => props.resellerOrder.status === 'cancelled')
const isReturned = computed(() => props.resellerOrder.status === 'returned')
const isEditable = computed(() => !isCompleted.value && !isCancelled.value && !isReturned.value)

const form = useForm({
    status: props.resellerOrder.status,
    total_amount: props.resellerOrder.total_amount,
    delivery_charge: props.resellerOrder.delivery_charge,
    shipping_address: props.resellerOrder.shipping_address,
    items: props.resellerOrder.items.map(item => ({
        id: item.id,
        quantity: item.quantity,
    })),
})

const subtotal = computed(() => {
    return props.resellerOrder.items.reduce((sum, item) => {
        const qty = form.items.find(i => i.id === item.id)?.quantity ?? item.quantity

        return sum + (item.sale_price ?? item.unit_price) * qty
    }, 0)
})

const grandTotal = computed(() => subtotal.value + Number(form.delivery_charge || 0))

function submit() {
    form.total_amount = grandTotal.value
    form.put(route('admin.reseller-orders.update', props.resellerOrder.id))
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

function statusColor(status) {
    const map = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    }

    return map[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <Head :title="`Edit Order ${resellerOrder.order_number}`" />

    <AdminMaster>
        <div class="max-w-4xl mx-auto space-y-6">
            <div>
                <Link :href="route('admin.reseller-orders.index')"
                    class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition mb-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back
                </Link>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        <span class="font-mono">{{ resellerOrder.order_number }}</span>
                    </h1>
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize ring-1 ring-inset" :class="statusColor(resellerOrder.status)">
                        {{ resellerOrder.status }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ formatDate(resellerOrder.created_at) }} &middot; {{ resellerOrder.user?.name ?? 'N/A' }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-4 sm:px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Order Items</h2>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div v-for="(item, index) in resellerOrder.items" :key="item.id" class="px-4 sm:px-5 py-3 flex items-center gap-3">
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-mono w-5 text-center shrink-0">{{ index + 1 }}</span>
                        <div class="shrink-0 w-11 h-11 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 ring-1 ring-gray-200/50 dark:ring-gray-700/50">
                            <img v-if="item.product?.image_url" :src="item.product.image_url"
                                :alt="item.product_name" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-[10px] text-gray-400 dark:text-gray-500">N/A</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ item.product_name }}</p>
                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                <span v-if="item.options?.length" class="text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ item.options.map(o => o.value).join(' / ') }}
                                </span>
                                <span v-if="item.variant?.sku" class="text-[10px] text-gray-400 dark:text-gray-500 font-mono">{{ item.variant.sku }}</span>
                            </div>
                        </div>

                        <div v-if="isEditable" class="flex items-center gap-2 shrink-0">
                            <label class="text-[11px] text-gray-500 dark:text-gray-400">Qty</label>
                            <input
                                v-model.number="form.items[index].quantity"
                                type="number"
                                min="1"
                                class="w-16 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-2.5 py-1.5 text-sm font-mono text-center focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                            />
                        </div>
                        <div v-else class="text-right shrink-0">
                            <p class="text-sm font-semibold font-mono text-gray-900 dark:text-white">x{{ form.items[index]?.quantity ?? item.quantity }}</p>
                        </div>

                        <div class="text-right shrink-0 min-w-[70px]">
                            <p class="text-sm font-semibold font-mono"
                                :class="item.sale_price ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'">
                                {{ (item.sale_price ?? item.unit_price) * (form.items[index]?.quantity ?? item.quantity) }}
                            </p>
                            <p v-if="item.sale_price" class="text-[10px] text-gray-400 dark:text-gray-500 font-mono line-through">
                                {{ item.unit_price * (form.items[index]?.quantity ?? item.quantity) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4 sm:p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Customer Details</h2>
                        <div class="space-y-2.5 text-xs text-gray-600 dark:text-gray-400">
                            <div v-if="resellerOrder.customer_name" class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>{{ resellerOrder.customer_name }}</span>
                                <span v-if="resellerOrder.mobile" class="text-gray-400 dark:text-gray-500">({{ resellerOrder.mobile }})</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="truncate">{{ resellerOrder.shipping_address || '—' }}</span>
                            </div>
                            <div v-if="resellerOrder.user?.user_detail?.company" class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span>{{ resellerOrder.user.user_detail.company }}</span>
                            </div>
                            <div v-if="resellerOrder.notes" class="flex items-start gap-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                                <svg class="w-3.5 h-3.5 text-purple-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="italic text-gray-500 dark:text-gray-400">{{ resellerOrder.notes }}</span>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 dark:border-gray-800 pt-3 space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ Number(subtotal).toLocaleString('en-IN') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Delivery</span>
                                <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ Number(form.delivery_charge || 0).toLocaleString('en-IN') }}</span>
                            </div>
                            <div class="flex justify-between pt-1.5 border-t border-gray-200 dark:border-gray-700">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">Total</span>
                                <span class="font-mono font-bold text-sm text-gray-900 dark:text-white">{{ Number(grandTotal).toLocaleString('en-IN') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4 sm:p-5 shadow-sm space-y-4">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Order Summary</h2>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select v-model="form.status"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    :disabled="isCompleted || isCancelled || isReturned">
                                    <option value="pending">Pending</option>
                                    <option value="processing">Processing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="returned">Returned</option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.status" />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Delivery Charge</label>
                                <input v-model="form.delivery_charge" type="number" step="0.01" min="0"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm font-mono focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    :disabled="isCompleted || isCancelled || isReturned" />
                                <InputError class="mt-1" :message="form.errors.delivery_charge" />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Shipping Address</label>
                                <textarea v-model="form.shipping_address" rows="2"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                                    :disabled="isCompleted || isCancelled || isReturned"></textarea>
                                <InputError class="mt-1" :message="form.errors.shipping_address" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="submit" :disabled="form.processing || isCompleted || isCancelled || isReturned"
                                class="flex-1 sm:flex-none bg-blue-600 text-white font-semibold px-6 py-2.5 rounded-lg text-sm hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ form.processing ? 'Saving...' : 'Update Order' }}
                            </button>
                            <Link :href="route('admin.reseller-orders.index')"
                                class="text-gray-500 dark:text-gray-400 text-sm hover:underline">Cancel</Link>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminMaster>
</template>
