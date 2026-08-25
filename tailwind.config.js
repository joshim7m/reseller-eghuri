import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#82514e',
                    container: '#e3a6a1',
                },
                'on-primary-container': '#673a37',
                charcoal: '#2D2D2D',
                ivory: '#FFFFFF',
                'sale-price': '#D97B73',
                'muted-gold': '#C5A059',
                surface: {
                    DEFAULT: '#fff8f7',
                    dim: '#e2d8d6',
                    bright: '#fff8f7',
                    variant: '#ebe0df',
                    container: {
                        DEFAULT: '#f1f1f1',
                        low: '#f4f4f4',
                        high: '#ebebeb',
                        highest: '#e0e0e0',
                        lowest: '#ffffff',
                    },
                },
                'on-surface': {
                    DEFAULT: '#1f1a1a',
                    variant: '#514442',
                },
                outline: {
                    DEFAULT: '#847372',
                    variant: '#d6c2c0',
                },
            },
            spacing: {
                'margin-mobile': '16px',
                'margin-desktop': '64px',
                gutter: '24px',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                inter: ['Inter', ...defaultTheme.fontFamily.sans],
                bengali: ['Noto Sans Bengali', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
