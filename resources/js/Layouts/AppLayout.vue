<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Sidebar from "../Sidebar.vue";

defineProps({
    navigation: {
        type: Object,
        default: () => ({})
    },
    title: {
        type: String,
        default: "Sigmie"
    },
    showTopBar: {
        type: Boolean,
        default: true
    }
});
</script>

<template>
    <div class="flex min-h-screen bg-black">
        <!-- Sidebar -->
        <Sidebar :navigation="navigation" />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col lg:ml-[100px] xl:ml-[280px] bg-black">
            <!-- Top Navigation Bar -->
            <div v-if="showTopBar" class="border-b border-gray-800 bg-black sticky top-0 z-40">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <Link
                            href="/resumes"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Resume Search
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="flex-1 overflow-y-auto bg-black">
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Layout specific styles */
</style>
