import { router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const notifications = ref([])
const unreadCount = ref(0)
const loading = ref(false)
const open = ref(false)

export function useNotifications() {
    async function fetchNotifications() {
        loading.value = true

        try {
            const res = await fetch(route('notifications.index'))
            const data = await res.json()
            notifications.value = data.notifications
            unreadCount.value = data.unreadCount
        } catch (e) {
            console.error('Failed to fetch notifications', e)
        } finally {
            loading.value = false
        }
    }

    async function markAllRead() {
        try {
            await fetch(route('notifications.mark-all-read'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || ''
                    ),
                },
            })
            unreadCount.value = 0
            notifications.value = notifications.value.map((n) => ({
                ...n,
                read_at: n.read_at || new Date().toISOString(),
            }))
        } catch (e) {
            console.error('Failed to mark notifications as read', e)
        }
    }

    function toggle() {
        open.value = !open.value

        if (open.value) {
            fetchNotifications().then(() => {
                if (unreadCount.value > 0) {
                    markAllRead()
                }
            })
        }
    }

    function close() {
        open.value = false
    }

    return {
        notifications,
        unreadCount,
        loading,
        open,
        fetchNotifications,
        markAllRead,
        toggle,
        close,
    }
}
