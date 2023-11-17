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
    <div class="bg-white">
        <div class="relative isolate px-6 pt-14 lg:px-8">
            <div
                class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
                aria-hidden="true"
            >
                <div
                    class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -tranzinc-x-1/2 rotate-[30deg] bg-gradient-to-tr from-zinc-300 to-zinc-800 opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
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
            <div class="mx-auto max-w-5xl py-32 sm:py-48 lg:py-56">
                <img
                    class="mx-auto h-36 mb-10"
                    src="https://github.com/sigmie/art/blob/main/logo/svg/logo-icon-black.svg?raw=true"
                />
                <div class="text-center">
                    <h1
                        class="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl"
                    >
                        A different Elasticsearch library
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-gray-600">
                        Sigmie library allows you to effortlessly create
                        powerful searches without mastering Elasticsearch. It
                        handles all the complexities, letting you focus solely
                        on relevance.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <Link
                            href="/docs/v0/introduction"
                            class="rounded-full bg-zinc-900 px-5 py-1.5 text-lg text-white shadow-sm hover:bg-zinc-900/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-600"
                            >Get started</Link
                        >
                    </div>
                </div>

                <div class="hidden sm:mb-8 sm:flex sm:justify-center mt-10">
                    <div
                        class="relative rounded-full px-3 py-1 text-sm leading-6 text-gray-600 ring-1 ring-gray-900/10 hover:ring-gray-900/20"
                    >
                        You want a Search as a Service ?
                        <a
                            href="https://sigmie.app"
                            class="font-semibold text-black"
                            ><span
                                class="absolute inset-0"
                                aria-hidden="true"
                            ></span
                            >Try our app<span aria-hidden="true"
                                >&rarr;</span
                            ></a
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style type="text/css"></style>
