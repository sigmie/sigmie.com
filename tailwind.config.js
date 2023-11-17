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
    purge: {
        options: {
            safelist: [
                    'p-4', 'mb-4', 'text-sm', 'rounded-lg',
                    'leading-relaxed',
                    'text-yellow-500',
                    'text-blue-500',
                    'text-red-500',
            ],
        },
    },
    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'orange': {
                    '50': '#fff3f1',
                    '100': '#ffe4df',
                    '200': '#ffcdc5',
                    '300': '#ffab9d',
                    '400': '#ff7a64',
                    '500': '#ff6248',
                    '600': '#ed3415',
                    '700': '#c8280d',
                    '800': '#a5240f',
                    '900': '#882414',
                },
            }
        },
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography'),
    require('flowbite/plugin')
    ],
};
