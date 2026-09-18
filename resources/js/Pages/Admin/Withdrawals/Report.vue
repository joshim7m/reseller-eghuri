<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue';

const props = defineProps({
    withdrawals: { type: Array, required: true },
    from: { type: String, required: true },
    to: { type: String, required: true },
});

const PAGE_SIZE = 10;

const PRESETS = {
    today: 'Today',
    thisWeek: 'This Week',
    thisMonth: 'This Month',
    lastMonth: 'Last 1 Month',
    custom: 'Custom',
};

const STATUS_LABELS = { pending: 'Pending', completed: 'Accepted', cancelled: 'Rejected' };

function pad(n) {
    return String(n).padStart(2, '0');
}

function toDateString(d) {
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function presetRange(key, custom) {
    const today = new Date();
    const startOfWeek = new Date(today);
    startOfWeek.setDate(today.getDate() - today.getDay());
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const startOfLastMonth = new Date(today);
    startOfLastMonth.setDate(today.getDate() - 30);

    switch (key) {
        case 'today':
            return { from: toDateString(today), to: toDateString(today) };
        case 'thisWeek':
            return { from: toDateString(startOfWeek), to: toDateString(today) };
        case 'thisMonth':
            return {
                from: toDateString(startOfMonth),
                to: toDateString(today),
            };
        case 'lastMonth':
            return {
                from: toDateString(startOfLastMonth),
                to: toDateString(today),
            };
        case 'custom':
        default:
            return { from: custom.from, to: custom.to };
    }
}

const from = ref(props.from);
const to = ref(props.to);
const customFrom = ref(props.from);
const customTo = ref(props.to);
const preset = ref('lastMonth');
const showCustom = ref(false);
const page = ref(1);
const selected = ref(new Set());
const notice = ref({ type: '', message: '' });
let noticeTimer = null;

function accountNumber(note) {
    const matches = (note || '').match(/(\d{6,})/);

    return matches?.[1] || '—';
}

const totalTransactions = computed(() => props.withdrawals.length);
const acceptedAmount = computed(() =>
    props.withdrawals
        .filter((w) => w.status === 'completed')
        .reduce((sum, w) => sum + Number(w.amount || 0), 0),
);
const pendingAmount = computed(() =>
    props.withdrawals
        .filter((w) => w.status === 'pending')
        .reduce((sum, w) => sum + Number(w.amount || 0), 0),
);
const rejectedCount = computed(
    () => props.withdrawals.filter((w) => w.status === 'cancelled').length,
);

const totalPages = computed(() =>
    Math.max(1, Math.ceil(props.withdrawals.length / PAGE_SIZE)),
);
const paged = computed(() => {
    const start = (page.value - 1) * PAGE_SIZE;

    return props.withdrawals.slice(start, start + PAGE_SIZE);
});

const allSelected = computed(
    () =>
        props.withdrawals.length > 0 &&
        props.withdrawals.every((w) => selected.value.has(w.id)),
);

function showNotice(message, type = 'info') {
    notice.value = { type, message };
    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(() => {
        notice.value = { type: '', message: '' };
    }, 4000);
}

function goToDateRange() {
    selected.value = new Set();
    page.value = 1;
    router.get(
        route('admin.withdrawals.report'),
        { from: from.value, to: to.value },
        { preserveState: true, preserveScroll: true },
    );
}

function applyPreset(key) {
    preset.value = key;
    showCustom.value = key === 'custom';

    const range = presetRange(key, {
        from: customFrom.value,
        to: customTo.value,
    });
    from.value = range.from;
    to.value = range.to;

    if (key !== 'custom') {
        goToDateRange();
    }
}

function applyCustom() {
    if (!customFrom.value || !customTo.value) {
        showNotice('Please pick both a start and end date.', 'error');

        return;
    }

    if (customFrom.value > customTo.value) {
        showNotice('Start date cannot be after the end date.', 'error');

        return;
    }

    preset.value = 'custom';
    from.value = customFrom.value;
    to.value = customTo.value;
    goToDateRange();
}

function toggleRow(id) {
    const next = new Set(selected.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    selected.value = next;
}

function toggleAll() {
    if (allSelected.value) {
        selected.value = new Set();
    } else {
        selected.value = new Set(props.withdrawals.map((w) => w.id));
    }
}

function exportUrl(ids) {
    return route('admin.withdrawals.export-report', {
        from: from.value,
        to: to.value,
        ...(ids.length ? { ids } : {}),
    });
}

function exportSelected() {
    if (! selected.value.size) {
        showNotice('Select at least one withdrawal to export.', 'error');

        return;
    }

    window.location.href = exportUrl([...selected.value]);
}

function exportAll() {
    window.location.href = exportUrl([]);
}

function formatPrice(price) {
    return '৳' + Number(price).toLocaleString('en-IN');
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function statusBadge(status) {
    const map = {
        pending:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        completed:
            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        cancelled:
            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };

    return map[status] || 'bg-gray-100 text-gray-800';
}

watch(
    () => props.withdrawals,
    () => {
        selected.value = new Set();
        page.value = 1;
    },
);
</script>

<template>
    <Head title="Withdrawals Report" />

    <AdminMaster>
        <div class="space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Withdrawals Report
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ from }} &rarr; {{ to }}
                    </p>
                </div>
                <Link
                    :href="route('admin.withdrawals.index')"
                    class="mt-1 inline-block text-xs text-blue-600 hover:underline dark:text-blue-400"
                    >&larr; Back to withdrawals</Link
                >
            </div>

            <div
                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <p
                    class="mb-3 font-mono text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                >
                    Date Range
                </p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="(label, key) in PRESETS"
                        :key="key"
                        @click="applyPreset(key)"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                        :class="
                            preset === key
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                        "
                    >
                        {{ label }}
                    </button>
                </div>

                <div
                    v-if="showCustom"
                    class="mt-4 flex flex-wrap items-end gap-3"
                >
                    <label class="block">
                        <span
                            class="mb-1 block text-xs text-gray-500 dark:text-gray-400"
                            >Start date</span
                        >
                        <input
                            v-model="customFrom"
                            type="date"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </label>
                    <label class="block">
                        <span
                            class="mb-1 block text-xs text-gray-500 dark:text-gray-400"
                            >End date</span
                        >
                        <input
                            v-model="customTo"
                            type="date"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </label>
                    <button
                        @click="applyCustom"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                    >
                        Apply
                    </button>
                </div>
            </div>

            <div
                v-if="notice.message"
                class="rounded-lg border px-4 py-3 text-sm"
                :class="
                    notice.type === 'error'
                        ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300'
                        : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
                "
            >
                {{ notice.message }}
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Withdrawals
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ totalTransactions }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Accepted Amount
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ formatPrice(acceptedAmount) }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Pending Amount
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ formatPrice(pendingAmount) }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Rejected
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ rejectedCount }}
                    </p>
                </div>
            </div>

            <div
                class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="bg-gray-50 text-left text-gray-500 dark:bg-gray-800/50 dark:text-gray-400"
                        >
                            <th class="whitespace-nowrap px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleAll"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-800"
                                />
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Date
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Reseller
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Method
                            </th>
                            <th
                                class="whitespace-nowrap px-4 py-3 font-medium"
                            >
                                Account Number
                            </th>
                            <th
                                class="whitespace-nowrap px-4 py-3 text-right font-medium"
                            >
                                Amount
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Status
                            </th>
                            <th class="whitespace-nowrap px-4 py-3 font-medium">
                                Action By
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-100 dark:divide-gray-800"
                    >
                        <tr v-if="withdrawals.length === 0">
                            <td
                                colspan="8"
                                class="px-4 py-12 text-center text-gray-500 dark:text-gray-400"
                            >
                                No withdrawals in this date range.
                            </td>
                        </tr>
                        <tr
                            v-for="withdrawal in paged"
                            :key="withdrawal.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800/30"
                        >
                            <td class="whitespace-nowrap px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="selected.has(withdrawal.id)"
                                    @change="toggleRow(withdrawal.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-800"
                                />
                            </td>
                            <td
                                class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400"
                            >
                                {{ formatDate(withdrawal.created_at) }}
                            </td>
                            <td
                                class="px-4 py-3 text-gray-900 dark:text-white"
                            >
                                {{ withdrawal.reseller_name }}
                                <span
                                    class="block text-xs text-gray-400 dark:text-gray-500"
                                    >{{ withdrawal.reseller_email }}</span
                                >
                            </td>
                            <td
                                class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300 capitalize"
                            >
                                {{ withdrawal.paymentmethod_name }}
                            </td>
                            <td
                                class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ accountNumber(withdrawal.note) }}
                            </td>
                            <td
                                class="whitespace-nowrap px-4 py-3 text-right font-medium text-gray-900 dark:text-white"
                            >
                                {{ formatPrice(withdrawal.amount) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="statusBadge(withdrawal.status)"
                                >
                                    {{ STATUS_LABELS[withdrawal.status] }}
                                </span>
                            </td>
                            <td
                                class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400"
                            >
                                {{ withdrawal.action_by || '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="totalPages > 1"
                class="flex items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-400"
            >
                <span>Page {{ page }} of {{ totalPages }}</span>
                <div class="flex gap-2">
                    <button
                        @click="page--"
                        :disabled="page === 1"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 transition hover:bg-gray-50 disabled:opacity-40 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800"
                    >
                        &larr; Prev
                    </button>
                    <button
                        @click="page++"
                        :disabled="page === totalPages"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 transition hover:bg-gray-50 disabled:opacity-40 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800"
                    >
                        Next &rarr;
                    </button>
                </div>
            </div>

            <div
                class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ selected.size }} withdrawal{{
                            selected.size !== 1 ? 's' : ''
                        }}
                        selected
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="exportSelected"
                            :disabled="
                                selected.size === 0 || withdrawals.length === 0
                            "
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200 disabled:opacity-40 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Export Selected ({{ selected.size }})
                        </button>
                        <button
                            @click="exportAll"
                            :disabled="withdrawals.length === 0"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-40"
                        >
                            Export All (Filtered) as Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminMaster>
</template>
