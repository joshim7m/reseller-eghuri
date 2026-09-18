<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    form: { type: Object, required: true },
    product: { type: Object, default: null },
})

const hasVariants = ref(Boolean(props.product?.variants?.length))

const VARIANT_NAME_SUGGESTIONS = [
    'color', 'size', 'weight', 'box', 'material', 'fabric', 'flavor', 'volume',
    'style', 'model', 'version', 'length', 'width', 'height', 'capacity',
]

const dimensions = ref([
    { name: 'color', valuesText: '' },
    { name: 'size', valuesText: '' },
])

const imagePickerTarget = ref(null)
const showImagePicker = ref(false)
const pendingDeleteIndex = ref(null)
const deleteConfirmRef = ref(null)

function generate5DigitSku() {
    let sku = ''

    for (let i = 0; i < 5; i++) {
        sku += Math.floor(Math.random() * 10)
    }

    return sku
}

function addDimension() {
    dimensions.value.push({ name: '', valuesText: '' })
}

function removeDimension(index) {
    dimensions.value.splice(index, 1)
}

function generateVariants() {
    const valueGroups = dimensions.value
        .map(d => ({ name: d.name.trim(), values: d.valuesText.split(',').map(v => v.trim()).filter(Boolean) }))
        .filter(g => g.name && g.values.length > 0)

    if (valueGroups.length === 0) {
return
}

    const valueArrays = valueGroups.map(g => g.values)

    function cartesian(arrays) {
        return arrays.reduce((a, b) => a.flatMap(d => b.map(e => [...d, e])), [[]])
    }

    const combinations = cartesian(valueArrays)

    // Map existing variant data by index for preservation
    const existing = props.form.variants || []

    props.form.variants = combinations.map((combo, i) => {
        const prev = existing[i] || null

        return {
            id: prev?.id ?? null,
            sku: prev?.sku ?? generate5DigitSku(),
            unit_price: prev?.unit_price ?? props.form.unit_price ?? '',
            sale_price: prev?.sale_price ?? props.form.sale_price ?? '',
            quantity: prev?.quantity ?? 10,
            product_image_id: prev?.product_image_id ?? null,
            image_url: prev?.image_url ?? null,
            options: combo.map((val, j) => ({ name: valueGroups[j].name, value: val })),
        }
    })
}

function mapExistingVariants() {
    if (! props.product?.variants?.length) {
return
}

    // Extract dimensions from existing variants' options
    const dimMap = new Map()

    for (const v of props.product.variants) {
        const opts = v.options || []

        for (const opt of opts) {
            if (! dimMap.has(opt.name)) {
                dimMap.set(opt.name, new Set())
            }

            dimMap.get(opt.name).add(opt.value)
        }
    }

    if (dimMap.size > 0) {
        dimensions.value = Array.from(dimMap.entries()).map(([name, values]) => ({
            name,
            valuesText: Array.from(values).join(', '),
        }))
    }

    props.form.variants = props.product.variants.map(v => ({
        id: v.id,
        sku: v.sku || '',
        unit_price: v.unit_price ?? '',
        sale_price: v.sale_price ?? '',
        quantity: v.quantity,
        product_image_id: v.product_image_id || null,
        image_url: v.image?.image_url || null,
        options: v.options || [],
    }))
}

onMounted(() => {
    document.addEventListener('click', handleDeleteClickOutside)
    document.addEventListener('keydown', handleDeleteKeydown)

    if (hasVariants.value) {
        mapExistingVariants()
    }
})
onUnmounted(() => {
    document.removeEventListener('click', handleDeleteClickOutside)
    document.removeEventListener('keydown', handleDeleteKeydown)
})

function requestRemoveVariant(index) {
    pendingDeleteIndex.value = index
}

function confirmRemoveVariant() {
    if (pendingDeleteIndex.value === null) {
return
}

    props.form.variants.splice(pendingDeleteIndex.value, 1)
    pendingDeleteIndex.value = null
}

function handleDeleteClickOutside(e) {
    if (deleteConfirmRef.value && !deleteConfirmRef.value.contains(e.target)) {
        pendingDeleteIndex.value = null
    }
}

function handleDeleteKeydown(e) {
    if (e.key === 'Escape') {
        pendingDeleteIndex.value = null
    }
}

function variantDeleteLabel(index) {
    const label = (props.form.variants[index]?.options || [])
        .map(o => o.value).join(' / ') || 'this variant'

    return `Remove "${label}"?`
}

function variantSku(index) {
    props.form.variants[index].sku = generate5DigitSku()
}

function variantLabel(variant) {
    const opts = variant.options || []

    return opts.map(o => o.value).join(' / ')
}

function openImagePicker(variantIndex) {
    imagePickerTarget.value = variantIndex
    showImagePicker.value = true
}

function selectImage(image) {
    if (imagePickerTarget.value !== null) {
        props.form.variants[imagePickerTarget.value].product_image_id = image.id
        props.form.variants[imagePickerTarget.value].image_url = image.image_url || '/' + image.image_path
    }

    showImagePicker.value = false
    imagePickerTarget.value = null
}

function closeImagePicker() {
    showImagePicker.value = false
    imagePickerTarget.value = null
}
</script>

<template>
    <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
        <div class="flex items-center gap-3 mb-4">
            <input id="has_variants" v-model="hasVariants" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
            <label for="has_variants" class="text-sm font-medium text-gray-700 dark:text-gray-300">This product has variants</label>
        </div>

        <template v-if="hasVariants">
            <!-- Dimension builder -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 space-y-3 mb-4">
                <div v-for="(dim, di) in dimensions" :key="di" class="flex items-start gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Variant Name</label>
                        <input
                            v-model="dim.name"
                            type="text"
                            :list="'dim-name-' + di"
                            placeholder="e.g: color, size, weight"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <datalist :id="'dim-name-' + di">
                            <option v-for="s in VARIANT_NAME_SUGGESTIONS" :key="s" :value="s" />
                        </datalist>
                    </div>
                    <div class="flex-[2]">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Values (comma separated)</label>
                        <input v-model="dim.valuesText" type="text" placeholder="e.g: Red, Pink, Yellow" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <button v-if="dimensions.length > 1" type="button" @click="removeDimension(di)" class="mt-6 p-1.5 text-red-500 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition" title="Remove dimension">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="addDimension" class="text-xs font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition">
                        + Add variant option
                    </button>
                </div>

                <button type="button" @click="generateVariants" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                    Generate Variants
                </button>
            </div>

            <p v-if="form.errors['variants']" class="text-sm text-red-600 mb-2">{{ form.errors['variants'] }}</p>

            <!-- Variants table -->
            <div v-if="form.variants.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Image</th>
                            <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Variant</th>
                            <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">SKU</th>
                            <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Unit Price</th>
                            <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Sale Price</th>
                            <th class="text-left py-2 pr-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap">Qty</th>
                            <th class="py-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(variant, vi) in form.variants" :key="vi" class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 pr-3">
                                <button type="button" @click="openImagePicker(vi)" class="w-10 h-10 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden hover:border-blue-400 dark:hover:border-blue-500 transition">
                                    <img v-if="variant.image_url" :src="variant.image_url" class="w-full h-full object-cover" />
                                    <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>
                            </td>
                            <td class="py-2 pr-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="(opt, oi) in variant.options" :key="oi" class="inline-flex items-center gap-1 px-1.5 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                                        <span class="text-gray-500 dark:text-gray-400">{{ opt.name }}:</span>
                                        {{ opt.value }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-2 pr-3">
                                <div class="flex items-center gap-1">
                                    <input v-model="variant.sku" type="text" class="w-16 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                    <button type="button" @click="variantSku(vi)" class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition" title="Generate SKU">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="py-2 pr-3">
                                <input v-model="variant.unit_price" type="number" min="0" class="w-20 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </td>
                            <td class="py-2 pr-3">
                                <input v-model="variant.sale_price" type="number" min="0" class="w-20 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </td>
                            <td class="py-2 pr-3">
                                <input v-model="variant.quantity" type="number" min="0" class="w-16 rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </td>
                            <td class="py-2">
                                <div v-if="form.variants.length > 1" ref="deleteConfirmRef" class="relative inline-flex">
                                    <button type="button" @click="requestRemoveVariant(vi)" class="p-2 text-red-500 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition" title="Remove variant">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    <div v-if="pendingDeleteIndex === vi" class="absolute z-50 right-full mr-2 top-1/2 -translate-y-1/2 w-56 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl p-3 text-left">
                                        <div class="absolute -right-1 top-1/2 -translate-y-1/2 w-2 h-2 rotate-45 bg-white dark:bg-gray-900 border-r border-t border-gray-200 dark:border-gray-700"></div>
                                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ variantDeleteLabel(vi) }}</p>
                                        <div class="flex items-center gap-2 mt-3">
                                            <button @click="confirmRemoveVariant" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-red-600 hover:bg-red-700 transition">Remove</button>
                                            <button @click="pendingDeleteIndex = null" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Image picker modal -->
    <Teleport to="body">
        <div v-if="showImagePicker" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closeImagePicker">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-800 p-6 max-w-lg w-full mx-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Select Image</h3>
                    <button type="button" @click="closeImagePicker" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div v-if="product?.images?.length" class="grid grid-cols-4 gap-3">
                    <button v-for="img in product.images" :key="img.id" type="button" @click="selectImage(img)" class="aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-500 transition focus:outline-none focus:border-blue-500">
                        <img :src="img.image_url || '/' + img.image_path" :alt="product.title" class="w-full h-full object-cover" />
                    </button>
                </div>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400">No images available. Upload images first.</p>
            </div>
        </div>
    </Teleport>
</template>
