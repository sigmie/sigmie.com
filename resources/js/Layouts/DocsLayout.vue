<script setup>
import { Head } from "@inertiajs/vue3";
import DocsSidebar from "../components/DocsSidebar.vue";

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
    <div class="flex min-h-screen bg-black overflow-x-hidden">
        <!-- Documentation Sidebar -->
        <DocsSidebar :navigation="navigation" :current-path="currentPath" />

        <!-- Main Content Area -->
        <div class="flex-1 lg:ml-64 bg-black min-w-0">
            <!-- Mobile Header Spacer -->
            <div class="h-14 lg:hidden"></div>

            <!-- Page Content -->
            <div class="flex-1 overflow-x-hidden bg-black w-full">
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Documentation layout specific styles */
</style>
