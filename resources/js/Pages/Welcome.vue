<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Sidebar from "../Sidebar.vue";
import Navbar from "../Navbar.vue";
import Banner from "../Banner.vue";

defineProps({
    title: String,
    posts: Object,
    navigation: Object,
});
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-black">
        <div class="relative isolate px-6 pt-14 lg:px-8">
            <div
                class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
                aria-hidden="true"
            >
                <div
                    class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-blue-600 to-purple-600 opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
                    style="
                        clip-path: polygon(
                            74.1% 44.1%,
                            100% 61.6%,
                            97.5% 26.9%,
                            85.5% 0.1%,
                            80.7% 2%,
                            72.5% 32.5%,
                            60.2% 62.4%,
                            52.4% 68.1%,
                            47.5% 58.3%,
                            45.2% 34.5%,
                            27.5% 76.7%,
                            0.1% 64.9%,
                            17.9% 100%,
                            27.6% 76.8%,
                            76.1% 97.7%,
                            74.1% 44.1%
                        );
                    "
                ></div>
            </div>
            <div class="mx-auto max-w-6xl py-32 sm:py-48 lg:py-56">
                <div class="flex flex-col items-center">
                    <img
                        class="h-24 mb-12"
                        src="https://github.com/sigmie/art/blob/main/logo/svg/logo-icon-black.svg?raw=true"
                    />
                    <div class="text-center max-w-4xl">
                        <h1
                            class="text-5xl sm:text-7xl font-bold tracking-tight text-gray-900 dark:text-gray-100 leading-tight"
                        >
                            A different<br />
                            <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                Elasticsearch
                            </span>
                            library
                        </h1>
                        <p class="mt-8 text-geist-lg leading-relaxed text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                            Sigmie library allows you to effortlessly create
                            powerful searches without mastering Elasticsearch. It
                            handles all the complexities, letting you focus solely
                            on relevance.
                        </p>
                        <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
                            <Link
                                href="/docs/v1/introduction"
                                class="inline-flex items-center justify-center px-6 py-3 text-geist-base font-medium text-white bg-gray-900 dark:bg-white dark:text-black rounded-geist hover:bg-gray-800 dark:hover:bg-gray-100 transition-all duration-200 shadow-geist hover:shadow-geist-hover"
                            >
                                Get started
                            </Link>
                            <a
                                href="https://github.com/sigmie"
                                target="_blank"
                                class="inline-flex items-center justify-center px-6 py-3 text-geist-base font-medium text-gray-900 dark:text-gray-100 bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-geist hover:bg-gray-50 dark:hover:bg-gray-900 transition-all duration-200"
                            >
                                View on GitHub
                            </a>
                        </div>
                    </div>

                    <div class="mt-16 flex items-center justify-center">
                        <div
                            class="relative px-4 py-2 text-geist-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-geist"
                        >
                            Looking for Search as a Service?
                            <a
                                href="https://sigmie.app"
                                class="ml-1 font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                            >
                                Try our app →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-800">
            <div class="mx-auto max-w-6xl px-6 py-24 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="p-6 bg-gray-50 dark:bg-gray-900 rounded-geist border border-gray-200 dark:border-gray-800">
                        <h3 class="text-geist-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                            Simple API
                        </h3>
                        <p class="text-geist-base text-gray-600 dark:text-gray-400">
                            Intuitive methods that abstract away Elasticsearch complexity while maintaining full power.
                        </p>
                    </div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-900 rounded-geist border border-gray-200 dark:border-gray-800">
                        <h3 class="text-geist-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                            Focus on Relevance
                        </h3>
                        <p class="text-geist-base text-gray-600 dark:text-gray-400">
                            Built-in best practices for search relevance, so you can focus on your product.
                        </p>
                    </div>
                    <div class="p-6 bg-gray-50 dark:bg-gray-900 rounded-geist border border-gray-200 dark:border-gray-800">
                        <h3 class="text-geist-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                            Production Ready
                        </h3>
                        <p class="text-geist-base text-gray-600 dark:text-gray-400">
                            Battle-tested in production environments with comprehensive documentation.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style type="text/css"></style>
