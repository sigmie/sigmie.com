<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import CodePreview from '../components/CodePreview.vue';
import CopyCommand from '../components/CopyCommand.vue';
import HeroCodeShowcase from '../components/HeroCodeShowcase.vue';
import SemanticSearchDemo from '../components/SemanticSearchDemo.vue';
import ImageSearchDemo from '../components/ImageSearchDemo.vue';
import { CAPABILITIES, MCP_CONFIG_JSON, MCP_TOOLS } from '../data/landingExamples';

defineProps({
    title: String,
    navigation: Object,
});

const install = 'composer require sigmie/sigmie';

const iconPaths = {
    sparkles: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z',
    layers: 'M12 3l9 4.5-9 4.5-9-4.5L12 3zm0 9l9 4.5-9 4.5-9-4.5L12 12z',
    filter: 'M3 4a1 1 0 011-1h16a1 1 0 01.8 1.6L14 13.33V19a1 1 0 01-1.45.89l-2-1A1 1 0 0110 18v-4.67L3.2 4.6A1 1 0 013 4z',
    chart: 'M3 3v18h18M7 15l4-4 3 3 5-6',
    cpu: 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z',
    server: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
};
</script>

<template>
    <Head :title="title" />

    <AppLayout :navigation="navigation">
        <section class="relative overflow-hidden pt-12 pb-16 sm:pt-16 sm:pb-24 lg:pt-20 lg:pb-28">
            <div class="hero-dot-grid pointer-events-none absolute inset-0" aria-hidden="true"></div>

            <div class="relative mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-light-steel bg-canvas-white/90 px-3 py-1 font-mono text-[12px] text-charcoal shadow-2xs dark:border-[#262624] dark:bg-[#111113] dark:text-[#a3a3a0]">
                        <span class="h-1.5 w-1.5 rounded-full bg-magic-green" aria-hidden="true"></span>
                        <span>Open source · MIT · PHP 8.1+</span>
                    </div>

                    <h1 class="mt-5 text-[40px] leading-[1.03] font-semibold tracking-[-0.038em] text-graphite sm:text-[54px] lg:text-[64px] dark:text-[#f5f5f4]">
                        Fluent Elasticsearch and OpenSearch for PHP<span class="text-magic-orange">.</span>
                    </h1>

                    <p class="mt-5 max-w-2xl text-[17px] leading-[1.6] text-charcoal sm:text-[18px] dark:text-[#a3a3a0]">
                        Keyword search, semantic vectors, facets, analytics, and reranking in one typed PHP API. Built for Laravel apps, SaaS catalogs, and AI agents.
                    </p>

                    <div class="mt-7 flex flex-col items-start gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        <Link
                            href="/docs/v2/quick-start"
                            class="group inline-flex items-center gap-2 rounded-full bg-graphite px-5 py-2.5 text-[14px] font-medium text-white shadow-sm transition-colors hover:bg-black dark:bg-[#f5f5f4] dark:text-graphite dark:hover:bg-white"
                        >
                            <span>Read the docs</span>
                            <span class="transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true">→</span>
                        </Link>
                        <CopyCommand :command="install" />
                    </div>

                    <ul class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-2.5 text-[13px] font-medium text-subtle-gray dark:text-[#a1a1aa]">
                        <li class="inline-flex items-center gap-2">
                            <img src="/elasticsearch.png" alt="" class="h-4 w-auto opacity-80 grayscale" width="16" height="16" />
                            <span>Elasticsearch 7–9</span>
                        </li>
                        <li class="inline-flex items-center gap-2">
                            <img src="/opensearch.png" alt="" class="h-4 w-auto opacity-80 grayscale" width="16" height="16" />
                            <span>OpenSearch 2–3</span>
                        </li>
                        <li>
                            <Link href="/docs/v2/laravel-scout" class="transition-colors hover:text-graphite dark:hover:text-white">Laravel Scout</Link>
                        </li>
                        <li>
                            <Link href="/docs/v2/laravel-ai" class="transition-colors hover:text-graphite dark:hover:text-white">Laravel AI SDK</Link>
                        </li>
                        <li>
                            <Link href="/docs/v2/mcp" class="transition-colors hover:text-graphite dark:hover:text-white">Remote MCP</Link>
                        </li>
                    </ul>
                </div>

                <div class="mt-10 sm:mt-12">
                    <HeroCodeShowcase />
                </div>
            </div>
        </section>

        <SemanticSearchDemo />
        <ImageSearchDemo />

        <section id="capabilities" class="scroll-mt-20 border-t border-light-steel bg-ghostly-gray py-20 sm:py-24 lg:py-28 dark:border-white/[0.08] dark:bg-[#09090b]">
            <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <p class="inline-flex items-center gap-2 font-mono text-[12px] font-medium uppercase tracking-[0.08em] text-magic-orange">
                        <span class="h-1.5 w-1.5 rounded-full bg-magic-orange" aria-hidden="true"></span>
                        03 · Capabilities
                    </p>
                    <h2 class="mt-3 text-[28px] leading-[1.12] font-semibold tracking-[-0.03em] text-graphite sm:text-[40px] dark:text-[#f5f5f4]">
                        Built for search relevance, not JSON boilerplate.
                    </h2>
                    <p class="mt-4 text-[16px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                        Describe your schema with semantic field types, then query with a fluent PHP chain. You get the developer ergonomics of a hosted search service on Elasticsearch or OpenSearch clusters you own.
                    </p>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="capability in CAPABILITIES"
                        :key="capability.id"
                        class="group flex flex-col rounded-2xl border border-light-steel bg-canvas-white p-6 transition-all duration-200 hover:-translate-y-0.5 hover:border-graphite/25 hover:shadow-md dark:border-[#262624] dark:bg-[#111113] dark:hover:border-white/20"
                    >
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl border border-light-steel bg-ghostly-gray text-graphite transition-colors group-hover:border-magic-orange/30 group-hover:text-magic-orange dark:border-[#262624] dark:bg-black dark:text-[#f5f5f4]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPaths[capability.icon]" />
                            </svg>
                        </div>

                        <h3 class="mt-4 text-[17px] leading-snug font-semibold text-graphite dark:text-[#f5f5f4]">
                            {{ capability.title }}
                        </h3>
                        <p class="mt-2 flex-1 text-[14px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                            {{ capability.description }}
                        </p>

                        <pre class="no-ligatures mt-5 overflow-x-auto rounded-lg bg-[#0b0d11] px-3 py-2.5 font-mono text-[11.5px] leading-relaxed text-[#e4e4e7] dark:border dark:border-white/[0.08]"><code>{{ capability.snippet }}</code></pre>

                        <Link
                            :href="capability.href"
                            class="mt-4 inline-flex items-center gap-1.5 text-[13px] font-medium text-graphite transition-colors hover:text-magic-orange dark:text-[#f5f5f4] dark:hover:text-magic-orange"
                        >
                            <span>{{ capability.linkLabel }}</span>
                            <span class="transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true">→</span>
                        </Link>
                    </article>
                </div>

                <div class="mt-14 grid gap-8 border-t border-light-steel pt-12 md:grid-cols-3 dark:border-[#262624]">
                    <div>
                        <h3 class="text-[15px] font-semibold text-graphite dark:text-[#f5f5f4]">For SaaS and e-commerce</h3>
                        <p class="mt-2 text-[14px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                            Ship typo-tolerant catalog search, price range filters, faceted counts, and highlighted snippets without writing raw Elasticsearch DSL. Start with the
                            <Link href="/docs/v2/introduction" class="font-medium text-graphite underline decoration-light-steel underline-offset-4 hover:text-magic-orange dark:text-[#f5f5f4]">introduction</Link>.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold text-graphite dark:text-[#f5f5f4]">Own your infrastructure</h3>
                        <p class="mt-2 text-[14px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                            Run Sigmie against self-hosted or managed Elasticsearch and OpenSearch clusters. No per-query vendor pricing, and switching engines is a single connection setting.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold text-graphite dark:text-[#f5f5f4]">Retrieval for PHP AI agents</h3>
                        <p class="mt-2 text-[14px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                            Combine hybrid vector retrieval, cross-encoder reranking, and <Link href="/docs/v2/laravel-ai" class="font-medium text-graphite underline decoration-light-steel underline-offset-4 hover:text-magic-orange dark:text-[#f5f5f4]">Laravel AI SDK</Link> tools while keeping generation in your model client.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="mcp" class="scroll-mt-20 border-t border-light-steel bg-canvas-white py-20 sm:py-24 lg:py-28 dark:border-white/[0.08] dark:bg-black">
            <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
                <div class="grid items-center gap-10 rounded-3xl border border-black/10 bg-[#0b0d11] p-7 text-white shadow-[0_32px_64px_-24px_rgba(0,0,0,0.3)] sm:p-10 lg:grid-cols-12 lg:p-12 dark:border-white/10">
                    <div class="lg:col-span-6">
                        <p class="inline-flex items-center gap-2 rounded-full border border-magic-green/25 bg-magic-green/10 px-3 py-1 font-mono text-[11px] uppercase tracking-[0.08em] text-[#63df9a]">
                            <span class="h-1.5 w-1.5 rounded-full bg-magic-green" aria-hidden="true"></span>
                            Live at sigmie.com/mcp
                        </p>
                        <h2 class="mt-5 text-[28px] leading-[1.12] font-semibold tracking-[-0.03em] text-white sm:text-[36px]">
                            Give your coding agent live Sigmie docs.
                        </h2>
                        <p class="mt-4 text-[15px] leading-relaxed text-[#a1a1aa]">
                            Connect Claude Code, Cursor, or any Streamable HTTP MCP client to search 649 indexed documentation sections while writing your PHP code.
                        </p>

                        <ul class="mt-6 space-y-2.5">
                            <li
                                v-for="tool in MCP_TOOLS"
                                :key="tool.name"
                                class="flex items-baseline gap-3 text-[13px]"
                            >
                                <code class="shrink-0 rounded-md border border-white/10 bg-white/[0.06] px-2 py-0.5 font-mono text-[12px] text-[#ff7b47]">{{ tool.name }}</code>
                                <span class="text-[#d4d4d8]">{{ tool.detail }}</span>
                            </li>
                        </ul>

                        <div class="mt-7 flex flex-wrap items-center gap-5 text-[13px] font-medium">
                            <Link href="/docs/v2/mcp" class="text-white transition-colors hover:text-[#ff7b47]">
                                Read the MCP guide →
                            </Link>
                            <a href="/llms.txt" class="text-[#a1a1aa] transition-colors hover:text-white">llms.txt</a>
                            <a href="/llms-full.txt" class="text-[#a1a1aa] transition-colors hover:text-white">llms-full.txt</a>
                        </div>
                    </div>

                    <div class="min-w-0 lg:col-span-6">
                        <CodePreview
                            :code="MCP_CONFIG_JSON"
                            filename=".mcp.json"
                            language="json"
                        />
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="scroll-mt-20 border-t border-light-steel bg-canvas-white py-20 sm:py-24 dark:border-white/[0.08] dark:bg-black">
            <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 rounded-2xl border border-light-steel bg-ghostly-gray/80 p-6 sm:flex-row sm:items-center sm:p-8 dark:border-[#262624] dark:bg-[#111113]">
                    <img
                        src="https://github.com/nicoorfi.png"
                        alt="Nico Orfanos"
                        class="h-16 w-16 shrink-0 rounded-2xl border border-light-steel object-cover dark:border-[#262624]"
                        width="64"
                        height="64"
                        loading="lazy"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-[11px] uppercase tracking-[0.08em] text-subtle-gray">Maintainer</p>
                        <h2 class="mt-1 text-[20px] font-semibold tracking-tight text-graphite dark:text-[#f5f5f4]">
                            Nico Orfanos
                        </h2>
                        <p class="mt-1 text-[14px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                            Search Relevance Engineer building and maintaining Sigmie in the open under the MIT license.
                        </p>
                        <ul class="mt-3 flex flex-wrap gap-1.5">
                            <li
                                v-for="tag in ['Elasticsearch', 'OpenSearch', 'PHP', 'Python']"
                                :key="tag"
                                class="rounded-full border border-light-steel bg-canvas-white px-2.5 py-0.5 text-[12px] text-charcoal dark:border-[#262624] dark:bg-black/60 dark:text-[#a3a3a0]"
                            >
                                {{ tag }}
                            </li>
                        </ul>
                    </div>

                    <ul class="flex shrink-0 items-center gap-2">
                        <li>
                            <a href="https://github.com/nicoorfi" target="_blank" rel="noopener noreferrer" aria-label="Nico Orfanos on GitHub" class="flex h-10 w-10 items-center justify-center rounded-full border border-light-steel bg-canvas-white text-charcoal transition-colors hover:border-graphite hover:text-graphite dark:border-[#262624] dark:bg-black dark:text-[#a3a3a0] dark:hover:border-white/30 dark:hover:text-white" title="GitHub">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" /></svg>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.linkedin.com/in/nicoorfi/" target="_blank" rel="noopener noreferrer" aria-label="Nico Orfanos on LinkedIn" class="flex h-10 w-10 items-center justify-center rounded-full border border-light-steel bg-canvas-white text-charcoal transition-colors hover:border-graphite hover:text-graphite dark:border-[#262624] dark:bg-black dark:text-[#a3a3a0] dark:hover:border-white/30 dark:hover:text-white" title="LinkedIn">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" /></svg>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.upwork.com/freelancers/~0168612ffb19d75fc6" target="_blank" rel="noopener noreferrer" aria-label="Nico Orfanos on Upwork" class="flex h-10 w-10 items-center justify-center rounded-full border border-light-steel bg-canvas-white text-charcoal transition-colors hover:border-graphite hover:text-graphite dark:border-[#262624] dark:bg-black dark:text-[#a3a3a0] dark:hover:border-white/30 dark:hover:text-white" title="Upwork">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.561 13.158c-1.102 0-2.135-.467-3.074-1.227l.228-1.076.008-.042c.207-1.143.849-3.06 2.839-3.06 1.492 0 2.703 1.212 2.703 2.703 0 1.489-1.211 2.702-2.704 2.702zm0-8.14c-2.539 0-4.51 1.649-5.31 4.366-1.22-1.834-2.148-4.036-2.687-5.892H7.828v7.112c-.002 1.406-1.141 2.546-2.547 2.548-1.405-.002-2.543-1.143-2.545-2.548V3.492H0v7.112c0 2.914 2.37 5.303 5.281 5.303 2.913 0 5.283-2.389 5.283-5.303v-1.19c.529 1.107 1.182 2.229 1.974 3.221l-1.673 7.873h2.797l1.213-5.71c1.063.679 2.285 1.109 3.686 1.109 3 0 5.439-2.452 5.439-5.45 0-3-2.439-5.439-5.439-5.439z" /></svg>
                            </a>
                        </li>
                        <li>
                            <a href="https://x.com/nicoorfi" target="_blank" rel="noopener noreferrer" aria-label="Nico Orfanos on X" class="flex h-10 w-10 items-center justify-center rounded-full border border-light-steel bg-canvas-white text-charcoal transition-colors hover:border-graphite hover:text-graphite dark:border-[#262624] dark:bg-black dark:text-[#a3a3a0] dark:hover:border-white/30 dark:hover:text-white" title="X">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" /></svg>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="mt-20 text-center">
                    <h2 class="text-[30px] leading-[1.12] font-semibold tracking-[-0.03em] text-graphite sm:text-[42px] dark:text-[#f5f5f4]">
                        Start searching in five minutes<span class="text-magic-orange">.</span>
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-[16px] leading-relaxed text-charcoal dark:text-[#a3a3a0]">
                        Install via Composer, connect to Elasticsearch or OpenSearch, and ship your first fluent query today.
                    </p>
                    <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row sm:flex-wrap">
                        <Link
                            href="/docs/v2/quick-start"
                            class="group inline-flex items-center gap-2 rounded-full bg-graphite px-6 py-2.5 text-[14px] font-medium text-white shadow-sm transition-colors hover:bg-black dark:bg-[#f5f5f4] dark:text-graphite dark:hover:bg-white"
                        >
                            <span>Quick Start</span>
                            <span class="transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true">→</span>
                        </Link>
                        <CopyCommand :command="install" />
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
