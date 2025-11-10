import styled from 'styled-components';
import tw from 'twin.macro';

const Label = styled.label<{ isLight?: boolean }>`
    ${tw`block text-sm font-semibold mb-1 sm:mb-2`};
    color: ${({ isLight }) => (isLight ? 'var(--theme-text-primary, #1e293b)' : 'var(--theme-text-primary, #fafafa)')};
`;

export default Label;
