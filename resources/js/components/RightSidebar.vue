<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import TableOfContents from "../TableOfContents.vue";

const props = defineProps({
    html: {
        type: String,
        required: true
    }
});

const sidebarRight = ref('0px');

const calculatePosition = () => {
    // Calculate right position based on viewport and max-w-[92rem] (1472px)
    const viewportWidth = window.innerWidth;
    const maxContentWidth = 1472; // 92rem = 1472px
    const containerPadding = 24; // px-6 = 24px

    if (viewportWidth > maxContentWidth + (containerPadding * 2)) {
        // Center the container and position sidebar accordingly
        const rightOffset = (viewportWidth - maxContentWidth) / 2 + containerPadding;
        sidebarRight.value = `${rightOffset}px`;
    } else {
        // Use padding when viewport is smaller
        sidebarRight.value = `${containerPadding}px`;
    }
};

onMounted(() => {
    // Calculate initial position
    calculatePosition();

    // Recalculate on window resize
    window.addEventListener('resize', calculatePosition);
});

onUnmounted(() => {
    window.removeEventListener('resize', calculatePosition);
});
</script>

<template>
    <div class="w-64 flex-shrink-0">
        <div class="fixed top-20 w-64 h-[calc(100vh-5rem)] overflow-y-auto" :style="{ right: sidebarRight }">
            <div class="py-8">
                <h5 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">On this page</h5>
                <TableOfContents :html="html" />
            </div>
        </div>
    </div>
</template>
