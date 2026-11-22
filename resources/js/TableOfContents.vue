<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

// Headings come from the server (Documentation::headings), so the list is in
// the SSR markup. Links are plain fragments: the browser scrolls, honours the
// headings' scroll-margin-top and records the hash in history.
const props = defineProps({
    headings: {
        type: Array,
        default: () => [],
    },
});

const activeId = ref('');

const handleScroll = () => {
    const scrollPosition = window.scrollY + 100;
    let currentActiveId = '';

    for (const heading of props.headings) {
        const element = document.getElementById(heading.id);
        if (element && element.offsetTop <= scrollPosition) {
            currentActiveId = heading.id;
        }
    }

    activeId.value = currentActiveId;
};

onMounted(() => {
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <nav v-if="headings.length > 0" aria-label="On this page">
        <ul class="border-l border-[#eaeaea] text-[13px] dark:border-white/[0.1]">
            <li v-for="heading in headings" :key="heading.id">
                <a
                    :href="`#${heading.id}`"
                    class="-ml-px block border-l py-1 leading-[1.4] transition-colors duration-150"
                    :class="[
                        heading.level === 2 ? 'pl-3' : heading.level === 3 ? 'pl-6' : 'pl-9',
                        activeId === heading.id
                            ? 'border-orange-ink font-medium text-[#171717] dark:border-[#ff7b47] dark:text-[#fafafa]'
                            : 'border-transparent text-[#737373] hover:text-[#171717] dark:text-[#8b8b93] dark:hover:text-white',
                    ]"
                    :aria-current="activeId === heading.id ? 'location' : undefined"
                >
                    {{ heading.text }}
                </a>
            </li>
        </ul>
    </nav>
</template>
