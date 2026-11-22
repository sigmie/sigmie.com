<script setup>
import { defineAsyncComponent, onMounted } from 'vue';
import SiteNavbar from '../components/SiteNavbar.vue';
import Footer from '../components/Footer.vue';
import { useChatPanel } from '../composables/useChatPanel';

// The chat (AI SDK, marked, highlight.js) is its own chunk, fetched the first
// time the panel opens.
const ChatWidget = defineAsyncComponent(() => import('../components/ChatWidget.vue'));

defineProps({
    navigation: {
        type: Array,
        default: null,
    },
});

const { enabled, open, loaded, showColumn, restore, openChat } = useChatPanel();

onMounted(() => restore(enabled.value));
</script>

<template>
    <div class="flex min-h-screen flex-col overflow-x-clip bg-canvas-white font-sans text-graphite dark:bg-black dark:text-[#f5f5f4]">
        <SiteNavbar :navigation="navigation" />

        <div
            class="flex min-w-0 flex-1 flex-col pt-16"
            :class="{ 'xl:mr-[400px]': showColumn }"
        >
            <div class="w-full flex-1">
                <slot />
            </div>

            <Footer />
        </div>

        <button
            v-if="enabled && !open"
            type="button"
            data-chat-launcher
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-full bg-graphite px-5 py-3 text-sm font-medium text-white shadow-lg transition hover:bg-black dark:bg-white dark:text-graphite dark:hover:bg-gray-100"
            aria-label="Open documentation chat"
            @click="openChat"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72A8.97 8.97 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Ask AI
        </button>

        <ChatWidget v-if="enabled && loaded" />
    </div>
</template>
