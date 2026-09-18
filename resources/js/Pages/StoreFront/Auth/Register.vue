<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue';

const page = usePage();
const settings = computed(() => page.props.settings || {});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    mobile: '',
    company: '',
    address: '',
});

const whatsappHref = computed(() => {
    const number = String(settings.value.whatsapp_number || '').replace(/\D/g, '');

    return number ? `https://wa.me/${number}` : null;
});

const socials = computed(() => [
    { handler: settings.value.facebook_handler, label: 'Facebook' },
    { handler: settings.value.instagram_handler, label: 'Instagram' },
    { handler: settings.value.x_handler, label: 'X' },
    { handler: settings.value.youtube_handler, label: 'YouTube' },
    { handler: settings.value.tread_handler, label: 'Threads' },
].filter((s) => s.handler && s.handler !== '#'));

function submit() {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}

const reasons = [
    {
        title: 'Wholesale pricing',
        text: 'Buy at reseller rates and keep a healthy margin on every sale.',
    },
    {
        title: 'Cash on delivery',
        text: 'Order with confidence and pay only when your goods arrive.',
    },
    {
        title: 'Daily restocks',
        text: 'Fresh arrivals and hot sellers added to the catalog regularly.',
    },
    {
        title: 'Dedicated support',
        text: 'A real team on call, WhatsApp and email for resellers.',
    },
];

const steps = [
    {
        step: '01',
        title: 'Sign up',
        text: 'Fill in your details to request a reseller account.',
    },
    {
        step: '02',
        title: 'Approval',
        text: 'Our team reviews and activates your account quickly.',
    },
    {
        step: '03',
        title: 'Shop & resell',
        text: 'Order at wholesale prices and deliver to your customers.',
    },
];
</script>

<template>
    <Head :title="`Reseller Registration | ${settings.company_name || 'Eghuri'}`" />

    <FrontEndMaster>
        <div class="mx-auto max-w-6xl">
            <!-- Hero banner -->
            <div
                class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-primary via-[#9c5a50] to-[#D9531E] p-6 text-white sm:p-8 md:mb-10 md:rounded-3xl md:p-10"
            >
                <div
                    class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"
                ></div>
                <div
                    class="absolute -bottom-20 left-1/3 h-52 w-52 rounded-full bg-white/5 blur-3xl"
                ></div>

                <div class="relative">
                    <p
                        class="text-xs font-semibold uppercase tracking-widest text-white/70"
                    >
                        Reseller Programme
                    </p>
                    <h1
                        class="mt-1.5 text-2xl font-bold leading-tight sm:text-3xl md:text-4xl"
                    >
                        Become a reseller with
                        <span class="whitespace-nowrap">{{
                            settings.company_name || 'Eghuri'
                        }}</span>
                    </h1>
                    <p
                        class="mt-2 max-w-xl text-sm leading-relaxed text-white/80 sm:text-base"
                    >
                        {{
                            settings.company_description ||
                            'Register to unlock wholesale pricing, track orders and build your own business.'
                        }}
                    </p>

                    <div
                        class="mt-4 h-1.5 w-20 rounded-full bg-gradient-to-r from-[#D9531E] via-amber-400 to-emerald-400 md:mt-5"
                    ></div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:gap-8 lg:grid-cols-5">
                <!-- Info column -->
                <div
                    class="order-2 space-y-5 lg:order-1 lg:col-span-2"
                >
                    <!-- Why resell -->
                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6"
                    >
                        <h2
                            class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        >
                            Why resell with us?
                        </h2>
                        <ul class="mt-4 space-y-4">
                            <li
                                v-for="r in reasons"
                                :key="r.title"
                                class="flex gap-3"
                            >
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-primary/20"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </span>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        {{ r.title }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
                                    >
                                        {{ r.text }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- How it works -->
                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6"
                    >
                        <h2
                            class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        >
                            How it works
                        </h2>
                        <ol class="mt-4 space-y-4">
                            <li v-for="(s, i) in steps" :key="s.step" class="flex gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-sm font-bold text-primary dark:bg-gray-800"
                                >
                                    {{ i + 1 }}
                                </span>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        {{ s.title }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
                                    >
                                        {{ s.text }}
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <!-- Contact from admin panel -->
                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                class="h-5 w-5 text-primary"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"
                                />
                            </svg>
                            <h2
                                class="text-base font-bold text-gray-800 dark:text-gray-100"
                            >
                                Talk to our team
                            </h2>
                        </div>

                        <div class="mt-4 space-y-3">
                            <a
                                v-if="settings.company_mobile || settings.company_email"
                                :href="settings.company_mobile ? `tel:${String(settings.company_mobile).replace(/\s/g, '')}` : `mailto:${settings.company_email}`"
                                class="flex items-center gap-3 text-sm text-gray-600 transition hover:text-primary dark:text-gray-300 dark:hover:text-primary"
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"
                                        />
                                    </svg>
                                </span>
                                <span>{{
                                    settings.company_mobile || settings.company_email
                                }}</span>
                            </a>

                            <a
                                v-if="whatsappHref"
                                :href="whatsappHref"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center gap-3 text-sm text-gray-600 transition hover:text-primary dark:text-gray-300 dark:hover:text-primary"
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800"
                                >
                                    <svg
                                        class="h-4 w-4 text-green-600 dark:text-green-400"
                                        fill="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.1-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                                        />
                                    </svg>
                                </span>
                                <span>{{ settings.whatsapp_number }}</span>
                            </a>

                            <p
                                v-if="settings.company_address"
                                class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300"
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"
                                        />
                                    </svg>
                                </span>
                                <span class="leading-relaxed">{{
                                    settings.company_address
                                }}</span>
                            </p>

                            <p
                                v-if="settings.company_working_hours"
                                class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-300"
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </span>
                                <span>{{ settings.company_working_hours }}</span>
                            </p>
                        </div>

                        <div
                            v-if="socials.length"
                            class="mt-5 flex items-center gap-2 border-t border-gray-100 pt-4 dark:border-gray-800"
                        >
                            <span
                                class="text-xs font-medium text-gray-400 dark:text-gray-500"
                                >Follow us:</span
                            >
                            <a
                                v-for="s in socials"
                                :key="s.label"
                                :href="s.handler"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-full p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-primary dark:hover:bg-gray-800 dark:hover:text-primary"
                                :aria-label="s.label"
                                :title="s.label"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M13.5 9H17l.5 2h-4v9h-3v-9H8.5v-2H10.5V7.345C10.5 5.388 11.69 4 13.733 4c.65 0 1.317.078 1.767.148V6h-.817c-.883 0-1.183.392-1.183 1.114V9z"
                                    />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Registration form -->
                <div class="order-1 lg:order-2 lg:col-span-3">
                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8 md:p-10"
                    >
                        <div class="mb-7 text-center sm:text-left">
                            <h2
                                class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                            >
                                Reseller Registration
                            </h2>
                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Already a reseller?
                                <Link
                                    :href="route('login')"
                                    class="font-medium text-primary hover:text-primary/80"
                                    >Sign in to your account</Link
                                >
                            </p>
                        </div>

                        <div
                            class="mb-6 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            <svg
                                class="mt-0.5 h-4 w-4 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                />
                            </svg>
                            <span>
                                After you submit, our team will review and
                                activate your account. You'll be able to shop at
                                reseller prices right after approval.
                            </span>
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <!-- Name -->
                                <div>
                                    <label
                                        for="reg-name"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Full Name <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        id="reg-name"
                                        type="text"
                                        v-model="form.name"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                        placeholder="Your full name"
                                    />
                                    <p
                                        v-if="form.errors.name"
                                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                    >
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label
                                        for="reg-email"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Email <span class="text-red-500">*</span></label
                                    >
                                    <input
                                        id="reg-email"
                                        type="email"
                                        v-model="form.email"
                                        required
                                        autocomplete="username"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                        placeholder="you@example.com"
                                    />
                                    <p
                                        v-if="form.errors.email"
                                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                    >
                                        {{ form.errors.email }}
                                    </p>
                                </div>

                                <!-- Mobile -->
                                <div>
                                    <label
                                        for="reg-mobile"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Mobile number</label
                                    >
                                    <input
                                        id="reg-mobile"
                                        type="tel"
                                        v-model="form.mobile"
                                        autocomplete="tel"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                        placeholder="01xxxxxxxxx"
                                    />
                                </div>

                                <!-- Company -->
                                <div>
                                    <label
                                        for="reg-company"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Company name</label
                                    >
                                    <input
                                        id="reg-company"
                                        type="text"
                                        v-model="form.company"
                                        autocomplete="organization"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                        placeholder="Your company name"
                                    />
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <label
                                    for="reg-address"
                                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Business address</label
                                >
                                <textarea
                                    id="reg-address"
                                    v-model="form.address"
                                    rows="2"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                    placeholder="Your business address"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <!-- Password -->
                                <div>
                                    <label
                                        for="reg-password"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Password <span class="text-red-500">*</span></label
                                    >
                                    <div class="relative">
                                        <input
                                            :type="showPassword ? 'text' : 'password'"
                                            id="reg-password"
                                            v-model="form.password"
                                            required
                                            autocomplete="new-password"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 pr-11 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                            placeholder="Minimum 8 characters"
                                        />
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                        >
                                            <svg
                                                v-if="!showPassword"
                                                class="h-[18px] w-[18px]"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="h-[18px] w-[18px]"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <p
                                        v-if="form.errors.password"
                                        class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                    >
                                        {{ form.errors.password }}
                                    </p>
                                </div>

                                <!-- Confirm password -->
                                <div>
                                    <label
                                        for="reg-password-confirmation"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Confirm password <span class="text-red-500">*</span></label
                                    >
                                    <div class="relative">
                                        <input
                                            :type="showConfirmPassword ? 'text' : 'password'"
                                            id="reg-password-confirmation"
                                            v-model="form.password_confirmation"
                                            required
                                            autocomplete="new-password"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 pr-11 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                            placeholder="Re-enter your password"
                                        />
                                        <button
                                            type="button"
                                            @click="showConfirmPassword = !showConfirmPassword"
                                            :aria-label="showConfirmPassword ? 'Hide password' : 'Show password'"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                        >
                                            <svg
                                                v-if="!showConfirmPassword"
                                                class="h-[18px] w-[18px]"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="h-[18px] w-[18px]"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-semibold text-white shadow-md shadow-primary/20 transition hover:bg-on-primary-container disabled:opacity-50"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="h-4 w-4 animate-spin"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                    />
                                </svg>
                                {{
                                    form.processing
                                        ? 'Submitting...'
                                        : 'Create Reseller Account'
                                }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </FrontEndMaster>
</template>