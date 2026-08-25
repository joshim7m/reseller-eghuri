<script setup>
import Modal from '@/Components/Modal.vue'

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    message: { type: String, required: true },
    confirmText: { type: String, default: 'Delete' },
    cancelText: { type: String, default: 'Cancel' },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
    <Modal :show="show" max-width="sm" @close="emit('cancel')">
        <div class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ message }}</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button @click="emit('cancel')" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">{{ cancelText }}</button>
                <button @click="emit('confirm')" class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition">{{ confirmText }}</button>
            </div>
        </div>
    </Modal>
</template>
