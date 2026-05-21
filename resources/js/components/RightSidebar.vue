<script setup>
import TableOfContents from "../TableOfContents.vue";

defineProps({
    html: {
        type: String,
        required: true
    }
});
</script>

<template>
    <aside class="hidden xl:block w-56 flex-shrink-0 font-sans">
        <div class="sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto py-10 scrollbar-thin">
            <h5 class="text-[13px] font-semibold text-graphite dark:text-white mb-3">On this page</h5>
            <TableOfContents :html="html" />
        </div>
    </aside>
</template>
