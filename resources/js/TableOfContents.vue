<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    html: String,
});

const headings = ref([]);
const activeId = ref('');

const extractHeadings = () => {
    if (typeof document === 'undefined') return;
    
    nextTick(() => {
        const article = document.querySelector('article');
        if (!article) return;
        
        const elements = article.querySelectorAll('h2, h3, h4');
        const items = [];
        
        elements.forEach((element) => {
            const id = element.id || element.textContent.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
            if (!element.id) {
                element.id = id;
            }
            
            // Remove leading # symbols from the text
            let cleanText = element.textContent.replace(/^#+\s*/, '');
            
            items.push({
                id,
                text: cleanText,
                level: parseInt(element.tagName.charAt(1)),
            });
        });
        
        headings.value = items;
    });
};

const handleScroll = () => {
    if (typeof document === 'undefined') return;
    
    const scrollPosition = window.scrollY + 100;
    let currentActiveId = '';
    
    for (const heading of headings.value) {
        const element = document.getElementById(heading.id);
        if (element && element.offsetTop <= scrollPosition) {
            currentActiveId = heading.id;
        }
    }
    
    activeId.value = currentActiveId;
};

const scrollToHeading = (id) => {
    const element = document.getElementById(id);
    if (element) {
        const offset = 80; // Account for fixed header
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
};

onMounted(() => {
    extractHeadings();
    handleScroll();
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('scroll', handleScroll);
    }
});
</script>

<template>
    <nav v-if="headings.length > 0" class="font-sans border-l border-light-steel dark:border-gray-800">
        <ul class="space-y-1 text-[13px]">
            <li
                v-for="heading in headings"
                :key="heading.id"
                :class="[
                    heading.level === 2 ? '' : heading.level === 3 ? 'ml-3' : 'ml-6',
                ]"
            >
                <a
                    :href="`#${heading.id}`"
                    @click.prevent="scrollToHeading(heading.id)"
                    :class="[
                        '-ml-px block py-1 pl-4 border-l transition-colors duration-150',
                        activeId === heading.id
                            ? 'text-graphite dark:text-white font-medium border-magic-orange'
                            : 'text-subtle-gray hover:text-graphite dark:text-gray-400 dark:hover:text-gray-200 border-transparent'
                    ]"
                >
                    {{ heading.text }}
                </a>
            </li>
        </ul>
    </nav>
</template>