<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";

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

const collapsedSections = ref(new Set());

const toggleSection = (title) => {
    collapsedSections.value.has(title)
        ? collapsedSections.value.delete(title)
        : collapsedSections.value.add(title);
    localStorage.setItem('collapsedSections', JSON.stringify([...collapsedSections.value]));
};

const isSectionCollapsed = (title) => collapsedSections.value.has(title);

onMounted(() => {
    const saved = localStorage.getItem('collapsedSections');
    if (!saved) return;
    try {
        collapsedSections.value = new Set(JSON.parse(saved));
    } catch (e) {
        console.error('Failed to parse collapsed sections:', e);
    }
});
</script>

<template>
    <aside class="hidden lg:block w-64 flex-shrink-0 font-sans">
        <div class="sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto py-10 pr-6 scrollbar-thin">
            <nav class="space-y-7">
                <div v-for="(section, index) in navigation" :key="index">
                    <button
                        @click="toggleSection(section.title)"
                        class="w-full flex items-center justify-between mb-2 text-[11px] font-semibold text-subtle-gray uppercase tracking-wider hover:text-graphite dark:hover:text-white transition-colors"
                    >
                        <span>{{ section.title }}</span>
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="{ '-rotate-90': isSectionCollapsed(section.title) }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <ul v-show="!isSectionCollapsed(section.title)" class="space-y-px">
                        <li v-for="link in section.links" :key="link.href">
                            <Link
                                :href="link.href"
                                class="block py-1.5 pl-3 -ml-px text-[14px] leading-[1.4] border-l transition-colors"
                                :class="
                                    currentPath === link.href
                                        ? 'text-graphite font-medium border-graphite dark:text-white dark:border-white'
                                        : 'text-charcoal hover:text-graphite border-transparent hover:border-light-steel dark:text-gray-400 dark:hover:text-white'
                                "
                            >
                                {{ link.title }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </aside>
</template>
