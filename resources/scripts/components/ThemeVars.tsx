import { useEffect } from 'react';
import { useStoreState } from '@/state/hooks';
import useFlash from '@/plugins/useFlash';
import { deriveThemeVariables } from '@/theme/deriveThemeVariables';

export default function ThemeVars() {
    const theme = useStoreState(s => s.theme.data);
    const mode = useStoreState(s => s.theme.mode ?? 'dark');
    const { addFlash } = useFlash();

    useEffect(() => {
        if (!theme) return;

        const { cssVariables, logos } = deriveThemeVariables(theme, mode, {
            warnPresetConflict: () =>
                addFlash({
                    key: 'theme:presets',
                    type: 'warning',
                    message:
                        'Multiple presets start at the same time with equal duration — please resolve to avoid conflicts.',
                }),
        });

        Object.entries(cssVariables).forEach(([variable, value]) => {
            try {
                document.documentElement.style.setProperty(variable, value);
            } catch (error) {
                // ignore DOM write failures
            }
        });

        const accentColor = cssVariables['--theme-accent'];
        if (accentColor) {
            const meta = document.querySelector('meta[name="theme-color"][data-theme-color]');
            if (meta) {
                meta.setAttribute('content', accentColor);
            }
        }

        try {
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            (window as any).__themeLoginLogo = logos.login ?? '';
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            (window as any).__themePanelLogo = logos.panel ?? '';
            window.dispatchEvent(new CustomEvent('theme:updated'));
        } catch (error) {
            // window globals may be unavailable during SSR/tests
        }
    }, [theme, mode, addFlash]);

    return null;
}
