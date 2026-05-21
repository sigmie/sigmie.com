<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";

defineProps({
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
const collapsedSections = ref(new Set());

const toggleSection = (sectionTitle) => {
    collapsedSections.value.has(sectionTitle)
        ? collapsedSections.value.delete(sectionTitle)
        : collapsedSections.value.add(sectionTitle);
    localStorage.setItem('collapsedSections', JSON.stringify([...collapsedSections.value]));
};

const isSectionCollapsed = (sectionTitle) => collapsedSections.value.has(sectionTitle);

const calculatePosition = () => {
    const viewportWidth = window.innerWidth;
    const maxContentWidth = 1472;
    const containerPadding = 24;

    if (viewportWidth > maxContentWidth + (containerPadding * 2)) {
        const leftOffset = (viewportWidth - maxContentWidth) / 2 + containerPadding;
        sidebarLeft.value = `${leftOffset}px`;
    } else {
        sidebarLeft.value = `${containerPadding}px`;
    }
};

onMounted(() => {
    const saved = localStorage.getItem('collapsedSections');
    if (saved) {
        try {
            collapsedSections.value = new Set(JSON.parse(saved));
        } catch (e) {
            console.error('Failed to parse collapsed sections:', e);
        }
    }

    calculatePosition();
    window.addEventListener('resize', calculatePosition);
});

onUnmounted(() => window.removeEventListener('resize', calculatePosition));
</script>

<template>
    <div class="w-64 flex-shrink-0 font-sans">
        <div class="fixed top-20 w-64 h-[calc(100vh-5rem)] overflow-y-auto pr-6 scrollbar-thin" :style="{ left: sidebarLeft }">
            <nav class="space-y-1 py-10">
                <div v-for="(section, index) in navigation" :key="index" class="mb-6">
                    <button
                        @click="toggleSection(section.title)"
                        class="w-full flex items-center justify-between px-2 py-2 text-[11px] font-semibold text-subtle-gray uppercase tracking-wider hover:text-graphite dark:hover:text-white transition-colors group"
                    >
                        <span>{{ section.title }}</span>
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="{ 'rotate-180': !isSectionCollapsed(section.title) }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="!isSectionCollapsed(section.title)" class="mt-1 space-y-0.5">
                        <Link
                            v-for="link in section.links"
                            :key="link.href"
                            :href="link.href"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-[14px] transition-colors"
                            :class="[
                                currentPath === link.href
                                    ? 'bg-ghostly-gray text-graphite font-medium dark:bg-gray-900 dark:text-white'
                                    : 'text-charcoal hover:text-graphite hover:bg-ghostly-gray dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-900'
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
