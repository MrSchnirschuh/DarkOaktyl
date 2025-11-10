import AdminBox from '@elements/AdminBox';
import { useStoreState } from '@/state/hooks';
import { faDesktop } from '@fortawesome/free-solid-svg-icons';
import { useRef, useCallback, useEffect } from 'react';
import { ensureReadableAccent } from '@/helpers/colorContrast';

export default ({
    reload,
    mode,
    size = 'medium',
}: {
    reload: boolean;
    mode: 'light' | 'dark';
    size?: 'small' | 'medium' | 'large';
}) => {
    const colors = useStoreState(s => s.theme.data!.colors) as Record<string, string>;
    const primary = colors[`primary_${mode}`] ?? colors.primary;
    const textColor =
        colors[`text_primary_${mode}`] ??
        colors[`text_${mode}`] ??
        colors.text_primary ??
        colors.text ??
        primary ??
        '#ffffff';

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
                (mode === 'light' ? '#1e293b' : '#fafafa');

            const getTextSecondary = () =>
                effective[`text_secondary_${mode}`] ??
                effective['text_secondary'] ??
                (mode === 'light' ? '#475569' : '#94a3b8');

            const primaryCol = effective[`primary_${mode}`] ?? effective['primary'] ?? '#16a34a';
            const secondaryCol = effective[`secondary_${mode}`] ?? effective['secondary'] ?? '#27272a';
            const backgroundCol = effective[`background_${mode}`] ?? effective['background'] ?? '#0f172a';
            const headers = effective[`headers_${mode}`] ?? effective['headers'] ?? '#111827';
            const sidebar = effective[`sidebar_${mode}`] ?? effective['sidebar'] ?? '#0b0f14';
            const accent =
                effective[`accent_primary_${mode}`] ??
                effective['accent_primary'] ??
                effective[`primary_${mode}`] ??
                effective['primary'] ??
                '#16a34a';
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

    return (
        <AdminBox title={'Preview'} icon={faDesktop} className={'lg:col-span-2'}>
            <div
                className={`w-full rounded-lg ${heightClass} border-2 transition duration-500 overflow-hidden`}
                style={{ borderColor: primary }}
            >
                <iframe
                    ref={iframeRef}
                    src={reload ? '/null' : '/'}
                    className={'w-full h-full'}
                    style={{ background: 'transparent' }}
                    onLoad={onLoad}
                />
                <div className={'p-4'}>
                    <div className={'rounded p-4'} style={{ background: 'rgba(0,0,0,0.4)' }}>
                        <h3 className={'text-lg font-medium'} style={{ color: textColor }}>
                            Sample Heading
                        </h3>
                        <p className={'text-sm mt-2'} style={{ color: textColor }}>
                            This is a small preview of the text color for the <strong>{mode}</strong> mode.
                        </p>
                    </div>
                </div>
            </div>
        </AdminBox>
    );
};
