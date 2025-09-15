import { usePage } from '@inertiajs/vue3';
import { watch, onMounted } from 'vue';

export function useSeo(options = {}) {
    const page = usePage();
    
    const updateMeta = () => {
        if (typeof document === 'undefined') return;
        
        // Default values
        const defaults = {
            title: options.title || page.props.title || 'Sigmie - A different Elasticsearch library',
            description: options.description || page.props.description || 'Sigmie is a modern, developer-friendly Elasticsearch library with powerful features for search, analytics, and data management.',
            keywords: options.keywords || page.props.keywords || 'elasticsearch, search, full-text search, analytics, sigmie, php, laravel',
            ogTitle: options.ogTitle || options.title || page.props.ogTitle || page.props.title,
            ogDescription: options.ogDescription || options.description || page.props.ogDescription || page.props.description,
            ogImage: options.ogImage || page.props.ogImage || '/og-image.png',
            ogUrl: options.ogUrl || page.props.ogUrl || window.location.href,
            twitterCard: options.twitterCard || page.props.twitterCard || 'summary_large_image',
            twitterTitle: options.twitterTitle || options.title || page.props.twitterTitle || page.props.title,
            twitterDescription: options.twitterDescription || options.description || page.props.twitterDescription || page.props.description,
            twitterImage: options.twitterImage || options.ogImage || page.props.twitterImage || page.props.ogImage,
            canonical: options.canonical || page.props.canonical || window.location.href,
            robots: options.robots || page.props.robots || 'index, follow',
            author: options.author || page.props.author || 'Sigmie',
        };
        
        // Update or create meta tags
        const setMetaTag = (name, content, property = false) => {
            if (!content) return;
            
            const attr = property ? 'property' : 'name';
            let meta = document.querySelector(`meta[${attr}="${name}"]`);
            
            if (!meta) {
                meta = document.createElement('meta');
                meta.setAttribute(attr, name);
                document.head.appendChild(meta);
            }
            
            meta.setAttribute('content', content);
        };
        
        // Set standard meta tags
        setMetaTag('description', defaults.description);
        setMetaTag('keywords', defaults.keywords);
        setMetaTag('robots', defaults.robots);
        setMetaTag('author', defaults.author);
        
        // Set Open Graph tags
        setMetaTag('og:title', defaults.ogTitle, true);
        setMetaTag('og:description', defaults.ogDescription, true);
        setMetaTag('og:image', defaults.ogImage, true);
        setMetaTag('og:url', defaults.ogUrl, true);
        setMetaTag('og:type', 'website', true);
        setMetaTag('og:site_name', 'Sigmie', true);
        
        // Set Twitter Card tags
        setMetaTag('twitter:card', defaults.twitterCard);
        setMetaTag('twitter:title', defaults.twitterTitle);
        setMetaTag('twitter:description', defaults.twitterDescription);
        setMetaTag('twitter:image', defaults.twitterImage);
        
        // Set canonical URL
        let canonical = document.querySelector('link[rel="canonical"]');
        if (!canonical) {
            canonical = document.createElement('link');
            canonical.setAttribute('rel', 'canonical');
            document.head.appendChild(canonical);
        }
        canonical.setAttribute('href', defaults.canonical);
        
        // Update title
        if (defaults.title) {
            document.title = defaults.title;
        }
    };
    
    // Update meta tags when component mounts
    onMounted(() => {
        updateMeta();
    });
    
    // Watch for changes in page props
    watch(() => page.props, () => {
        updateMeta();
    }, { deep: true });
    
    return {
        updateMeta,
    };
}