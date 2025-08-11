<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Sidebar from "../Sidebar.vue";
import Navbar from "../Navbar.vue";

defineProps({
    html: String,
    title: String,
    description: String,
    navigation: Object,
    href: String,
    card: String,
    currentVersion: String,
    availableVersions: Array,
});
</script>

<template>
    <Head :title="title">
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

    <div class="pt-16 min-h-screen bg-white dark:bg-black">
        <div class="flex flex-col">
            <Navbar 
                :navigation="navigation"
                :currentVersion="currentVersion"
                :availableVersions="availableVersions"
            ></Navbar>
            <div class="flex flex-row justify-center max-w-7xl mx-auto w-full">
                <Sidebar :navigation="navigation"></Sidebar>
                <main class="flex-1 max-w-4xl px-6 lg:px-12 py-12">
                    <article class="prose prose-gray dark:prose-invert max-w-none">
                        <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-gray-100 mb-8">{{ title }}</h1>
                        <div v-html="html" class="text-gray-600 dark:text-gray-400"></div>
                    </article>
                </main>
            </div>
        </div>
    </div>
</template>

<style type="text/css">
pre code {
    @apply block p-4 rounded-geist;
}

.prose :where(pre):not(:where([class~="not-prose"] *)) {
    @apply bg-gray-950 dark:bg-gray-900 border border-gray-800 rounded-geist overflow-x-auto;
}

.prose :where(code):not(:where([class~="not-prose"] *)) {
    @apply text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-900 rounded-geist-sm px-1.5 py-0.5 text-sm font-mono;
}

pre code {
    @apply bg-transparent p-4;
}

.heading-permalink {
    @apply mr-2 no-underline text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors;
}

.table-of-contents li::marker {
    content: "";
}

.table-of-contents > li > a {
    @apply no-underline font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400;
}

.table-of-contents > li > ul > li > a {
    @apply no-underline font-normal text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100;
}

.table-of-contents > li > ul > li > ul > li > a {
    @apply no-underline text-gray-500 dark:text-gray-500 font-normal hover:text-gray-700 dark:hover:text-gray-300;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4 {
    @apply text-gray-900 dark:text-gray-100 font-semibold;
}

.prose h1 {
    @apply text-3xl;
}

.prose h2 {
    @apply text-2xl mt-12 mb-6;
}

.prose h3 {
    @apply text-xl mt-8 mb-4;
}

.prose p {
    @apply text-gray-600 dark:text-gray-400 leading-7;
}

.prose a {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 no-underline hover:underline;
}

.prose strong {
    @apply text-gray-900 dark:text-gray-100 font-semibold;
}

.prose ul, .prose ol {
    @apply text-gray-600 dark:text-gray-400;
}

.prose li {
    @apply my-2;
}

.prose blockquote {
    @apply border-l-4 border-gray-300 dark:border-gray-700 pl-4 italic text-gray-600 dark:text-gray-400;
}

.prose table {
    @apply w-full border-collapse;
}

.prose th {
    @apply text-left font-medium text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-900 p-3 border border-gray-200 dark:border-gray-800;
}

.prose td {
    @apply p-3 border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400;
}

/*
  Blur and dim the lines that don't have the `.line-focus` class,
  but are within a code block that contains any focus lines.
*/
.torchlight.has-focus-lines .line:not(.line-focus) {
    transition: filter 0.35s, opacity 0.35s;
    filter: blur(0.095rem);
    opacity: 0.5;
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
    @apply bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4 rounded-geist my-6;
}

.callout.danger {
    @apply bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400;
}

.callout.danger::before {
    @apply font-medium;
}

.callout.warning {
    @apply bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-700 dark:text-yellow-400;
}

.callout.warning::before {
    @apply font-medium;
}

.callout.info::before {
    @apply font-medium;
}

.callout.info {
    @apply bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400;
}
</style>