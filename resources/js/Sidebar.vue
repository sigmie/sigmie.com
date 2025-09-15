<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Banner from "./Banner.vue";
import Twitter from "./Twitter.vue";

defineProps({
    navigation: Object,
});
</script>

<template>
    <aside
        class="hidden lg:block w-[234px] xl:w-[250px] flex-shrink-0 fixed left-0 top-16 bottom-0 border-r border-gray-200 dark:border-gray-800"
    >
        <div class="h-full overflow-y-auto py-8 px-5">
            <!-- Sigmie Logo -->
            <Link href="/" class="flex items-center space-x-3 mb-8 group">
                <img
                    class="h-7 w-auto transition-transform group-hover:scale-105"
                    src="/logo.png"
                    alt="Sigmie"
                />
                <span class="font-semibold text-lg text-gray-900 dark:text-white">Sigmie</span>
            </Link>
            
            <nav class="space-y-6">
                <div v-for="(section, index) in navigation" :key="index">
                    <h5 class="mb-3 font-semibold text-sm text-gray-900 dark:text-white">
                        {{ section.title }}
                    </h5>
                    <ul class="space-y-1">
                        <li v-for="(link, linkIndex) in section.links" :key="linkIndex">
                            <Link
                                :href="link.href"
                                :class="[
                                    'group flex items-center px-2 py-1.5 text-sm rounded-md transition-colors',
                                    $page.url === link.href
                                        ? 'bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-white font-medium'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-900/50'
                                ]"
                            >
                                <span class="truncate">{{ link.title }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Optional: Add these back if needed -->
            <!-- <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-800 space-y-4">
                <Banner />
                <Twitter />
            </div> -->
        </div>
    </aside>
</template>

<style type="text/css"></style>