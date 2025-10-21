<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

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
                    <img src="https://raw.githubusercontent.com/sigmie/art/refs/heads/main/logo/svg/logo-full-white.svg" alt="Sigmie" class="h-8" />
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
            class="fixed top-0 left-0 h-full w-64 bg-black border-r border-gray-800 overflow-y-auto transition-transform duration-300 z-40"
            :class="[
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
                'lg:translate-x-0'
            ]"
        >
            <!-- Logo -->
            <div class="p-6 border-b border-gray-800">
                <Link href="/" class="flex items-center gap-2">
                    <img src="https://raw.githubusercontent.com/sigmie/art/refs/heads/main/logo/svg/logo-full-white.svg" alt="Sigmie" class="h-8" />
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-0">
                <div v-for="item in navigation" :key="item.path">
                    <!-- Section Header -->
                    <div v-if="item.section" class="px-3 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        {{ item.section }}
                    </div>

                    <!-- Navigation Link -->
                    <Link
                        v-else
                        :href="item.path"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                        :class="[
                            isActive(item.path)
                                ? 'bg-gray-800 text-white'
                                : 'text-gray-400 hover:text-white hover:bg-gray-900'
                        ]"
                        @click="isMobileMenuOpen = false"
                    >
                        <svg v-if="item.icon" class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path>
                        </svg>
                        <span>{{ item.name }}</span>
                    </Link>
                </div>
            </nav>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-800 mt-auto">
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
