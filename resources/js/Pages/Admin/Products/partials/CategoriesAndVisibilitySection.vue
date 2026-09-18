<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    form: { type: Object, required: true },
    categories: { type: Array, required: true },
    mode: { type: String, default: 'multi' },
})

const categorySearch = ref('')
const categoryOpen = ref(false)

const filteredCategories = computed(() =>
    props.categories.filter(c => c.name.toLowerCase().includes(categorySearch.value.toLowerCase()))
)

const selectedCategoryNames = computed(() =>
    props.categories.filter(c => props.form.category_ids.includes(c.id))
)

function toggleCategory(id) {
    const idx = props.form.category_ids.indexOf(id)

    if (idx === -1) {
        props.form.category_ids.push(id)
    } else {
        props.form.category_ids.splice(idx, 1)
    }
}

function onClickOutside(e) {
    if (categoryOpen.value && !e.target.closest('.category-select')) {
        categoryOpen.value = false
    }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Multi-select mode -->
        <div v-if="mode === 'multi'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categories <span class="text-red-500">*</span></label>
            <div class="relative category-select">
                <button type="button" @click="categoryOpen = !categoryOpen" class="w-full flex items-center gap-1 flex-wrap rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 min-h-[42px] text-sm text-left text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <template v-if="selectedCategoryNames.length">
                        <span v-for="cat in selectedCategoryNames" :key="cat.id" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 rounded-full">
                            {{ cat.name }}
                            <button type="button" @click.stop="toggleCategory(cat.id)" class="hover:text-blue-900 dark:hover:text-blue-100">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                    <span v-else class="text-gray-400">Select categories</span>
                    <svg class="w-4 h-4 text-gray-400 ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div v-if="categoryOpen" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-hidden">
                    <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                        <input v-model="categorySearch" type="text" placeholder="Search categories..." class="w-full rounded-md border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-2 py-1.5 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="overflow-y-auto max-h-44 p-1">
                        <label v-for="cat in filteredCategories" :key="cat.id" class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-sm">
                            <input type="checkbox" :checked="form.category_ids.includes(cat.id)" @change="toggleCategory(cat.id)" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" />
                            <span class="text-gray-900 dark:text-white">{{ cat.name }}</span>
                        </label>
                        <p v-if="!filteredCategories.length" class="text-sm text-gray-400 p-2 text-center">No categories found</p>
                    </div>
                </div>
            </div>
            <p v-if="form.errors.category_ids" class="mt-1 text-sm text-red-600">{{ form.errors.category_ids }}</p>
        </div>

        <!-- Single-select mode -->
        <div v-else>
            <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
            <select id="category_id" v-model="form.category_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Select a category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</p>
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status <span class="text-red-500">*</span></label>
            <select id="status" v-model="form.status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="draft">Draft</option>
            </select>
            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <input id="featured" v-model="form.featured" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
        <label for="featured" class="text-sm font-medium text-gray-700 dark:text-gray-300">Featured</label>
    </div>
</template>
