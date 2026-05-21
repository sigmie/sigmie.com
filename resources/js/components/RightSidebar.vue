<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import TableOfContents from "../TableOfContents.vue";

defineProps({
    html: {
        type: String,
        required: true
    }
});

const sidebarRight = ref('0px');

const calculatePosition = () => {
    const viewportWidth = window.innerWidth;
    const maxContentWidth = 1472;
    const containerPadding = 24;

    if (viewportWidth > maxContentWidth + (containerPadding * 2)) {
        const rightOffset = (viewportWidth - maxContentWidth) / 2 + containerPadding;
        sidebarRight.value = `${rightOffset}px`;
    } else {
        sidebarRight.value = `${containerPadding}px`;
    }
};

onMounted(() => {
    calculatePosition();
    window.addEventListener('resize', calculatePosition);
});

onUnmounted(() => window.removeEventListener('resize', calculatePosition));
</script>

<template>
    <div class="w-64 flex-shrink-0 font-sans">
        <div class="fixed top-20 w-64 h-[calc(100vh-5rem)] overflow-y-auto scrollbar-thin" :style="{ right: sidebarRight }">
            <div class="py-10">
                <h5 class="text-[11px] font-semibold uppercase tracking-wider text-subtle-gray mb-4 pl-4">On this page</h5>
                <TableOfContents :html="html" />
            </div>
        </div>
    </div>
</template>
