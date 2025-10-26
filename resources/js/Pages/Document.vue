<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed, onMounted, nextTick } from "vue";
import DocsSidebar from "../components/DocsSidebar.vue";
import Navbar from "../Navbar.vue";
import TableOfContents from "../TableOfContents.vue";

const props = defineProps({
    html: String,
    title: String,
    description: String,
    navigation: Object,
    href: String,
    card: String,
});

// Clean up hash symbols from headings in the HTML
const cleanedHtml = computed(() => {
    if (!props.html) return '';
    // Remove # symbols at the start of headings
    return props.html.replace(/>(\s*)#+ /g, '>$1');
});

// Also clean up after mount
onMounted(() => {
    nextTick(() => {
        const headings = document.querySelectorAll('article h1, article h2, article h3, article h4');
        headings.forEach(heading => {
            if (heading.textContent.startsWith('#')) {
                heading.textContent = heading.textContent.replace(/^#+\s*/, '');
            }
        });

        // Inject JSON-LD structured data
        const existingScript = document.querySelector('script[data-seo="article-ld"]');
        if (existingScript) existingScript.remove();

        const articleSchema = {
            '@context': 'https://schema.org',
            '@type': 'Article',
            'headline': props.title,
            'description': props.description,
            'image': props.card,
            'url': props.href,
            'publisher': {
                '@type': 'Organization',
                'name': 'Sigmie',
                'logo': {
                    '@type': 'ImageObject',
                    'url': 'https://sigmie.com/logo.svg'
                }
            },
            'datePublished': '2024-01-01',
            'dateModified': '2024-01-01'
        };

        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.setAttribute('data-seo', 'article-ld');
        script.textContent = JSON.stringify(articleSchema);
        document.head.appendChild(script);
    });
});
</script>

<template>
    <Head :title="title">
        <!-- Primary Meta Tags -->
        <meta name="title" :content="title" />
        <meta name="description" :content="description" />
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
        <meta name="language" content="en-us" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <link rel="canonical" :href="href" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="href" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="card" />
        <meta property="og:site_name" content="Sigmie Documentation" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" :content="href" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="card" />

    </Head>

    <div class="min-h-screen bg-black">
        <!-- Documentation Sidebar -->
        <DocsSidebar :navigation="navigation" :current-path="$page.url" />

        <Navbar :navigation="navigation" />

        <div class="mx-auto max-w-screen-2xl">
            <div class="flex">
                <!-- Main Content -->
                <main class="min-w-0 flex-1 lg:ml-64 pt-16">
                    <div class="flex">
                        <!-- Article Content -->
                        <article class="flex-1 px-6 pb-12 sm:px-8 lg:px-12 xl:px-16 flex justify-center">
                            <div class="max-w-3xl pt-8 pb-8 w-full">
                                <!-- Title -->
                                <h1 class="text-4xl font-bold tracking-tight text-white mb-8">
                                    {{ title }}
                                </h1>

                                <!-- Content -->
                                <div
                                    v-html="cleanedHtml"
                                    class="prose prose-invert max-w-none
                                           prose-headings:scroll-mt-20 prose-headings:font-semibold
                                           prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4 prose-h2:pb-2 prose-h2:border-b prose-h2:border-gray-800
                                           prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-4
                                           prose-h4:text-lg prose-h4:mt-6 prose-h4:mb-3
                                           prose-p:text-gray-300 prose-p:leading-7
                                           prose-a:text-blue-400 prose-a:no-underline prose-a:font-medium hover:prose-a:underline
                                           prose-code:bg-gray-900 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-sm prose-code:font-normal
                                           prose-pre:bg-gray-950 prose-pre:border prose-pre:border-gray-800
                                           prose-blockquote:border-l-4 prose-blockquote:border-gray-700 prose-blockquote:pl-4 prose-blockquote:italic
                                           prose-ul:my-6 prose-ol:my-6 prose-li:my-2
                                           prose-table:my-8 prose-thead:border-b prose-thead:border-gray-700
                                           prose-tr:border-b prose-tr:border-gray-800
                                           prose-th:text-left prose-th:py-2 prose-th:px-4 prose-th:font-semibold
                                           prose-td:py-2 prose-td:px-4"
                                ></div>
                            </div>
                        </article>

                        <!-- Right Table of Contents -->
                        <aside class="hidden xl:block w-64 flex-shrink-0">
                            <div class="sticky top-24 max-h-[calc(100vh-6rem)] overflow-y-auto pr-8 pb-8">
                                <TableOfContents :html="cleanedHtml" />
                            </div>
                        </aside>
                    </div>
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
    @apply bg-gray-950 border border-gray-800 rounded-geist overflow-x-auto;
}

.prose :where(code):not(:where([class~="not-prose"] *)) {
    @apply text-gray-300 bg-gray-900 rounded-geist-sm px-1.5 py-0.5 text-sm font-mono;
}

pre code {
    @apply bg-transparent p-4;
}

.heading-permalink {
    @apply mr-2 no-underline text-gray-400 hover:text-gray-300 transition-colors;
}

/* No special first-letter styling needed */

.table-of-contents li::marker {
    content: "";
}

.table-of-contents > li > a {
    @apply no-underline font-medium text-gray-100 hover:text-blue-400;
}

.table-of-contents > li > ul > li > a {
    @apply no-underline font-normal text-gray-400 hover:text-gray-100;
}

.table-of-contents > li > ul > li > ul > li > a {
    @apply no-underline text-gray-500 font-normal hover:text-gray-300;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4 {
    @apply text-gray-100 font-semibold;
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
    @apply text-gray-400 leading-7;
}

.prose a {
    @apply text-blue-400 hover:text-blue-300 no-underline hover:underline;
}

.prose strong {
    @apply text-gray-100 font-semibold;
}

.prose ul, .prose ol {
    @apply text-gray-400;
}

.prose li {
    @apply my-2;
}

.prose blockquote {
    @apply border-l-4 border-gray-700 pl-4 italic text-gray-400;
}

.prose table {
    @apply w-full border-collapse;
}

.prose th {
    @apply text-left font-medium text-gray-100 bg-gray-900 p-3 border border-gray-800;
}

.prose td {
    @apply p-3 border border-gray-800 text-gray-400;
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
    @apply bg-gray-900 border border-gray-800 p-4 rounded-geist my-6;
}

.callout.danger {
    @apply bg-red-900/20 border-red-800 text-red-400;
}

.callout.danger::before {
    @apply font-medium;
}

.callout.warning {
    @apply bg-yellow-900/20 border-yellow-800 text-yellow-400;
}

.callout.warning::before {
    @apply font-medium;
}

.callout.info::before {
    @apply font-medium;
}

.callout.info {
    @apply bg-blue-900/20 border-blue-800 text-blue-400;
}
</style>
