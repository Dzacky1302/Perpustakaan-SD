import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(15 23 42 / 0.05), 0 8px 24px -12px rgb(15 23 42 / 0.18)',
                pop: '0 12px 32px -12px rgb(15 23 42 / 0.28)',
            },
            animation: {
                'fade-up': 'fade-up 0.25s ease-out both',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'toast-progress': {
                    '0%': { transform: 'scaleX(1)' },
                    '100%': { transform: 'scaleX(0)' },
                },
            },
        },
    },

    plugins: [forms],
};
