const colors = require('tailwindcss/colors');
const plugin = require('tailwindcss/plugin');

const withOpacityValue = variable => ({ opacityValue }) => {
    if (opacityValue === undefined) {
        return `rgb(var(${variable}))`;
    }

    return `rgb(var(${variable}) / ${opacityValue})`;
};

module.exports = {
    content: ['./resources/scripts/**/*.{js,ts,tsx}'],
    theme: {
        extend: {
            fontFamily: {
                header: ['"IBM Plex Sans"', '"Roboto"', 'system-ui', 'sans-serif'],
            },
            colors: {
                black: '#09090b',
                slate: colors.slate,
                // "primary" and "neutral" are deprecated, prefer the use of "blue" and "gray"
                // in new code.
                primary: colors.green,
                neutral: colors.slate,
                cyan: colors.cyan,
                zinc: colors.zinc,
                red: {
                    ...colors.red,
                    50: withOpacityValue('--theme-danger-soft-alt-rgb'),
                    100: withOpacityValue('--theme-danger-soft-alt-rgb'),
                    200: withOpacityValue('--theme-danger-soft-rgb'),
                    300: withOpacityValue('--theme-danger-soft-rgb'),
                    400: withOpacityValue('--theme-danger-rgb'),
                    500: withOpacityValue('--theme-danger-rgb'),
                    600: withOpacityValue('--theme-danger-rgb'),
                    700: withOpacityValue('--theme-danger-strong-rgb'),
                    800: withOpacityValue('--theme-danger-strong-rgb'),
                },
                yellow: {
                    ...colors.yellow,
                    100: withOpacityValue('--theme-warning-soft-alt-rgb'),
                    200: withOpacityValue('--theme-warning-soft-rgb'),
                    300: withOpacityValue('--theme-warning-soft-rgb'),
                    400: withOpacityValue('--theme-warning-rgb'),
                    500: withOpacityValue('--theme-warning-rgb'),
                    600: withOpacityValue('--theme-warning-rgb'),
                    800: withOpacityValue('--theme-warning-strong-rgb'),
                    900: withOpacityValue('--theme-warning-strong-rgb'),
                },
                green: {
                    ...colors.green,
                    100: withOpacityValue('--theme-success-soft-alt-rgb'),
                    200: withOpacityValue('--theme-success-soft-rgb'),
                    300: withOpacityValue('--theme-success-soft-rgb'),
                    400: withOpacityValue('--theme-success-rgb'),
                    500: withOpacityValue('--theme-success-rgb'),
                    600: withOpacityValue('--theme-success-rgb'),
                    700: withOpacityValue('--theme-success-strong-rgb'),
                },
            },
            fontSize: {
                '2xs': '0.625rem',
            },
            transitionDuration: {
                250: '250ms',
            },
            backgroundImage: {
                'login': "url('https://images.unsplash.com/photo-1531257114315-24a694751517')",
            },
            borderColor: theme => ({
                default: theme('colors.neutral.400', 'currentColor'),
            }),
        },
    },
    plugins: [
        require('@tailwindcss/line-clamp'),
        require('@tailwindcss/forms')({
            strategy: 'class',
        }),
        plugin(({ addUtilities }) => {
            const textUtilities = {
                '.text-theme-primary': {
                    color: 'var(--theme-text-primary, #e5e7eb)',
                },
                '.text-theme-secondary': {
                    color: 'var(--theme-text-secondary, #9ca3af)',
                },
                '.text-theme-muted': {
                    color: 'var(--theme-text-muted, #6b7280)',
                },
                '.text-theme-inverse': {
                    color: 'var(--theme-text-inverse, #111827)',
                },
                '.text-theme-accent': {
                    color: 'var(--theme-accent, #22c55e)',
                },
                '.text-theme-on-accent': {
                    color: 'var(--theme-on-accent, #f9fafb)',
                },
            };

            const backgroundUtilities = {
                '.bg-theme-background': {
                    backgroundColor: 'var(--theme-background, #0f172a)',
                },
                '.bg-theme-body': {
                    backgroundColor: 'var(--theme-body, #111827)',
                },
                '.bg-theme-surface': {
                    backgroundColor: 'var(--theme-surface-card, #1e293b)',
                },
            };

            const borderUtilities = {
                '.border-theme-primary': {
                    borderColor: 'var(--theme-primary, #22c55e)',
                },
                '.border-theme-muted': {
                    borderColor: 'var(--theme-text-muted, #6b7280)',
                },
            };

            const spinnerUtilities = {
                '.text-theme-spinner-track': {
                    color: 'var(--theme-spinner-track, var(--theme-background, rgba(255, 255, 255, 0.2)))',
                },
                '.text-theme-spinner-foreground': {
                    color: 'var(--theme-spinner-foreground, var(--theme-primary, rgb(255, 255, 255)))',
                },
                '.text-theme-spinner-track-accent': {
                    color: 'var(--theme-spinner-track-accent, hsla(212, 92%, 43%, 0.2))',
                },
                '.text-theme-spinner-foreground-accent': {
                    color: 'var(--theme-spinner-foreground-accent, hsl(212, 92%, 43%))',
                },
            };

            const variants = ['responsive', 'hover', 'focus', 'focus-visible', 'active', 'group-hover', 'group-focus', 'peer-focus'];

            addUtilities(textUtilities, variants);
            addUtilities(backgroundUtilities, variants);
            addUtilities(borderUtilities, variants);
            addUtilities(spinnerUtilities, variants);
        }),
    ],
};
