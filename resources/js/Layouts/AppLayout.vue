<script setup>
import Sidebar from "../Sidebar.vue";
import Footer from "../components/Footer.vue";

defineProps({
    navigation: {
        type: Object,
        default: () => ({})
    },
    title: {
        type: String,
        default: "Sigmie"
    }
});
</script>

<template>
    <div class="flex min-h-screen bg-canvas-white dark:bg-black overflow-x-hidden font-sans text-graphite dark:text-white">
        <Sidebar :navigation="navigation" />

        <div class="flex-1 flex flex-col lg:ml-[100px] xl:ml-[280px] bg-canvas-white dark:bg-black min-w-0">
            <div class="flex-1 overflow-x-hidden w-full">
                <slot />
            </div>

            <Footer />
        </div>
    </div>
</template>
