<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
    navigation: {
        type: Array,
        required: true
    },
    currentPath: {
        type: String,
        default: ''
    }
});

const sidebarLeft = ref('0px');

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

const calculatePosition = () => {
    // Calculate left position based on viewport and max-w-[92rem] (1472px)
    const viewportWidth = window.innerWidth;
    const maxContentWidth = 1472; // 92rem = 1472px
    const containerPadding = 24; // px-6 = 24px

    if (viewportWidth > maxContentWidth + (containerPadding * 2)) {
        // Center the container and position sidebar accordingly
        const leftOffset = (viewportWidth - maxContentWidth) / 2 + containerPadding;
        sidebarLeft.value = `${leftOffset}px`;
    } else {
        // Use padding when viewport is smaller
        sidebarLeft.value = `${containerPadding}px`;
    }
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

    // Calculate initial position
    calculatePosition();

    // Recalculate on window resize
    window.addEventListener('resize', calculatePosition);
});

onUnmounted(() => {
    window.removeEventListener('resize', calculatePosition);
});
</script>

<template>
    <div class="w-64 flex-shrink-0">
        <div class="fixed top-20 w-64 h-[calc(100vh-5rem)] overflow-y-auto pr-6" :style="{ left: sidebarLeft }">
            <nav class="space-y-3 py-8">
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
                                currentPath === link.href
                                    ? 'bg-gray-200 dark:bg-gray-800 text-gray-900 dark:text-white font-medium'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-900'
                            ]"
                        >
                            <span>{{ link.title }}</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</template>
