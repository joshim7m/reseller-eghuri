<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const props = defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, required: true },
})

const search = ref('')
const categoryId = ref('')
const statusFilter = ref('')
const featuredFilter = ref('')
const showDeleteDialog = ref(false)
const deletingId = ref(null)
const showCategoryDropdown = ref(false)
const categoryDropdownRef = ref(null)

const categoryOptions = computed(() => {
    const options = []
    const walk = (cats, depth) => {
        for (const cat of cats) {
            options.push({ id: cat.id, name: cat.name, depth })

            if (cat.children?.length) {
walk(cat.children, depth + 1)
}
        }
    }
    walk(props.categories, 0)

    return options
})

const selectedCategoryName = computed(() => {
    if (!categoryId.value) {
return 'All Categories'
}

    if (categoryId.value === 'none') {
return 'No Category'
}

    return categoryOptions.value.find((option) => option.id === categoryId.value)?.name ?? 'All Categories'
})

function selectCategory(id) {
    categoryId.value = id
    showCategoryDropdown.value = false
}

function handleClickOutside(e) {
    if (categoryDropdownRef.value && !categoryDropdownRef.value.contains(e.target)) {
        showCategoryDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    document.addEventListener('click', handleToggleClickOutside)
    document.addEventListener('keydown', handleToggleKeydown)
})
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    document.removeEventListener('click', handleToggleClickOutside)
    document.removeEventListener('keydown', handleToggleKeydown)
})

function truncate(text, len = 50) {
    return text?.length > len ? text.substring(0, len) + '...' : text
}

let debounceTimer
watch([search, categoryId, statusFilter, featuredFilter], () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        router.get(route('admin.products.index'), {
            search: search.value,
            category_id: categoryId.value,
            status: statusFilter.value,
            featured: featuredFilter.value,
        }, { preserveState: true, replace: true })
    }, 300)
})

function destroyProduct(id) {
    deletingId.value = id
    showDeleteDialog.value = true
}

function confirmDelete() {
    router.delete(route('admin.products.destroy', deletingId.value), {
        onFinish: () => {
            showDeleteDialog.value = false
            deletingId.value = null
        }
    })
}

const pendingToggle = ref(null)
const togglingKey = ref('')
const toggleConfirmRef = ref(null)

const togglePopoverMessage = computed(() => {
    if (!pendingToggle.value) {
return ''
}

    const { product, field, next } = pendingToggle.value
    const title = truncate(product.title, 30)

    if (field === 'status') {
        return next === 'active'
            ? `Activate "${title}"?`
            : `Deactivate "${title}"?`
    }

    return next
        ? `Feature "${title}"?`
        : `Remove "${title}" from featured?`
})

function isPending(product, field) {
    return pendingToggle.value?.product?.id === product.id && pendingToggle.value?.field === field
}

function handleToggleClickOutside(e) {
    if (toggleConfirmRef.value && !toggleConfirmRef.value.contains(e.target)) {
        pendingToggle.value = null
    }
}

function handleToggleKeydown(e) {
    if (e.key === 'Escape') {
        pendingToggle.value = null
    }
}

function requestToggle(product, field) {
    const next = field === 'status'
        ? (product.status === 'active' ? 'inactive' : 'active')
        : !product.featured

    pendingToggle.value = { product, field, next }
}

function confirmToggle() {
    if (!pendingToggle.value) {
return
}

    const { product, field, next } = pendingToggle.value
    const routeName = field === 'status' ? 'admin.products.update-status' : 'admin.products.update-featured'
    const payload = field === 'status' ? { status: next } : { featured: next }

    pendingToggle.value = null
    togglingKey.value = `${product.id}-${field}`

    router.patch(route(routeName, product.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            togglingKey.value = ''
        },
    })
}

function isToggling(product, field) {
    return togglingKey.value === `${product.id}-${field}`
}

function formatPrice(price) {
    return '৳' + price.toLocaleString('en-IN')
}
</script>

<template>
    <Head title="Products" />

    <AdminMaster>
        <div class="space-y-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Products</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your product inventory</p>
                </div>
                <Link :href="route('admin.products.create')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Product
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex flex-wrap gap-3">
                    <input v-model="search" type="text" placeholder="Search by title or sku..." class="flex-1 min-w-[200px] rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    <select v-model="statusFilter" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="draft">Draft</option>
                    </select>
                    <select v-model="featuredFilter" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Featured</option>
                        <option value="true">Featured</option>
                        <option value="false">Not Featured</option>
                    </select>
                    <div ref="categoryDropdownRef" class="relative">
                        <button @click.stop="showCategoryDropdown = !showCategoryDropdown" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm px-3 py-2 min-w-[180px] justify-between hover:border-gray-400 dark:hover:border-gray-600 transition">
                            <span class="truncate">{{ selectedCategoryName }}</span>
                            <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div v-show="showCategoryDropdown" class="absolute z-50 mt-1 w-full min-w-[220px] rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg max-h-60 overflow-y-auto admin-scrollbar">
                            <button @click="selectCategory('')" class="w-full text-left px-3 py-2 text-sm transition" :class="!categoryId ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'">All Categories</button>
                            <button @click="selectCategory('none')" class="w-full text-left px-3 py-2 text-sm transition" :class="categoryId === 'none' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'">No Category</button>
                            <button v-for="option in categoryOptions" :key="option.id" @click="selectCategory(option.id)" class="w-full text-left px-3 py-2 text-sm transition" :style="{ paddingLeft: (12 + option.depth * 16) + 'px' }" :class="categoryId === option.id ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'">{{ option.name }}</button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto admin-scrollbar">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400">
                                <th class="text-left px-4 py-3 font-medium w-12">SN</th>
                                <th class="text-left px-4 py-3 font-medium">Product</th>
                                <th class="text-left px-4 py-3 font-medium">Category</th>
                                <th class="text-right px-4 py-3 font-medium">Price</th>
                                <th class="text-center px-4 py-3 font-medium">Stock</th>
                                <th class="text-center px-4 py-3 font-medium">Status</th>
                                <th class="text-center px-4 py-3 font-medium">Featured</th>
                                <th class="text-right px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="(product, index) in products.data" :key="product.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ products.from + index }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 overflow-hidden shrink-0">
                                            <img v-if="product.image_url" :src="product.image_url" :alt="product.title" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">N/A</div>
                                        </div>
                                        <div class="min-w-0">
                                            <Link :href="route('admin.products.edit', product.id)" class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition block truncate">{{ truncate(product.title, 40) }}</Link>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate">{{ product.sku }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-if="product.categories?.length" v-for="cat in product.categories" :key="cat.id" class="inline-flex px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">{{ cat.name }}</span>
                                        <span v-else class="text-gray-600 dark:text-gray-400">{{ product.category?.name || '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="text-gray-900 dark:text-white font-medium">{{ formatPrice(product.sale_price) }}</div>
                                    <div class="text-xs text-gray-400">{{ formatPrice(product.unit_price) }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-sm" :class="product.total_stock <= 5 ? 'text-red-600 font-medium' : 'text-gray-600 dark:text-gray-400'">{{ product.total_stock }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="relative inline-flex items-center justify-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" role="switch" :aria-checked="product.status === 'active'" :disabled="isToggling(product, 'status')" @click="requestToggle(product, 'status')" class="relative inline-flex h-4 w-7 items-center rounded-full transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50" :class="product.status === 'active' ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'">
                                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition" :class="product.status === 'active' ? 'translate-x-4' : 'translate-x-0'" />
                                            </button>
                                            <span class="text-xs capitalize text-gray-500 dark:text-gray-400">{{ product.status }}</span>
                                        </div>
                                        <div v-if="isPending(product, 'status')" ref="toggleConfirmRef" class="absolute z-50 left-1/2 top-full mt-2 w-64 -translate-x-1/2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl p-3 text-left">
                                            <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 rotate-45 bg-white dark:bg-gray-900 border-l border-t border-gray-200 dark:border-gray-700"></div>
                                            <p class="text-sm text-gray-800 dark:text-gray-200">{{ togglePopoverMessage }}</p>
                                            <div class="flex items-center gap-2 mt-3">
                                                <button @click="confirmToggle" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 transition" :disabled="isToggling(product, 'status')">{{ isToggling(product, 'status') ? 'Saving...' : 'Confirm' }}</button>
                                                <button @click="pendingToggle = null" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="relative inline-flex items-center justify-center">
                                        <button type="button" role="switch" :aria-checked="product.featured" :disabled="isToggling(product, 'featured')" @click="requestToggle(product, 'featured')" class="relative inline-flex h-4 w-7 items-center rounded-full transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50" :class="product.featured ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600'">
                                            <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition" :class="product.featured ? 'translate-x-4' : 'translate-x-0'" />
                                        </button>
                                        <div v-if="isPending(product, 'featured')" ref="toggleConfirmRef" class="absolute z-50 left-1/2 top-full mt-2 w-64 -translate-x-1/2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xl p-3 text-left">
                                            <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 rotate-45 bg-white dark:bg-gray-900 border-l border-t border-gray-200 dark:border-gray-700"></div>
                                            <p class="text-sm text-gray-800 dark:text-gray-200">{{ togglePopoverMessage }}</p>
                                            <div class="flex items-center gap-2 mt-3">
                                                <button @click="confirmToggle" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-amber-600 hover:bg-amber-700 transition" :disabled="isToggling(product, 'featured')">{{ isToggling(product, 'featured') ? 'Saving...' : 'Confirm' }}</button>
                                                <button @click="pendingToggle = null" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link :href="route('admin.products.edit', product.id)" class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-600 hover:text-white bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-600 dark:hover:bg-blue-600 transition" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </Link>
                                        <button @click="destroyProduct(product.id)" class="w-8 h-8 rounded-lg flex items-center justify-center text-red-600 hover:text-white bg-red-50 dark:bg-red-900/20 hover:bg-red-600 dark:hover:bg-red-600 transition" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No products found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="products.last_page > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Showing {{ products.from }} to {{ products.to }} of {{ products.total }} entries</p>
                    <div class="flex gap-1">
                        <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="px-3 py-1.5 text-sm rounded-lg transition" :class="link.active ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'" />
                    </div>
                </div>
            </div>
        </div>
        <ConfirmDialog
            :show="showDeleteDialog"
            title="Delete Product"
            message="Are you sure you want to delete this product?"
            @confirm="confirmDelete"
            @cancel="showDeleteDialog = false"
        />
    </AdminMaster>
</template>