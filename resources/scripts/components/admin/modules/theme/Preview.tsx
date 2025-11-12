import AdminBox from '@elements/AdminBox';
import { useStoreState } from '@/state/hooks';
import { faDesktop } from '@fortawesome/free-solid-svg-icons';
import { useRef, useCallback, useEffect } from 'react';
import { ensureReadableAccent } from '@/helpers/colorContrast';

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
    const colors = useStoreState(s => s.theme.data!.colors) as Record<string, string>;
    const primary = colors[`primary_${mode}`] ?? colors.primary ?? '#008000';

    const heightClass = size === 'small' ? 'h-[40vh]' : size === 'large' ? 'h-[80vh]' : 'h-[60vh]';

    const iframeRef = useRef<HTMLIFrameElement | null>(null);

    const applyPreviewVars = useCallback(() => {
        try {
            const iframe = iframeRef.current;
            if (!iframe || !iframe.contentDocument) return;
            const docEl = iframe.contentDocument.documentElement as HTMLElement;

            // derive values similar to ThemeVars but specific to the preview mode
            const effective = { ...(colors as Record<string, string>) } as Record<string, string>;

            const getTextPrimary = () =>
                effective[`text_primary_${mode}`] ??
                effective[`text_${mode}`] ??
                effective['text_primary'] ??
                effective['text'] ??
                effective[`primary_${mode}`] ??
                effective['primary'] ??
                '#e5e7eb';

            const getTextSecondary = () =>
                effective[`text_secondary_${mode}`] ??
                effective['text_secondary'] ??
                (mode === 'light' ? '#4b5563' : '#9ca3af');

            const primaryCol = effective[`primary_${mode}`] ?? effective['primary'] ?? '#008000';
            const secondaryCol = effective[`secondary_${mode}`] ?? effective['secondary'] ?? '#27272a';
            const backgroundCol = effective[`background_${mode}`] ?? effective['background'] ?? '#0f172a';
            const headers = effective[`headers_${mode}`] ?? effective['headers'] ?? '#111827';
            const sidebar = effective[`sidebar_${mode}`] ?? effective['sidebar'] ?? '#0b0f14';
            const accent =
                effective[`accent_primary_${mode}`] ??
                effective['accent_primary'] ??
                effective[`primary_${mode}`] ??
                effective['primary'] ??
                '#008000';
            const accentText = ensureReadableAccent(accent, backgroundCol, getTextPrimary());
            const accentContrast = ensureReadableAccent(
                accent,
                mode === 'light' ? '#ffffff' : '#1f2937',
                getTextPrimary(),
            );

            // set property even when value is empty string (to allow clearing)
            const setVar = (n: string, v?: string) => {
                if (typeof v === 'undefined') return;
                try {
                    docEl.style.setProperty(n, v);
                } catch (e) {
                    // ignore
                }
            };

            const textPrimary = getTextPrimary();
            setVar('--theme-text-primary', textPrimary);
            setVar('--theme-text', textPrimary);
            setVar('--theme-text-secondary', getTextSecondary());
            setVar('--theme-primary', primaryCol);
            setVar('--theme-secondary', secondaryCol);
            setVar('--theme-background', backgroundCol);
            setVar('--theme-headers', headers);
            setVar('--theme-sidebar', sidebar);
            setVar('--theme-accent', accent);
            setVar('--theme-accent-text', accentText);
            setVar('--theme-accent-contrast', accentContrast);
            // background image per-mode
            const bgImage = effective[`background_image_${mode}`] ?? effective['background_image'] ?? '';
            setVar('--theme-background-image', bgImage ? `url(${bgImage})` : '');
        } catch (e) {
            // ignore failures when accessing cross-origin iframe
        }
    }, [colors, mode]);

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
    }, [mode, colors, applyPreviewVars]);

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
                        'pointer-events-none absolute inset-x-0 bottom-0 flex justify-between gap-2 bg-gradient-to-t from-black/60 to-transparent p-3 text-xs text-neutral-200'
                    }
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
