import { useEffect } from 'react';
import { useStoreState } from '@/state/hooks';
import useFlash from '@/plugins/useFlash';
import { ensureReadableText, accentForeground, mixColors, colorToRgbString } from '@/helpers/colorContrast';

export default function ThemeVars() {
    const theme = useStoreState(s => s.theme.data);
    const mode = useStoreState(s => s.theme.mode ?? 'dark');
    const { addFlash } = useFlash();

    useEffect(() => {
        if (!theme) return;
        const colors = (theme.colors as Record<string, string>) ?? {};
        const palette = theme.palettes?.[mode];
        const textPalette = theme.textPalettes?.[mode];
        const surfacePalette = theme.surfacePalettes?.[mode];

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
        if (palette) {
            Object.entries(palette).forEach(([key, value]) => {
                effectiveColors[key] = value;
                if (key === 'button_text') {
                    effectiveColors.buttonText = value;
                }
                if (key === 'text_primary') {
                    effectiveColors.text = value;
                }
                if (key === 'muted_text') {
                    effectiveColors.muted = value;
                }
            });
        }
        if (textPalette) {
            effectiveColors.text_primary = textPalette.primary;
            effectiveColors.text = textPalette.primary;
            effectiveColors.text_secondary = textPalette.secondary;
            effectiveColors.muted_text = textPalette.muted;
            effectiveColors.muted = textPalette.muted;
            effectiveColors.text_inverse = textPalette.inverse;
            effectiveColors.text_on_accent = textPalette.on_accent;
        }
        if (surfacePalette) {
            effectiveColors.surface_background = surfacePalette.background;
            effectiveColors.surface_body = surfacePalette.body;
            effectiveColors.surface_headers = surfacePalette.headers;
            effectiveColors.surface_sidebar = surfacePalette.sidebar;
            effectiveColors.surface_card = surfacePalette.card;
        }
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

        const background =
            surfacePalette?.background ??
            effectiveColors[`background_${mode}`] ??
            effectiveColors['surface_background'] ??
            effectiveColors['background'] ??
            (mode === 'light' ? '#ffffff' : '#0f172a');
        const bodySurface =
            surfacePalette?.body ??
            effectiveColors[`body_${mode}`] ??
            effectiveColors['surface_body'] ??
            effectiveColors['body'] ??
            (mode === 'light' ? '#f8fafc' : '#111827');
        const headersSurface =
            surfacePalette?.headers ??
            effectiveColors[`headers_${mode}`] ??
            effectiveColors['surface_headers'] ??
            effectiveColors['headers'] ??
            (mode === 'light' ? '#e2e8f0' : '#111827');
        const sidebarSurface =
            surfacePalette?.sidebar ??
            effectiveColors[`sidebar_${mode}`] ??
            effectiveColors['surface_sidebar'] ??
            effectiveColors['sidebar'] ??
            (mode === 'light' ? '#e2e8f0' : '#0b0f14');

        const primary = effectiveColors[`primary_${mode}`] ?? effectiveColors['primary'] ?? '#008000';
        const secondary = effectiveColors[`secondary_${mode}`] ?? effectiveColors['secondary'] ?? '#27272a';
        const accent = effectiveColors[`accent_primary_${mode}`] ?? effectiveColors['accent_primary'] ?? '#008000';

        const textPrimaryRaw =
            textPalette?.primary ??
            effectiveColors[`text_primary_${mode}`] ??
            effectiveColors[`text_${mode}`] ??
            effectiveColors['text_primary'] ??
            effectiveColors['text'];
        const textPrimary = ensureReadableText(
            textPrimaryRaw,
            background,
            mode === 'light' ? '#111827' : '#f8fafc',
            4.5,
        );

        const textSecondaryRaw =
            textPalette?.secondary ?? effectiveColors[`text_secondary_${mode}`] ?? effectiveColors['text_secondary'];
        const textSecondary = ensureReadableText(
            textSecondaryRaw,
            background,
            mode === 'light' ? '#4b5563' : '#cbd5f5',
            3.5,
        );

        const mutedRaw =
            textPalette?.muted ??
            effectiveColors[`muted_text_${mode}`] ??
            effectiveColors['muted_text'] ??
            effectiveColors['muted'];
        const mutedText = ensureReadableText(mutedRaw, background, mode === 'light' ? '#6b7280' : '#94a3b8', 3);

        const textInverseRaw =
            textPalette?.inverse ??
            effectiveColors[`text_inverse_${mode}`] ??
            effectiveColors['text_inverse'] ??
            (mode === 'light' ? '#f8fafc' : '#111827');
        const textInverse = ensureReadableText(
            textInverseRaw,
            textPrimary,
            mode === 'light' ? '#f8fafc' : '#111827',
            4.5,
        );

        const onAccentCandidate =
            textPalette?.on_accent ?? effectiveColors['text_on_accent'] ?? accentForeground(accent);
        const onAccent = ensureReadableText(onAccentCandidate, accent, accentForeground(accent), 3);

        const spinnerTrack = mixColors(primary, background, mode === 'light' ? 0.35 : 0.65);
        const spinnerForeground = ensureReadableText(primary, background, primary, 3);
        const spinnerAccentTrack = mixColors(accent, background, mode === 'light' ? 0.45 : 0.7);
        const spinnerAccentForeground = ensureReadableText(accent, background, accent, 3);

        const headingAccent = accent;
        const contrastAccent = accent;

        const cardSurface =
            surfacePalette?.card ?? effectiveColors['surface_card'] ?? effectiveColors['body'] ?? bodySurface;

        effectiveColors.text_primary = textPrimary;
        effectiveColors.text_secondary = textSecondary;
        effectiveColors.muted_text = mutedText;
        effectiveColors.text_inverse = textInverse;
        effectiveColors.text_on_accent = onAccent;
        effectiveColors.background = background;
        effectiveColors.body = bodySurface;

        set('--theme-text-primary', textPrimary);
        set('--theme-text', textPrimary);
        set('--theme-text-secondary', textSecondary);
        set('--theme-text-muted', mutedText);
        set('--theme-text-inverse', textInverse);
        set('--theme-primary', primary);
        set('--theme-secondary', secondary);
        set('--theme-background', background);
        set('--theme-body', bodySurface);
        set('--theme-headers', headersSurface);
        set('--theme-sidebar', sidebarSurface);
        set('--theme-surface-body', bodySurface);
        set('--theme-surface-card', cardSurface);
        set('--theme-surface-headers', headersSurface);
        set('--theme-surface-sidebar', sidebarSurface);
        set('--theme-accent', accent);
        set('--theme-accent-text', headingAccent);
        set('--theme-accent-contrast', contrastAccent);
        set('--theme-on-accent', onAccent);
        set('--theme-spinner-track', spinnerTrack);
        set('--theme-spinner-foreground', spinnerForeground);
        set('--theme-spinner-track-accent', spinnerAccentTrack);
        set('--theme-spinner-foreground-accent', spinnerAccentForeground);

        const backgroundRgb = colorToRgbString(background) ?? '15 23 42';
        const bodySurfaceRgb = colorToRgbString(bodySurface) ?? '17 24 39';
        const headersSurfaceRgb = colorToRgbString(headersSurface) ?? bodySurfaceRgb;
        const sidebarSurfaceRgb = colorToRgbString(sidebarSurface) ?? bodySurfaceRgb;
        const surfaceCardRgb = colorToRgbString(cardSurface) ?? bodySurfaceRgb;
        const secondaryRgb = colorToRgbString(secondary) ?? '39 39 42';
        const textPrimaryRgb = colorToRgbString(textPrimary) ?? '229 231 235';
        const textSecondaryRgb = colorToRgbString(textSecondary) ?? '148 163 184';
        const mutedTextRgb = colorToRgbString(mutedText) ?? '156 163 175';
        const textInverseRgb = colorToRgbString(textInverse) ?? '15 23 42';
        const primaryRgb = colorToRgbString(primary) ?? '34 197 94';
        const accentRgb = colorToRgbString(accent) ?? primaryRgb;
        const onAccentRgb = colorToRgbString(onAccent) ?? '249 250 251';
        const spinnerTrackRgb = colorToRgbString(spinnerTrack) ?? textSecondaryRgb;
        const spinnerForegroundRgb = colorToRgbString(spinnerForeground) ?? primaryRgb;
        const spinnerAccentTrackRgb = colorToRgbString(spinnerAccentTrack) ?? accentRgb;
        const spinnerAccentForegroundRgb = colorToRgbString(spinnerAccentForeground) ?? onAccentRgb;

        set('--theme-background-rgb', backgroundRgb);
        set('--theme-body-rgb', bodySurfaceRgb);
        set('--theme-headers-rgb', headersSurfaceRgb);
        set('--theme-sidebar-rgb', sidebarSurfaceRgb);
        set('--theme-surface-card-rgb', surfaceCardRgb);
        set('--theme-secondary-rgb', secondaryRgb);
        set('--theme-text-primary-rgb', textPrimaryRgb);
        set('--theme-text-secondary-rgb', textSecondaryRgb);
        set('--theme-text-muted-rgb', mutedTextRgb);
        set('--theme-text-inverse-rgb', textInverseRgb);
        set('--theme-primary-rgb', primaryRgb);
        set('--theme-accent-rgb', accentRgb);
        set('--theme-on-accent-rgb', onAccentRgb);
        set('--theme-spinner-track-rgb', spinnerTrackRgb);
        set('--theme-spinner-foreground-rgb', spinnerForegroundRgb);
        set('--theme-spinner-track-accent-rgb', spinnerAccentTrackRgb);
        set('--theme-spinner-foreground-accent-rgb', spinnerAccentForegroundRgb);
        // background image per-mode
        const bgImage = effectiveColors[`background_image_${mode}`] ?? effectiveColors['background_image'] ?? '';
        if (bgImage) set('--theme-background-image', `url(${bgImage})`);
    }, [theme, mode]);

    return null;
}
