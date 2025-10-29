<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref, onMounted } from "vue";
import Logo from "./Logo.vue";
import { useTheme } from "../composables/useTheme";

const props = defineProps({
    navigation: {
        type: Array,
        default: () => []
    },
    currentPath: {
        type: String,
        default: ''
    }
});

const isActive = (path) => {
    return props.currentPath === path;
};

const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const { theme, toggleTheme, initTheme } = useTheme();

onMounted(() => {
    initTheme();
});
</script>

<template>
    <div>
        <!-- Mobile Menu Toggle -->
        <div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white dark:bg-black border-b border-gray-200 dark:border-gray-800 px-4 py-3">
            <div class="flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2">
                    <Logo :height="32" />
                </Link>
                <button
                    @click="toggleMobileMenu"
                    class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Sidebar -->
        <aside
            class="fixed top-0 left-0 h-full w-72 bg-white dark:bg-black border-r border-gray-200 dark:border-gray-800 transition-transform duration-300 z-40 flex flex-col"
            :class="[
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
                'lg:translate-x-0'
            ]"
        >
            <!-- Logo - Sticky at top -->
            <div class="sticky top-0 z-10 p-6 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-black">
                <Link href="/" class="flex items-center gap-2">
                    <Logo :height="32" />
                </Link>
            </div>

            <!-- Navigation - Scrollable -->
            <nav class="p-4 space-y-1 overflow-y-auto flex-1">
                <div v-for="(section, index) in navigation" :key="index" class="mb-4">
                    <!-- Section Header -->
                    <div class="px-3 pt-4 pb-2 text-xs font-semibold text-gray-500 dark:text-gray-500 uppercase tracking-wider">
                        {{ section.title }}
                    </div>

                    <!-- Navigation Links -->
                    <div v-for="link in section.links" :key="link.href" class="space-y-0.5">
                        <Link
                            :href="link.href"
                            class="flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-colors"
                            :class="[
                                isActive(link.href)
                                    ? 'bg-gray-200 dark:bg-gray-800 text-gray-900 dark:text-white font-medium'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-900'
                            ]"
                            @click="isMobileMenuOpen = false"
                        >
                            <span>{{ link.title }}</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Theme Switcher - Sticky at bottom -->
            <div class="sticky bottom-0 p-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-black">
                <button
                    @click="toggleTheme"
                    class="flex items-center gap-3 px-3 py-2 rounded-md text-sm w-full transition-colors text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-900"
                    :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                >
                    <svg v-if="theme === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                    <span>{{ theme === 'dark' ? 'Light Mode' : 'Dark Mode' }}</span>
                </button>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <div
            v-if="isMobileMenuOpen"
            @click="toggleMobileMenu"
            class="fixed inset-0 bg-black/50 dark:bg-black/50 z-30 lg:hidden"
        ></div>
    </div>
</template>

<style scoped>
/* Documentation sidebar specific styles */
</style>
