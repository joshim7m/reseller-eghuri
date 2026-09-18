<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { dimensionNames, dimensionValues, findVariantByOptions, variantOptions } from '@/composables/useVariantOptions'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

const props = defineProps({
    deliveryAreas: { type: Array, default: () => [] },
})

function parseAreas(areas) {
    const defaults = [{ name: 'Inside Dhaka', charge: 50 }, { name: 'Outside Dhaka', charge: 120 }]

    return areas.length
        ? areas.filter((area) => area?.name).map((area) => ({ name: String(area.name), charge: parseInt(area.charge) || 0 }))
        : defaults
}

const deliveryAreas = parseAreas(props.deliveryAreas)

const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)

const bdMobileRegex = /^01[3-9]\d{8}$/

const mobileInvalid = computed(() => {
    const number = form.mobile.trim()

    return number.length > 0 && !bdMobileRegex.test(number)
})

const mobileValid = computed(() => bdMobileRegex.test(form.mobile.trim()))

const mobileHelper = computed(() => {
    const number = form.mobile.trim()

    if (number.length === 0) {
        return null
    }

    if (mobileInvalid.value && number.length < 11) {
        return 'Mobile number must be 11 digits.'
    }

    if (mobileInvalid.value && !number.startsWith('01')) {
        return 'Mobile number must start with 01.'
    }

    if (mobileInvalid.value) {
        return 'Operator code must be 3-9 (e.g. 013, 017, 019).'
    }

    return null
})

const form = useForm({
    items: [],
    delivery_charge: String(deliveryAreas[0].charge),
    customer_name: '',
    mobile: '',
    shipping_address: '',
    notes: '',
})

let searchTimeout = null

function searchProducts() {
    clearTimeout(searchTimeout)

    if (searchQuery.value.length < 2) {
        searchResults.value = []

        return
    }

    searchTimeout = setTimeout(async () => {
        searching.value = true

        try {
            const res = await fetch(route('reseller-orders.search-products') + '?q=' + encodeURIComponent(searchQuery.value))
            searchResults.value = await res.json()
        } finally {
            searching.value = false
        }
    }, 300)
}

function addItem(product) {
    const existing = form.items.find((i) => i.product_id === product.id)

    if (existing) {
        existing.quantity++
        calculateLineTotal(existing)
        clearSearch()

        return
    }

    const variants = product.variants || []
    const defaultVariant = variants.length > 0 ? variants[0] : null
    const unitPrice = parseFloat(defaultVariant?.unit_price ?? (product.purchase_price || product.sale_price)) || 0
    const salePrice = parseFloat(defaultVariant?.sale_price ?? product.sale_price) || 0

    const defaultOptions = variantOptions(defaultVariant || {})
    const selection = {}

    for (const opt of defaultOptions) {
        if (!(opt.name in selection)) {
            selection[opt.name] = opt.value
        }
    }

    form.items.push({
        product_id: product.id,
        product_name: product.title,
        image: defaultVariant?.image || product.image || null,
        productImage: product.image || null,
        unit_price: unitPrice,
        sale_price: salePrice,
        quantity: 1,
        options: selection,
        availableVariants: variants,
        selectedVariantId: defaultVariant?.id || '',
        lineTotal: salePrice,
    })

    clearSearch()
}

function clearSearch() {
    searchQuery.value = ''
    searchResults.value = []
}

function removeItem(index) {
    form.items.splice(index, 1)
}

function calculateLineTotal(item) {
    const qty = parseInt(item.quantity) || 0

    const variant = findVariantByOptions(item.availableVariants, item.options)
    item.selectedVariantId = variant?.id || ''
    item.image = variant?.image || item.productImage || null

    if (variant?.unit_price != null) {
        item.unit_price = parseFloat(variant.unit_price)
    }

    if (variant?.sale_price != null) {
        item.sale_price = parseFloat(variant.sale_price)
    }

    item.lineTotal = (parseInt(item.quantity) || 0) * (parseFloat(item.sale_price) || 0)
}

function variantDimensions(item) {
    return dimensionNames(item.availableVariants).map((name) => ({
        name,
        values: dimensionValues(item.availableVariants, name),
    }))
}

function selectOption(item, name, value) {
    item.options = { ...item.options, [name]: value }
    calculateLineTotal(item)
}

const subtotal = computed(() => form.items.reduce((sum, item) => sum + (item.lineTotal || 0), 0))

const grandTotal = computed(() => subtotal.value + parseInt(form.delivery_charge || 0))

function submit() {
    form.clearErrors()

    if (!form.mobile.trim()) {
        form.setError('mobile', 'Mobile number is required.')
        return
    }

    if (mobileInvalid.value) {
        form.setError('mobile', mobileHelper.value)
        return
    }

    form.transform((data) => ({
        ...data,
        items: data.items.map((item) => {
            const variant = item.availableVariants.find(v => v.id === item.selectedVariantId) || null
            const opts = variantOptions(variant || {})

            return {
                product_id: item.product_id,
                product_name: item.product_name,
                quantity: item.quantity,
                unit_price: item.unit_price,
                sale_price: item.sale_price,
                variant_id: item.selectedVariantId || null,
                options: opts.length ? opts : null,
            }
        }),
    })).post(route('reseller-orders.store'), { preserveScroll: true })
}
</script>

<template>
    <Head title="New Reseller Order" />

    <FrontEndMaster>
        <div class="max-w-4xl mx-auto">
            <Link :href="route('reseller-orders.index')" class="inline-flex items-center gap-1.5 text-sm font-medium text-on-surface-variant transition hover:text-primary dark:text-[#cbb8b6] dark:hover:text-[#f6b7b2] mb-6">
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                Back to Orders
            </Link>

            <h1 class="text-3xl md:text-4xl font-bold text-charcoal dark:text-[#f9eeed] mb-2">New Reseller Order</h1>
            <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6] mb-8">Create a new order for your customer.</p>

            <form @submit.prevent="submit">
                <div class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-6 mb-6">
                    <h2 class="text-lg font-bold text-charcoal dark:text-[#f9eeed] mb-4">Search Products</h2>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-outline dark:text-[#cbb8b6]" aria-hidden="true">search</span>
                        <input v-model="searchQuery" @input="searchProducts" type="text" placeholder="Search by product name or SKU..."
                            class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] pl-10 pr-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
                        <div v-if="searchResults.length > 0" class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-[#241d1c] rounded-xl border border-outline-variant dark:border-[#3a302e] shadow-lg z-50 max-h-80 overflow-y-auto">
                            <button v-for="product in searchResults" :key="product.id" type="button" @click="addItem(product)"
                                class="flex items-center gap-3 w-full px-4 py-3 text-left hover:bg-surface-container-low dark:hover:bg-[#2e2523] transition border-b border-outline-variant dark:border-[#3a302e] last:border-0">
                                <img v-if="product.image" :src="product.image" alt="" class="w-10 h-10 rounded-lg object-cover bg-surface-container dark:bg-[#2e2523] shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-charcoal dark:text-[#f9eeed] truncate">{{ product.title }}</p>
                                    <p class="text-xs font-mono text-on-surface-variant dark:text-[#cbb8b6]">
                                        <span v-if="product.sku" class="mr-1.5">{{ product.sku }}</span>
                                        Price: ৳{{ product.sale_price }}<span v-if="product.variants && product.variants.length"> | {{ product.variants.length }} variant(s)</span>
                                    </p>
                                </div>
                                <span class="text-xs text-primary shrink-0 font-medium">Add +</span>
                            </button>
                        </div>
                        <p v-if="searching" class="text-xs text-on-surface-variant dark:text-[#cbb8b6] mt-1.5">Searching...</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-charcoal dark:text-[#f9eeed]">Order Items</h2>
                        <span class="text-xs text-on-surface-variant dark:text-[#cbb8b6]">{{ form.items.length }} item(s)</span>
                    </div>

                    <p v-if="form.errors.items" class="text-xs text-sale-price mb-3">{{ form.errors.items }}</p>
                    <p v-if="form.items.length === 0" class="text-on-surface-variant dark:text-[#cbb8b6] text-sm py-8 text-center">No items added yet. Search and add products above.</p>

                    <div v-for="(item, index) in form.items" :key="index" class="border border-outline-variant dark:border-[#3a302e] rounded-xl p-4 mb-3">
                        <div class="flex items-start gap-3 mb-3">
                            <img v-if="item.image" :src="item.image" alt="" class="w-12 h-12 rounded-lg object-cover bg-surface-container dark:bg-[#2e2523] shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-charcoal dark:text-[#f9eeed] truncate">{{ item.product_name }}</p>
                                <p class="text-xs font-mono text-on-surface-variant dark:text-[#cbb8b6]">Unit Price: ৳{{ item.unit_price }}</p>
                            </div>
                            <button type="button" @click="removeItem(index)" class="text-sale-price/60 hover:text-sale-price p-1 shrink-0 ml-2 transition">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">delete</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-on-surface-variant dark:text-[#cbb8b6] mb-1">Quantity</label>
                                <input v-model.number="item.quantity" @input="calculateLineTotal(item)" type="number" min="1" max="100"
                                    :class="form.errors[`items.${index}.quantity`] ? 'border-sale-price' : ''"
                                    class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-3 py-2 text-sm text-charcoal dark:text-[#f9eeed] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
                                <p v-if="form.errors[`items.${index}.quantity`]" class="text-xs text-sale-price mt-1">{{ form.errors[`items.${index}.quantity`] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-on-surface-variant dark:text-[#cbb8b6] mb-1">Unit Price (৳)</label>
                                <input :value="item.unit_price" type="number" step="0.01" min="0" disabled readonly
                                    title="Unit price is set by the admin and cannot be changed"
                                    class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container dark:bg-[#2e2523] px-3 py-2 text-sm text-on-surface-variant dark:text-[#cbb8b6] cursor-not-allowed focus:outline-none" />
                                <p v-if="form.errors[`items.${index}.unit_price`]" class="text-xs text-sale-price mt-1">{{ form.errors[`items.${index}.unit_price`] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-on-surface-variant dark:text-[#cbb8b6] mb-1">Sale Price (৳)</label>
                                <input v-model.number="item.sale_price" @input="calculateLineTotal(item)" type="number" step="0.01" min="0"
                                    :class="form.errors[`items.${index}.sale_price`] ? 'border-sale-price ring-2 ring-sale-price/20' : ''"
                                    class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-3 py-2 text-sm text-charcoal dark:text-[#f9eeed] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
                                <p v-if="form.errors[`items.${index}.sale_price`]" class="text-xs text-sale-price mt-1">{{ form.errors[`items.${index}.sale_price`] }}</p>
                            </div>
                            <template v-for="dim in variantDimensions(item)" :key="dim.name">
                                <div>
                                    <label class="block text-xs font-medium text-on-surface-variant dark:text-[#cbb8b6] mb-1 capitalize">{{ dim.name }}</label>
                                    <select :value="item.options[dim.name] || ''" @change="selectOption(item, dim.name, $event.target.value)"
                                        class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-3 py-2 text-sm text-charcoal dark:text-[#f9eeed] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition">
                                        <option value="">Select</option>
                                        <option v-for="value in dim.values" :key="value" :value="value">{{ value }}</option>
                                    </select>
                                </div>
                            </template>
                        </div>
                        <div class="mt-2.5 text-right">
                            <span class="text-xs text-on-surface-variant dark:text-[#cbb8b6]">Line Total: </span>
                            <span class="text-sm font-mono font-bold text-charcoal dark:text-[#f9eeed]">৳{{ Number(item.lineTotal).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-6 mb-6">
                    <h2 class="text-lg font-bold text-charcoal dark:text-[#f9eeed] mb-4">Customer Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_name" class="block text-xs font-medium text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider mb-1.5">Customer Name *</label>
                            <input id="customer_name" v-model="form.customer_name" type="text"
                                :class="form.errors.customer_name ? 'border-sale-price' : ''"
                                class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-3 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
                            <p v-if="form.errors.customer_name" class="text-xs text-sale-price mt-1">{{ form.errors.customer_name }}</p>
                        </div>
                        <div>
                            <label for="mobile" class="block text-xs font-medium text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider mb-1.5">Mobile *</label>
                            <input id="mobile" v-model="form.mobile" type="text"
                                :class="mobileInvalid || form.errors.mobile ? 'border-sale-price ring-2 ring-sale-price/20' : ''"
                                class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-3 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
                            <p v-if="mobileInvalid" class="text-xs text-sale-price mt-1">{{ mobileHelper }}</p>
                            <p v-if="form.errors.mobile" class="text-xs text-sale-price mt-1">{{ form.errors.mobile }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="shipping_address" class="block text-xs font-medium text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider mb-1.5">Shipping Address *</label>
                        <textarea id="shipping_address" v-model="form.shipping_address" rows="3"
                            :class="form.errors.shipping_address ? 'border-sale-price' : ''"
                            class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-3 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition resize-none"></textarea>
                        <p v-if="form.errors.shipping_address" class="text-xs text-sale-price mt-1">{{ form.errors.shipping_address }}</p>
                    </div>
                    <div class="mt-4">
                        <label for="notes" class="block text-xs font-medium text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider mb-1.5">Notes (optional)</label>
                        <textarea id="notes" v-model="form.notes" rows="2"
                            class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-3 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition resize-none"></textarea>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-6 mb-6">
                    <h2 class="text-lg font-bold text-charcoal dark:text-[#f9eeed] mb-4">Delivery &amp; Summary</h2>
                    <div class="mb-5">
                        <label class="block text-xs font-medium text-on-surface-variant dark:text-[#cbb8b6] uppercase tracking-wider mb-2.5">Delivery Area *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label v-for="area in deliveryAreas" :key="area.charge" class="cursor-pointer">
                                <input type="radio" v-model="form.delivery_charge" :value="String(area.charge)" class="sr-only" />
                                <div class="flex items-center gap-3 rounded-xl border-2 px-4 py-3 transition"
                                    :class="form.delivery_charge === String(area.charge)
                                        ? 'border-primary bg-primary/5 dark:bg-primary/10 ring-1 ring-primary'
                                        : 'border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] hover:border-primary/40'">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition"
                                        :class="form.delivery_charge === String(area.charge) ? 'border-primary' : 'border-outline-variant dark:border-[#3a302e]'">
                                        <span class="h-2.5 w-2.5 rounded-full bg-primary transition"
                                            :class="form.delivery_charge === String(area.charge) ? 'scale-100' : 'scale-0'"></span>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-charcoal dark:text-[#f9eeed] truncate">{{ area.name }}</p>
                                        <p class="text-xs font-mono text-on-surface-variant dark:text-[#cbb8b6]">৳{{ area.charge }}</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <p v-if="form.errors.delivery_charge" class="text-xs text-sale-price mt-1.5">{{ form.errors.delivery_charge }}</p>
                    </div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">Total Items:</span>
                        <span class="text-sm font-mono font-bold text-charcoal dark:text-[#f9eeed]">{{ form.items.length }}</span>
                    </div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">Subtotal:</span>
                        <span class="text-sm font-mono text-charcoal dark:text-[#f9eeed]">৳{{ subtotal.toFixed(2) }}</span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">Delivery Charge:</span>
                        <span class="text-sm font-mono text-charcoal dark:text-[#f9eeed]">৳{{ parseInt(form.delivery_charge).toFixed(2) }}</span>
                    </div>
                    <div class="flex items-center justify-between mb-4 pt-3 border-t border-outline-variant dark:border-[#3a302e]">
                        <span class="text-sm font-semibold text-charcoal dark:text-[#f9eeed]">Grand Total:</span>
                        <span class="text-lg font-mono font-bold text-primary">৳{{ grandTotal.toFixed(2) }}</span>
                    </div>
                    <button type="submit" :disabled="form.items.length === 0 || mobileInvalid || form.processing"
                        :class="form.items.length === 0 || mobileInvalid || form.processing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-primary'"
                        class="w-full bg-charcoal text-white font-semibold px-6 py-3 rounded-xl text-sm transition dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]">
                        {{ form.processing ? 'Placing Order...' : 'Submit Order' }}
                    </button>
                </div>
            </form>
        </div>
    </FrontEndMaster>
</template>
