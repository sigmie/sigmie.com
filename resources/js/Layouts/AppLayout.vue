<script setup>
import { Link } from "@inertiajs/vue3";
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
    <div class="flex min-h-screen bg-canvas-white dark:bg-black overflow-x-hidden font-sans text-graphite dark:text-white">
        <Sidebar :navigation="navigation" />

        <div class="flex-1 flex flex-col lg:ml-[100px] xl:ml-[280px] bg-canvas-white dark:bg-black min-w-0">
            <div
                v-if="showTopBar"
                class="border-b border-light-steel dark:border-gray-800 bg-canvas-white/80 dark:bg-black/80 backdrop-blur sticky top-0 z-40"
            >
                <div class="px-6 sm:px-8 py-4">
                    <div class="flex items-center justify-end">
                        <Link
                            href="/resumes"
                            class="inline-flex items-center gap-2 px-5 py-2 text-[14px] font-medium text-canvas-white bg-carbon-black hover:bg-graphite dark:bg-white dark:text-black dark:hover:bg-gray-100 rounded-full transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Resume Search
                        </Link>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-x-hidden w-full">
                <slot />
            </div>
        </div>
    </div>
</template>
