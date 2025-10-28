<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import Logo from "./Logo.vue";

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
</script>

<template>
    <div>
        <!-- Mobile Menu Toggle -->
        <div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-black border-b border-gray-800 px-4 py-3">
            <div class="flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2">
                    <Logo :height="32" />
                </Link>
                <button
                    @click="toggleMobileMenu"
                    class="p-2 text-gray-400 hover:text-white transition-colors"
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
            class="fixed top-0 left-0 h-full w-64 bg-black border-r border-gray-800 transition-transform duration-300 z-40 flex flex-col"
            :class="[
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
                'lg:translate-x-0'
            ]"
        >
            <!-- Logo - Sticky at top -->
            <div class="sticky top-0 z-10 p-6 border-b border-gray-800 bg-black">
                <Link href="/" class="flex items-center gap-2">
                    <Logo :height="32" />
                </Link>
            </div>

            <!-- Navigation - Scrollable -->
            <nav class="p-4 space-y-1 overflow-y-auto flex-1">
                <div v-for="(section, index) in navigation" :key="index" class="mb-4">
                    <!-- Section Header -->
                    <div class="px-3 pt-4 pb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        {{ section.title }}
                    </div>

                    <!-- Navigation Links -->
                    <div v-for="link in section.links" :key="link.href" class="space-y-0.5">
                        <Link
                            :href="link.href"
                            class="flex items-center gap-2 px-3 py-2 rounded-md text-sm transition-colors"
                            :class="[
                                isActive(link.href)
                                    ? 'bg-gray-800 text-white font-medium'
                                    : 'text-gray-400 hover:text-white hover:bg-gray-900'
                            ]"
                            @click="isMobileMenuOpen = false"
                        >
                            <span>{{ link.title }}</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Footer - Sticky at bottom -->
            <div class="sticky bottom-0 p-4 border-t border-gray-800 bg-black">
                <Link
                    href="/"
                    class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-400 hover:text-white transition-colors rounded-md hover:bg-gray-900"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Home
                </Link>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <div
            v-if="isMobileMenuOpen"
            @click="toggleMobileMenu"
            class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        ></div>
    </div>
</template>

<style scoped>
/* Documentation sidebar specific styles */
</style>
