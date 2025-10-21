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
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
            <div class="prose prose-invert max-w-none">
                <h1 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                    Welcome to Sigmie Documentation
                </h1>

                <p class="text-lg text-gray-400 leading-relaxed mb-8">
                    Sigmie is a modern, developer-friendly Elasticsearch library that handles all complexities,
                    letting you focus solely on search relevance.
                </p>

                <div class="border border-gray-800 rounded-lg p-6 bg-black/50 mb-8">
                    <h2 class="text-xl font-semibold text-white mb-4">Quick Example</h2>
                    <pre class="bg-gray-900 text-gray-300 p-4 rounded-md overflow-x-auto"><code>$search = $index
    ->newSearch()
    ->semantic()
    ->queryString('romantic comedy')
    ->filters('type:"Movie"')
    ->get();</code></pre>
                </div>

                <h2 class="text-2xl font-bold text-white mb-4 mt-12">
                    Features
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <div class="border border-gray-800 rounded-lg p-4 bg-black/50">
                        <h3 class="text-lg font-semibold text-white mb-2">Semantic Search</h3>
                        <p class="text-gray-400 text-sm">Natural language understanding with AI-powered search capabilities.</p>
                    </div>
                    <div class="border border-gray-800 rounded-lg p-4 bg-black/50">
                        <h3 class="text-lg font-semibold text-white mb-2">Simple API</h3>
                        <p class="text-gray-400 text-sm">Clean, fluent interface that makes complex queries simple.</p>
                    </div>
                    <div class="border border-gray-800 rounded-lg p-4 bg-black/50">
                        <h3 class="text-lg font-semibold text-white mb-2">Production Ready</h3>
                        <p class="text-gray-400 text-sm">Battle-tested in production environments at scale.</p>
                    </div>
                    <div class="border border-gray-800 rounded-lg p-4 bg-black/50">
                        <h3 class="text-lg font-semibold text-white mb-2">Laravel First</h3>
                        <p class="text-gray-400 text-sm">Built specifically for Laravel with full framework integration.</p>
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-white mb-4 mt-12">
                    Getting Started
                </h2>

                <p class="text-gray-400 leading-relaxed mb-4">
                    To get started with Sigmie, install it via Composer:
                </p>

                <pre class="bg-gray-900 text-gray-300 p-4 rounded-md overflow-x-auto mb-4"><code>composer require sigmie/sigmie</code></pre>

                <p class="text-gray-400 leading-relaxed">
                    Then follow our <a href="/docs/installation" class="text-blue-400 hover:text-blue-300">installation guide</a> to configure your application.
                </p>
            </div>
        </div>
    </DocsLayout>
</template>
