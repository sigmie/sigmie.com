<script setup>
import SiteNavbar from '../components/SiteNavbar.vue';
import Footer from '../components/Footer.vue';
import ChatWidget from '../components/ChatWidget.vue';
import { useChatPanel } from '../composables/useChatPanel';

defineProps({
    navigation: {
        type: Array,
        default: null,
    },
});

const { showColumn } = useChatPanel();
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

        <ChatWidget />
    </div>
</template>
