<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
    navigation: {
        type: Array,
        default: () => []
    }
});

// Icon components for each menu item
const icons = {
    'Semantic Search': 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'Text to Image': 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    'Smart Discovery': 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    'About Us': 'M12 4.354a4 4 0 110 8.646 4 4 0 010-8.646M9 9H3v11a1 1 0 001 1h16a1 1 0 001-1v-11h-6m0 0V7a1 1 0 10-2 0v2m0 0H9m3 0h3'
};

// Map link titles to section IDs
const linkToSectionId = {
    'Semantic Search': 'semantic-search',
    'Text to Image': 'image-search',
    'Smart Discovery': 'recommendations',
    'About Us': 'about'
};

// Flatten all links from sections
const allLinks = computed(() => {
    if (!props.navigation || !Array.isArray(props.navigation)) return [];
    return props.navigation.flatMap(section => section.links || []);
});

// Track active section based on scroll position
const activeSection = ref('semantic-search');

const handleScroll = () => {
    // Get all sections
    const sections = allLinks.value.map(link => ({
        id: linkToSectionId[link.title],
        href: link.href
    }));

    // Find which section is currently in view
    for (const section of sections) {
        const element = document.getElementById(section.id);
        if (element) {
            const rect = element.getBoundingClientRect();
            // If section is in upper half of viewport
            if (rect.top < window.innerHeight / 2) {
                activeSection.value = section.id;
            }
        }
    }
};

const isLinkActive = (link) => {
    const sectionId = linkToSectionId[link.title];
    return activeSection.value === sectionId;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    handleScroll(); // Call once on mount
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <aside
        class="hidden lg:block w-[100px] xl:w-[280px] flex-shrink-0 fixed left-0 top-0 h-screen bg-black border-r border-gray-800"
    >
        <div class="h-full flex flex-col pt-12 pb-6">
            <!-- Navigation Links -->
            <nav class="flex-1 flex flex-col w-full">
                <div v-for="link in allLinks" :key="link.href" class="w-full">
                    <Link
                        :href="link.href"
                        :class="[
                            'relative flex items-center justify-center xl:justify-start gap-4 px-4 py-4 xl:px-6 transition-all duration-200 w-full',
                            isLinkActive(link)
                                ? 'text-blue-400'
                                : 'text-gray-500 hover:text-gray-300'
                        ]"
                    >
                        <!-- Active Right Border -->
                        <div
                            v-if="isLinkActive(link)"
                            class="absolute right-0 top-0 bottom-0 w-0.5 bg-blue-400"
                        ></div>

                        <!-- Icon -->
                        <svg
                            class="w-5 h-5 xl:w-6 xl:h-6 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons[link.title]" />
                        </svg>

                        <!-- Text (hidden on mobile, visible on lg, visible on xl) -->
                        <span class="hidden xl:inline text-sm font-medium truncate">
                            {{ link.title }}
                        </span>
                    </Link>
                </div>
            </nav>

            <!-- Footer Links -->
            <div class="space-y-1 px-4 pb-2">
                <!-- Read the Docs -->
                <a
                    href="https://docs.sigmie.com"
                    target="_blank"
                    class="flex items-center justify-center xl:justify-start gap-3 px-4 py-3 text-gray-100 hover:text-white transition-colors w-full"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span class="hidden xl:inline text-sm font-medium">Read the docs</span>
                </a>

                <!-- GitHub -->
                <a
                    href="https://github.com/sigmie/sigmie"
                    target="_blank"
                    class="flex items-center justify-center xl:justify-start gap-3 px-4 py-3 text-gray-500 hover:text-gray-300 transition-colors w-full"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    <span class="hidden xl:inline text-sm font-medium">GitHub</span>
                </a>
            </div>
        </div>
    </aside>
</template>

<style type="text/css"></style>