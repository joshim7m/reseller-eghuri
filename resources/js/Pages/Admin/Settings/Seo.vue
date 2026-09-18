<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'

const settings = usePage().props.settings || {}

const form = useForm({
    seo_title_suffix: settings.seo_title_suffix || '| Eghuri',
    seo_keywords: settings.seo_keywords || 'nightdress, night wear, sexy clothes, sexy women clothing, women secret wear, reseller website in bangladesh, resell kori, chinese bra panty, chinese nightdress in bangladesh, lingerie online bangladesh, reseller shop in dhaka, wholesale dress in dhaka, নাইটড্রেস, রিসেল করি',
    seo_home_title: settings.seo_home_title || '',
    seo_home_description: settings.seo_home_description || '',
    seo_products_title: settings.seo_products_title || '',
    seo_products_description: settings.seo_products_description || '',
    seo_new_arrivals_title: settings.seo_new_arrivals_title || '',
    seo_new_arrivals_description: settings.seo_new_arrivals_description || '',
    seo_hot_sale_title: settings.seo_hot_sale_title || '',
    seo_hot_sale_description: settings.seo_hot_sale_description || '',
    seo_categories_title: settings.seo_categories_title || '',
    seo_categories_description: settings.seo_categories_description || '',
    seo_category_title_pattern: settings.seo_category_title_pattern || '{name} in Bangladesh',
    seo_category_description_pattern: settings.seo_category_description_pattern || 'Buy {name} online in Bangladesh. Best price, COD available, reseller price.',
    seo_product_title_pattern: settings.seo_product_title_pattern || '{title} - Best Price in BD',
    seo_product_description_pattern: settings.seo_product_description_pattern || 'Buy {title} online in Bangladesh. Best price available. Reseller price. WhatsApp for order.',
    seo_page_description_pattern: settings.seo_page_description_pattern || 'Learn more about {title} at Eghuri. Trusted online shopping in Bangladesh.',
    seo_not_found_title: settings.seo_not_found_title || 'Page Not Found',
    seo_not_found_description: settings.seo_not_found_description || 'The page you are looking for does not exist or has been moved.',
    seo_twitter_handle: settings.seo_twitter_handle || '',
    seo_enable_sitemap: settings.seo_enable_sitemap !== undefined ? settings.seo_enable_sitemap === '1' : true,
    seo_schema_org: settings.seo_schema_org !== undefined ? settings.seo_schema_org === '1' : true,
    seo_schema_website: settings.seo_schema_website !== undefined ? settings.seo_schema_website === '1' : true,
    seo_robots_custom: settings.seo_robots_custom || '',
})

function submit() {
    form.seo_enable_sitemap = form.seo_enable_sitemap ? '1' : '0'
    form.seo_schema_org = form.seo_schema_org ? '1' : '0'
    form.seo_schema_website = form.seo_schema_website ? '1' : '0'
    form.post(route('admin.settings.update'))
}
</script>

<template>
    <Head title="SEO Settings" />

    <AdminMaster>
        <div class="max-w-3xl mx-auto space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">SEO Settings</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage global SEO configuration for the storefront</p>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- General -->
                    <section class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">General</h2>

                        <div>
                            <label for="seo_title_suffix" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title Suffix</label>
                            <input id="seo_title_suffix" v-model="form.seo_title_suffix" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="| Store Name" />
                            <p class="mt-1 text-xs text-gray-400">Appended to every page title. E.g. "| Eghuri"</p>
                        </div>

                        <div>
                            <label for="seo_keywords" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Keywords</label>
                            <textarea id="seo_keywords" v-model="form.seo_keywords" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="nightdress, sexy clothes, reseller website in bangladesh"></textarea>
                            <p class="mt-1 text-xs text-gray-400">Comma-separated. Used in &lt;meta name="keywords"&gt; on every storefront page.</p>
                        </div>
                    </section>

                    <!-- Page Titles & Descriptions -->
                    <section class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">Page Titles & Descriptions</h2>
                        <p class="text-xs text-gray-400">Leave blank to use defaults. {name} and {title} are replaced with the entity name.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Home Title</label>
                                <input v-model="form.seo_home_title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Home Description</label>
                                <input v-model="form.seo_home_description" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Products Page Title</label>
                                <input v-model="form.seo_products_title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Products Page Description</label>
                                <input v-model="form.seo_products_description" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Arrivals Title</label>
                                <input v-model="form.seo_new_arrivals_title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Arrivals Description</label>
                                <input v-model="form.seo_new_arrivals_description" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hot Sale Title</label>
                                <input v-model="form.seo_hot_sale_title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hot Sale Description</label>
                                <input v-model="form.seo_hot_sale_description" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categories Index Title</label>
                                <input v-model="form.seo_categories_title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categories Index Description</label>
                                <input v-model="form.seo_categories_description" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>
                    </section>

                    <!-- Entity Patterns -->
                    <section class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">Entity Patterns</h2>
                        <p class="text-xs text-gray-400">Use <code>{name}</code> for categories, <code>{title}</code> for products. These patterns are overridden by entity-level meta fields.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category Title Pattern</label>
                                <input v-model="form.seo_category_title_pattern" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category Description Pattern</label>
                                <input v-model="form.seo_category_description_pattern" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product Title Pattern</label>
                                <input v-model="form.seo_product_title_pattern" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product Description Pattern</label>
                                <input v-model="form.seo_product_description_pattern" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">404 Title</label>
                                <input v-model="form.seo_not_found_title" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">404 Description</label>
                                <input v-model="form.seo_not_found_description" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CMS Page Description Pattern</label>
                            <input v-model="form.seo_page_description_pattern" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                    </section>

                    <!-- Social / Sitemap / Schema -->
                    <section class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">Social & Sitemap</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Twitter Handle</label>
                                <input v-model="form.seo_twitter_handle" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="@yourhandle" />
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <input id="seo_enable_sitemap" v-model="form.seo_enable_sitemap" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                                <label for="seo_enable_sitemap" class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable XML Sitemap (/sitemap.xml)</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="seo_schema_org" v-model="form.seo_schema_org" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                                <label for="seo_schema_org" class="text-sm font-medium text-gray-700 dark:text-gray-300">Output Organization JSON-LD Schema</label>
                            </div>
                            <div class="flex items-center gap-2">
                                <input id="seo_schema_website" v-model="form.seo_schema_website" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                                <label for="seo_schema_website" class="text-sm font-medium text-gray-700 dark:text-gray-300">Output Website JSON-LD Schema</label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom robots.txt body</label>
                            <textarea v-model="form.seo_robots_custom" rows="4" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm shadow-sm font-mono focus:border-blue-500 focus:ring-blue-500" placeholder="Leave empty for default: User-agent: *&#10;Disallow:&#10;Sitemap: {url}/sitemap.xml"></textarea>
                            <p class="mt-1 text-xs text-gray-400">Leave empty for default. Sitemap URL is appended automatically when enabled.</p>
                        </div>
                    </section>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Saving...' : 'Save SEO Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
