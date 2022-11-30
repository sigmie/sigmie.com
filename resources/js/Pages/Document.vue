<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3';

defineProps({
html: String,
title: String,
navigation: Object,
});
</script>

<template>
    <div class="pt-20">
        <div class="flex flex-col font-display relative">
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

            <div class="flex flex-row">
                <div
                    class="w-[400px] max-h-screen overflow-y-scroll fixed left-0 top-0 pt-20 pb-5 z-10 min-h-screen lg:block hidden"
                >
                    <div class="block w-[300px] float-right pt-5 pb-4">
                        <nav
                            class="mt-5 flex-1 space-y-1 px-2 flex-col flex space-y-5"
                        >
                            <div
                                v-for="(section, index) in navigation"
                                :key="index"
                            >
                                <h4 class="font-semibold mb-2 text-sm">
                                    {{ section.title }}
                                </h4>
                                <div class="flex flex-col border-l">
                                    <Link
                                        v-for="(link, index) in section.links"
                                        :key="index"
                                        :class="
                                            $page.url === link.href
                                                ? 'text-orange-500 border-orange-500 border-l'
                                                : 'text-slate-500 hover:text-slate-600 hover:before:block hover:border-gray-400 hover:border-l'
                                        "
                                        class="w-full pl-3.5 my-1 -ml-[1px] text-sm"
                                        :href="link.href"
                                    >
                                        {{ link.title }}
                                    </Link>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
                <main class="prose mx-auto w-full max-w-3xl px-1 py-10">
                    <h1>{{ title }}</h1>
                    <div v-html="html"></div>
                </main>
            </div>
        </div>
    </div>
</template>

<style type="text/css">
pre code {
    @apply block p-3;
}
.prose :where(pre):not(:where([class~="not-prose"] *)) {
    background-color: #292d3e;
}

code {
    background-color: #292d3e;
    color: #a6accd !important;
    @apply rounded px-1 py-0.5 font-normal !important;
}

.heading-permalink {
    @apply mr-2 no-underline text-orange-500;
}

.table-of-contents  li::marker {
    content: '#';
    @apply text-orange-500;
}

.table-of-contents > li > a {
    @apply no-underline font-bold;
}

.table-of-contents > li > ul > li > a {
    @apply no-underline font-semibold;
}

.table-of-contents > li > ul > li > ul > li > a {
    @apply no-underline text-gray-700 font-normal;
}

/*
  Blur and dim the lines that don't have the `.line-focus` class,
  but are within a code block that contains any focus lines.
*/
.torchlight.has-focus-lines .line:not(.line-focus) {
    transition: filter 0.35s, opacity 0.35s;
    filter: blur(0.095rem);
    opacity: 0.65;
}

/*
  When the code block is hovered, bring all the lines into focus.
*/
.torchlight.has-focus-lines:hover .line:not(.line-focus) {
    filter: blur(0px);
    opacity: 1;
}
.torchlight summary:focus {
    outline: none;
}

/* Hide the default markers, as we provide our own */
.torchlight details > summary::marker,
.torchlight details > summary::-webkit-details-marker {
    display: none;
}

.torchlight details .summary-caret::after {
    pointer-events: none;
}

/* Add spaces to keep everything aligned */
.torchlight .summary-caret-empty::after,
.torchlight details .summary-caret-middle::after,
.torchlight details .summary-caret-end::after {
    content: " ";
}

/* Show a minus sign when the block is open. */
.torchlight details[open] .summary-caret-start::after {
    content: "-";
}

/* And a plus sign when the block is closed. */
.torchlight details:not([open]) .summary-caret-start::after {
    content: "+";
}

/* Hide the [...] indicator when open. */
.torchlight details[open] .summary-hide-when-open {
    display: none;
}

/* Show the [...] indicator when closed. */
.torchlight details:not([open]) .summary-hide-when-open {
    display: initial;
}

.callout {
    @apply bg-gray-100 p-4 rounded-xl w-full min-w-full my-5;
}

.callout.danger {
    @apply bg-amber-400/70 text-amber-700/80;
}

.callout.danger::before {
    @apply font-bold;
}

.callout.warning {
    @apply bg-slate-700/90 text-white;
}

.callout.warning::before {
    @apply font-bold;
}

.callout.info::before {
    @apply font-bold;
}

.callout.info {
    @apply bg-sky-600/70 text-white;
}
</style>
