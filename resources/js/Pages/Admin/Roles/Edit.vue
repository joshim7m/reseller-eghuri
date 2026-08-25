<script setup>
import AdminMaster from '@/Layouts/Admin/AdminMaster.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const { role, modules } = defineProps({
    role: { type: Object, required: true },
    modules: { type: Array, required: true },
})

const rolePermissionIds = role.permissions.map(p => p.id)

const form = useForm({
    permissions: [...rolePermissionIds],
})

function submit() {
    form.put(route('admin.roles.update', role.id))
}

function togglePermission(permissionId) {
    const idx = form.permissions.indexOf(permissionId)
    if (idx === -1) {
        form.permissions.push(permissionId)
    } else {
        form.permissions.splice(idx, 1)
    }
}

function selectAll(permissions) {
    permissions.forEach(p => {
        if (!form.permissions.includes(p.id)) {
            form.permissions.push(p.id)
        }
    })
}

function deselectAll(permissions) {
    permissions.forEach(p => {
        const idx = form.permissions.indexOf(p.id)
        if (idx !== -1) form.permissions.splice(idx, 1)
    })
}

function moduleAllSelected(permissions) {
    return permissions.every(p => form.permissions.includes(p.id))
}
</script>

<template>
    <Head title="Edit Role" />

    <AdminMaster>
        <div class="max-w-3xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Role</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ role.name }}</p>
                </div>
                <Link :href="route('admin.roles.index')" class="text-sm font-medium text-blue-600 hover:text-blue-500">&larr; Back</Link>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div v-for="mod in modules" :key="mod.id" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white capitalize">{{ mod.name }}</h3>
                            <div class="flex gap-2">
                                <button type="button" @click="selectAll(mod.permissions)" class="text-xs font-medium text-blue-600 hover:text-blue-500">Select All</button>
                                <button type="button" @click="deselectAll(mod.permissions)" class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Clear</button>
                            </div>
                        </div>
                        <div class="px-5 py-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <label v-for="perm in mod.permissions" :key="perm.id" class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/30 cursor-pointer transition">
                                <input type="checkbox" :checked="form.permissions.includes(perm.id)" @change="togglePermission(perm.id)" class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:ring-blue-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ perm.name }}</span>
                            </label>
                        </div>
                    </div>

                    <p v-if="form.errors.permissions" class="text-sm text-red-600">{{ form.errors.permissions }}</p>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition shadow-sm">
                            {{ form.processing ? 'Updating...' : 'Update Permissions' }}
                        </button>
                        <Link :href="route('admin.roles.index')" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">Cancel</Link>
                    </div>
                </form>
            </div>
        </div>
    </AdminMaster>
</template>
