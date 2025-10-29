import { ref, watch, onMounted } from 'vue';

const theme = ref('dark');

export function useTheme() {
    const setTheme = (newTheme) => {
        theme.value = newTheme;
        localStorage.setItem('theme', newTheme);

        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    const toggleTheme = () => {
        setTheme(theme.value === 'dark' ? 'light' : 'dark');
    };

    const initTheme = () => {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');

        setTheme(initialTheme);
    };

    return {
        theme,
        setTheme,
        toggleTheme,
        initTheme,
    };
}
