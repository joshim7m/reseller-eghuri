<script setup>
const form = defineModel({ type: Object })
</script>

<template>
    <div class="space-y-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide">SEO (Search Engine Optimization)</h3>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Title</label>
            <input v-model="form.meta_title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Leave blank to use default pattern" />
            <p class="mt-1 text-xs text-gray-400">50-60 chars recommended. Overrides the global product title pattern.</p>
            <p v-if="form.errors?.meta_title" class="mt-1 text-sm text-red-600">{{ form.errors.meta_title }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Description</label>
            <textarea v-model="form.meta_description" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Leave blank to use default pattern"></textarea>
            <p class="mt-1 text-xs text-gray-400">140-160 chars recommended. First sentence should be actionable for BD buyers (e.g. "COD available in Dhaka").</p>
            <p v-if="form.errors?.meta_description" class="mt-1 text-sm text-red-600">{{ form.errors.meta_description }}</p>
        </div>
    </div>
</template>
