<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <footer class="mt-0 border-t border-[#eaeaea] bg-white dark:border-white/[0.1] dark:bg-black">
        <div class="mx-auto max-w-site px-6 py-14 sm:px-10">
            <div class="mb-10 grid grid-cols-2 gap-10 md:grid-cols-4">
                <div class="col-span-2 md:col-span-1">
                    <h2 class="mb-4 text-[13px] font-semibold uppercase tracking-wider text-muted-ink dark:text-[#9ca3af]">Sigmie</h2>
                    <p class="text-[14px] leading-relaxed text-charcoal dark:text-gray-400">
                        Fluent Elasticsearch and OpenSearch library for PHP and Laravel.
                    </p>
                    <div class="mt-5 flex items-center gap-3">
                        <img
                            src="https://github.com/nicoorfi.png"
                            alt="Nico Orfanos"
                            class="h-9 w-9 shrink-0 rounded-full border border-light-steel object-cover dark:border-[#242422]"
                            width="36"
                            height="36"
                            loading="lazy"
                        />
                        <div class="min-w-0">
                            <p class="text-[13px] font-medium text-graphite dark:text-[#f5f5f4]">Maintained by Nico Orfanos</p>
                            <p class="text-[12px] text-muted-ink dark:text-[#9ca3af]">
                                <a href="https://github.com/nicoorfi" target="_blank" rel="noopener noreferrer" class="hover:text-orange-ink dark:hover:text-[#ff7b47]">GitHub</a>
                                ·
                                <a href="https://www.linkedin.com/in/nicoorfi/" target="_blank" rel="noopener noreferrer" class="hover:text-orange-ink dark:hover:text-[#ff7b47]">LinkedIn</a>
                                ·
                                <a href="https://x.com/nicoorfi" target="_blank" rel="noopener noreferrer" class="hover:text-orange-ink dark:hover:text-[#ff7b47]">X</a>
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="mb-4 text-[13px] font-semibold uppercase tracking-wider text-muted-ink dark:text-[#9ca3af]">Documentation</h2>
                    <ul class="space-y-2 text-[14px]">
                        <li><Link href="/docs/v2/introduction" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Introduction</Link></li>
                        <li><Link href="/docs/v2/quick-start" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Quick Start</Link></li>
                        <li><Link href="/docs/v2/installation" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Installation</Link></li>
                        <li><Link href="/docs/v2/semantic-search" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Semantic Search</Link></li>
                        <li><Link href="/docs/v2/mappings" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Mappings</Link></li>
                        <li><Link href="/docs/v2/analytics" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Analytics</Link></li>
                        <li><Link href="/docs/v2/mcp" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">MCP Server</Link></li>
                    </ul>
                </div>

                <div>
                    <h2 class="mb-4 text-[13px] font-semibold uppercase tracking-wider text-muted-ink dark:text-[#9ca3af]">Resources</h2>
                    <ul class="space-y-2 text-[14px]">
                        <li><a href="/llms.txt" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">llms.txt</a></li>
                        <li><a href="/llms-full.txt" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">llms-full.txt</a></li>
                        <li><a href="/sitemap.xml" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Sitemap</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="mb-4 text-[13px] font-semibold uppercase tracking-wider text-muted-ink dark:text-[#9ca3af]">Community</h2>
                    <ul class="space-y-2 text-[14px]">
                        <li><a href="https://github.com/sigmie/sigmie" target="_blank" rel="noopener" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">GitHub</a></li>
                        <li><a href="https://packagist.org/packages/sigmie/sigmie" target="_blank" rel="noopener" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Packagist</a></li>
                        <li><a href="https://github.com/sigmie/sigmie/issues" target="_blank" rel="noopener" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Report an issue</a></li>
                        <li><a href="https://github.com/sigmie/sigmie/discussions" target="_blank" rel="noopener" class="text-charcoal transition-colors hover:text-orange-ink dark:text-gray-300 dark:hover:text-[#ff7b47]">Discussions</a></li>
                    </ul>
                </div>
            </div>

            <div class="flex flex-col justify-between gap-2 border-t border-light-steel pt-8 text-[13px] text-muted-ink sm:flex-row dark:border-gray-800 dark:text-[#9ca3af]">
                <p>© {{ new Date().getFullYear() }} Sigmie. MIT licensed.</p>
                <p>Built with PHP, Laravel, Inertia.js and Vue.</p>
            </div>
        </div>
    </footer>
</template>
