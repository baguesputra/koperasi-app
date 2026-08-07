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
                sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                brand: {
                    navy: {
                        DEFAULT: '#0F1E36',
                        light: '#1A2E4D',
                        dark: '#0A1526',
                    },
                    green: {
                        DEFAULT: '#1FA24C',
                        light: '#E8F8EE',
                        dark: '#178A3E',
                    },
                },
            },
        },
    },
    plugins: [],
};