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
    <aside class="hidden w-60 shrink-0 border-r border-[#eaeaea] lg:block dark:border-white/[0.1]">
        <div class="sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto py-12 pr-5 pl-10 scrollbar-thin sm:py-16">
            <nav class="space-y-6" aria-label="Documentation">
                <div v-for="section in navigation" :key="section.title">
                    <button
                        type="button"
                        class="mb-1.5 flex w-full items-center justify-between text-[13px] font-semibold text-[#171717] dark:text-[#fafafa]"
                        :aria-expanded="!isSectionCollapsed(section.title)"
                        @click="toggleSection(section.title)"
                    >
                        <span>{{ section.title }}</span>
                        <svg
                            class="h-3.5 w-3.5 text-[#a1a1aa] transition-transform duration-200"
                            :class="{ '-rotate-90': isSectionCollapsed(section.title) }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <ul v-show="!isSectionCollapsed(section.title)" class="space-y-px">
                        <li v-for="link in section.links" :key="link.href">
                            <Link
                                :href="link.href"
                                class="-mx-2.5 block rounded-md px-2.5 py-1.5 text-[14px] leading-[1.35] transition-colors"
                                :class="currentPath === link.href
                                    ? 'bg-[#f4f4f5] font-medium text-[#171717] dark:bg-white/[0.07] dark:text-[#fafafa]'
                                    : 'text-[#525252] hover:bg-[#fafafa] hover:text-[#171717] dark:text-[#a1a1aa] dark:hover:bg-white/[0.03] dark:hover:text-white'"
                                :aria-current="currentPath === link.href ? 'page' : undefined"
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
