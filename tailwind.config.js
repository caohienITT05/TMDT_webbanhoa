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
                    rose: '#9f3657',
                    plum: '#2f1d28',
                    ink: '#352932',
                    muted: '#766a70',
                    canvas: '#fcfaf8',
                    blush: '#f7eeed',
                    line: '#eadfdb',
                    danger: '#b23e4c',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
