<script setup>
import { Head, Link, router as Inertia } from "@inertiajs/vue3";
import Search from "./Search.vue";
import Banner from "./Banner.vue";
import VersionSwitcher from "./VersionSwitcher.vue";
import { onMounted, ref, computed } from "vue";
import { SigmieSearch } from "@sigmie/vue";

let showMenu = ref(false);
const toggleNav = () => (showMenu.value = !showMenu.value);
const hideNav = () => (showMenu.value = true);

onMounted(() => {
    hideNav();
});

let visit = (url) => {
    showMenu.value = true;
    Inertia.visit(url);
};

const props = defineProps({
    navigation: Object,
    currentVersion: String,
    availableVersions: Array,
});

// Check if we're on a docs page
const isDocsPage = computed(() => {
    return window.location.pathname.startsWith('/docs/');
});
</script>

<template>
    <div>
        <!-- Mobile Navigation -->
        <div
            v-if="!showMenu"
            class="lg:hidden fixed inset-0 top-16 z-40 bg-white dark:bg-black"
        >
            <div class="h-full overflow-y-auto px-6 py-6">
                <nav class="space-y-6">
                    <div v-for="(section, index) in navigation" :key="index">
                        <h5 class="mb-3 font-semibold text-sm text-gray-900 dark:text-white">
                            {{ section.title }}
                        </h5>
                        <ul class="space-y-1">
                            <li v-for="(link, linkIndex) in section.links" :key="linkIndex">
                                <button
                                    @click.prevent="() => visit(link.href)"
                                    :class="[
                                        'flex items-center w-full px-2 py-1.5 text-sm rounded-md transition-colors text-left',
                                        $page.url === link.href
                                            ? 'bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white font-medium'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-900/50'
                                    ]"
                                >
                                    {{ link.title }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        <header
            class="bg-white dark:bg-black h-16 fixed top-0 right-0 z-40 border-b border-gray-200 dark:border-gray-800 lg:left-[260px] xl:left-[280px] left-0"
        >
            <nav class="h-full flex items-center justify-between px-6">
                <!-- Left side - Title or breadcrumb could go here -->
                <div class="flex items-center">
                    <!-- Empty for now -->
                </div>

                <!-- Right side -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="hidden md:block">
                        <Link 
                            href="/search"
                            class="flex items-center space-x-3 px-3 py-1.5 text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-800 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Search docs...</span>
                            <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-black rounded border border-gray-200 dark:border-gray-700">⌘K</kbd>
                        </Link>
                    </div>
                    
                    <!-- Version Switcher -->
                    <VersionSwitcher 
                        v-if="isDocsPage && currentVersion && availableVersions"
                        :currentVersion="currentVersion"
                        :availableVersions="availableVersions"
                        class="hidden sm:block"
                    />
                    
                    <!-- GitHub -->
                    <a
                        href="https://github.com/sigmie/sigmie"
                        target="_blank"
                        class="hidden sm:block p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                    </a>
                    
                    <!-- Mobile menu -->
                    <button
                        @click="toggleNav"
                        class="lg:hidden p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                    >
                        <svg v-if="showMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </nav>
        </header>
    </div>
</template>

<style type="text/css"></style>