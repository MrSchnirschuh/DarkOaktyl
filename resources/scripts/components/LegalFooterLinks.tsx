import tw, { styled } from 'twin.macro';

const FooterWrapper = styled.div`
    ${tw`fixed left-0 right-0 bottom-4 flex justify-center px-4 pointer-events-none`};
    z-index: 50;
`;

const FooterNav = styled.nav`
    ${tw`inline-flex flex-wrap items-center gap-3 text-xs font-medium pointer-events-auto`};
    padding: 0.4rem 1rem;
    border-radius: 9999px;
    background-color: rgba(15, 23, 42, 0.8);
    color: rgba(226, 232, 240, 0.85);
    border: 1px solid rgba(148, 163, 184, 0.3);
    backdrop-filter: blur(10px);

    @media (prefers-color-scheme: light) {
        background-color: rgba(248, 250, 252, 0.9);
        color: rgba(71, 85, 105, 0.9);
        border-color: rgba(148, 163, 184, 0.5);
    }

    color: var(--theme-text-muted, currentColor);
    border-color: color-mix(in srgb, var(--theme-secondary, rgba(148, 163, 184, 0.4)) 35%, transparent);
    background-color: color-mix(in srgb, var(--theme-surface-card, rgba(15, 23, 42, 0.8)) 85%, transparent);
`;

const FooterLink = styled.a`
    ${tw`transition-colors duration-150`};
    color: var(--theme-text-muted, currentColor);
    text-decoration: none;

    &:hover,
    &:focus-visible {
        color: var(--theme-accent, #38bdf8);
        outline: none;
    }
`;

const Separator = styled.span`
    opacity: 0.45;
`;

const LegalFooterLinks = () => (
    <FooterWrapper>
        <FooterNav aria-label="Legal links">
            <FooterLink href="https://darkoak.eu" target="_blank" rel="noopener noreferrer">
                Powered by DarkOak.eu
            </FooterLink>
            <Separator aria-hidden="true">|</Separator>
            <FooterLink href="/legal/terms-of-service" rel="noopener">
                Terms of Service
            </FooterLink>
            <Separator aria-hidden="true">|</Separator>
            <FooterLink href="/legal/legal-notice" rel="noopener">
                Legal Notice
            </FooterLink>
        </FooterNav>
    </FooterWrapper>
);

export default LegalFooterLinks;
