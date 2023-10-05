<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Banner from "./Banner.vue";
import Twitter from "./Twitter.vue";

defineProps({
    navigation: Object,
});
</script>

<template>
    <div
        class="z-10 min-h-screen lg:block hidden max-w-xl"
    >
        <div class="block mx-auto pt-5 pb-4 max-h-screen overflow-y-scroll">
            <nav class="mt-5 flex-1 px-2 flex-col flex space-y-5 mx-auto">
                <div v-for="(section, index) in navigation" :key="index">
                    <h4 class="font-semibold mb-2 text-sm">
                        {{ section.title }}
                    </h4>
                    <div class="flex flex-col border-l">
                        <Link
                            v-for="(link, index) in section.links"
                            :key="index"
                            :class="
                                $page.url === link.href
                                    ? 'text-zinc-500 border-zinc-500 border-l'
                                    : 'text-zinc-500 hover:text-zinc-600 hover:before:block hover:border-gray-400 hover:border-l'
                            "
                            class="w-full pl-3.5 my-1 -ml-[1px] text-sm"
                            :href="link.href"
                        >
                            {{ link.title }}
                        </Link>
                    </div>
                </div>

                <Banner />
                <Twitter />
            </nav>
        </div>
    </div>
</template>

<style type="text/css"></style>
