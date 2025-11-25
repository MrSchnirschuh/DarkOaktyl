import AdminBox from '@elements/AdminBox';
import { useStoreState } from '@/state/hooks';
import { faDesktop } from '@fortawesome/free-solid-svg-icons';
import { useRef, useCallback, useEffect } from 'react';
import { ensureReadableAccent } from '@/helpers/colorContrast';
import { deriveThemeVariables } from '@/theme/deriveThemeVariables';

export default ({
    reload,
    mode,
    size = 'medium',
    className,
}: {
    reload: boolean;
    mode: 'light' | 'dark';
    size?: 'small' | 'medium' | 'large';
    className?: string;
}) => {
    const theme = useStoreState(s => s.theme.data);
    const colorTokens = (theme?.colors as Record<string, string>) ?? {};
    const primary = colorTokens[`primary_${mode}`] ?? colorTokens.primary ?? '#008000';

    const heightClass = size === 'small' ? 'h-[40vh]' : size === 'large' ? 'h-[80vh]' : 'h-[60vh]';

    const iframeRef = useRef<HTMLIFrameElement | null>(null);

    const applyPreviewVars = useCallback(() => {
        try {
            const iframe = iframeRef.current;
            if (!iframe || !iframe.contentDocument || !theme) return;
            const docEl = iframe.contentDocument.documentElement as HTMLElement;
            const { cssVariables } = deriveThemeVariables(theme, mode);

            Object.entries(cssVariables).forEach(([name, value]) => {
                try {
                    docEl.style.setProperty(name, value);
                } catch (error) {
                    // ignore iframe styling issues
                }
            });

            const accent = cssVariables['--theme-accent'];
            const textPrimary = cssVariables['--theme-text-primary'] ?? '#e5e7eb';
            if (accent) {
                const accentText = ensureReadableAccent(
                    accent,
                    cssVariables['--theme-background'] ?? '#0f172a',
                    textPrimary,
                );
                const accentContrast = ensureReadableAccent(
                    accent,
                    mode === 'light' ? '#ffffff' : '#1f2937',
                    textPrimary,
                );
                try {
                    docEl.style.setProperty('--theme-accent-text', accentText);
                    docEl.style.setProperty('--theme-accent-contrast', accentContrast);
                } catch (error) {
                    // ignore
                }
            }
        } catch (e) {
            // ignore failures when accessing cross-origin iframe
        }
    }, [theme, mode]);

    const onLoad = useCallback(() => {
        // apply preview vars once iframe content has loaded
        applyPreviewVars();
        // schedule a couple retries in case the iframe applies styles after load/rehydration
        setTimeout(() => applyPreviewVars(), 150);
        setTimeout(() => applyPreviewVars(), 500);
    }, [applyPreviewVars]);

    // Re-apply whenever the preview mode or colors change (iframe must be loaded)
    useEffect(() => {
        try {
            const iframe = iframeRef.current;
            if (iframe && iframe.contentDocument && iframe.contentDocument.readyState === 'complete') {
                applyPreviewVars();
                setTimeout(() => applyPreviewVars(), 150);
            }
        } catch (e) {
            // ignore cross-origin or access errors
        }
    }, [mode, theme, applyPreviewVars]);

    const wrapperClass = className ?? 'lg:col-span-2';

    return (
        <AdminBox title={'Preview'} icon={faDesktop} className={wrapperClass}>
            <div
                className={`relative w-full rounded-lg ${heightClass} border-2 transition duration-500 overflow-hidden bg-neutral-950`}
                style={{ borderColor: primary }}
            >
                <iframe
                    ref={iframeRef}
                    src={reload ? 'about:blank' : '/admin'}
                    className={'w-full h-full border-0'}
                    style={{ background: 'transparent' }}
                    onLoad={onLoad}
                    title={'Theme preview'}
                />
                <div
                    className={
                        'pointer-events-none absolute inset-x-0 bottom-0 flex justify-between gap-2 bg-gradient-to-t from-black/60 to-transparent p-3 text-xs'
                    }
                    style={{ color: 'var(--theme-text-secondary, #9ca3af)' }}
                >
                    <span>Interaktiver Panel-Vorschau. Navigationen wirken sich nur auf dieses Fenster aus.</span>
                    <span className={'hidden sm:block'}>
                        Modus: <strong>{mode === 'dark' ? 'Dark' : 'Light'}</strong>
                    </span>
                </div>
            </div>
        </AdminBox>
    );
};
