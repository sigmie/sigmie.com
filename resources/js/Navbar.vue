<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3';
import Search from './Search.vue';

defineProps({
});
</script>

<template>
    <div
        class="flex bg-black flex-row h-[70px] fixed top-0 left-0 right-0 z-50 shadow-md"
    >
        <div
            class="flex flex-row h-full w-full justify-between items-center mx-auto px-10"
        >
            <Link class="flex flex-shrink-0 items-center px-4">
                <img
                    class="h-10 w-auto"
                    src="https://res.cloudinary.com/markos-nikolaos-orfanos/image/upload/v1585732959/white_na5bw6.png"
                    alt="Sigmie"
                />
            </Link>

            <!-- <Search></Search> -->

            <div class="flex flex-row space-x-3">
                <nav
                    class="text-sm leading-6 font-semibold text-slate-300 flex flex-row space-x-10 mr-10"
                >
                    <a
                        target="_blank"
                        href="https://app.sigmie.com"
                        class="cursor-pointer hover:text-slate-400"
                        >Application</a
                    >

                    <a
                        target="_blank"
                        href="https://blog.sigmie.com"
                        class="cursor-pointer hover:text-slate-400"
                        >Blog</a
                    >
                </nav>
                <a
                    target="_blank"
                    href="https://github.com/sigmie"
                    class="cursor-pointer"
                >
                    <svg
                        class="h-5 w-5 fill-slate-400 hover:fill-slate-500"
                        viewBox="0 0 16 16"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"
                        ></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</template>

<style type="text/css"></style>