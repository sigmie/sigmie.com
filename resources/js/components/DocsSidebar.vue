<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref, onMounted } from "vue";
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

// State for collapsible sections
const collapsedSections = ref(new Set());

// Toggle section collapse
const toggleSection = (sectionTitle) => {
    if (collapsedSections.value.has(sectionTitle)) {
        collapsedSections.value.delete(sectionTitle);
    } else {
        collapsedSections.value.add(sectionTitle);
    }
    // Store in localStorage
    localStorage.setItem('collapsedSections', JSON.stringify([...collapsedSections.value]));
};

// Check if section is collapsed
const isSectionCollapsed = (sectionTitle) => {
    return collapsedSections.value.has(sectionTitle);
};

onMounted(() => {
    // Load collapsed sections from localStorage
    const saved = localStorage.getItem('collapsedSections');
    if (saved) {
        try {
            collapsedSections.value = new Set(JSON.parse(saved));
        } catch (e) {
            console.error('Failed to parse collapsed sections:', e);
        }
    }
});
</script>

<template>
    <div>
        <!-- Mobile Menu Toggle -->
        <div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white dark:bg-black px-4 py-3">
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
            class="fixed top-0 left-0 h-full w-64 bg-white dark:bg-black transition-transform duration-300 z-40 flex flex-col"
            :class="[
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
                'lg:translate-x-0'
            ]"
        >
            <!-- Logo - Sticky at top -->
            <div class="sticky top-0 z-10 p-6 bg-white dark:bg-black">
                <Link href="/" class="flex items-center gap-2">
                    <Logo :height="32" />
                </Link>
            </div>

            <!-- Navigation - Scrollable -->
            <nav class="p-4 space-y-3 overflow-y-auto flex-1">
                <div v-for="(section, index) in navigation" :key="index" class="mb-4">
                    <!-- Section Header - Clickable -->
                    <button
                        @click="toggleSection(section.title)"
                        class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-md transition-colors group"
                    >
                        <span class="uppercase tracking-wide">{{ section.title }}</span>
                        <svg
                            class="w-4 h-4 transition-transform duration-200"
                            :class="{ 'rotate-180': !isSectionCollapsed(section.title) }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Section Links - Collapsible -->
                    <div
                        v-show="!isSectionCollapsed(section.title)"
                        class="mt-2 space-y-0.5"
                    >
                        <Link
                            v-for="link in section.links"
                            :key="link.href"
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
