<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
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

const isActive = (path) => props.currentPath === path;

const isMobileMenuOpen = ref(false);
const toggleMobileMenu = () => (isMobileMenuOpen.value = !isMobileMenuOpen.value);

const collapsedSections = ref(new Set());

const toggleSection = (sectionTitle) => {
    collapsedSections.value.has(sectionTitle)
        ? collapsedSections.value.delete(sectionTitle)
        : collapsedSections.value.add(sectionTitle);
    localStorage.setItem('collapsedSections', JSON.stringify([...collapsedSections.value]));
};

const isSectionCollapsed = (sectionTitle) => collapsedSections.value.has(sectionTitle);

onMounted(() => {
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
    <div class="font-sans">
        <div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-canvas-white dark:bg-black border-b border-light-steel dark:border-gray-800 px-4 py-3">
            <div class="flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2">
                    <Logo :height="32" />
                </Link>
                <button
                    @click="toggleMobileMenu"
                    aria-label="Toggle docs navigation"
                    class="p-2 rounded-full text-charcoal hover:text-graphite hover:bg-ghostly-gray dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-900 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <aside
            class="fixed top-0 left-0 h-full w-64 bg-canvas-white dark:bg-black border-r border-light-steel dark:border-gray-800 transition-transform duration-300 z-40 flex flex-col"
            :class="[
                isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
                'lg:translate-x-0'
            ]"
        >
            <div class="sticky top-0 z-10 p-6 bg-canvas-white dark:bg-black">
                <Link href="/" aria-label="Sigmie home" class="flex items-center gap-2">
                    <Logo :height="32" />
                </Link>
            </div>

            <nav class="p-4 space-y-1 overflow-y-auto flex-1 scrollbar-thin">
                <div v-for="(section, index) in navigation" :key="index" class="mb-6">
                    <button
                        @click="toggleSection(section.title)"
                        class="w-full flex items-center justify-between px-2 py-2 text-[11px] font-semibold text-subtle-gray uppercase tracking-wider hover:text-graphite dark:hover:text-white transition-colors"
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
                                isActive(link.href)
                                    ? 'bg-ghostly-gray text-graphite font-medium dark:bg-gray-900 dark:text-white'
                                    : 'text-charcoal hover:text-graphite hover:bg-ghostly-gray dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-900'
                            ]"
                            @click="isMobileMenuOpen = false"
                        >
                            <span>{{ link.title }}</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </aside>

        <div
            v-if="isMobileMenuOpen"
            @click="toggleMobileMenu"
            class="fixed inset-0 bg-graphite/50 z-30 lg:hidden"
        ></div>
    </div>
</template>
