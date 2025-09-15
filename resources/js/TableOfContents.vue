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
    <nav class="sticky top-20 max-h-[calc(100vh-5rem)] overflow-y-auto" v-if="headings.length > 0">
        <h5 class="font-semibold text-xs uppercase tracking-wide text-gray-900 dark:text-gray-100 mb-4">
            On this page
        </h5>
        <ul class="space-y-2.5 text-sm">
            <li 
                v-for="heading in headings" 
                :key="heading.id"
                :class="[
                    heading.level === 2 ? '' : heading.level === 3 ? 'ml-4' : 'ml-8',
                ]"
            >
                <a
                    :href="`#${heading.id}`"
                    @click.prevent="scrollToHeading(heading.id)"
                    :class="[
                        activeId === heading.id
                            ? 'text-blue-600 dark:text-blue-400 font-medium'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
                        'block py-1 transition-colors duration-200 border-l-2 pl-4 -ml-px',
                        activeId === heading.id
                            ? 'border-blue-600 dark:border-blue-400'
                            : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'
                    ]"
                >
                    {{ heading.text }}
                </a>
            </li>
        </ul>
    </nav>
</template>