import tw from 'twin.macro';
import { createGlobalStyle } from 'styled-components';

export default createGlobalStyle`
    body {
        ${tw`font-sans`};
        color: var(--theme-text-primary, #fafafa);
        background: var(--theme-background, #0f172a);
        letter-spacing: 0.015em;
    }

    h1, h2, h3, h4, h5, h6 {
        ${tw`font-medium tracking-normal font-header`};
        /* Titles use the readable accent color (e.g. the green title like "Everest" / "Welcome") */
        color: var(--theme-accent-text, var(--theme-accent, var(--theme-primary, #16a34a)));
    }

    p {
        ${tw`leading-snug font-sans`};
        color: var(--theme-text-primary, #fafafa);
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
    /* Map white-like utilities to primary text, gray/neutrals to secondary text */
    .text-white,
    .text-neutral-50,
    .text-neutral-100,
    .text-neutral-200,
    .text-gray-50,
    .text-gray-100,
    .text-gray-200 {
        color: var(--theme-text-primary, #fafafa) !important;
    }

    .text-neutral-400,
    .text-gray-400 {
        color: var(--theme-text-secondary, #94a3b8) !important;
    }

    .text-neutral-300,
    .text-neutral-500,
    .text-neutral-600,
    .text-gray-300,
    .text-gray-500,
    .text-gray-600 {
        color: var(--theme-text-secondary, #94a3b8) !important;
    }
`;
