<script setup>

import { Head, Link } from '@inertiajs/inertia-vue3';
import Banner from './Banner.vue';

defineProps({
navigation: Object,
});
</script>

<template>
    <div
        class="w-[500px] max-h-screen overflow-y-scroll fixed left-0 top-0 pt-20 pb-5 z-10 min-h-screen lg:block hidden"
    >
        <div class="block w-[400px] float-right pt-5 pb-4">
            <nav
                class="mt-5 flex-1 space-y-1 px-2 flex-col flex space-y-5 mx-auto"
            >
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
                                    ? 'text-orange-500 border-orange-500 border-l'
                                    : 'text-slate-500 hover:text-slate-600 hover:before:block hover:border-gray-400 hover:border-l'
                            "
                            class="w-full pl-3.5 my-1 -ml-[1px] text-sm"
                            :href="link.href"
                        >
                            {{ link.title }}
                        </Link>
                    </div>
                </div>

                <Banner />
            </nav>
        </div>
    </div>
</template>

<style type="text/css"></style>
