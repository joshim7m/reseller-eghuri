<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'
import { useCart } from '@/composables/useCart'

const props = defineProps({
    user: { type: Object, default: () => ({ name: '', mobile: '', shipping_address: '' }) },
})

const { items: cart, total, clear } = useCart()

const form = useForm({
    name: props.user.name,
    mobile: props.user.mobile,
    shipping_address: props.user.shipping_address,
    shipping_area: '',
    payment_method: 'cod',
    items: [],
    delivery_charge: 0,
})

const errors = ref({})

const DELIVERY_CHARGES = { inside_dhaka: 50, outside_dhaka: 120 }

const deliveryCharge = computed(() => DELIVERY_CHARGES[form.shipping_area] || 0)

const subtotal = computed(() => total.value)

const grandTotal = computed(() => subtotal.value + deliveryCharge.value)

const MOBILE_REGEX = /^(013|014|015|016|017|018|019)\d{8}$/

function validate() {
    const errs = {}

    if (!form.name || form.name.length < 3) errs.name = 'Name must be at least 3 characters'
    else if (form.name.length > 30) errs.name = 'Name must not exceed 30 characters'

    if (!form.mobile) errs.mobile = 'Mobile number is required'
    else if (!MOBILE_REGEX.test(form.mobile)) errs.mobile = 'Enter a valid Bangladeshi mobile number (e.g. 017xxxxxxxx)'

    if (!form.shipping_address || form.shipping_address.length < 12) errs.shipping_address = 'Address must be at least 12 characters'
    else if (form.shipping_address.length > 60) errs.shipping_address = 'Address must not exceed 60 characters'

    if (!form.shipping_area) errs.shipping_area = 'Select a shipping area'

    errors.value = errs
    return Object.keys(errs).length === 0
}

function submitOrder() {
    if (!validate()) return

    form.items = cart.value.map(item => ({
        product_id: item.product_id,
        variant_id: item.variant_id || null,
        size: item.size || null,
        color: item.color || null,
        item_name: item.name,
        quantity: item.qty,
        price: item.price,
    }))

    form.delivery_charge = deliveryCharge.value

    form.post(route('checkout.store'), {
        preserveScroll: true,
        onSuccess: () => {
            clear()
        },
        onError: (errs) => {
            errors.value = Object.assign(errors.value, errs)
        },
    })
}
</script>

<template>
    <Head title="Checkout" />

    <FrontEndMaster>
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Checkout</h1>
        </div>

        <form @submit.prevent="submitOrder" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div v-if="Object.keys(errors).length" class="lg:col-span-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 dark:text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <p class="text-sm text-red-700 dark:text-red-400">Please fix the errors below and try again.</p>
            </div>
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Shipping Information</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.name" maxlength="30" class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400" :class="errors.name ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-gray-600'" placeholder="Your full name">
                            <p v-if="errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mobile <span class="text-red-500">*</span></label>
                            <input type="tel" v-model="form.mobile" maxlength="11" class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400" :class="errors.mobile ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-gray-600'" placeholder="017xxxxxxxx">
                            <p v-if="errors.mobile" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.mobile }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Shipping Address <span class="text-red-500">*</span></label>
                            <textarea v-model="form.shipping_address" rows="2" maxlength="60" class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400" :class="errors.shipping_address ? 'border-red-400 dark:border-red-500' : 'border-gray-300 dark:border-gray-600'" placeholder="House, road, area, district"></textarea>
                            <p v-if="errors.shipping_address" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.shipping_address }}</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ form.shipping_address.length || 0 }} / 60</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shipping Area <span class="text-red-500">*</span></label>
                            <div class="flex flex-wrap gap-3">
                                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:border-blue-200 dark:hover:border-blue-600 transition flex-1 min-w-[180px]" :class="form.shipping_area === 'inside_dhaka' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-500' : 'border-gray-200 dark:border-gray-600'">
                                    <input type="radio" v-model="form.shipping_area" value="inside_dhaka" class="text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Inside Dhaka</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">৳50 delivery charge</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:border-blue-200 dark:hover:border-blue-600 transition flex-1 min-w-[180px]" :class="form.shipping_area === 'outside_dhaka' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-500' : 'border-gray-200 dark:border-gray-600'">
                                    <input type="radio" v-model="form.shipping_area" value="outside_dhaka" class="text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Outside Dhaka</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">৳120 delivery charge</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="errors.shipping_area" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.shipping_area }}</p>
                            <p v-if="errors.delivery_charge" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.delivery_charge }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Payment Method</h2>
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:border-blue-200 dark:hover:border-blue-600 transition" :class="form.payment_method === 'cod' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-500' : 'border-gray-200 dark:border-gray-600'">
                            <input type="radio" v-model="form.payment_method" value="cod" class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Cash on Delivery</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Pay when you receive your order</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:border-blue-200 dark:hover:border-blue-600 transition" :class="form.payment_method === 'bkash' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-500' : 'border-gray-200 dark:border-gray-600'">
                            <input type="radio" v-model="form.payment_method" value="bkash" class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">bKash</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Pay via bKash mobile banking</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:border-blue-200 dark:hover:border-blue-600 transition" :class="form.payment_method === 'nagad' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-500' : 'border-gray-200 dark:border-gray-600'">
                            <input type="radio" v-model="form.payment_method" value="nagad" class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Nagad</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Pay via Nagad mobile banking</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 h-fit sticky top-24">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Order Summary</h3>
                <div v-for="(item, i) in cart" :key="i" class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800 dark:text-gray-100 truncate">{{ item.name }}</p>
                        <p v-if="item.variant_name" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ item.variant_name }}</p>
                        <p v-if="item.sku" class="text-xs text-gray-400 dark:text-gray-500">SKU: {{ item.sku }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Qty: {{ item.qty }}</p>
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">৳{{ (item.price * item.qty).toLocaleString('en-IN') }}</p>
                </div>
                <hr class="my-3 border-gray-200 dark:border-gray-700">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>Subtotal</span>
                        <span>৳{{ subtotal.toLocaleString('en-IN') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-gray-400">
                        <span>Delivery</span>
                        <span v-if="deliveryCharge">৳{{ deliveryCharge.toLocaleString('en-IN') }}</span>
                        <span v-else class="text-gray-400 dark:text-gray-500">—</span>
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between font-bold text-gray-900 dark:text-white text-base">
                        <span>Total</span>
                        <span>৳{{ grandTotal.toLocaleString('en-IN') }}</span>
                    </div>
                </div>
                <button type="submit" :disabled="form.processing || !cart.length"
                    class="w-full mt-6 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition disabled:opacity-50">
                    {{ form.processing ? 'Processing...' : 'Place Order' }}
                </button>
            </div>
        </form>
    </FrontEndMaster>
</template>
