<script setup>
import DocsSidebar from "../components/DocsSidebar.vue";
import ChatWidget from "../components/ChatWidget.vue";
import { useChatPanel } from "../composables/useChatPanel";

const { showColumn } = useChatPanel();

defineProps({
    navigation: {
        type: Array,
        default: () => []
    },
    title: {
        type: String,
        default: "Documentation"
    },
    currentPath: {
        type: String,
        default: ''
    }
});
</script>

<template>
    <div class="flex min-h-screen bg-canvas-white dark:bg-black overflow-x-hidden font-sans text-graphite dark:text-white">
        <DocsSidebar :navigation="navigation" :current-path="currentPath" />

        <div class="flex-1 lg:ml-64 bg-canvas-white dark:bg-black min-w-0" :class="{ 'xl:mr-[400px]': showColumn }">
            <div class="h-14 lg:hidden"></div>
            <div class="flex-1 overflow-x-hidden w-full">
                <slot />
            </div>
        </div>

        <ChatWidget />
    </div>
</template>
