import type { ReactNode } from 'react';
import { useEffect } from 'react';
import tw from 'twin.macro';
import ContentContainer from '@elements/ContentContainer';
import FlashMessageRender from '@/components/FlashMessageRender';

export interface PageContentBlockProps {
    children?: ReactNode;

    title?: string;
    header?: boolean;
    description?: string;
    className?: string;
    showFlashKey?: string;
}

function PageContentBlock({ title, header, description, showFlashKey, className, children }: PageContentBlockProps) {
    useEffect(() => {
        if (title) {
            document.title = title;
        }
    }, [title]);

    return (
        <>
            <ContentContainer css={tw`my-4 sm:my-10`} className={className}>
                {showFlashKey && <FlashMessageRender byKey={showFlashKey} css={tw`mb-4`} />}
                {header && (
                    <div className={'text-3xl lg:text-5xl font-bold mt-8 mb-12'}>
                        {title}
                        {description && (
                            <p className={'text-[var(--theme-text-secondary)] font-normal text-sm mt-1'}>
                                {description}
                            </p>
                        )}
                    </div>
                )}
                {children}
            </ContentContainer>

            <ContentContainer css={tw`mb-4`}>
                <p css={tw`text-center text-theme-muted text-xs flex flex-wrap gap-1 justify-center items-center`}>
                    <a
                        rel={'noopener nofollow noreferrer'}
                        href={'https://DarkOak.eu'}
                        target={'_blank'}
                        css={tw`no-underline text-theme-muted hover:text-theme-accent transition-colors`}
                        aria-label={'Powered by DarkOak.eu'}
                    >
                        Powered by DarkOak.eu
                    </a>
                    <span css={tw`opacity-60`}>|</span>
                    <a
                        rel={'noopener nofollow noreferrer'}
                        href={'https://DarkOak.eu'}
                        target={'_blank'}
                        css={tw`no-underline text-theme-muted hover:text-theme-accent transition-colors`}
                        aria-label={'Copyright 2025 DarkOak.eu'}
                    >
                        © 2025 DarkOak.eu
                    </a>
                    <span css={tw`opacity-60`}>|</span>
                    <a
                        href={'/legal/terms-of-service'}
                        css={tw`no-underline text-theme-muted hover:text-theme-accent transition-colors`}
                    >
                        Terms of Service
                    </a>
                    <span css={tw`opacity-60`}>|</span>
                    <a
                        href={'/legal/legal-notice'}
                        css={tw`no-underline text-theme-muted hover:text-theme-accent transition-colors`}
                    >
                        Legal Notice
                    </a>
                </p>
            </ContentContainer>
        </>
    );
}

export default PageContentBlock;
