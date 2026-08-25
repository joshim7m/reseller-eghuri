<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

const page = usePage()
const auth = page.props.auth

const userDetail = auth?.user?.user_detail

const form = useForm({
    name: auth?.user?.name || '',
    email: auth?.user?.email || '',
    mobile: userDetail?.mobile || '',
    company: userDetail?.company || '',
    address: userDetail?.address || ''
})

function updateProfile() {
    form.patch(route('profile.update'), {
        preserveScroll: true
    })
}
</script>

<template>
    <Head title="My Profile" />

    <FrontEndMaster>
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl md:text-4xl font-bold text-charcoal dark:text-[#f9eeed] mb-2">My Profile</h1>
            <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6] mb-8">Manage your account information.</p>

            <form @submit.prevent="updateProfile" class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-6 md:p-8 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-charcoal dark:text-[#f9eeed] mb-1.5">Name</label>
                    <input type="text" v-model="form.name"
                        class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition"
                        required />
                    <p v-if="form.errors.name" class="mt-1.5 text-sm text-sale-price">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal dark:text-[#f9eeed] mb-1.5">Email</label>
                    <input type="email" v-model="form.email"
                        class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition"
                        required />
                    <p v-if="form.errors.email" class="mt-1.5 text-sm text-sale-price">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal dark:text-[#f9eeed] mb-1.5">Mobile</label>
                    <input type="tel" v-model="form.mobile"
                        class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
                    <p v-if="form.errors.mobile" class="mt-1.5 text-sm text-sale-price">{{ form.errors.mobile }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal dark:text-[#f9eeed] mb-1.5">Company</label>
                    <input type="text" v-model="form.company"
                        class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-charcoal dark:text-[#f9eeed] mb-1.5">Address</label>
                    <textarea v-model="form.address" rows="3"
                        class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition resize-none"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="px-6 py-2.5 bg-charcoal text-white rounded-xl text-sm font-semibold hover:bg-primary transition disabled:opacity-50 dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                    <Link :href="route('home')"
                        class="px-6 py-2.5 border border-outline-variant dark:border-[#3a302e] text-on-surface-variant dark:text-[#cbb8b6] rounded-xl text-sm font-medium hover:bg-surface-container dark:hover:bg-[#241d1c] transition">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </FrontEndMaster>
</template>
