<script setup>
import { Head, Link } from "@inertiajs/inertia-vue3";
import Search from "./Search.vue";
import Banner from "./Banner.vue";
import { onMounted, ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
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

defineProps({
    navigation: Object,
});
</script>

<template>
    <div>
        <div
            v-if="showMenu"
            class="w-full lg:hidden block md:w-auto bg-white px-2"
        >
            <ul
                class="fixed bg-white left-0 right-0 max-h-screen overflow-scroll flex flex-col h-screen lg:h-auto lg:flex-row lg:space-x-8 lg:mt-0 lg:text-sm lg:font-medium lg:border-0 lg:bg-white dark:bg-gray-800 lg:dark:bg-gray-900 dark:border-gray-700 pb-20 lg:pb-0 -mt-3"
            >
                <li
                    class="lg:hidden pt-3 px-5"
                    v-for="(section, index) in navigation"
                    :key="index"
                >
                    <h4
                        class="mb-4 mt-6 font-semibold text-slate-900 dark:text-slate-200"
                    >
                        {{ section.title }}
                    </h4>
                    <div class="space-y-6 lg:space-y-2 border-l border-slate-100 dark:border-slate-700">
                        <button
                            @click.prevent="() => visit(link.href)"
                            v-for="(link, index) in section.links"
                            :key="index"
                            :class="{
                                'bg-zinc-100/40 text-zinc-500':
                                    $page.url === link.href,
                                'text-gray-700': !$page.url.startsWith('/docs'),
                            }"
                            class="block border-l pl-4 -ml-px border-transparent hover:border-slate-400 dark:hover:border-slate-500 text-slate-700 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-300"
                        >
                            {{ link.title }}
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <nav
            class="border-zinc-200 sm:px-4 py-2.5 rounded dark:bg-gray-900 flex bg-white flex-row h-[70px] fixed top-0 left-0 right-0 z-50 border-b shadow-none"
        >
            <div
                class="max-w-6xl w-full flex flex-row items-center justify-between mx-auto"
            >
                <Link href="/" class="flex items-center pl-2">
                    <img
                        class="h-10 w-auto"
                        src="/logo.png"
                        alt="Sigmie Logo"
                    />
                </Link>

                <Search></Search>

                <a
                    target="_blank"
                    href="https://github.com/sigmie"
                    class="block py-2 pl-3 pr-4 text-gray-700 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-zinc-600 md:p-0 dark:text-gray-400 md:dark:hover:text-white dark:hover:bg-gray-700 dark:hover:text-white md:dark:hover:bg-transparent"
                >
                    <svg
                        class="h-5 w-5 fill-zinc-400 hover:fill-zinc-500"
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
                    class="inline-flex items-center p-2 mr-2 text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                    aria-controls="navbar-default"
                    aria-expanded="false"
                >
                    <span class="sr-only">Open main menu</span>
                    <svg
                        class="w-6 h-6"
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
        </nav>
    </div>
</template>

<style type="text/css"></style>
