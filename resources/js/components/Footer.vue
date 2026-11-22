<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <footer class="mt-0 border-t border-light-steel bg-ghostly-gray dark:border-[#262624] dark:bg-black">
        <div class="mx-auto max-w-6xl px-5 py-14 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-10">
                <div>
                    <h2 class="text-[13px] uppercase tracking-wider font-semibold text-subtle-gray mb-4">Sigmie</h2>
                    <p class="text-[14px] text-charcoal dark:text-gray-400 leading-relaxed">
                        A modern Elasticsearch and OpenSearch library for PHP and Laravel.
                    </p>
                </div>

                <div>
                    <h2 class="text-[13px] uppercase tracking-wider font-semibold text-subtle-gray mb-4">Documentation</h2>
                    <ul class="space-y-2 text-[14px]">
                        <li><Link href="/docs/v2/introduction" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Introduction</Link></li>
                        <li><Link href="/docs/v2/quick-start" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Quick Start</Link></li>
                        <li><Link href="/docs/v2/installation" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Installation</Link></li>
                        <li><Link href="/docs/v2/semantic-search" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Semantic Search</Link></li>
                        <li><Link href="/docs/v2/mappings" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Mappings</Link></li>
                        <li><Link href="/docs/v2/analytics" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Analytics</Link></li>
                        <li><Link href="/docs/v2/mcp" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">MCP Server</Link></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-[13px] uppercase tracking-wider font-semibold text-subtle-gray mb-4">Resources</h2>
                    <ul class="space-y-2 text-[14px]">
                        <li><a href="/llms.txt" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">llms.txt</a></li>
                        <li><a href="/llms-full.txt" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">llms-full.txt</a></li>
                        <li><a href="/sitemap.xml" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Sitemap</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-[13px] uppercase tracking-wider font-semibold text-subtle-gray mb-4">Community</h2>
                    <ul class="space-y-2 text-[14px]">
                        <li><a href="https://github.com/sigmie/sigmie" target="_blank" rel="noopener" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">GitHub</a></li>
                        <li><a href="https://packagist.org/packages/sigmie/sigmie" target="_blank" rel="noopener" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Packagist</a></li>
                        <li><a href="https://github.com/sigmie/sigmie/issues" target="_blank" rel="noopener" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Report an issue</a></li>
                        <li><a href="https://github.com/sigmie/sigmie/discussions" target="_blank" rel="noopener" class="text-charcoal hover:text-magic-orange dark:text-gray-300 dark:hover:text-magic-orange transition-colors">Discussions</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-light-steel dark:border-gray-800 flex flex-col sm:flex-row gap-2 justify-between text-[13px] text-subtle-gray">
                <p>© {{ new Date().getFullYear() }} Sigmie. MIT licensed.</p>
                <p>Built with PHP, Laravel, Inertia.js and Vue.</p>
            </div>
        </div>
    </footer>
</template>
