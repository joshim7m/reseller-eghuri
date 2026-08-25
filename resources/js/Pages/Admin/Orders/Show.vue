<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const { order } = defineProps({
    order: { type: Object, required: true },
})

function formatPrice(price) {
    return '৳' + price.toLocaleString('en-IN')
}

function itemImage(item) {
    return item.variant?.image?.image_path || item.variant?.product?.image_url || null
}

const statusForm = useForm({
    status: order.status,
})

function updateStatus() {
    statusForm.patch(route('admin.orders.update-status', order.id))
}

const paymentForm = useForm({
    payment_status: order.payment_status === 'paid' ? 'paid' : 'unpaid',
})

function updatePayment() {
    paymentForm.patch(route('admin.orders.update-payment', order.id))
}

const invoiceForm = useForm({
    payment_method: '',
    paid_amount: '',
})

function issueInvoice() {
    invoiceForm.post(route('admin.orders.issue-invoice', order.id))
}

function statusBadge(s) {
    const map = { pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }
    return map[s] || 'bg-gray-100 text-gray-800'
}

function paymentBadge(s) {
    const map = { paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', unpaid: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', partial: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }
    return map[s] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <Head title="Order Details" />

    <AdminMaster>
        <div class="space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Order #{{ order.order_number }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Placed on {{ new Date(order.created_at).toLocaleString() }}</p>
                </div>
                <Link :href="route('admin.orders.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back to Orders</Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Order Items</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                        <th class="text-left px-3 py-2 font-medium">Item</th>
                                        <th class="text-center px-3 py-2 font-medium">Qty</th>
                                        <th class="text-right px-3 py-2 font-medium">Price</th>
                                        <th class="text-right px-3 py-2 font-medium">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <tr v-for="item in order.items" :key="item.id">
                                        <td class="px-3 py-2.5">
                                            <div class="flex items-center gap-3">
                                                <img v-if="itemImage(item)" :src="itemImage(item)" :alt="item.item_name" class="w-12 h-12 rounded-lg object-cover bg-gray-100 dark:bg-gray-800 shrink-0" loading="lazy">
                                                <div v-else class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-300 dark:text-gray-600 shrink-0">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ item.item_name }}</p>
                                                    <p v-if="item.size || item.color" class="text-xs text-gray-500 dark:text-gray-400">Variant: {{ item.size }} / {{ item.color }}</p>
                                                    <p v-if="item.variant?.sku" class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ item.variant.sku }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ item.quantity }}</td>
                                        <td class="px-3 py-2.5 text-right text-gray-600 dark:text-gray-400">{{ formatPrice(item.price_at_purchase) }}</td>
                                        <td class="px-3 py-2.5 text-right font-medium text-gray-900 dark:text-white">{{ formatPrice(item.total) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-gray-200 dark:border-gray-700">
                                        <td colspan="3" class="px-3 py-2.5 text-right text-sm font-medium text-gray-700 dark:text-gray-300">Total</td>
                                        <td class="px-3 py-2.5 text-right font-bold text-gray-900 dark:text-white">{{ formatPrice(order.total_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div v-if="order.invoice" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Invoice</h2>
                            <a :href="route('admin.orders.invoice', order.id)" class="text-sm font-medium text-blue-600 hover:text-blue-500">Download PDF</a>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-gray-500 dark:text-gray-400">Invoice #</span><span class="ml-2 font-medium text-gray-900 dark:text-white">{{ order.invoice.invoice_number }}</span></div>
                            <div><span class="text-gray-500 dark:text-gray-400">Date</span><span class="ml-2 font-medium text-gray-900 dark:text-white">{{ new Date(order.invoice.invoice_date).toLocaleDateString() }}</span></div>
                            <div><span class="text-gray-500 dark:text-gray-400">Payment Method</span><span class="ml-2 font-medium text-gray-900 dark:text-white capitalize">{{ order.invoice.payment_method }}</span></div>
                            <div><span class="text-gray-500 dark:text-gray-400">Payment Status</span><span class="ml-2"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize" :class="paymentBadge(order.invoice.payment_status)">{{ order.invoice.payment_status }}</span></span></div>
                            <div><span class="text-gray-500 dark:text-gray-400">Paid</span><span class="ml-2 font-medium text-gray-900 dark:text-white">{{ formatPrice(order.invoice.paid_amount) }}</span></div>
                            <div><span class="text-gray-500 dark:text-gray-400">Due</span><span class="ml-2 font-medium text-gray-900 dark:text-white">{{ formatPrice(order.invoice.due_amount) }}</span></div>
                        </div>
                    </div>

                    <div v-if="!order.invoice" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Issue Invoice</h2>
                        <form @submit.prevent="issueInvoice" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method</label>
                                    <select id="payment_method" v-model="invoiceForm.payment_method" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        <option value="">Select</option>
                                        <option value="cash">Cash</option>
                                        <option value="bkash">bKash</option>
                                        <option value="nagad">Nagad</option>
                                        <option value="bank">Bank Transfer</option>
                                        <option value="card">Card</option>
                                    </select>
                                    <p v-if="invoiceForm.errors.payment_method" class="mt-1 text-sm text-red-600">{{ invoiceForm.errors.payment_method }}</p>
                                </div>
                                <div>
                                    <label for="paid_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Paid Amount (BDT)</label>
                                    <input id="paid_amount" v-model="invoiceForm.paid_amount" type="number" min="0" :max="order.total_amount" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                                    <p v-if="invoiceForm.errors.paid_amount" class="mt-1 text-sm text-red-600">{{ invoiceForm.errors.paid_amount }}</p>
                                </div>
                            </div>
                            <button type="submit" :disabled="invoiceForm.processing" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                                {{ invoiceForm.processing ? 'Issuing...' : 'Issue Invoice' }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Customer</h2>
                        <div class="space-y-2 text-sm">
                            <p class="text-gray-900 dark:text-white font-medium">{{ order.user?.name }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ order.user?.email }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ order.user?.user_detail?.mobile || '—' }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ order.shipping_address || '—' }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Order Status</h2>
                        <form @submit.prevent="updateStatus" class="space-y-3">
                            <select v-model="statusForm.status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <p v-if="statusForm.errors.status" class="text-sm text-red-600">{{ statusForm.errors.status }}</p>
                            <button type="submit" :disabled="statusForm.processing" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                                {{ statusForm.processing ? 'Updating...' : 'Update Status' }}
                            </button>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment Status</h2>
                        <form @submit.prevent="updatePayment" class="space-y-3">
                            <select v-model="paymentForm.payment_status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                            </select>
                            <p v-if="paymentForm.errors.payment_status" class="text-sm text-red-600">{{ paymentForm.errors.payment_status }}</p>
                            <button type="submit" :disabled="paymentForm.processing" class="w-full px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 disabled:opacity-50 transition shadow-sm">
                                {{ paymentForm.processing ? 'Updating...' : 'Update Payment' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
