<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Banner from "./Banner.vue";
import Twitter from "./Twitter.vue";

defineProps({
    navigation: Object,
});
</script>

<template>
    <div
        class="z-10 min-h-screen lg:block hidden max-w-xs border-r border-gray-200 dark:border-gray-800"
    >
        <div class="block mx-auto pt-8 pb-4 max-h-screen overflow-y-auto">
            <nav class="mt-8 flex-1 px-6 flex-col flex space-y-6 mx-auto">
                <div v-for="(section, index) in navigation" :key="index">
                    <h4 class="font-medium mb-3 text-geist-sm text-gray-900 dark:text-gray-100">
                        {{ section.title }}
                    </h4>
                    <div class="flex flex-col space-y-1">
                        <Link
                            v-for="(link, index) in section.links"
                            :key="index"
                            :class="
                                $page.url === link.href
                                    ? 'text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-900 font-medium'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'
                            "
                            class="px-3 py-1.5 rounded-geist-sm text-geist-sm transition-all duration-200"
                            :href="link.href"
                        >
                            {{ link.title }}
                        </Link>
                    </div>
                </div>

                <Banner />
                <Twitter />
            </nav>
        </div>
    </div>
</template>

<style type="text/css"></style>