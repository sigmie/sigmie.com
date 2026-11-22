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

                <section class="relative overflow-hidden border-t border-[#eaeaea] bg-white py-16 text-center sm:py-20 dark:border-white/[0.1] dark:bg-black">
                    <GridCrosshairs />
                    <div class="vercel-prism-glow pointer-events-none absolute inset-0 opacity-75" aria-hidden="true"></div>

                    <div class="relative mx-auto max-w-2xl px-6">
                        <h2 class="text-balance text-[30px] leading-[1.08] font-semibold tracking-[-0.04em] text-[#171717] sm:text-[42px] dark:text-[#fafafa]">
                            Ready to ship better search?
                        </h2>
                        <p class="mt-3 text-[16px] leading-relaxed text-[#525252] dark:text-[#a1a1aa]">
                            Install via Composer and query your own Elasticsearch or OpenSearch cluster in minutes.
                        </p>

                        <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            <Link
                                href="/docs/v2/quick-start"
                                class="group inline-flex items-center gap-2 rounded-full bg-[#171717] px-5 py-2.5 text-[14px] font-medium text-white transition-colors hover:bg-black dark:bg-[#fafafa] dark:text-[#171717] dark:hover:bg-white"
                            >
                                <span>Quick start</span>
                                <span class="transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true">→</span>
                            </Link>
                            <CopyCommand command="composer require sigmie/sigmie" />
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
