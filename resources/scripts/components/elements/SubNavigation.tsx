import tw from 'twin.macro';
import { Link } from '@inertiajs/react';
import { SubNavigationProps } from '@/types/components/SubNavigation';

const StyledNavigation = tw.nav`
    bg-[var(--color-headers)]
    border
    border-[rgba(0,0,0,0.05)]
    rounded
    p-2
    shadow-sm
`;

const StyledNavigationLink = tw(Link)`
    inline-flex
    items-center
    px-4
    py-2
    text-sm
    font-medium
    rounded
    text-[var(--color-headers-contrast)]
    hover:bg-[rgba(0,0,0,0.04)]
`;

export default function SubNavigation(props: SubNavigationProps) {
    return (
        <StyledNavigation aria-label="Secondary">
            <ul css={tw`flex flex-wrap gap-2`}>
                {props.links.map(link => (
                    <li key={link.url}>
                        <StyledNavigationLink
                            href={link.url}
                            aria-current={link.active ? 'page' : undefined}
                            css={link.active ? tw`bg-[rgba(0,0,0,0.08)]` : null}
                        >
                            {link.label}
                        </StyledNavigationLink>
                    </li>
                ))}
            </ul>
        </StyledNavigation>
    );
}
