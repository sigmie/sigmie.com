<script setup>
import { Head, Link } from "@inertiajs/vue3";
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
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
        <meta name="description" content="Latest articles and updates about Sigmie Elasticsearch library." />
        <meta name="keywords" content="sigmie blog, elasticsearch tutorials, search implementation, sigmie updates, elasticsearch best practices, full-text search, semantic search" />
        <meta name="author" content="Sigmie Team" />
        <meta name="language" content="en-us" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />

        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://sigmie.com/blog" />
        <meta property="og:title" content="Blog - Sigmie" />
        <meta property="og:description" content="Latest articles and updates about Sigmie Elasticsearch library." />
        <meta property="og:image" content="https://sigmie.com/og-image.png" />
        <meta property="og:site_name" content="Sigmie" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://sigmie.com/blog" />
        <meta name="twitter:title" content="Blog - Sigmie" />
        <meta name="twitter:description" content="Latest articles and updates about Sigmie Elasticsearch library." />
        <meta name="twitter:image" content="https://sigmie.com/og-image.png" />

        <link rel="canonical" href="https://sigmie.com/blog" />
    </Head>

    <div class="min-h-screen bg-canvas-white dark:bg-black font-sans text-graphite dark:text-white">
        <Navbar :navigation="navigation" />

        <div class="pt-16">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-16">
                <main class="max-w-3xl">
                    <div class="mb-16">
                        <p class="text-[13px] uppercase tracking-wider font-semibold text-magic-orange mb-3">Sigmie blog</p>
                        <h1 class="text-[40px] sm:text-[64px] leading-[0.9] font-semibold text-graphite dark:text-white tracking-tight text-balance">
                            {{ title }}
                        </h1>
                        <p class="mt-6 text-[17px] text-charcoal dark:text-gray-400 leading-[1.5] max-w-2xl">
                            Notes on search, Elasticsearch internals, and what we are building.
                        </p>
                    </div>

                    <div
                        class="space-y-6"
                        v-for="(section, index) in posts"
                        :key="index"
                    >
                        <Link
                            v-for="(link, linkIndex) in section.links"
                            :key="linkIndex"
                            :href="link.href"
                            class="block rounded-xl bg-ghostly-gray dark:bg-gray-900 p-8 transition-shadow hover:shadow-xl group"
                        >
                            <h2 class="text-[24px] leading-[1.33] font-semibold text-graphite dark:text-white tracking-tight group-hover:text-magic-orange transition-colors">
                                {{ link.title }}
                            </h2>
                            <p v-if="link.description" class="mt-3 text-[15px] text-charcoal dark:text-gray-400 leading-[1.5]">
                                {{ link.description }}
                            </p>
                            <span class="mt-4 inline-flex items-center gap-2 text-[13px] font-medium text-magic-orange">
                                Read article
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </span>
                        </Link>
                    </div>
                </main>

                <aside class="hidden lg:block">
                    <div class="sticky top-24">
                        <Banner />
                    </div>
                </aside>
            </div>
        </div>
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
</style>
