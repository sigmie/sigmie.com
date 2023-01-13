<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3';
import Sidebar from '../Sidebar.vue';
import Navbar from '../Navbar.vue';

defineProps({
html: String,
title: String,
description: String,
navigation: Object,
href: String,
card: String,
});
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta
            head-key="description"
            name="description"
            :content="description"
        />

        <meta name="title" :content="title" />
        <meta name="description" :content="description" />

        <meta property="og:type" content="website" />
        <meta property="og:url" :content="href" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="card" />

        <meta property="twitter:card" content="summary_large_image" />
        <meta property="twitter:url" :content="href" />
        <meta property="twitter:title" :content="title" />
        <meta property="twitter:description" :content="description" />
        <meta property="twitter:image" :content="card" />
    </Head>

    <div class="pt-20">
        <div class="flex flex-col font-display relative">
            <Navbar :navigation="navigation"></Navbar>
            <div class="flex flex-row">
                <Sidebar :navigation="navigation"></Sidebar>
                <main
                    class="prose mx-auto w-full max-w-3xl md:pl-52 px-2 py-10"
                >
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

.table-of-contents li::marker {
    content: "#";
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
