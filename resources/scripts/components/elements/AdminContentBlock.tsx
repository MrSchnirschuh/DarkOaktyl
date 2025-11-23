import type { ReactNode } from 'react';
import { useEffect } from 'react';
import tw from 'twin.macro';
import FlashMessageRender from '@/components/FlashMessageRender';

const AdminContentBlock: React.FC<{
    children: ReactNode;
    title?: string;
    showFlashKey?: string;
}> = ({ children, title, showFlashKey }) => {
    useEffect(() => {
        if (!title) return;
        document.title = `Admin | ${title}`;
    }, [title]);

    return (
        <>
            {showFlashKey && <FlashMessageRender byKey={showFlashKey} css={tw`mb-4`} />}
            {children}
            <p
                css={tw`text-center text-theme-muted text-xs mt-4 mb-8 flex flex-wrap gap-1 items-center justify-center`}
            >
                <a
                    rel={'noopener nofollow noreferrer'}
                    href={'https://DarkOak.eu'}
                    target={'_blank'}
                    css={tw`no-underline text-theme-muted hover:text-theme-accent transition-colors`}
                >
                    Powered by DarkOak.eu
                </a>
                <span css={tw`opacity-60`}>|</span>
                <a
                    rel={'noopener nofollow noreferrer'}
                    href={'https://DarkOak.eu'}
                    target={'_blank'}
                    css={tw`no-underline text-theme-muted hover:text-theme-accent transition-colors`}
                >
                    &copy; {new Date().getFullYear()} DarkOak.eu
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
        </>
    );
};

export default AdminContentBlock;
