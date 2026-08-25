<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const boutiqueCategory = computed(() => {
    const topLevel = props.categories.filter(c => !c.parent_id && c.image_url);
    if (!topLevel.length) return null;
    return topLevel[Math.floor(Math.random() * topLevel.length)];
});
</script>

<template>
    <section class="mb-16">
        <div class="rounded-3xl bg-surface-container dark:bg-[#241d1c] overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <!-- Image half -->
                <div class="relative aspect-square md:h-[500px] overflow-hidden">
                    <img
                        v-if="boutiqueCategory"
                        :src="boutiqueCategory.image_url"
                        :alt="boutiqueCategory.name"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <img
                        v-else
                        src="https://images.pexels.com/photos/30703877/pexels-photo-30703877.jpeg?auto=compress&cs=tinysrgb&w=800"
                        alt="Boutique Style Collection"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent to-surface-container/20 dark:to-[#241d1c]/20"></div>
                </div>

                <!-- Text half -->
                <div class="flex flex-col justify-center p-8 md:p-12 lg:p-16">
                    <span class="text-xs font-semibold uppercase tracking-widest text-primary dark:text-[#f6b7b2]">New for 2026</span>
                    <h2 class="mt-3 text-2xl font-bold text-charcoal dark:text-[#f9eeed] md:text-3xl lg:text-4xl">{{ boutiqueCategory?.name || 'Boutique Style' }}</h2>
                    <p class="mt-4 max-w-md text-sm leading-relaxed text-on-surface-variant dark:text-[#cbb8b6]">
                        Discover our handpicked collection of premium pieces, designed for those who appreciate understated elegance and timeless sophistication.
                    </p>
                    <Link
                        :href="boutiqueCategory ? route('category.show', boutiqueCategory.slug || boutiqueCategory.id) : route('products.index')"
                        class="mt-8 inline-flex w-fit items-center gap-2 rounded-full bg-charcoal px-6 py-3 text-sm font-medium text-white transition hover:bg-primary dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]"
                    >
                        View Collection
                        <span class="material-symbols-outlined text-[16px]" aria-hidden="true">arrow_forward</span>
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
