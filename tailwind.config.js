const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./node_modules/flowbite/**/*.js",
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './app/Services/Documentation.php'
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', ...defaultTheme.fontFamily.sans],
                mono: ['SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', 'Fira Mono', 'Droid Sans Mono', 'Courier New', 'monospace'],
            },
            colors: {
                gray: {
                    50: '#fafafa',
                    100: '#f5f5f5',
                    200: '#e5e5e5',
                    300: '#d4d4d4',
                    400: '#a3a3a3',
                    500: '#737373',
                    600: '#525252',
                    700: '#404040',
                    800: '#262626',
                    900: '#171717',
                    950: '#0a0a0a',
                },
                blue: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#0070f3',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                },
                geist: {
                    background: '#ffffff',
                    foreground: '#000000',
                    'background-dark': '#000000',
                    'foreground-dark': '#ffffff',
                    accent: '#0070f3',
                    'accent-light': '#3291ff',
                    success: '#0070f3',
                    error: '#ee0000',
                    warning: '#f5a623',
                    violet: '#7928ca',
                    cyan: '#79ffe1',
                },
            },
            borderRadius: {
                'geist-sm': '6px',
                'geist': '12px',
                'geist-lg': '16px',
            },
            boxShadow: {
                'geist-sm': '0 0 0 1px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04)',
                'geist': '0 0 0 1px rgba(0,0,0,.08), 0 2px 4px rgba(0,0,0,.04), 0 2px 8px rgba(0,0,0,.04)',
                'geist-md': '0 0 0 1px rgba(0,0,0,.08), 0 2px 8px rgba(0,0,0,.04), 0 4px 16px rgba(0,0,0,.04)',
                'geist-lg': '0 0 0 1px rgba(0,0,0,.08), 0 2px 8px rgba(0,0,0,.04), 0 8px 32px rgba(0,0,0,.12)',
                'geist-hover': '0 0 0 1px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.08), 0 8px 32px rgba(0,0,0,.12)',
            },
            animation: {
                'fade-in': 'fadeIn 200ms ease',
                'fade-out': 'fadeOut 200ms ease',
                'slide-in': 'slideIn 200ms ease',
                'slide-out': 'slideOut 200ms ease',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                fadeOut: {
                    '0%': { opacity: '1' },
                    '100%': { opacity: '0' },
                },
                slideIn: {
                    '0%': { transform: 'translateY(-10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                slideOut: {
                    '0%': { transform: 'translateY(0)', opacity: '1' },
                    '100%': { transform: 'translateY(-10px)', opacity: '0' },
                },
            },
            fontSize: {
                'geist-xs': ['12px', '16px'],
                'geist-sm': ['14px', '20px'],
                'geist-base': ['16px', '24px'],
                'geist-lg': ['18px', '28px'],
                'geist-xl': ['20px', '28px'],
                'geist-2xl': ['24px', '32px'],
                'geist-3xl': ['30px', '36px'],
                'geist-4xl': ['36px', '40px'],
            },
        },
    },
    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography'),
    require('flowbite/plugin')
    ],
};
