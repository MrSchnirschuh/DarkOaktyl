import tw from 'twin.macro';
import { SiteTheme } from '@/state/theme';
import styled from 'styled-components/macro';

const SubNavigation = styled.div<{ theme: SiteTheme }>`
    ${tw`bg-zinc-800 mt-6 mb-3 rounded-full mx-auto px-3 max-w-5xl lg:w-[fit-content] overflow-x-auto container-snap`};

    & > div {
        ${tw`flex justify-center items-center text-sm mx-auto px-2`};

        & > a,
        & > div {
            ${tw`inline-block py-3 px-4 font-semibold no-underline whitespace-nowrap transition-all duration-300`};
            color: var(--theme-text-secondary);

            &:not(:first-of-type) {
                ${tw`ml-2`};
            }

            &:hover {
                color: var(--theme-text-primary);
                box-shadow: inset 0 2px ${({ theme }) => theme.colors.primary};
            }

            &:active,
            &.active {
                color: var(--theme-text-primary);
                box-shadow: inset 0 2px ${({ theme }) => theme.colors.primary};
            }
        }
    }
`;

export default SubNavigation;
