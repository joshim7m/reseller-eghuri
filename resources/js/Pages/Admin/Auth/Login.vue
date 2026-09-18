<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const form = useForm({
    email: '',
    password: '',
    remember: '0',
});

const showPassword = ref(false);
const isDark = ref(localStorage.getItem('darkMode') === 'true');

function toggleDark() {
    isDark.value = !isDark.value;
    localStorage.setItem('darkMode', isDark.value);
    document.documentElement.classList.toggle('dark', isDark.value);
}

onMounted(() => {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    }
});

function submit() {
    form.post(route('admin.login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Admin Login" />

    <div
        class="flex min-h-screen flex-col bg-surface lg:flex-row dark:bg-gray-950"
    >
        <!-- Branding Panel -->
        <div
            class="relative flex flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-primary to-primary/80 p-8 lg:min-h-screen lg:w-[440px] lg:p-12 dark:from-[#5d3533] dark:via-primary dark:to-gray-950"
        >
            <div
                class="absolute inset-0 opacity-[0.07]"
                style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"
            ></div>

            <div class="relative z-10 text-center text-white">
                <div
                    class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm dark:bg-white/10"
                >
                    <svg
                        viewBox="0 0 316 316"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-9 w-9"
                    >
                        <path
                            fill="white"
                            d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125ZM144.2 227.205L100.57 202.515L146.39 176.135L196.66 147.195L240.33 172.335L208.29 190.625L144.2 227.205ZM244.75 114.995V164.795L226.39 154.225L201.03 139.625V89.825L219.39 100.395L244.75 114.99C244.76 115 244.75 115 244.75 114.995ZM153.98 120.135L112.53 144.215L89.63 130.195V111.685L131.06 87.625L153.98 101.635V120.135ZM183.34 96.995L160.39 82.975V66.555L205.31 41.885L228.28 55.915L232.84 58.495V77.855L183.34 107.005V96.995ZM226.83 114.89L204.06 100.905V89.825L253.55 60.655L253.56 60.655L253.58 60.645L276.36 74.675V93.925L226.83 123.05V114.89Z"
                        />
                    </svg>
                </div>

                <h1 class="mb-2 text-2xl font-bold tracking-tight">Eghuri</h1>
                <p
                    class="max-w-[240px] text-sm leading-relaxed text-white/70"
                >
                    Administrator control panel for managing orders, products,
                    users &amp; settings.
                </p>
            </div>

            <div
                class="absolute -bottom-32 -left-32 h-64 w-64 rounded-full bg-white/5 blur-3xl"
            />
            <div
                class="absolute -right-24 -top-24 h-48 w-48 rounded-full bg-white/5 blur-2xl"
            />
        </div>

        <!-- Form Panel -->
        <div
            class="relative flex flex-1 items-center justify-center overflow-hidden px-4 py-10 sm:px-6 lg:px-16"
        >
            <div
                class="pointer-events-none absolute -left-24 top-1/4 h-72 w-72 rounded-full bg-primary/10 blur-3xl dark:bg-primary/15"
            ></div>
            <div
                class="pointer-events-none absolute -right-20 bottom-0 h-64 w-64 rounded-full bg-primary/5 blur-3xl"
            ></div>

            <div
                class="relative w-full max-w-md rounded-3xl border border-gray-200/60 bg-white/70 p-6 shadow-xl shadow-primary/5 backdrop-blur-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900/70 dark:shadow-black/40"
            >
                <!-- Dark mode toggle -->
                <div class="absolute right-4 top-4 sm:right-5 sm:top-5">
                    <button
                        type="button"
                        @click="toggleDark"
                        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:text-primary dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:text-primary dark:hover:border-primary/60"
                    >
                        <svg
                            v-if="isDark"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Mobile logo -->
                <div class="mb-7 text-center lg:hidden">
                    <div
                        class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 dark:bg-primary/20"
                    >
                        <svg
                            viewBox="0 0 316 316"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-7 w-7"
                        >
                            <path
                                fill="#82514e"
                                d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125ZM144.2 227.205L100.57 202.515L146.39 176.135L196.66 147.195L240.33 172.335L208.29 190.625L144.2 227.205ZM244.75 114.995V164.795L226.39 154.225L201.03 139.625V89.825L219.39 100.395L244.75 114.99C244.76 115 244.75 115 244.75 114.995ZM153.98 120.135L112.53 144.215L89.63 130.195V111.685L131.06 87.625L153.98 101.635V120.135ZM183.34 96.995L160.39 82.975V66.555L205.31 41.885L228.28 55.915L232.84 58.495V77.855L183.34 107.005V96.995ZM226.83 114.89L204.06 100.905V89.825L253.55 60.655L253.56 60.655L253.58 60.645L276.36 74.675V93.925L226.83 123.05V114.89Z"
                            />
                        </svg>
                    </div>
                    <h1
                        class="text-xl font-bold text-on-surface dark:text-gray-100"
                    >
                        Admin Panel
                    </h1>
                    <p
                        class="mt-1 text-sm text-on-surface/60 dark:text-gray-400"
                    >
                        Sign in to manage your store
                    </p>
                </div>

                <!-- Desktop heading -->
                <div class="mb-7 hidden lg:block">
                    <h2
                        class="text-2xl font-bold text-on-surface dark:text-gray-100"
                    >
                        Welcome back
                    </h2>
                    <p class="mt-1 text-sm text-on-surface/60 dark:text-gray-400">
                        Enter your credentials to access the admin panel
                    </p>
                </div>

                <!-- Flash errors -->
                <div
                    v-if="$page.props.flash?.error"
                    class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300"
                >
                    <svg
                        class="h-5 w-5 shrink-0 text-red-500 dark:text-red-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                        />
                    </svg>
                    {{ $page.props.flash.error }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-on-surface dark:text-gray-300"
                            >Email</label
                        >
                        <div class="relative mt-1.5">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"
                            >
                                <svg
                                    class="h-[18px] w-[18px] text-outline dark:text-gray-500"
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
                                id="email"
                                type="email"
                                v-model="form.email"
                                class="block w-full rounded-xl border border-outline/30 bg-white py-2.5 pl-11 pr-4 text-sm text-on-surface outline-none transition placeholder:text-outline focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-primary dark:focus:ring-primary/30"
                                placeholder="you@company.com"
                                required
                                autofocus
                                autocomplete="username"
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
                        <label
                            for="password"
                            class="block text-sm font-medium text-on-surface dark:text-gray-300"
                            >Password</label
                        >
                        <div class="relative mt-1.5">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5"
                            >
                                <svg
                                    class="h-[18px] w-[18px] text-outline dark:text-gray-500"
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
                                id="password"
                                v-model="form.password"
                                class="block w-full rounded-xl border border-outline/30 bg-white py-2.5 pl-11 pr-11 text-sm text-on-surface outline-none transition placeholder:text-outline focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-gray-700 dark:bg-gray-800/70 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-primary dark:focus:ring-primary/30"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-outline transition hover:text-on-surface-variant dark:text-gray-500 dark:hover:text-gray-300"
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
                    <div class="flex items-center justify-between pt-0.5">
                        <label
                            class="flex cursor-pointer select-none items-center gap-2.5"
                        >
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                :true-value="'1'"
                                :false-value="'0'"
                                class="h-4 w-4 cursor-pointer rounded border-outline/40 bg-white text-primary focus:ring-primary/30 dark:border-gray-600 dark:bg-gray-800"
                            />
                            <span
                                class="text-sm text-on-surface/70 dark:text-gray-300"
                                >Remember me</span
                            >
                        </label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-1 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-semibold text-white shadow-md shadow-primary/20 transition hover:bg-on-primary-container disabled:opacity-50 dark:shadow-primary/30 dark:hover:brightness-110"
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
                                : 'Sign in to Admin'
                        }}
                    </button>
                </form>

                <p
                    class="mt-8 text-center text-xs text-outline dark:text-gray-500"
                >
                    <Link
                        :href="route('home')"
                        class="inline-flex items-center gap-1 font-medium text-primary transition hover:text-on-primary-container dark:text-primary dark:hover:text-primary/80"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"
                            />
                        </svg>
                        Back to Store
                    </Link>
                </p>
            </div>
        </div>
    </div>
</template>