<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Sidebar from '../Sidebar.vue';
import Navbar from '../Navbar.vue';

defineProps({
html: String,
title: String,
description: String,
href: String,
card: String,
navigation: Object,
});
</script>

<template>
    <Head :title="`${title} - Sigmie Blog`">
        <!-- Primary Meta Tags -->
        <meta name="title" :content="`${title} - Sigmie Blog`" />
        <meta name="description" :content="description" />
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
        <meta name="author" content="Sigmie Team" />
        <meta name="language" content="en-us" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <link rel="canonical" :href="href" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="href" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="card" />
        <meta property="og:site_name" content="Sigmie Blog" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" :content="href" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="card" />

        <!-- Structured Data (JSON-LD) -->
        <script type="application/ld+json" v-bind:innerHTML="`{
  \"@context\": \"https://schema.org\",
  \"@type\": \"BlogPosting\",
  \"headline\": \"${title}\",
  \"description\": \"${description}\",
  \"image\": \"${card}\",
  \"url\": \"${href}\",
  \"datePublished\": \"2024-01-01\",
  \"dateModified\": \"2024-01-01\",
  \"author\": {
    \"@type\": \"Organization\",
    \"name\": \"Sigmie\"
  },
  \"publisher\": {
    \"@type\": \"Organization\",
    \"name\": \"Sigmie\",
    \"logo\": {
      \"@type\": \"ImageObject\",
      \"url\": \"https://sigmie.com/logo.svg\"
    }
  }
}`"></script>
    </Head>
    <div class="pt-20">
        <div class="flex flex-col font-display relative">
            <Navbar :navigation="navigation"></Navbar>
            <div class="flex flex-row">
                <Sidebar :navigation="navigation"></Sidebar>
                <div class="mx-auto w-full max-w-3xl md:pl-52 px-2 py-10">
                    <img
                        class="mb-10 rounded-lg shadow border"
                        :src="card"
                        :alt="description"
                    />
                    <main class="prose">
                        <div v-html="html"></div>
                    </main>
                </div>
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
