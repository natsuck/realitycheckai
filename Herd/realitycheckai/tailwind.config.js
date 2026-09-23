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
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                headline: ['Montserrat', 'Inter', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                primary: '#002045',
                'primary-container': '#1a365d',
                accent: {
                    DEFAULT: '#fcb812',
                    hover: '#ffbb1e',
                    soft: '#ffdea7',
                    ink: '#7c5800',
                },
                surface: '#f8f9ff',
                'surface-low': '#eff4ff',
                'surface-high': '#dce9ff',
                ink: '#0b1c30',
                'ink-muted': '#43474e',
                'ink-faint': '#74777f',
            },

            boxShadow: {
                ambient: '0px 4px 12px rgba(26, 54, 93, 0.08)',
                'ambient-lg': '0px 8px 24px rgba(26, 54, 93, 0.12)',
            },
        },
    },

    plugins: [forms],
};
