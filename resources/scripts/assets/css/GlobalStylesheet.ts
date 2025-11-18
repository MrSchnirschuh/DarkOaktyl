import tw from 'twin.macro';
import { createGlobalStyle } from 'styled-components';

const escapeClassName = (className: string): string =>
    className.replace(/([ !"#$%&'()*+,./:;<=>?@[\]^`{|}~])/g, '\\$1');

const createTextColorRule = (classNames: string[], cssValue: string): string => {
    const selectors = new Set<string>();

    classNames.forEach(name => {
        const trimmed = name.trim();
        if (!trimmed) return;
        const escaped = escapeClassName(trimmed);
        selectors.add(`.${escaped}`);

        if (trimmed.startsWith('!')) {
            return;
        }

        selectors.add(`.${escapeClassName(`hover:${trimmed}`)}:hover`);
        selectors.add(`.${escapeClassName(`focus:${trimmed}`)}:focus`);
        selectors.add(`.${escapeClassName(`focus-visible:${trimmed}`)}:focus-visible`);
        selectors.add(`.${escapeClassName(`active:${trimmed}`)}:active`);

        selectors.add(`.group:hover .${escapeClassName(`group-hover:${trimmed}`)}`);
        selectors.add(`.group:hover .${escapeClassName(`group-hover:hover:${trimmed}`)}:hover`);
        selectors.add(`.group:focus .${escapeClassName(`group-focus:${trimmed}`)}`);
        selectors.add(`.group:active .${escapeClassName(`group-active:${trimmed}`)}`);

        selectors.add(`.peer:focus ~ .${escapeClassName(`peer-focus:${trimmed}`)}`);
        selectors.add(`.peer:focus ~ .${escapeClassName(`peer-focus:hover:${trimmed}`)}:hover`);
        selectors.add(`.peer:hover ~ .${escapeClassName(`peer-hover:${trimmed}`)}`);
        selectors.add(`.peer:focus-visible ~ .${escapeClassName(`peer-focus-visible:${trimmed}`)}`);
    });

    if (!selectors.size) return '';

    return `${Array.from(selectors).join(',\n    ')} {\n        color: ${cssValue} !important;\n    }\n`;
};

const createBackgroundRule = (classNames: string[], cssValue: string): string => {
    const selectors = new Set<string>();

    classNames.forEach(name => {
        const trimmed = name.trim();
        if (!trimmed) return;
        const escaped = escapeClassName(trimmed);
        selectors.add(`.${escaped}`);

        if (trimmed.startsWith('!')) {
            return;
        }

        selectors.add(`.${escapeClassName(`hover:${trimmed}`)}:hover`);
        selectors.add(`.${escapeClassName(`focus:${trimmed}`)}:focus`);
        selectors.add(`.${escapeClassName(`active:${trimmed}`)}:active`);
        selectors.add(`.group:hover .${escapeClassName(`group-hover:${trimmed}`)}`);
        selectors.add(`.group:focus .${escapeClassName(`group-focus:${trimmed}`)}`);
        selectors.add(`.peer:focus ~ .${escapeClassName(`peer-focus:${trimmed}`)}`);
    });

    if (!selectors.size) return '';

    return `${Array.from(selectors).join(',\n    ')} {\n        background-color: ${cssValue} !important;\n    }\n`;
};

const themePrimaryTextClasses = [
    'text-white',
    'text-theme-primary',
    'text-theme-primary',
    'text-theme-primary',
    'text-theme-primary',
    'text-theme-primary',
    'text-theme-primary',
    'text-zinc-50',
    'text-zinc-100',
    'text-stone-50',
    'text-stone-100',
    '!text-white',
    '!text-theme-primary',
];

const themeSecondaryTextClasses = [
    'text-theme-secondary',
    'text-theme-secondary',
    'text-theme-secondary',
    'text-theme-secondary',
    'text-theme-secondary',
    'text-theme-secondary',
    'text-zinc-200',
    'text-zinc-300',
    'text-stone-200',
    'text-stone-300',
];

const themeMutedTextClasses = [
    'text-theme-muted',
    'text-theme-muted',
    'text-theme-muted',
    'text-theme-muted',
    'text-theme-muted',
    'text-theme-muted',
    'text-theme-muted',
    'text-theme-muted',
    'text-theme-muted',
    'text-zinc-400',
    'text-zinc-500',
    'text-zinc-600',
    'text-stone-400',
    'text-stone-500',
    'text-stone-600',
];

const themeInverseTextClasses = [
    'text-neutral-700',
    'text-neutral-800',
    'text-neutral-900',
    'text-gray-700',
    'text-gray-800',
    'text-gray-900',
    'text-slate-700',
    'text-slate-800',
    'text-slate-900',
    'text-zinc-700',
    'text-zinc-800',
    'text-zinc-900',
    'text-stone-700',
    'text-stone-800',
    'text-stone-900',
    'text-black',
];

const semiTransparentPrimaryClasses = ['text-theme-primary/50'];

const primaryTextRule = createTextColorRule(themePrimaryTextClasses, 'var(--theme-text-primary, #f9fafb)');
const secondaryTextRule = createTextColorRule(themeSecondaryTextClasses, 'var(--theme-text-secondary, #9ca3af)');
const mutedTextRule = createTextColorRule(themeMutedTextClasses, 'var(--theme-text-muted, #6b7280)');
const inverseTextRule = createTextColorRule(themeInverseTextClasses, 'var(--theme-text-inverse, #111827)');
const semiTransparentPrimaryRule = createTextColorRule(
    semiTransparentPrimaryClasses,
    'rgb(var(--theme-text-primary-rgb, 229 231 235) / 0.5)',
);

const secondaryBackgroundClasses = [
    'bg-neutral-900',
    'bg-neutral-950',
    'bg-gray-900',
    'bg-slate-900',
    'bg-zinc-900',
    'bg-stone-900',
];
const secondaryBackgroundRule = createBackgroundRule(secondaryBackgroundClasses, 'var(--theme-secondary, #27272a)');
const secondaryBackgroundAlpha60Rule = createBackgroundRule(
    ['bg-neutral-900/60'],
    'rgb(var(--theme-secondary-rgb, 39 39 42) / 0.6)',
);
const secondaryBackgroundAlpha70Rule = createBackgroundRule(
    ['bg-neutral-900/70'],
    'rgb(var(--theme-secondary-rgb, 39 39 42) / 0.7)',
);

const secondarySurfaceBackgroundClasses = [
    'bg-neutral-800',
    'bg-gray-800',
    'bg-slate-800',
    'bg-zinc-800',
    'bg-stone-800',
];
const secondarySurfaceBackgroundRule = createBackgroundRule(
    secondarySurfaceBackgroundClasses,
    'var(--theme-secondary, #1f2937)',
);

const cardBackgroundClasses = [
    'bg-neutral-700',
    'bg-gray-700',
    'bg-slate-700',
    'bg-zinc-700',
    'bg-stone-700',
];
const cardBackgroundRule = createBackgroundRule(cardBackgroundClasses, 'var(--theme-surface-card, #1e293b)');
const cardBackgroundAlphaRule = createBackgroundRule(
    ['bg-neutral-700/20'],
    'rgb(var(--theme-surface-card-rgb, 30 41 59) / 0.2)',
);

const tertiaryBackgroundClasses = [
    'bg-neutral-600',
    'bg-gray-600',
    'bg-slate-600',
    'bg-zinc-600',
    'bg-stone-600',
];
const tertiaryBackgroundRule = createBackgroundRule(tertiaryBackgroundClasses, 'var(--theme-surface-card, #334155)');
const tertiaryBackgroundAlphaRule = createBackgroundRule(
    ['bg-neutral-600/95'],
    'rgb(var(--theme-surface-card-rgb, 51 65 85) / 0.95)',
);

export default createGlobalStyle`
    body {
        ${tw`font-sans`};
        color: var(--theme-text, #e5e7eb);
        background: var(--theme-background, #0f172a);
        letter-spacing: 0.015em;
    }

    h1, h2, h3, h4, h5, h6 {
        ${tw`font-medium tracking-normal font-header`};
        /* Titles use the readable accent color (e.g. the green title like "DarkOak" / "Welcome") */
    color: var(--theme-accent-text, var(--theme-accent, var(--theme-primary, #008000)));
    }

    p {
        ${tw`leading-snug font-sans`};
        color: var(--theme-text-primary, var(--theme-primary, #e5e7eb));
    }

    form {
        ${tw`m-0`};
    }

    textarea, select, input, button, button:focus, button:focus-visible {
        ${tw`outline-none`};
    }

    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield !important;
    }

    /* Scroll Bar Style */
    ::-webkit-scrollbar {
        background: none;
        width: 16px;
        height: 16px;
    }

    ::-webkit-scrollbar-thumb {
        border: solid 0 rgb(0 0 0 / 0%);
        border-right-width: 4px;
        border-left-width: 4px;
        -webkit-border-radius: 9px 4px;
        -webkit-box-shadow: inset 0 0 0 1px hsl(211, 10%, 53%), inset 0 0 0 4px hsl(209deg 18% 30%);
    }

    ::-webkit-scrollbar-track-piece {
        margin: 4px 0;
    }

    ::-webkit-scrollbar-thumb:horizontal {
        border-right-width: 0;
        border-left-width: 0;
        border-top-width: 4px;
        border-bottom-width: 4px;
        -webkit-border-radius: 4px 9px;
    }

    ::-webkit-scrollbar-corner {
        background: transparent;
    }

    /* Map common Tailwind text utilities to theme-driven CSS variables so that light and dark
       mode palettes remain consistent without hunting for individual class names. */
    ${primaryTextRule}
    ${secondaryTextRule}
    ${mutedTextRule}
    ${inverseTextRule}
    ${semiTransparentPrimaryRule}
    ${secondaryBackgroundRule}
    ${secondarySurfaceBackgroundRule}
    ${cardBackgroundRule}
    ${tertiaryBackgroundRule}
    ${secondaryBackgroundAlpha60Rule}
    ${secondaryBackgroundAlpha70Rule}
    ${cardBackgroundAlphaRule}
    ${tertiaryBackgroundAlphaRule}
`;

