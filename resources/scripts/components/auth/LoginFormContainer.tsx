import { forwardRef, useEffect, useState } from 'react';
import * as React from 'react';
import { Form } from 'formik';
import styled from 'styled-components';
import { breakpoint } from '@/theme';
import FlashMessageRender from '@/components/FlashMessageRender';
import tw from 'twin.macro';

type Props = React.DetailedHTMLProps<React.FormHTMLAttributes<HTMLFormElement>, HTMLFormElement> & {
    title?: string;
};

const Container = styled.div<{ isVisible: boolean }>`
    opacity: ${({ isVisible }) => (isVisible ? 1 : 0)};
    transition: opacity 0.5s ease-in;

    ${breakpoint('sm')`
        ${tw`w-full max-w-md mx-auto`}
    `};

    ${breakpoint('md')`
        ${tw`w-full max-w-lg mx-auto`}
    `};
`;

export default forwardRef<HTMLFormElement, Props>(({ title, ...props }, ref) => {
    const [visible, setVisible] = useState(false);
    const [loginLogo, setLoginLogo] = useState<string | undefined>(
        typeof window !== 'undefined' ? (window as any).__themeLoginLogo : undefined,
    );

    useEffect(() => {
        const timeout = setTimeout(() => setVisible(true), 50);
        return () => clearTimeout(timeout);
    }, []);

    useEffect(() => {
        const handler = () => {
            try {
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                setLoginLogo((window as any).__themeLoginLogo || undefined);
            } catch (e) {
                // noop
            }
        };
        window.addEventListener('theme:updated', handler as EventListener);
        return () => window.removeEventListener('theme:updated', handler as EventListener);
    }, []);

    return (
        <Container isVisible={visible}>
            <div className={'w-full'}>
                <div className={'w-full'}>
                    {title && (
                        <>
                            {/* If a login logo is configured in the theme, show it above the title */}
                            {loginLogo && (
                                <img
                                    src={loginLogo}
                                    alt={title}
                                    className={'mx-auto mb-4 max-h-24'}
                                />
                            )}
                            <h2 css={tw`text-3xl text-center text-theme-primary font-medium py-4`}>{title}</h2>
                        </>
                    )}
                    <FlashMessageRender css={tw`mb-2 px-1`} />
                    <Form {...props} ref={ref}>
                        <div css={tw`w-full bg-theme-surface shadow-lg rounded-lg p-8`}>
                            <div css={tw`flex-1`}>{props.children}</div>
                        </div>
                    </Form>
                    <p css={tw`text-center text-theme-secondary text-xs mt-4`}>
                        &copy; {new Date().getFullYear()}&nbsp;
                        <a
                            rel={'noopener nofollow noreferrer'}
                            href={'https://DarkOak.eu'}
                            target={'_blank'}
                            css={tw`no-underline text-theme-secondary hover:text-green-400 duration-300`}
                        >
                            DarkOak.eu
                        </a>
                    </p>
                </div>
            </div>
        </Container>
    );
});
