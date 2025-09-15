<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Sidebar from '../Sidebar.vue';
import Navbar from '../Navbar.vue';
import Banner from '../Banner.vue';

defineProps({
title: String,
posts: Object,
navigation: Object
});
</script>

<template>
    <Head>
        <title>Blog - Sigmie</title>
        <meta name="description" content="Latest articles and updates about Sigmie Elasticsearch library. Learn about search implementation, best practices, and tips for using Sigmie." />
        <meta name="keywords" content="sigmie blog, elasticsearch tutorials, search implementation, sigmie updates, elasticsearch best practices" />
        <meta property="og:title" content="Blog - Sigmie" />
        <meta property="og:description" content="Latest articles and updates about Sigmie Elasticsearch library. Learn about search implementation, best practices, and tips." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://sigmie.com/blog" />
        <meta property="og:image" content="https://sigmie.com/og-image.png" />
        <meta property="og:site_name" content="Sigmie" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Blog - Sigmie" />
        <meta name="twitter:description" content="Latest articles and updates about Sigmie Elasticsearch library. Learn about search implementation and best practices." />
        <meta name="twitter:image" content="https://sigmie.com/og-image.png" />
        <link rel="canonical" href="https://sigmie.com/blog" />
    </Head>
    <div class="pt-20">
        <div class="flex flex-col font-display relative">
            <Navbar :navigation="navigation"></Navbar>
            <div class="flex flex-row">
                <div
                    class="w-[500px] max-h-screen overflow-y-scroll fixed left-0 top-0 pt-20 pb-5 z-10 min-h-screen lg:block hidden"
                >
                    <div class="block w-[400px] float-right pt-5 pb-4">
                        <nav
                            class="mt-5 flex-1 space-y-1 px-2 flex-col flex space-y-5 mx-auto"
                        >
                            <Banner />
                        </nav>
                    </div>
                </div>
                <main class="mx-auto w-full max-w-3xl md:pl-52 px-2 py-10">
                    <h1 class="text-4xl font-bold mb-5">{{ title }}</h1>
                    <!-- <div v-html="html"></div> -->

                    <div
                        class="flex flex-col space-y-1"
                        v-for="(section, index) in posts"
                        :key="index"
                    >
                        <ul :key="index" v-for="(link, index) in section.links">
                            <li>
                                <Link
                                    :href="link.href"
                                    class="flex flex-row space-x-2 items-center p-1 text-md no-underline text-gray-700"
                                >
                                    <span class="text-zinc-500 text-2xl"
                                        >#</span
                                    >
                                    <span
                                        class="w-full text-md font-semibold"
                                        >{{ link.title }}</span
                                    >
                                </Link>
                            </li>
                        </ul>
                    </div>
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
    background-color: #0f111a;
}

code {
    background-color: #0f111a;
    color: #a6accd !important;
    @apply rounded px-1 py-0.5 font-normal !important;
}

.heading-permalink {
    @apply mr-2 no-underline text-zinc-500;
}

.table-of-contents li::marker {
    content: "#";
    @apply text-zinc-500;
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
    @apply bg-zinc-700/90 text-white;
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
