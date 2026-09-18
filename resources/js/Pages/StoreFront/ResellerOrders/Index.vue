<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import FrontEndMaster from '@/Layouts/Frontend/FrontEndMaster.vue'

const props = defineProps({
    orders: { type: Object, required: true },
    wallet: { type: Object, default: null },
    pendingBalance: { type: Number, default: 0 },
    cancelledBalance: { type: Number, default: 0 },
    pendingWithdrawalAmount: { type: Number, default: 0 },
    withdrawalMethods: { type: Array, default: () => [] },
})

const showWithdrawModal = ref(false)
const withdrawForm = useForm({
    amount: '',
    payment_method: '',
    account_number: '',
    password: '',
})

const bdPhoneRegex = /^01[3-9]\d{8}$/

const accountNumberInvalid = computed(() => {
    const number = withdrawForm.account_number.trim()

    return number.length > 0 && !bdPhoneRegex.test(number)
})

const accountNumberValid = computed(() => bdPhoneRegex.test(withdrawForm.account_number.trim()))

const accountNumberHelper = computed(() => {
    const number = withdrawForm.account_number.trim()

    if (number.length === 0) {
        return null
    }

    if (accountNumberInvalid.value && number.length < 11) {
        return 'Account number must be 11 digits.'
    }

    if (accountNumberInvalid.value && !number.startsWith('01')) {
        return 'Account number must start with 01.'
    }

    if (accountNumberInvalid.value) {
        return 'For mobile accounts, operator code must be 3-9 (e.g. 013, 017, 019).'
    }

    return null
})

const selectedWithdrawMethod = computed(() =>
    props.withdrawalMethods.find((method) => method.name === withdrawForm.payment_method) || null,
)

const withdrawableBalance = computed(() => Number(props.wallet?.balance ?? 0))

function openWithdrawModal() {
    withdrawForm.clearErrors()
    withdrawForm.reset('amount', 'account_number', 'password', 'payment_method')
    withdrawForm.payment_method = props.withdrawalMethods[0]?.name ?? ''
    showWithdrawModal.value = true
}

function submitWithdraw() {
    withdrawForm.clearErrors()

    if (!withdrawForm.payment_method) {
        withdrawForm.setError('payment_method', 'Please select a payment method.')
        return
    }

    if (accountNumberInvalid.value) {
        withdrawForm.setError('account_number', accountNumberHelper.value)
        return
    }

    withdrawForm.post(route('reseller-orders.withdraw'), {
        preserveScroll: true,
        onSuccess: () => {
            showWithdrawModal.value = false
        },
    })
}

const search = ref(new URLSearchParams(window.location.search).get('search') || '')
let timeout = null

function onSearch() {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        const params = new URLSearchParams(window.location.search)

        if (search.value) {
            params.set('search', search.value)
        } else {
            params.delete('search')
        }

        router.get(route('reseller-orders.index') + '?' + params.toString(), {}, { preserveScroll: true, preserveState: true })
    }, 400)
}

function formatPrice(price) {
    return '৳' + Number(price ?? 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function statusColor(status) {
    const map = {
        pending: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        processing: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        cancelled: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    }

    return map[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}

function statusDot(status) {
    const map = {
        pending: 'bg-amber-500',
        processing: 'bg-blue-500',
        completed: 'bg-emerald-500',
        cancelled: 'bg-red-500',
    }

    return map[status] || 'bg-gray-400'
}

function formatDateTime(date) {
    return new Date(date).toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true })
}
</script>

<template>
    <Head title="Reseller Orders" />

    <FrontEndMaster>
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-8 flex-wrap gap-3">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-charcoal dark:text-[#f9eeed] mb-1">Reseller Orders</h1>
                    <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">Track and manage your orders.</p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <Link :href="route('reseller-transactions')"
                        class="border border-primary text-primary font-semibold px-5 py-2.5 rounded-xl text-sm hover:bg-primary hover:text-white transition">
                        Transactions
                    </Link>
                    <Link :href="route('reseller-orders.create')"
                        class="bg-charcoal text-white font-semibold px-5 py-2.5 rounded-xl text-sm hover:bg-primary transition dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]">
                        + New Order
                    </Link>
                </div>
            </div>

            <div class="mb-6 relative max-w-sm">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-outline dark:text-[#cbb8b6]" aria-hidden="true">search</span>
                <input v-model="search" @input="onSearch" type="text" placeholder="Search by order number..."
                    class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] pl-10 pr-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
            </div>

            <div v-if="wallet" class="bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-5 md:p-6 mb-6">
                <h2 class="text-xs font-bold uppercase tracking-widest text-on-surface-variant dark:text-[#cbb8b6] mb-4">Wallet Summary</h2>
                <div class="flex items-center gap-6 flex-wrap">
                    <div>
                        <p class="font-mono text-2xl font-bold text-charcoal dark:text-[#f9eeed]">{{ formatPrice(wallet?.balance) }}</p>
                        <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6] mt-0.5">Available Balance</p>
                    </div>
                    <div class="h-10 w-px bg-outline-variant dark:bg-[#3a302e]"></div>
                    <div>
                        <p class="font-mono text-sm font-semibold text-amber-600 dark:text-amber-400">{{ formatPrice(pendingBalance) }}</p>
                        <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6]">Pending</p>
                    </div>
                    <div class="h-10 w-px bg-outline-variant dark:bg-[#3a302e]"></div>
                    <div>
                        <p class="font-mono text-lg font-semibold text-red-600 dark:text-red-400">{{ formatPrice(pendingWithdrawalAmount) }}</p>
                        <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6]">Pending Withdrawal</p>
                    </div>
                    <div class="h-10 w-px bg-outline-variant dark:bg-[#3a302e]"></div>
                    <div>
                        <p class="font-mono text-sm font-semibold text-sale-price">{{ formatPrice(cancelledBalance) }}</p>
                        <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6]">Cancelled</p>
                    </div>
                    <button @click="openWithdrawModal" :disabled="withdrawableBalance < 500"
                        class="ml-auto bg-primary text-white font-semibold px-5 py-2.5 rounded-xl text-sm hover:bg-charcoal transition disabled:opacity-40 disabled:cursor-not-allowed">
                        Withdraw
                    </button>
                </div>
            </div>

            <div v-if="orders.data.length" class="space-y-3">
                <Link v-for="order in orders.data" :key="order.id" :href="route('reseller-orders.show', order.id)"
                    class="block bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] p-5 transition hover:shadow-md hover:border-primary/30 dark:hover:border-[#f6b7b2]/30">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm font-medium text-charcoal dark:text-[#f9eeed]">{{ order.order_number }}</span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="statusColor(order.status)">
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(order.status)"></span>
                                {{ order.status }}
                            </span>
                        </div>
                        <div class="font-mono text-charcoal dark:text-[#f9eeed] font-bold">{{ formatPrice(order.total_amount) }}</div>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between text-xs text-on-surface-variant dark:text-[#cbb8b6]">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">schedule</span>
                            {{ formatDateTime(order.created_at) }}
                        </span>
                        <span>{{ order.items?.length || 0 }} item(s)</span>
                    </div>
                </Link>

                <div v-if="orders.links && orders.links.length > 3" class="mt-8 pt-6 border-t border-outline-variant dark:border-[#3a302e] flex justify-between items-center">
                    <p class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">Page {{ orders.current_page }} of {{ orders.last_page }}</p>
                    <div class="flex gap-2">
                        <button v-if="orders.prev_page_url" @click="router.get(orders.prev_page_url, {}, { preserveScroll: true, preserveState: true })"
                            class="flex items-center gap-2 text-sm font-medium text-charcoal dark:text-[#f9eeed] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_back</span>
                            Previous
                        </button>
                        <button v-if="orders.next_page_url" @click="router.get(orders.next_page_url, {}, { preserveScroll: true, preserveState: true })"
                            class="flex items-center gap-2 text-sm font-medium text-charcoal dark:text-[#f9eeed] hover:text-primary dark:hover:text-[#f6b7b2] transition-colors">
                            Next
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-20">
                <span class="material-symbols-outlined text-6xl text-outline-variant dark:text-[#3a302e] mb-4" aria-hidden="true">receipt_long</span>
                <p class="text-on-surface-variant dark:text-[#cbb8b6] text-sm mb-4">No reseller orders yet.</p>
                <Link :href="route('reseller-orders.create')"
                    class="inline-block bg-charcoal text-white font-semibold px-6 py-2.5 rounded-xl text-sm hover:bg-primary transition dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]">
                    Create Your First Order
                </Link>
            </div>
        </div>

        <!-- Withdraw Modal -->
        <div v-if="showWithdrawModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape="showWithdrawModal = false">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showWithdrawModal = false"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-[#1e1917] rounded-2xl border border-outline-variant dark:border-[#3a302e] shadow-xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-charcoal dark:text-[#f9eeed]">Withdraw Balance</h3>
                        <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6] mt-0.5">
                            Available: <span class="font-mono font-semibold">{{ formatPrice(wallet?.balance) }}</span>
                        </p>
                    </div>
                    <button @click="showWithdrawModal = false" class="text-on-surface-variant dark:text-[#cbb8b6] hover:text-primary transition">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">close</span>
                    </button>
                </div>

                <form @submit.prevent="submitWithdraw" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-charcoal dark:text-[#f9eeed] mb-1.5">Amount (৳)</label>
                        <input v-model="withdrawForm.amount" type="number" min="500" step="0.01"
                            placeholder="Minimum ৳500"
                            class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
                        <p v-if="withdrawForm.errors.amount" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ withdrawForm.errors.amount }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-charcoal dark:text-[#f9eeed] mb-1.5">Payment Method</label>
                        <div v-if="withdrawalMethods.length" class="grid grid-cols-2 gap-2.5">
                            <button v-for="method in withdrawalMethods" :key="method.id" type="button"
                                @click="withdrawForm.payment_method = method.name"
                                class="flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left transition"
                                :class="withdrawForm.payment_method === method.name
                                    ? 'border-primary bg-primary/10 ring-1 ring-primary/40'
                                    : 'border-outline-variant dark:border-[#3a302e] hover:border-primary/40 dark:hover:border-[#f6b7b2]/40'">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-container dark:bg-[#241d1c]">
                                    <img v-if="method.image_url" :src="method.image_url" :alt="method.name"
                                        class="h-9 w-9 object-contain" loading="lazy" />
                                    <span v-else class="material-symbols-outlined text-[20px] text-on-surface-variant dark:text-[#cbb8b6]" aria-hidden="true">account_balance_wallet</span>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-charcoal dark:text-[#f9eeed]">{{ method.name }}</span>
                                    <span class="block truncate text-[11px] text-on-surface-variant dark:text-[#cbb8b6]">{{ method.provider || 'Mobile banking' }}</span>
                                </span>
                                <span v-if="withdrawForm.payment_method === method.name"
                                    class="material-symbols-outlined ml-auto text-[18px] text-primary" aria-hidden="true">check_circle</span>
                            </button>
                        </div>
                        <p v-else class="text-sm text-on-surface-variant dark:text-[#cbb8b6]">No withdrawal payment methods are available right now. Please contact support.</p>
                        <div v-if="withdrawForm.payment_method && !withdrawalMethods.length" class="sr-only">
                            {{ withdrawForm.payment_method }}
                        </div>
                        <p v-if="withdrawForm.errors.payment_method" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ withdrawForm.errors.payment_method }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-charcoal dark:text-[#f9eeed] mb-1.5">
                            {{ selectedWithdrawMethod ? selectedWithdrawMethod.name : 'Payment' }} Number
                        </label>
                        <input v-model="withdrawForm.account_number" type="text" inputmode="numeric" maxlength="11"
                            placeholder="01XXXXXXXXX"
                            class="w-full rounded-xl border bg-surface-container-low dark:bg-[#241d1c] px-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 transition"
                            :class="withdrawForm.errors.account_number || accountNumberInvalid
                                ? 'border-red-500 dark:border-red-500'
                                : 'border-outline-variant dark:border-[#3a302e] focus:border-primary'" />
                        <p v-if="withdrawForm.errors.account_number" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ withdrawForm.errors.account_number }}</p>
                        <p v-else-if="accountNumberHelper" class="text-xs text-amber-600 dark:text-amber-400 mt-1">{{ accountNumberHelper }}</p>
                        <p v-else-if="accountNumberValid" class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">check_circle</span>
                            Valid {{ selectedWithdrawMethod ? selectedWithdrawMethod.name : 'BD' }} mobile number
                        </p>
                        <p v-if="selectedWithdrawMethod?.account_number" class="text-[11px] text-on-surface-variant dark:text-[#cbb8b6] mt-1.5 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true">verified</span>
                            {{ selectedWithdrawMethod.name }} account on file: <span class="font-mono">{{ selectedWithdrawMethod.account_number }}</span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-charcoal dark:text-[#f9eeed] mb-1.5">Password</label>
                        <input v-model="withdrawForm.password" type="password" autocomplete="current-password"
                            placeholder="Confirm your password"
                            class="w-full rounded-xl border border-outline-variant dark:border-[#3a302e] bg-surface-container-low dark:bg-[#241d1c] px-4 py-2.5 text-sm text-charcoal dark:text-[#f9eeed] placeholder-outline focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition" />
                        <p v-if="withdrawForm.errors.password" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ withdrawForm.errors.password }}</p>
                    </div>

                    <p class="text-xs text-on-surface-variant dark:text-[#cbb8b6]">
                        Your balance will be held immediately and returned automatically if the admin rejects this request.
                    </p>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="showWithdrawModal = false"
                            class="flex-1 border border-outline-variant dark:border-[#3a302e] text-on-surface-variant dark:text-[#cbb8b6] font-semibold px-4 py-2.5 rounded-xl text-sm hover:bg-surface-container">
                            Cancel
                        </button>
                        <button type="submit" :disabled="withdrawForm.processing"
                            class="flex-1 bg-charcoal text-white font-semibold px-4 py-2.5 rounded-xl text-sm hover:bg-primary transition disabled:opacity-50 dark:bg-[#f9eeed] dark:text-charcoal dark:hover:bg-[#f6b7b2]">
                            {{ withdrawForm.processing ? 'Submitting...' : 'Request Withdraw' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </FrontEndMaster>
</template>
