<script setup>
const props = defineProps({
    form: { type: Object, required: true },
    showSku: { type: Boolean, default: true },
    showQuantity: { type: Boolean, default: true },
})

function generate5DigitSku() {
    let sku = ''

    for (let i = 0; i < 5; i++) {
        sku += Math.floor(Math.random() * 10)
    }

    return sku
}

function generateSku() {
    props.form.sku = generate5DigitSku()
}
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="unit_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit Price (BDT) <span class="text-red-500">*</span></label>
            <input id="unit_price" v-model="form.unit_price" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
            <p v-if="form.errors.unit_price" class="mt-1 text-sm text-red-600">{{ form.errors.unit_price }}</p>
        </div>
        <div>
            <label for="sale_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sale Price (BDT) <span class="text-red-500">*</span></label>
            <input id="sale_price" v-model="form.sale_price" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
            <p v-if="form.errors.sale_price" class="mt-1 text-sm text-red-600">{{ form.errors.sale_price }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div v-if="showSku">
            <label for="sku_simple" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
            <div class="flex gap-2">
                <input id="sku_simple" v-model="form.sku" type="text" class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Unique code" />
                <button type="button" @click="generateSku" class="shrink-0 px-3 py-2 text-xs font-medium text-blue-600 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition whitespace-nowrap">Generate</button>
            </div>
            <p v-if="form.errors.sku" class="mt-1 text-sm text-red-600">{{ form.errors.sku }}</p>
        </div>
        <div v-if="showQuantity">
            <label for="quantity_simple" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
            <input id="quantity_simple" v-model="form.quantity" type="number" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            <p v-if="form.errors.quantity" class="mt-1 text-sm text-red-600">{{ form.errors.quantity }}</p>
        </div>
    </div>
</template>
