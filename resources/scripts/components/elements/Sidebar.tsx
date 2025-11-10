import tw, { css, styled } from 'twin.macro';

import { withSubComponents } from '@/components/helpers';
import { SiteTheme } from '@/state/theme';
import { useStoreState } from '@/state/hooks';
import React from 'react';

const Icon: React.FC<{ icon: React.ElementType }> = ({ icon: Icon }) => {
    const theme = useStoreState(s => s.theme.data!);
    const accent = theme.colors.accent_primary ?? theme.colors.primary;

    const accentVar = `var(--theme-accent-contrast, ${accent})`;
    return <Icon color={accent} style={{ color: accentVar, stroke: accentVar }} />;
};

const Wrapper = styled.div<{ theme: SiteTheme; $admin?: boolean }>`
    ${tw`w-full flex flex-col px-4`};

    & > a {
        ${tw`w-full flex flex-row items-center cursor-pointer select-none px-4`};
        color: var(--theme-text-secondary);
        height: ${({ $admin }) => ($admin ? '2.5rem' : '4rem')};
        ${tw`transition ease-in-out delay-200 duration-200`};

        & > svg {
            ${tw`h-6 w-6 flex flex-shrink-0`};
        }

        & > span {
            ${tw`font-header font-medium text-lg whitespace-nowrap leading-none ml-3`};
        }

        &:hover {
            color: var(--theme-text-primary);
        }

        &:active,
        &.active {
            ${tw`bg-black/25 rounded-lg`};
            color: var(--theme-accent-text, ${({ theme }) => theme.colors.accent_primary ?? theme.colors.primary});
        }
    }
`;

const Section = styled.div`
    ${tw`h-[18px] font-header font-medium text-xs whitespace-nowrap uppercase ml-4 mb-1 select-none`};
    color: var(--theme-text-secondary);

    &:not(:first-of-type) {
        ${tw`mt-4`};
    }
`;

const User = styled.div`
    ${tw`h-16 w-full flex items-center bg-black/25 justify-center`};
`;

const Sidebar = styled.div<{ $collapsed?: boolean; theme: SiteTheme }>`
    ${tw`hidden md:flex h-screen flex-col items-center flex-shrink-0 overflow-x-hidden ease-linear`};
    ${tw`transition-all duration-500`};
    ${tw`w-[15rem]`};

    background-color: ${({ theme }) => theme.colors.sidebar};

    & > a,
    & > span > a {
        ${tw`h-10 w-full flex flex-row items-center cursor-pointer select-none px-8`};
        color: var(--theme-text-secondary);

        & > svg {
            ${tw`transition-none h-6 w-6 flex flex-shrink-0`};
        }

        & > span {
            ${tw`font-header font-medium text-lg whitespace-nowrap leading-none ml-3`};
        }

        &:hover {
            color: var(--theme-text-primary);
        }

        &.active {
            ${tw`bg-black/25 rounded-lg`};
            color: var(--theme-accent-text, ${({ theme }) => theme.colors.accent_primary ?? theme.colors.primary});
        }
    }

    ${props =>
        props.$collapsed &&
        css`
            ${tw`w-20`};

            ${Section} {
                ${tw`invisible`};
            }

            ${Wrapper} {
                ${tw`px-5`};

                & > a {
                    ${tw`justify-center px-0`};
                }
            }

            & > a {
                ${tw`justify-center px-4`};
            }

            & > a > span,
            ${User} > div,
            ${User} > a,
            ${Wrapper} > a > span {
                ${tw`hidden`};
            }
        `};
`;

const SidebarWithComponents: typeof Sidebar & {
    Section: typeof Section;
    Wrapper: typeof Wrapper;
    User: typeof User;
    Icon: typeof Icon;
} = withSubComponents(Sidebar, { Section, Wrapper, User, Icon });

export default SidebarWithComponents;
