<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Navbar from '../Navbar.vue';
import Footer from '../components/Footer.vue';

const props = defineProps({
    html: String,
    title: String,
    pageHeading: String,
    description: String,
    href: String,
    card: String,
    navigation: Object,
});

const cleanedHtml = computed(() => {
    if (!props.html) return '';
    return props.html
        .replace(/^\s*<h1\b[^>]*>[\s\S]*?<\/h1>\s*/i, '')
        .replace(/<(\/?)h1(\b[^>]*)>/gi, '<$1h2$2>');
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-canvas-white dark:bg-black font-sans text-graphite dark:text-white">
        <Navbar :navigation="navigation" />

        <div class="pt-16">
            <article class="max-w-3xl mx-auto px-6 lg:px-8 py-20">
                <Link
                    href="/blog"
                    class="inline-flex items-center gap-2 text-[13px] font-medium text-subtle-gray hover:text-graphite dark:hover:text-white mb-12 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to blog
                </Link>

                <header class="mb-12">
                    <p class="text-[13px] uppercase tracking-wider font-semibold text-magic-orange mb-4">Sigmie blog</p>
                    <h1 class="text-[40px] sm:text-[64px] leading-[0.9] font-semibold tracking-tight text-graphite dark:text-white text-balance mb-6">
                        {{ pageHeading || title }}
                    </h1>
                    <p v-if="description" class="text-[17px] leading-[1.5] text-charcoal dark:text-gray-400 max-w-2xl">
                        {{ description }}
                    </p>
                </header>

                <img
                    v-if="card"
                    class="mb-16 rounded-xl border border-light-steel dark:border-gray-800 w-full"
                    :src="card"
                    :alt="description"
                />

                <main class="prose prose-neutral dark:prose-invert max-w-none
                            prose-headings:font-semibold prose-headings:tracking-tight
                            prose-headings:text-graphite dark:prose-headings:text-white
                            prose-p:text-charcoal dark:prose-p:text-gray-300 prose-p:leading-[1.7]
                            prose-a:text-magic-orange hover:prose-a:text-graphite prose-a:no-underline
                            prose-strong:text-graphite dark:prose-strong:text-white
                            prose-blockquote:border-l-light-steel prose-blockquote:text-charcoal
                            prose-hr:border-light-steel">
                    <div v-html="cleanedHtml"></div>
                </main>
            </article>
        </div>

        <Footer />
    </div>
</template>

<style>
pre code { display: block; padding: 0.75rem; }
.prose :where(pre):not(:where([class~="not-prose"] *)) { background-color: #0f111a; }
code {
    background-color: #0f111a;
    color: #a6accd !important;
    border-radius: 0.25rem;
    padding: 0.125rem 0.25rem;
    font-weight: 400 !important;
}

.heading-permalink { margin-right: 0.5rem; text-decoration: none; color: var(--color-subtle-gray); }
.table-of-contents li::marker { content: "#"; color: var(--color-subtle-gray); }
.table-of-contents > li > a { text-decoration: none; font-weight: 600; }
.table-of-contents > li > ul > li > a { text-decoration: none; font-weight: 500; }
.table-of-contents > li > ul > li > ul > li > a { text-decoration: none; color: var(--color-charcoal); font-weight: 400; }

.torchlight.has-focus-lines .line:not(.line-focus) {
    transition: filter 0.35s, opacity 0.35s;
    filter: blur(0.095rem);
    opacity: 0.65;
}
.torchlight.has-focus-lines:hover .line:not(.line-focus) {
    filter: blur(0px);
    opacity: 1;
}
.torchlight summary:focus { outline: none; }
.torchlight details > summary::marker,
.torchlight details > summary::-webkit-details-marker { display: none; }
.torchlight details .summary-caret::after { pointer-events: none; }
.torchlight .summary-caret-empty::after,
.torchlight details .summary-caret-middle::after,
.torchlight details .summary-caret-end::after { content: " "; }
.torchlight details[open] .summary-caret-start::after { content: "-"; }
.torchlight details:not([open]) .summary-caret-start::after { content: "+"; }
.torchlight details[open] .summary-hide-when-open { display: none; }
.torchlight details:not([open]) .summary-hide-when-open { display: initial; }

.callout {
    background-color: var(--color-ghostly-gray);
    padding: 1rem;
    border-radius: 12px;
    width: 100%;
    margin: 1.25rem 0;
}
.callout.danger { background-color: rgba(255, 83, 16, 0.15); color: var(--color-magic-orange); }
.callout.warning { background-color: var(--color-graphite); color: var(--color-canvas-white); }
.callout.info { background-color: rgba(0, 152, 241, 0.15); color: var(--color-product-blue); }
.callout.danger::before,
.callout.warning::before,
.callout.info::before { font-weight: 700; }
</style>
