<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3';

defineProps({
html: String,
title: String,
navigation: Object,
});
</script>

<template>
    <div class="flex flex-row">
        <div class="w-[400px] bg-slate-100 min-h-screen lg:block hidden">
            <div class="block w-[300px] float-right pt-5 pb-4">
                <Link class="flex flex-shrink-0 items-center px-4 mb-16 mt-10">
                    <img
                        class="h-12 w-auto"
                        src="https://res.cloudinary.com/markos-nikolaos-orfanos/image/upload/v1668604010/logo-text_unvbgf.svg"
                        alt="Sigmie"
                    />
                </Link>
                <nav class="mt-5 flex-1 space-y-1 px-2 flex-col flex space-y-5">
                    <div v-for="(section, index) in navigation" :key="index">
                        <h4 class="font-semibold mb-1">
                            {{ section.title }}
                        </h4>
                        <div class="flex flex-col space-y-2">
                            <Link
                                v-for="(link, index) in section.links"
                                :key="index"
                                :class="
                                    $page.url === link.href
                                        ? 'text-orange-500'
                                        : 'text-slate-500 hover:text-slate-600 hover:before:block'
                                "
                                class="block w-full pl-3.5 before:pointer-events-none before:absolute before:-left-1 before:top-1/2 before:h-1.5 before:w-1.5 before:-translate-y-1/2 before:rounded-full before:hidden before:bg-slate-300"
                                :href="link.href"
                            >
                                {{ link.title }}
                            </Link>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
        <main class="prose mx-auto w-full max-w-2xl py-10">
            <h1>{{ title }}</h1>
            <div v-html="html"></div>
        </main>
    </div>
</template>

<style type="text/css">
pre code {
    @apply block p-3;
}
.prose :where(pre):not(:where([class~="not-prose"] *)) {
    background-color: #292d3e;
}
/*
  Blur and dim the lines that don't have the `.line-focus` class,
  but are within a code block that contains any focus lines.
*/
.torchlight.has-focus-lines .line:not(.line-focus) {
    transition: filter 0.35s, opacity 0.35s;
    filter: blur(0.095rem);
    opacity: 0.65;
}

/*
  When the code block is hovered, bring all the lines into focus.
*/
.torchlight.has-focus-lines:hover .line:not(.line-focus) {
    filter: blur(0px);
    opacity: 1;
}
.torchlight summary:focus {
    outline: none;
}

/* Hide the default markers, as we provide our own */
.torchlight details > summary::marker,
.torchlight details > summary::-webkit-details-marker {
    display: none;
}

.torchlight details .summary-caret::after {
    pointer-events: none;
}

/* Add spaces to keep everything aligned */
.torchlight .summary-caret-empty::after,
.torchlight details .summary-caret-middle::after,
.torchlight details .summary-caret-end::after {
    content: " ";
}

/* Show a minus sign when the block is open. */
.torchlight details[open] .summary-caret-start::after {
    content: "-";
}

/* And a plus sign when the block is closed. */
.torchlight details:not([open]) .summary-caret-start::after {
    content: "+";
}

/* Hide the [...] indicator when open. */
.torchlight details[open] .summary-hide-when-open {
    display: none;
}

/* Show the [...] indicator when closed. */
.torchlight details:not([open]) .summary-hide-when-open {
    display: initial;
}
</style>
