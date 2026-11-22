<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import CopyCommand from '../components/CopyCommand.vue';
import RelevanceStudio from '../components/RelevanceStudio.vue';
import ImageSearchDemo from '../components/ImageSearchDemo.vue';
import PlatformAndMcp from '../components/PlatformAndMcp.vue';

defineProps({
    title: String,
    navigation: Object,
    initialRelevance: {
        type: Object,
        default: () => ({}),
    },
    initialClip: {
        type: Object,
        default: () => ({ query: 'Owl', results: [] }),
    },
    initialMcp: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :navigation="navigation">
        <RelevanceStudio :initial="initialRelevance" />
        <ImageSearchDemo :initial="initialClip" />
        <PlatformAndMcp :initial-mcp="initialMcp" />

        <section class="border-t border-light-steel bg-ghostly-gray py-14 sm:py-16 dark:border-[#242422] dark:bg-[#0c0c0e]">
            <div class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-6 px-5 sm:px-6 md:flex-row md:items-center lg:px-8">
                <div>
                    <h2 class="text-[24px] leading-[1.15] font-semibold tracking-[-0.03em] text-graphite sm:text-[30px] dark:text-[#f5f5f4]">
                        Ship fluent search on your own cluster.
                    </h2>
                    <p class="mt-1.5 text-[14.5px] text-charcoal dark:text-[#a3a3a0]">
                        MIT licensed. PHP 8.1+. Works with Elasticsearch 7–9 and OpenSearch 2–3.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Link
                        href="/docs/v2/quick-start"
                        class="group inline-flex items-center gap-1.5 rounded-full bg-graphite px-5 py-2 text-[13.5px] font-medium text-white transition-colors hover:bg-black dark:bg-[#f5f5f4] dark:text-graphite dark:hover:bg-white"
                    >
                        <span>Quick start</span>
                        <span class="transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true">→</span>
                    </Link>
                    <CopyCommand command="composer require sigmie/sigmie" />
                </div>
            </div>
        </section>
    </AppLayout>
</template>
