<script setup>
import { computed, watch } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Write something...' },
    minHeight: { type: String, default: '150px' },
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
    content: props.modelValue || '',
    immediatelyRender: false,
    extensions: [
        StarterKit,
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'rich-text-content outline-none px-3 py-2.5',
            style: `min-height: ${props.minHeight};`,
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML())
    },
})

watch(() => props.modelValue, (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
        editor.value.commands.setContent(value || '')
    }
})

const isActive = (name, attrs = {}) => editor.value?.isActive(name, attrs) ?? false

const can = (name) => {
    if (!editor.value) return false
    return editor.value.can().chain().focus()[name]().run()
}

const run = (command) => {
    editor.value?.chain().focus()[command]().run()
}

const groups = computed(() => [
    [
        { title: 'Undo', icon: 'M9 14L4 9l5-5M4 9h10.5a5.5 5.5 0 015.5 5.5 5.5 5.5 0 01-5.5 5.5H11', action: () => run('undo'), active: () => false },
        { title: 'Redo', icon: 'M15 14l5-5-5-5M20 9H9.5A5.5 5.5 0 004 14.5 5.5 5.5 0 009.5 20H13', action: () => run('redo'), active: () => false },
    ],
    [
        { title: 'Paragraph', icon: 'M13 5H7a3 3 0 000 6h6M9 5v14M13 19H9', action: () => run('setParagraph'), active: () => isActive('paragraph') },
        { title: 'Heading 1', icon: 'M4 5v14M9 5v14M4 12h5M13 5v14M13 12h4M17 5v2M17 9v10', action: () => run('toggleHeading', { level: 1 }), active: () => isActive('heading', { level: 1 }) },
        { title: 'Heading 2', icon: 'M4 5v14M9 5v14M4 12h5M13 5v14M21 5h-4v3h4v3h-4v8', action: () => run('toggleHeading', { level: 2 }), active: () => isActive('heading', { level: 2 }) },
        { title: 'Heading 3', icon: 'M4 5v14M9 5v14M4 12h5M18 10.5a2 2 0 00-2-1.5 2 2 0 000 4 2 2 0 010 4 2 2 0 002-1.5M14 19v-3', action: () => run('toggleHeading', { level: 3 }), active: () => isActive('heading', { level: 3 }) },
    ],
    [
        { title: 'Bold', icon: 'M6 4h8a4 4 0 010 8 4 4 0 010 8H6V4zM6 12h8', action: () => run('toggleBold'), active: () => isActive('bold') },
        { title: 'Italic', icon: 'M10 4h6M14 4L10 20M8 20h6', action: () => run('toggleItalic'), active: () => isActive('italic') },
        { title: 'Strikethrough', icon: 'M6 12h12M16 7c-1-1-2-1.5-4-1.5S8.5 6.5 8.5 8c0 2.5 7 2 7 4.5 0 1.5-1.5 2.5-3.5 2.5-1.5 0-3-.5-4-1.5', action: () => run('toggleStrike'), active: () => isActive('strike') },
    ],
    [
        { title: 'Bullet List', icon: 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01', action: () => run('toggleBulletList'), active: () => isActive('bulletList') },
        { title: 'Numbered List', icon: 'M9 6h11M9 12h11M9 18h11M3.5 5.5l1 .8V9M3.5 12h1m-1 0c1 0 1.5-.5 1.5-1 0-1-1-1.5-1.5-2.5M3.5 19c.7.6 1.4 1 2 1 .8 0 1.5-.5 1.5-1.5S6 17 5 17m-1.5.5L4.5 20', action: () => run('toggleOrderedList'), active: () => isActive('orderedList') },
        { title: 'Quote', icon: 'M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1zM15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z', action: () => run('toggleBlockquote'), active: () => isActive('blockquote') },
        { title: 'Code Block', icon: 'M8 9l-3 3 3 3M16 9l3 3-3 3M13 6l-2 12', action: () => run('toggleCodeBlock'), active: () => isActive('codeBlock') },
    ],
])
</script>

<template>
    <div class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition">
        <div class="flex flex-wrap items-center gap-0.5 px-1.5 py-1 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
            <template v-for="(group, gi) in groups" :key="gi">
                <div v-if="gi > 0" class="w-px h-5 bg-gray-200 dark:bg-gray-700 mx-1 hidden sm:block"></div>
                <button
                    v-for="(btn, bi) in group" :key="bi"
                    type="button"
                    :title="btn.title"
                    :aria-label="btn.title"
                    @mousedown.prevent="btn.action()"
                    class="p-1.5 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-700 transition touch-manipulation"
                    :class="btn.active() ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' : ''"
                >
                    <svg class="w-4 h-4 sm:w-[18px] sm:h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path :d="btn.icon" /></svg>
                </button>
            </template>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
