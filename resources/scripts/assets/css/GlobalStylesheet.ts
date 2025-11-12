import tw from 'twin.macro';
import { createGlobalStyle } from 'styled-components';

export default createGlobalStyle`
    body {
        ${tw`font-sans`};
        color: var(--theme-text, #e5e7eb);
        background: var(--theme-background, #0f172a);
        letter-spacing: 0.015em;
    }

    h1, h2, h3, h4, h5, h6 {
        ${tw`font-medium tracking-normal font-header`};
        /* Titles use the readable accent color (e.g. the green title like "Everest" / "Welcome") */
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

    /* Map common Tailwind "neutral"/"gray" text utilities to the theme text variable so
       static utility classes pick up the current theme text color. This is intentionally
       broad to ensure titles, sidebar items and other static text follow the selected
       theme. If you want more granular control later, we can restrict or opt-out specific
       selectors. */
    /* Map white-like utilities to primary text, lighter neutrals to secondary text, deeper neutrals to muted/inverse */
    .text-white,
    .text-neutral-50,
    .text-neutral-100,
    .text-gray-50,
    .text-gray-100,
    .text-slate-50,
    .text-slate-100,
    .text-zinc-50,
    .text-zinc-100,
    .text-stone-50,
    .text-stone-100 {
        color: var(--theme-text-primary, #ffffff) !important;
    }

    .text-neutral-200,

    .bg-neutral-900,
    .bg-gray-900,
    .bg-slate-900,
    .bg-zinc-900,
    .bg-stone-900 {
    background-color: var(--theme-secondary, #27272a) !important;
    }

    .bg-neutral-800,
    .bg-gray-800,
    .bg-slate-800,
    .bg-zinc-800,
    .bg-stone-800 {
        background-color: var(--theme-secondary, #1f2937) !important;
    }

    .bg-neutral-700,
    .bg-gray-700,
    .bg-slate-700,
    .bg-zinc-700,
    .bg-stone-700 {
        background-color: var(--theme-surface-card, #1e293b) !important;
    }
    .text-neutral-300,
    .text-gray-200,
    .text-gray-300,
    .text-slate-200,
    .text-slate-300,
    .text-zinc-200,
    .text-zinc-300,
    .text-stone-200,
    .text-stone-300 {
        color: var(--theme-text-secondary, #cbd5f5) !important;
    }

    .text-neutral-400,
    .text-neutral-500,
    .text-neutral-600,
    .text-gray-400,
    .text-gray-500,
    .text-gray-600,
    .text-slate-400,
    .text-slate-500,
    .text-slate-600,
    .text-zinc-400,
    .text-zinc-500,
    .text-zinc-600,
    .text-stone-400,
    .text-stone-500,
    .text-stone-600 {
        color: var(--theme-text-muted, #9ca3af) !important;
    }

    .text-neutral-700,
    .text-neutral-800,
    .text-neutral-900,
    .text-gray-700,
    .text-gray-800,
    .text-gray-900,
    .text-slate-700,
    .text-slate-800,
    .text-slate-900,
    .text-zinc-700,
    .text-zinc-800,
    .text-zinc-900,
    .text-stone-700,
    .text-stone-800,
    .text-stone-900,
    .text-black {
        color: var(--theme-text-inverse, #0f172a) !important;
    }
`;
