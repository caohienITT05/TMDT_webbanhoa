import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                bloom: {
                    rose: '#b94f64',
                    plum: '#2d2728',
                    ink: '#2d2728',
                    muted: '#776e70',
                    canvas: '#fff9f7',
                    blush: '#fff1ef',
                    line: '#eadddd',
                    accent: '#d3a35d',
                    danger: '#b84343',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
