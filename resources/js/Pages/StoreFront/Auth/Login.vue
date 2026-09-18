<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue';

const page = usePage();
const settings = computed(() => page.props.settings || {});

const form = useForm({
    email: '',
    password: '',
    remember: '0',
});

const showPassword = ref(false);

const whatsappHref = computed(() => {
    const number = String(settings.value.whatsapp_number || '').replace(/\D/g, '');

    return number ? `https://wa.me/${number}` : null;
});

function submit() {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            form.remember = '0';
        },
        preserveState: (page) => Object.keys(page.props.errors).length > 0,
    });
}

const benefits = [
    {
        icon: 'tracking',
        title: 'Track your orders',
        text: 'Follow your shipments and reseller orders in real time.',
    },
    {
        icon: 'price',
        title: 'Reseller pricing',
        text: 'Unlock wholesale rates and bulk-order discounts.',
    },
    {
        icon: 'checkout',
        title: 'Faster checkout',
        text: 'Save your details and fly through future purchases.',
    },
    {
        icon: 'heart',
        title: 'Sync your wishlist',
        text: 'Keep favourite items saved and ready for you.',
    },
];
</script>

<template>
    <Head :title="`Sign in | ${settings.company_name || 'Eghuri'}`" />

    <FrontEndMaster>
        <div class="mx-auto max-w-6xl">
            <!-- Hero banner -->
            <div
                class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-primary via-[#9c5a50] to-[#D9531E] p-6 text-white sm:p-8 md:mb-10 md:rounded-3xl md:p-10"
            >
                <div
                    class="custom absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/10 blur-2xl"
                ></div>
                <div
                    class="custom absolute -bottom-20 left-1/3 h-52 w-52 rounded-full bg-white/5 blur-3xl"
                ></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/70">
                        Member Sign In
                    </p>
                    <h1
                        class="mt-1.5 text-2xl font-bold leading-tight sm:text-3xl md:text-4xl"
                    >
                        Welcome back to
                        <span class="whitespace-nowrap">{{
                            settings.company_name || 'Eghuri'
                        }}</span>
                    </h1>
                    <p
                        class="mt-2 max-w-xl text-sm leading-relaxed text-white/80 sm:text-base"
                    >
                        {{
                            settings.company_description ||
                            'Sign in to manage orders, unlocks wholesale pricing and enjoy faster checkout.'
                        }}
                    </p>

                    <div
                        class="mt-4 h-1.5 w-20 rounded-full bg-gradient-to-r from-[#D9531E] via-amber-400 to-emerald-400 md:mt-5"
                    ></div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:gap-8 lg:grid-cols-5">
                <!-- Info column -->
                <div class="order-2 space-y-5 lg:order-1 lg:col-span-2">
                    <!-- Why sign in -->
                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6"
                    >
                        <h2
                            class="text-sm font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        >
                            Why sign in?
                        </h2>
                        <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <li v-for="b in benefits" :key="b.title" class="flex gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary"
                                >
                                    <svg
                                        v-if="b.icon === 'tracking'"
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"
                                        />
                                    </svg>
                                    <svg
                                        v-else-if="b.icon === 'price'"
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <svg
                                        v-else-if="b.icon === 'checkout'"
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .414.336.75.75.75z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                                        />
                                    </svg>
                                </span>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        {{ b.title }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
                                    >
                                        {{ b.text }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Trust strip -->
                    <div
                        class="flex flex-wrap items-center gap-2 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                    >
                        <span
                            v-for="chip in ['Cash on Delivery', 'Secure Payment', 'Easy Returns', '24/7 Support']"
                            :key="chip"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        >
                            <svg
                                class="h-3.5 w-3.5 text-green-600 dark:text-green-400"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4.5 12.75l6 6 9-13.5"
                                />
                            </svg>
                            {{ chip }}
                        </span>
                    </div>

                    <!-- Help card -->
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
                                    d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"
                                />
                            </svg>
                            <h2
                                class="text-base font-bold text-gray-800 dark:text-gray-100"
                            >
                                Need help signing in?
                            </h2>
                        </div>

                        <div class="mt-4 space-y-3">
                            <a
                                v-if="settings.company_email"
                                :href="`mailto:${settings.company_email}`"
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
                                            d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
                                        />
                                    </svg>
                                </span>
                                <span class="break-all">{{ settings.company_email }}</span>
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
                        </div>

                        <Link
                            :href="route('home')"
                            class="mt-4 block w-full rounded-xl border border-primary/30 px-4 py-2.5 text-center text-sm font-semibold text-primary transition hover:bg-primary hover:text-white dark:border-primary/50 dark:text-primary dark:hover:bg-primary"
                        >
                            Explore the store
                        </Link>
                    </div>
                </div>

                <!-- Login card -->
                <div
                    class="order-1 lg:order-2 lg:col-span-3"
                >
                    <div
                        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8 md:p-10"
                    >
                        <div class="mb-7 text-center sm:text-left">
                            <h2
                                class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                            >
                                Sign in to your account
                            </h2>
                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                New to us?
                                <Link
                                    :href="route('register')"
                                    class="font-medium text-primary hover:text-primary/80"
                                    >Create a reseller account</Link
                                >
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <!-- Email -->
                            <div>
                                <label
                                    for="login-email"
                                    class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Email address</label
                                >
                                <div class="relative">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"
                                    >
                                        <svg
                                            class="h-[18px] w-[18px] text-gray-400 dark:text-gray-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        id="login-email"
                                        type="email"
                                        v-model="form.email"
                                        required
                                        autofocus
                                        autocomplete="username"
                                        class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-11 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                        placeholder="you@example.com"
                                    />
                                </div>
                                <p
                                    v-if="form.errors.email"
                                    class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <!-- Password -->
                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label
                                        for="login-password"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >Password</label
                                    >
                                </div>
                                <div class="relative">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"
                                    >
                                        <svg
                                            class="h-[18px] w-[18px] text-gray-400 dark:text-gray-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        :type="showPassword ? 'text' : 'password'"
                                        id="login-password"
                                        v-model="form.password"
                                        required
                                        autocomplete="current-password"
                                        class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-11 pr-11 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500"
                                        placeholder="Enter your password"
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

                            <!-- Remember me -->
                            <label
                                class="flex cursor-pointer select-none items-center gap-2.5"
                            >
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    :true-value="'1'"
                                    :false-value="'0'"
                                    class="h-4 w-4 rounded border-gray-300 bg-white text-primary focus:ring-primary/30 dark:border-gray-600 dark:bg-gray-800"
                                />
                                <span
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                    >Remember me on this device</span
                                >
                            </label>

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
                                        ? 'Signing in...'
                                        : 'Sign in to my account'
                                }}
                            </button>
                        </form>

                        <div
                            class="mt-6 flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500"
                        >
                            <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
                            New to {{ settings.company_name || 'Eghuri' }}?
                            <span
                                class="h-px flex-1 bg-gray-200 dark:bg-gray-700"
                            ></span>
                        </div>

                        <Link
                            :href="route('register')"
                            class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border-2 border-primary/30 px-4 py-2.5 text-sm font-semibold text-primary transition hover:border-primary hover:bg-primary hover:text-white dark:border-primary/50 dark:text-primary dark:hover:bg-primary"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"
                                />
                            </svg>
                            Create a Reseller Account
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </FrontEndMaster>
</template>