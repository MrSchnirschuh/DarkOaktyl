import styled, { css } from 'styled-components';
import tw from 'twin.macro';

export interface Props {
    isLight?: boolean;
    hasError?: boolean;
}

const light = css<Props>`
    ${tw`border-neutral-200`};
    background-color: var(--theme-surface-card, #ffffff);
    color: var(--theme-text-primary, #111827);

    &:disabled {
        background-color: var(--theme-surface-card, #f3f4f6);
        border-color: var(--theme-text-muted, #d1d5db);
    }
`;

const checkboxStyle = css<Props>`
    ${tw`bg-neutral-500 cursor-pointer appearance-none inline-block align-middle select-none flex-shrink-0 w-4 h-4 text-primary-400 border border-neutral-300 rounded-sm`};
    color-adjust: exact;
    background-origin: border-box;
    transition: all 75ms linear, box-shadow 25ms linear;

    &:checked {
        ${tw`border-transparent bg-no-repeat bg-center`};
        background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M5.707 7.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4a1 1 0 0 0-1.414-1.414L7 8.586 5.707 7.293z'/%3e%3c/svg%3e");
        background-color: currentColor;
        background-size: 100% 100%;
    }
`;

const inputStyle = css<Props>`
    resize: none;
    ${tw`appearance-none outline-none w-full min-w-0`};
    ${tw`py-2.5 px-3 border-2 rounded text-sm transition-all duration-150`};
    ${tw`border-zinc-700 hover:border-neutral-400 shadow-none`};

    background-color: var(--theme-secondary, #27272a);
    color: var(--theme-text-primary, #e5e7eb);

    & + .input-help {
        ${tw`mt-1 text-xs`};
        color: var(--theme-text-muted, #9ca3af);
    }

    &:required,
    &:invalid {
        ${tw`shadow-none`};
    }

    &:disabled {
        ${tw`opacity-75`};
    }

    &:not(.ignoreReadOnly):read-only {
        border-color: var(--theme-text-muted, #4b5563);
        background-color: var(--theme-secondary, #27272a);
    }

    ${props =>
        props.hasError &&
        css`
            ${tw`border-red-400 hover:border-red-300`};
            color: var(--theme-text-inverse, #fee2e2);

            & + .input-help {
                ${props.isLight ? tw`text-red-500` : tw`text-red-200`};
            }
        `};

    ${props =>
        props.isLight &&
        css`
            ${light};

            & + .input-help {
                color: var(--theme-text-secondary, #4b5563);
            }

            &:not(.ignoreReadOnly):read-only {
                background-color: var(--theme-surface-card, #ffffff);
            }
        `};
`;

const Input = styled.input<Props>`
    &:not([type='checkbox']):not([type='radio']) {
        ${inputStyle};
    }

    &[type='checkbox'],
    &[type='radio'] {
        ${checkboxStyle};

        &[type='radio'] {
            ${tw`rounded-full`};
        }
    }
`;

const Textarea = styled.textarea<Props>`
    ${inputStyle}
`;

export { Textarea };
export default Input;
