import { useEffect } from 'react';
import { useStoreState } from '@/state/hooks';
import useFlash from '@/plugins/useFlash';
import { ensureReadableAccent, accentForeground } from '@/helpers/colorContrast';

export default function ThemeVars() {
    const theme = useStoreState(s => s.theme.data);
    const mode = useStoreState(s => s.theme.mode ?? 'dark');
    const { addFlash } = useFlash();

    useEffect(() => {
        if (!theme) return;
        const colors = (theme.colors as Record<string, string>) ?? {};

        // Check for presets with scheduling/defaults and compute an "effective" color map
        const presetKeys = Object.keys(colors).filter(k => k.startsWith('presets:'));
        const parsedPresets: Array<{ key: string; meta: any }> = [];
        for (const k of presetKeys) {
            try {
                const parsed = JSON.parse((colors as any)[k]);
                parsedPresets.push({ key: k, meta: parsed });
            } catch (e) {
                // ignore malformed legacy presets
            }
        }

        const now = new Date();

        // find active scheduled preset (start <= now < end) or default if none active
        let activePreset: any = null;
        const scheduledPresets = parsedPresets
            .map(p => {
                const s = p.meta?.schedule?.start ? new Date(p.meta.schedule.start) : null;
                const e = p.meta?.schedule?.end ? new Date(p.meta.schedule.end) : null;
                const duration = s && e ? e.getTime() - s.getTime() : null;
                return { key: p.key, meta: p.meta, start: s, end: e, duration };
            })
            .filter(p => p.start && p.start <= now && (!p.end || now < p.end));

        if (scheduledPresets.length > 0) {
            // Choose the preset that started most recently (max start). If multiple have the same start time
            // and the same duration, surface a warning to the user.
            scheduledPresets.sort((a, b) => b.start!.getTime() - a.start!.getTime());
            const latestStart = scheduledPresets[0]!.start!.getTime();
            const sameStart = scheduledPresets.filter(p => p.start!.getTime() === latestStart);
            if (sameStart.length > 1) {
                // If all have equal duration, warn the user about the ambiguity.
                const allEqualDuration = sameStart.every(p => p.duration === sameStart[0]!.duration);
                if (allEqualDuration) {
                    addFlash({
                        key: 'theme:presets',
                        type: 'warning',
                        message:
                            'Multiple presets start at the same time with equal duration — please resolve to avoid conflicts.',
                    });
                }
            }
            activePreset = scheduledPresets[0]!.meta;
        } else {
            // fallback to default
            const def = parsedPresets.find(p => p.meta && p.meta.default);
            if (def) activePreset = def.meta;
        }

        // Build an effective colors map merging preset values (if present) over the stored colors.
        const effectiveColors: Record<string, string> = { ...colors };
        if (activePreset && activePreset.modes) {
            const keysToApply = ['primary', 'accent_primary', 'secondary', 'background'];
            for (const k of keysToApply) {
                const lightVal = activePreset.modes.light?.[k];
                const darkVal = activePreset.modes.dark?.[k];
                if (lightVal) {
                    effectiveColors[k] = lightVal;
                    effectiveColors[`${k}_light`] = lightVal;
                }
                if (darkVal) {
                    effectiveColors[`${k}_dark`] = darkVal;
                }
            }
            // logos/background images per-mode from presets
            const logoPanelLight = activePreset.modes.light?.logo_panel;
            const logoPanelDark = activePreset.modes.dark?.logo_panel;
            const logoLoginLight = activePreset.modes.light?.logo_login;
            const logoLoginDark = activePreset.modes.dark?.logo_login;
            if (logoPanelLight) {
                effectiveColors['logo_panel'] = logoPanelLight;
                effectiveColors['logo_panel_light'] = logoPanelLight;
            }
            if (logoPanelDark) effectiveColors['logo_panel_dark'] = logoPanelDark;
            if (logoLoginLight) {
                effectiveColors['logo_login'] = logoLoginLight;
                effectiveColors['logo_login_light'] = logoLoginLight;
            }
            if (logoLoginDark) effectiveColors['logo_login_dark'] = logoLoginDark;
            const bgImgLight = activePreset.modes.light?.background_image;
            const bgImgDark = activePreset.modes.dark?.background_image;
            if (bgImgLight) {
                effectiveColors['background_image'] = bgImgLight;
                effectiveColors['background_image_light'] = bgImgLight;
            }
            if (bgImgDark) effectiveColors['background_image_dark'] = bgImgDark;
        }

        const set = (name: string, value?: string) => {
            if (typeof value === 'undefined') return;
            try {
                document.documentElement.style.setProperty(name, value);
            } catch (e) {
                // ignore
            }
        };

        // Expose logo URLs on window for places that render outside React tree (login form container)
        try {
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            // prefer per-mode variants when available
            const loginLogo = effectiveColors[`logo_login_${mode}`] ?? effectiveColors['logo_login'] ?? '';
            const panelLogo = effectiveColors[`logo_panel_${mode}`] ?? effectiveColors['logo_panel'] ?? '';
            (window as any).__themeLoginLogo = loginLogo;
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            (window as any).__themePanelLogo = panelLogo;
            // notify any non-react parts (or components that read window globals once) that theme values updated
            try {
                window.dispatchEvent(new CustomEvent('theme:updated'));
            } catch (e) {
                // ignore if CustomEvent is not supported
            }
        } catch (e) {
            // noop
        }

        // Choose text color priority: text_primary_{mode} -> text_primary -> text_{mode} -> text -> primary
        const textPrimary =
            effectiveColors[`text_primary_${mode}`] ??
            effectiveColors[`text_${mode}`] ??
            effectiveColors['text_primary'] ??
            effectiveColors['text'] ??
            effectiveColors[`primary_${mode}`] ??
            effectiveColors['primary'] ??
            '#e5e7eb';
        // Secondary text (greys)
        const textSecondary =
            effectiveColors[`text_secondary_${mode}`] ??
            effectiveColors['text_secondary'] ??
            (mode === 'light' ? '#4b5563' : '#d1d5db');

        const primary = effectiveColors[`primary_${mode}`] ?? effectiveColors['primary'] ?? '#16a34a';
        const secondary = effectiveColors[`secondary_${mode}`] ?? effectiveColors['secondary'] ?? '#27272a';
        const background = effectiveColors[`background_${mode}`] ?? effectiveColors['background'] ?? '#0f172a';
        const headers = effectiveColors[`headers_${mode}`] ?? effectiveColors['headers'] ?? '#111827';
        const sidebar = effectiveColors[`sidebar_${mode}`] ?? effectiveColors['sidebar'] ?? '#0b0f14';
        const accent = effectiveColors[`accent_primary_${mode}`] ?? effectiveColors['accent_primary'] ?? '#16a34a';
        const headingAccent = ensureReadableAccent(accent, background, textPrimary);
        const contrastAccent = ensureReadableAccent(accent, mode === 'light' ? '#ffffff' : '#1f2937', textPrimary);
    const onAccent = accentForeground(accent);

        set('--theme-text-primary', textPrimary);
        set('--theme-text', textPrimary);
        set('--theme-text-secondary', textSecondary);
        set('--theme-primary', primary);
        set('--theme-secondary', secondary);
        set('--theme-background', background);
        set('--theme-headers', headers);
        set('--theme-sidebar', sidebar);
        set('--theme-accent', accent);
        set('--theme-accent-text', headingAccent);
        set('--theme-accent-contrast', contrastAccent);
    set('--theme-on-accent', onAccent);
        // background image per-mode
        const bgImage = effectiveColors[`background_image_${mode}`] ?? effectiveColors['background_image'] ?? '';
        if (bgImage) set('--theme-background-image', `url(${bgImage})`);
    }, [theme, mode]);

    return null;
}
