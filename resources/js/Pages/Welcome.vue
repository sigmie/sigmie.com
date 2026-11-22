<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import CopyCommand from '../components/CopyCommand.vue';
import GridCrosshairs from '../components/GridCrosshairs.vue';
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
        <div class="bg-white dark:bg-black">
            <div class="mx-auto max-w-[1120px] border-x border-[#eaeaea] dark:border-white/[0.1]">
                <RelevanceStudio :initial="initialRelevance" />
                <ImageSearchDemo :initial="initialClip" />
                <PlatformAndMcp :initial-mcp="initialMcp" />

                <section class="relative overflow-hidden border-t border-[#eaeaea] bg-white dark:border-white/[0.1] dark:bg-black">
                    <div aria-hidden="true" class="brand-glow pointer-events-none absolute inset-0 rotate-180"></div>
                    <GridCrosshairs />

                    <div class="relative grid gap-8 px-6 py-20 sm:px-10 sm:py-28 lg:grid-cols-12 lg:items-end">
                        <h2 class="text-balance text-[36px] leading-[1.02] font-semibold tracking-[-0.04em] text-[#171717] sm:text-[52px] lg:col-span-6 dark:text-[#fafafa]">
                            Run it against your own cluster.
                        </h2>
                        <div class="lg:col-span-6">
                            <p class="text-[16px] leading-relaxed text-[#525252] dark:text-[#a1a1aa]">
                                Sigmie supports Elasticsearch 7 to 9 and OpenSearch 2 to 3 on PHP 8.1 and up.
                            </p>
                            <div class="mt-6 flex flex-wrap items-center gap-3">
                                <Link
                                    href="/docs/v2/quick-start"
                                    class="press inline-flex items-center rounded-full bg-[#171717] px-5 whitespace-nowrap py-2.5 text-[14px] font-medium text-white hover:bg-black dark:bg-[#fafafa] dark:text-[#171717] dark:hover:bg-white"
                                >
                                    Read the quick start
                                </Link>
                                <CopyCommand command="composer require sigmie/sigmie" />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
