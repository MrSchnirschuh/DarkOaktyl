import styled from 'styled-components';
import tw from 'twin.macro';

const Label = styled.label<{ isLight?: boolean }>`
    ${tw`block text-sm font-semibold mb-1 sm:mb-2`};
    color: ${({ isLight }) =>
        isLight ? 'var(--theme-text-primary, #111827)' : 'var(--theme-text-primary, #e5e7eb)'};
`;

export default Label;
