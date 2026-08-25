<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineProps({
    products: { type: Object, required: true },
    categories: { type: Array, required: true },
})

const search = ref('')
const categoryId = ref('')
const showDeleteDialog = ref(false)
const deletingId = ref(null)

function truncate(text, len = 50) {
    return text?.length > len ? text.substring(0, len) + '...' : text
}

let debounceTimer
watch([search, categoryId], () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        router.get(route('admin.products.index'), { search: search.value, category_id: categoryId.value }, { preserveState: true, replace: true })
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

function formatPrice(price) {
    return '৳' + price.toLocaleString('en-IN')
}

function statusBadge(status) {
    const map = { active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400', draft: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }
    return map[status] || 'bg-gray-100 text-gray-800'
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
                    <input v-model="search" type="text" placeholder="Search products..." class="flex-1 min-w-[200px] rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    <select v-model="categoryId" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
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
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusBadge(product.status)">{{ product.status }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="product.featured" class="text-amber-500 text-lg">&#9733;</span>
                                    <span v-else class="text-gray-300 dark:text-gray-600 text-lg">&#9734;</span>
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
