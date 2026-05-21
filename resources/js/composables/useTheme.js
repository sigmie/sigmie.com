import { ref } from 'vue';

const getInitialTheme = () => {
    if (typeof window === 'undefined') return 'light';
    const saved = localStorage.getItem('theme');
    if (saved === 'dark' || saved === 'light') return saved;
    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const theme = ref(getInitialTheme());

export function useTheme() {
    const setTheme = (newTheme) => {
        theme.value = newTheme;
        if (typeof window === 'undefined') return;
        localStorage.setItem('theme', newTheme);
        document.documentElement.classList.toggle('dark', newTheme === 'dark');
    };

    const toggleTheme = () => {
        setTheme(theme.value === 'dark' ? 'light' : 'dark');
    };

    const initTheme = () => {
        if (typeof window === 'undefined') return;
        // Ensure the html class agrees with the current ref value (covers cases
        // where another tab updated localStorage or the inline init script ran
        // before this module loaded).
        document.documentElement.classList.toggle('dark', theme.value === 'dark');
    };

    return {
        theme,
        setTheme,
        toggleTheme,
        initTheme,
    };
}
