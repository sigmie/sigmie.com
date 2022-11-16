<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3';

defineProps({
html: String,
title: String,
navigation: Object,
});
</script>

<template>
    <div>
        <div class="relative z-40 md:hidden" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>

            <div class="fixed inset-0 z-40 flex">
                <div
                    class="relative flex w-full max-w-xs flex-1 flex-col bg-white"
                >
                    <div class="absolute top-0 right-0 -mr-12 pt-2">
                        <button
                            type="button"
                            class="ml-1 flex h-10 w-10 items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                        >
                            <span class="sr-only">Close sidebar</span>
                            <!-- Heroicon name: outline/x-mark -->
                            <svg
                                class="h-6 w-6 text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="h-0 flex-1 overflow-y-auto pt-5 pb-4">
                        <div class="flex flex-shrink-0 items-center px-4">
                            <img
                                class="h-8 w-auto"
                                src="https://tailwindui.com/img/logos/mark.svg?color=indigo&shade=600"
                                alt="Your Company"
                            />
                        </div>
                        <nav class="mt-5 space-y-1 px-2">
                            <a
                                href="#"
                                class="bg-gray-100 text-gray-900 group flex items-center px-2 py-2 text-base font-medium rounded-md"
                            >
                                Dashboard
                            </a>

                            <a
                                href="#"
                                class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-base font-medium rounded-md"
                            >
                                <!-- Heroicon name: outline/users -->
                                <svg
                                    class="text-gray-400 group-hover:text-gray-500 mr-4 flex-shrink-0 h-6 w-6"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
                                    />
                                </svg>
                                Team
                            </a>
                        </nav>
                    </div>
                    <div
                        class="flex flex-shrink-0 border-t border-gray-200 p-4"
                    >
                        <a href="#" class="group block flex-shrink-0">
                            <div class="flex items-center">
                                <div>
                                    <img
                                        class="inline-block h-10 w-10 rounded-full"
                                        src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                        alt=""
                                    />
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="w-14 flex-shrink-0"></div>
            </div>
        </div>

        <div class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col">
            <div
                class="flex min-h-0 flex-1 flex-col border-r border-gray-200 bg-white"
            >
                <div class="flex flex-1 flex-col overflow-y-auto pt-5 pb-4">
                    <div class="flex flex-shrink-0 items-center px-4">
                        <img
                            class="h-8 w-auto"
                            src="https://tailwindui.com/img/logos/mark.svg?color=indigo&shade=600"
                            alt="Your Company"
                        />
                    </div>
                    <nav
                        v-for="(section, index) in navigation"
                        :key="index"
                        class="mt-5 flex-1 space-y-1 bg-white px-2"
                    >
                        <h4>{{ section.title }}</h4>
                        <div
                            v-for="(link, index) in section.links"
                            :key="index"
                        >
                            <Link
                                :class="
                                    $page.url === link.href
                                        ? 'bg-gray-100 text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md'
                                "
                                :href="link.href"
                            >
                                {{ link.title }}
                            </Link>
                        </div>
                    </nav>
                </div>
                <div class="flex flex-shrink-0 border-t border-gray-200 p-4">
                    <a href="#" class="group block w-full flex-shrink-0">
                        <div class="flex items-center">Bottom</div>
                    </a>
                </div>
            </div>
        </div>
        <div class="flex flex-1 flex-col md:pl-64">
            <div
                class="sticky top-0 z-10 bg-gray-100 pl-1 pt-1 sm:pl-3 sm:pt-3 md:hidden"
            >
                <button
                    type="button"
                    class="-ml-0.5 -mt-0.5 inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                >
                    <span class="sr-only">Open sidebar</span>
                    <!-- Heroicon name: outline/bars-3 -->
                    <svg
                        class="h-6 w-6"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                        />
                    </svg>
                </button>
            </div>
            <main class="prose mx-auto w-full max-w-5xl py-10">
                <h1>{{ title }}</h1>
                <div v-html="html"></div>
            </main>
        </div>
    </div>
</template>

<style type="text/css">
pre code {
    @apply block p-3;
}
.prose :where(pre):not(:where([class~="not-prose"] *)) {
    background-color: #292d3e;
}
</style>
