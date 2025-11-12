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
        ${tw`w-4/5 mx-auto`}
    `};

    ${breakpoint('md')`
        ${tw`p-10`}
    `};

    ${breakpoint('lg')`
        ${tw`w-3/5`}
    `};

    ${breakpoint('xl')`
        ${tw`w-full my-auto`}
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
            <div className={'w-full grid lg:grid-cols-2'}>
                <div className={'lg:w-1/2 lg:mx-auto'}>
                    {title && (
                        <>
                            {/* If a login logo is configured in the theme, show it above the title */}
                            {/* eslint-disable-next-line @typescript-eslint/ban-ts-comment */}
                            {/* @ts-ignore */}
                            {typeof window !== 'undefined' && (
                                // read theme from global store via DOM if available — fallback handled in LoginContainer
                                <img
                                    src={loginLogo}
                                    alt={title}
                                    className={'mx-auto mb-2 max-h-24'}
                                    style={{ display: loginLogo ? 'block' : 'none' }}
                                />
                            )}
                            <h2 css={tw`text-3xl text-center text-neutral-100 font-medium py-4`}>{title}</h2>
                        </>
                    )}
                    <FlashMessageRender css={tw`mb-2 px-1`} />
                    <Form {...props} ref={ref}>
                        <div css={tw`w-full bg-zinc-800/50 shadow-lg rounded-lg p-6 mx-1`}>
                            <div css={tw`flex-1`}>{props.children}</div>
                        </div>
                    </Form>
                    <p css={tw`text-center text-neutral-300 text-xs mt-4`}>
                        &copy; {new Date().getFullYear()}&nbsp;
                        <a
                            rel={'noopener nofollow noreferrer'}
                            href={'https://DarkOak.eu'}
                            target={'_blank'}
                            css={tw`no-underline text-neutral-300 hover:text-green-400 duration-300`}
                        >
                            DarkOak.eu
                        </a>
                    </p>
                </div>
            </div>
        </Container>
    );
});
