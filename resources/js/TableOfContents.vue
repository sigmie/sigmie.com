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
    <nav v-if="headings.length > 0" aria-label="On this page">
        <ul class="border-l border-[#eaeaea] text-[13px] dark:border-white/[0.1]">
            <li v-for="heading in headings" :key="heading.id">
                <a
                    :href="`#${heading.id}`"
                    class="-ml-px block border-l py-1 leading-[1.4] transition-colors duration-150"
                    :class="[
                        heading.level === 2 ? 'pl-3' : heading.level === 3 ? 'pl-6' : 'pl-9',
                        activeId === heading.id
                            ? 'border-orange-ink font-medium text-[#171717] dark:border-[#ff7b47] dark:text-[#fafafa]'
                            : 'border-transparent text-[#737373] hover:text-[#171717] dark:text-[#8b8b93] dark:hover:text-white',
                    ]"
                    @click.prevent="scrollToHeading(heading.id)"
                >
                    {{ heading.text }}
                </a>
            </li>
        </ul>
    </nav>
</template>
