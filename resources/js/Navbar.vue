<script setup>
import { Head, Link, router as Inertia } from "@inertiajs/vue3";
import Search from "./Search.vue";
import Banner from "./Banner.vue";
import VersionSwitcher from "./VersionSwitcher.vue";
import { onMounted, ref, computed } from "vue";
import { SigmieSearch } from "@sigmie/vue";

let showMenu = ref(false);
const toggleNav = () => (showMenu.value = !showMenu.value);
const hideNav = () => (showMenu.value = true);

onMounted(() => {
    hideNav();
});

let visit = (url) => {
    showMenu.value = true;
    Inertia.visit(url);
};

const props = defineProps({
    navigation: Object,
    currentVersion: String,
    availableVersions: Array,
});

// Check if we're on a docs page
const isDocsPage = computed(() => {
    return window.location.pathname.startsWith('/docs/');
});
</script>

<template>
    <div>
        <div
            v-if="!showMenu"
            class="w-full lg:hidden block md:w-auto bg-white dark:bg-black px-2"
        >
            <ul
                class="fixed bg-white dark:bg-black left-0 right-0 max-h-screen overflow-scroll flex flex-col h-screen lg:h-auto lg:flex-row lg:space-x-8 lg:mt-0 lg:text-sm lg:font-medium lg:border-0 pb-20 lg:pb-0 -mt-3"
            >
                <li
                    class="lg:hidden pt-3 px-5"
                    v-for="(section, index) in navigation"
                    :key="index"
                >
                    <h4
                        class="mb-4 mt-6 font-semibold text-gray-900 dark:text-gray-100"
                    >
                        {{ section.title }}
                    </h4>
                    <div class="space-y-2 border-l border-gray-200 dark:border-gray-800">
                        <button
                            @click.prevent="() => visit(link.href)"
                            v-for="(link, index) in section.links"
                            :key="index"
                            :class="{
                                'bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 border-gray-900 dark:border-gray-100':
                                    $page.url === link.href,
                                'text-gray-600 dark:text-gray-400': !$page.url.startsWith('/docs'),
                            }"
                            class="block border-l-2 pl-4 -ml-px border-transparent hover:border-gray-400 dark:hover:border-gray-600 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                        >
                            {{ link.title }}
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <nav
            class="bg-white/80 dark:bg-black/80 backdrop-blur-md flex flex-row h-16 fixed top-0 left-0 right-0 z-50 border-b border-gray-200 dark:border-gray-800"
        >
            <div
                class="max-w-7xl w-full flex flex-row items-center justify-between mx-auto px-4 sm:px-6 lg:px-8"
            >
                <Link href="/" class="flex items-center">
                    <img
                        class="h-8"
                        src="/logo.png"
                        alt="Sigmie Logo"
                    />
                </Link>

                <Search></Search>

                <div class="flex items-center space-x-4">
                    <VersionSwitcher 
                        v-if="isDocsPage && currentVersion && availableVersions"
                        :currentVersion="currentVersion"
                        :availableVersions="availableVersions"
                    />
                    <a
                        target="_blank"
                        href="https://github.com/sigmie"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 transition-colors"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 16 16"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"
                            ></path>
                        </svg>
                    </a>
                    <button
                        @click="toggleNav"
                        type="button"
                        class="inline-flex items-center p-2 text-gray-600 dark:text-gray-400 rounded-geist-sm hover:bg-gray-100 dark:hover:bg-gray-900 md:hidden transition-colors"
                        aria-controls="navbar-default"
                        aria-expanded="false"
                    >
                        <span class="sr-only">Open main menu</span>
                        <svg
                            class="w-5 h-5"
                            aria-hidden="true"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                    </button>
                </div>
            </div>
        </nav>
    </div>
</template>

<style type="text/css"></style>