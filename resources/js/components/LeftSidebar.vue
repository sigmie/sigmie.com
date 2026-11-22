<script setup>
import { onMounted, ref } from 'vue';
import DocsNav from './DocsNav.vue';

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

const scroller = ref(null);

// A page far down the list opens with its link in view, not the list top.
onMounted(() => {
    const box = scroller.value;
    const current = box?.querySelector('[aria-current="page"]');
    if (!current) return;
    const offset = current.getBoundingClientRect().top - box.getBoundingClientRect().top;
    if (offset > box.clientHeight - 96) box.scrollTop = offset - box.clientHeight / 3;
});
</script>

<template>
    <aside class="hidden w-60 shrink-0 border-r border-[#eaeaea] lg:block dark:border-white/[0.1]">
        <div
            ref="scroller"
            class="sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto overscroll-contain pt-12 pr-5 pb-16 pl-10 scrollbar-thin [mask-image:linear-gradient(to_bottom,#000_calc(100%-3rem),transparent)] sm:pt-16"
        >
            <nav aria-label="Documentation">
                <DocsNav :navigation="navigation" :current-path="currentPath" />
            </nav>
        </div>
    </aside>
</template>
