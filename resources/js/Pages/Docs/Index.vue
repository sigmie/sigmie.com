<script setup>
import { Head } from "@inertiajs/vue3";
import DocsLayout from "../../Layouts/DocsLayout.vue";

const navigation = [
    { section: 'Getting Started' },
    { name: 'Introduction', path: '/docs' },
    { name: 'Installation', path: '/docs/installation' },
    { name: 'Quick Start', path: '/docs/quick-start' },
    { section: 'Core Concepts' },
    { name: 'Search', path: '/docs/search' },
    { name: 'Filters', path: '/docs/filters' },
    { name: 'Aggregations', path: '/docs/aggregations' },
    { name: 'Sorting', path: '/docs/sorting' },
    { section: 'Advanced' },
    { name: 'Custom Analyzers', path: '/docs/analyzers' },
    { name: 'Boosting', path: '/docs/boosting' },
    { name: 'Performance', path: '/docs/performance' },
];
</script>

<template>
    <Head>
        <title>Documentation - Sigmie</title>
    </Head>

    <DocsLayout :navigation="navigation" :current-path="$page.url">
        <div class="max-w-3xl mx-auto px-6 lg:px-8 py-20 font-sans">
            <p class="text-[13px] uppercase tracking-wider font-semibold text-magic-orange mb-4">Documentation</p>
            <h1 class="text-[40px] sm:text-[64px] leading-[0.9] font-semibold tracking-tight text-graphite dark:text-white text-balance mb-6">
                Welcome to Sigmie
            </h1>
            <p class="text-[17px] leading-[1.5] text-charcoal dark:text-gray-400 mb-12 max-w-2xl">
                A modern, developer-friendly Elasticsearch library. Sigmie handles the complexity so you can focus on search relevance.
            </p>

            <div class="bg-ghostly-gray dark:bg-gray-900 rounded-xl p-8 mb-12">
                <h2 class="text-[20px] font-semibold text-graphite dark:text-white mb-4">Quick example</h2>
                <pre class="bg-graphite text-canvas-white p-5 rounded-xl overflow-x-auto text-[13px] leading-relaxed"><code>$search = $index
    -&gt;newSearch()
    -&gt;semantic()
    -&gt;queryString('romantic comedy')
    -&gt;filters('type:"Movie"')
    -&gt;get();</code></pre>
            </div>

            <h2 class="text-[24px] leading-[1.33] font-semibold tracking-tight text-graphite dark:text-white mb-6">
                Features
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">
                <div class="bg-canvas-white dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-xl p-8 transition-shadow hover:shadow-xl">
                    <h3 class="text-[17px] font-semibold text-graphite dark:text-white mb-2">Semantic search</h3>
                    <p class="text-[14px] text-charcoal dark:text-gray-400 leading-relaxed">Natural language understanding with AI-powered search capabilities.</p>
                </div>
                <div class="bg-canvas-white dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-xl p-8 transition-shadow hover:shadow-xl">
                    <h3 class="text-[17px] font-semibold text-graphite dark:text-white mb-2">Simple API</h3>
                    <p class="text-[14px] text-charcoal dark:text-gray-400 leading-relaxed">A clean, fluent interface that makes complex queries simple.</p>
                </div>
                <div class="bg-canvas-white dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-xl p-8 transition-shadow hover:shadow-xl">
                    <h3 class="text-[17px] font-semibold text-graphite dark:text-white mb-2">Production ready</h3>
                    <p class="text-[14px] text-charcoal dark:text-gray-400 leading-relaxed">Battle-tested in production environments at scale.</p>
                </div>
                <div class="bg-canvas-white dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-xl p-8 transition-shadow hover:shadow-xl">
                    <h3 class="text-[17px] font-semibold text-graphite dark:text-white mb-2">Laravel first</h3>
                    <p class="text-[14px] text-charcoal dark:text-gray-400 leading-relaxed">Built specifically for Laravel with full framework integration.</p>
                </div>
            </div>

            <h2 class="text-[24px] leading-[1.33] font-semibold tracking-tight text-graphite dark:text-white mb-4">
                Getting started
            </h2>

            <p class="text-[15px] text-charcoal dark:text-gray-400 leading-relaxed mb-4">
                Install Sigmie via Composer:
            </p>

            <pre class="bg-graphite text-canvas-white p-5 rounded-xl overflow-x-auto text-[13px] leading-relaxed mb-6"><code>composer require sigmie/sigmie</code></pre>

            <p class="text-[15px] text-charcoal dark:text-gray-400 leading-relaxed">
                Then follow the <a href="/docs/installation" class="text-magic-orange font-medium hover:underline">installation guide</a> to configure your application.
            </p>
        </div>
    </DocsLayout>
</template>
