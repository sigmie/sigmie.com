<script setup>
import TableOfContents from "../TableOfContents.vue";

defineProps({
    headings: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <aside class="hidden w-64 shrink-0 border-l border-[#eaeaea] xl:block dark:border-white/[0.1]">
        <div class="sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto px-6 py-12 scrollbar-thin sm:py-16">
            <p class="mb-3 text-[13px] font-medium text-[#171717] dark:text-[#fafafa]">On this page</p>
            <TableOfContents :headings="headings" />
        </div>
    </aside>
</template>
