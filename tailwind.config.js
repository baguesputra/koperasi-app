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
            boxShadow: {
                '2xs': '0 1px 1px 0 rgba(15, 23, 42, 0.04)',
                xs: '0 1px 2px 0 rgba(15, 23, 42, 0.06)',
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