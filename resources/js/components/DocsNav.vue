<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

// The docs page list, in the desktop sidebar (lg up) and the phone menu
// (below lg). Pages hang off a rail like "On this page"; the current one
// takes the orange mark.
defineProps({
    navigation: {
        type: Array,
        required: true,
    },
    currentPath: {
        type: String,
        default: '',
    },
});

defineEmits(['navigate']);

const collapsed = ref(new Set());

const toggle = (title) => {
    const next = new Set(collapsed.value);
    next.has(title) ? next.delete(title) : next.add(title);
    collapsed.value = next;
    try {
        localStorage.setItem('collapsedSections', JSON.stringify([...next]));
    } catch {}
};

onMounted(() => {
    try {
        collapsed.value = new Set(JSON.parse(localStorage.getItem('collapsedSections') || '[]'));
    } catch {}
});
</script>

<template>
    <div class="space-y-7">
        <section v-for="section in navigation" :key="section.title">
            <button
                type="button"
                class="group flex w-full items-center justify-between py-1 text-left text-[12.5px] font-medium text-[#737373] transition-colors hover:text-[#171717] dark:text-[#8b8b93] dark:hover:text-white"
                :aria-expanded="!collapsed.has(section.title)"
                @click="toggle(section.title)"
            >
                {{ section.title }}
                <svg
                    class="h-3.5 w-3.5 transition duration-200 group-hover:opacity-100 group-focus-visible:opacity-100"
                    :class="collapsed.has(section.title) ? '-rotate-90 opacity-100' : 'opacity-0'"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <ul v-show="!collapsed.has(section.title)" class="mt-1.5 border-l border-[#eaeaea] dark:border-white/[0.1]">
                <li v-for="link in section.links" :key="link.href">
                    <Link
                        :href="link.href"
                        class="-ml-px block border-l py-2.5 pl-3.5 text-[15px] leading-[1.35] transition-colors duration-150 lg:py-[5px] lg:text-[14px]"
                        :class="currentPath === link.href
                            ? 'border-orange-ink font-medium text-[#171717] dark:border-[#ff7b47] dark:text-[#fafafa]'
                            : 'border-transparent text-[#525252] hover:border-[#a1a1aa] hover:text-[#171717] dark:text-[#a1a1aa] dark:hover:border-[#52525b] dark:hover:text-white'"
                        :aria-current="currentPath === link.href ? 'page' : undefined"
                        @click="$emit('navigate')"
                    >
                        {{ link.title }}
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
