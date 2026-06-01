import type { ThemeMode } from '@/api/admin/theme/getPalette';
import type { SiteTheme } from '@/state/theme';
import {
    ensureReadableText,
    accentForeground,
    mixColors,
    colorToRgbString,
    normalizeColorHex,
} from '@/helpers/colorContrast';

interface DerivedThemeOptions {
    warnPresetConflict?: () => void;
    timestamp?: Date;
}

export interface DerivedThemeVariablesResult {
    cssVariables: Record<string, string>;
    logos: {
        login?: string;
        panel?: string;
    };
    emailPalette: {
        primary_color: string;
        secondary_color: string;
        accent_color: string;
        background_color: string;
        body_color: string;
        text_color: string;
        muted_text_color: string;
        button_color: string;
        button_text_color: string;
    };
    effectiveColors: Record<string, string>;
}

const defaultEmailPalette = () => ({
    primary_color: '#2563EB',
    secondary_color: '#1E40AF',
    accent_color: '#F97316',
    background_color: '#0F172A',
    body_color: '#111827',
    text_color: '#F8FAFC',
    muted_text_color: '#94A3B8',
    button_color: '#2563EB',
    button_text_color: '#FFFFFF',
});

const buildStatusVariants = (value: string, fallbackHex: string, mode: ThemeMode) => {
    const normalized = normalizeColorHex(value) ?? fallbackHex;
    const contrastBase = mode === 'light' ? '#FFFFFF' : '#0F172A';
    const solidContrast = ensureReadableText(contrastBase, normalized, contrastBase, 4.5);
    const softMix = mode === 'light' ? 0.9 : 0.55;
    const softAltMix = Math.min(1, softMix + 0.05);
    const strongMix = mode === 'light' ? 0.25 : 0.6;
    const soft = mixColors(normalized, '#FFFFFF', softMix);
    const softAlt = mixColors(normalized, '#FFFFFF', softAltMix);
    const strong = mixColors(normalized, '#000000', strongMix);
    const softContrast = ensureReadableText(strong, soft, mode === 'light' ? '#0F172A' : '#F8FAFC', 4);

    return {
        base: normalized,
        contrast: solidContrast,
        soft,
        softAlt,
        strong,
        softContrast,
    };
};

export const deriveThemeVariables = (
    theme: SiteTheme | undefined,
    mode: ThemeMode,
    options?: DerivedThemeOptions,
): DerivedThemeVariablesResult => {
    if (!theme) {
        return {
            cssVariables: {},
            logos: {},
            emailPalette: defaultEmailPalette(),
            effectiveColors: {},
        };
    }

    const colors = (theme.colors as Record<string, string>) ?? {};
    const palette = theme.palettes?.[mode];
    const textPalette = theme.textPalettes?.[mode];
    const surfacePalette = theme.surfacePalettes?.[mode];

    const presetKeys = Object.keys(colors).filter(key => key.startsWith('presets:'));
    const parsedPresets: Array<{ key: string; meta: any }> = [];

    presetKeys.forEach(key => {
        try {
            const parsed = JSON.parse(colors[key] ?? 'null');
            if (parsed) {
                parsedPresets.push({ key, meta: parsed });
            }
        } catch (error) {
            // ignore invalid preset payloads
        }
    });

    const now = options?.timestamp ?? new Date();
    let activePreset: any = null;

    const scheduledPresets = parsedPresets
        .map(entry => {
            const start = entry.meta?.schedule?.start ? new Date(entry.meta.schedule.start) : null;
            const end = entry.meta?.schedule?.end ? new Date(entry.meta.schedule.end) : null;
            const duration = start && end ? end.getTime() - start.getTime() : null;

            return { key: entry.key, meta: entry.meta, start, end, duration };
        })
        .filter(entry => entry.start && entry.start <= now && (!entry.end || now < entry.end));

    if (scheduledPresets.length > 0) {
        scheduledPresets.sort((a, b) => b.start!.getTime() - a.start!.getTime());
        const latestStart = scheduledPresets[0]!.start!.getTime();
        const matching = scheduledPresets.filter(entry => entry.start!.getTime() === latestStart);
        if (matching.length > 1) {
            const equalDuration = matching.every(entry => entry.duration === matching[0]!.duration);
            if (equalDuration && options?.warnPresetConflict) {
                options.warnPresetConflict();
            }
        }
        activePreset = scheduledPresets[0]!.meta;
    } else {
        const fallback = parsedPresets.find(entry => entry.meta && entry.meta.default);
        if (fallback) {
            activePreset = fallback.meta;
        }
    }

    const effectiveColors: Record<string, string> = { ...colors };

    if (palette) {
        Object.entries(palette).forEach(([key, value]) => {
            if (typeof value === 'string') {
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
        keysToApply.forEach(key => {
            const lightVal = activePreset.modes.light?.[key];
            const darkVal = activePreset.modes.dark?.[key];
            if (lightVal) {
                effectiveColors[key] = lightVal;
                effectiveColors[`${key}_light`] = lightVal;
            }
            if (darkVal) {
                effectiveColors[`${key}_dark`] = darkVal;
            }
        });

        const logoPanelLight = activePreset.modes.light?.logo_panel;
        const logoPanelDark = activePreset.modes.dark?.logo_panel;
        const logoLoginLight = activePreset.modes.light?.logo_login;
        const logoLoginDark = activePreset.modes.dark?.logo_login;
        if (logoPanelLight) {
            effectiveColors['logo_panel'] = logoPanelLight;
            effectiveColors['logo_panel_light'] = logoPanelLight;
        }
        if (logoPanelDark) {
            effectiveColors['logo_panel_dark'] = logoPanelDark;
        }
        if (logoLoginLight) {
            effectiveColors['logo_login'] = logoLoginLight;
            effectiveColors['logo_login_light'] = logoLoginLight;
        }
        if (logoLoginDark) {
            effectiveColors['logo_login_dark'] = logoLoginDark;
        }
        const bgImgLight = activePreset.modes.light?.background_image;
        const bgImgDark = activePreset.modes.dark?.background_image;
        if (bgImgLight) {
            effectiveColors['background_image'] = bgImgLight;
            effectiveColors['background_image_light'] = bgImgLight;
        }
        if (bgImgDark) {
            effectiveColors['background_image_dark'] = bgImgDark;
        }
    }

    const background =
        surfacePalette?.background ??
        effectiveColors[`background_${mode}`] ??
        effectiveColors['surface_background'] ??
        effectiveColors['background'] ??
        (mode === 'light' ? '#FFFFFF' : '#0F172A');
    const bodySurface =
        surfacePalette?.body ??
        effectiveColors[`body_${mode}`] ??
        effectiveColors['surface_body'] ??
        effectiveColors['body'] ??
        (mode === 'light' ? '#F8FAFC' : '#111827');
    const headersSurface =
        surfacePalette?.headers ??
        effectiveColors[`headers_${mode}`] ??
        effectiveColors['surface_headers'] ??
        effectiveColors['headers'] ??
        (mode === 'light' ? '#E2E8F0' : '#111827');
    const sidebarSurface =
        surfacePalette?.sidebar ??
        effectiveColors[`sidebar_${mode}`] ??
        effectiveColors['surface_sidebar'] ??
        effectiveColors['sidebar'] ??
        (mode === 'light' ? '#E2E8F0' : '#0B0F14');

    const primary = effectiveColors[`primary_${mode}`] ?? effectiveColors['primary'] ?? '#008000';
    const secondary = effectiveColors[`secondary_${mode}`] ?? effectiveColors['secondary'] ?? '#27272A';
    const accent = effectiveColors[`accent_primary_${mode}`] ?? effectiveColors['accent_primary'] ?? '#008000';

    const textPrimaryRaw =
        textPalette?.primary ??
        effectiveColors[`text_primary_${mode}`] ??
        effectiveColors[`text_${mode}`] ??
        effectiveColors['text_primary'] ??
        effectiveColors['text'];
    const textPrimary = ensureReadableText(textPrimaryRaw, background, mode === 'light' ? '#111827' : '#F8FAFC', 4.5);

    const textSecondaryRaw =
        textPalette?.secondary ?? effectiveColors[`text_secondary_${mode}`] ?? effectiveColors['text_secondary'];
    const textSecondary = ensureReadableText(
        textSecondaryRaw,
        background,
        mode === 'light' ? '#4B5563' : '#CBD5F5',
        3.5,
    );

    const mutedRaw =
        textPalette?.muted ??
        effectiveColors[`muted_text_${mode}`] ??
        effectiveColors['muted_text'] ??
        effectiveColors['muted'];
    const mutedText = ensureReadableText(mutedRaw, background, mode === 'light' ? '#6B7280' : '#94A3B8', 3);

    const textInverseRaw =
        textPalette?.inverse ??
        effectiveColors[`text_inverse_${mode}`] ??
        effectiveColors['text_inverse'] ??
        (mode === 'light' ? '#F8FAFC' : '#111827');
    const textInverse = ensureReadableText(textInverseRaw, textPrimary, mode === 'light' ? '#F8FAFC' : '#111827', 4.5);

    const onAccentCandidate = textPalette?.on_accent ?? effectiveColors['text_on_accent'] ?? accentForeground(accent);
    const onAccent = ensureReadableText(onAccentCandidate, accent, accentForeground(accent), 3);

    const spinnerTrack = mixColors(primary, background, mode === 'light' ? 0.35 : 0.65);
    const spinnerForeground = ensureReadableText(primary, background, primary, 3);
    const spinnerAccentTrack = mixColors(accent, background, mode === 'light' ? 0.45 : 0.7);
    const spinnerAccentForeground = ensureReadableText(accent, background, accent, 3);

    const cardSurface =
        surfacePalette?.card ?? effectiveColors['surface_card'] ?? effectiveColors['body'] ?? bodySurface;

    effectiveColors.text_primary = textPrimary;
    effectiveColors.text_secondary = textSecondary;
    effectiveColors.muted_text = mutedText;
    effectiveColors.text_inverse = textInverse;
    effectiveColors.text_on_accent = onAccent;
    effectiveColors.background = background;
    effectiveColors.body = bodySurface;

    const cssVariables: Record<string, string> = {};
    const setVar = (name: string, value?: string) => {
        if (typeof value === 'undefined') return;
        cssVariables[name] = value;
    };

    setVar('--theme-text-primary', textPrimary);
    setVar('--theme-text', textPrimary);
    setVar('--theme-text-secondary', textSecondary);
    setVar('--theme-text-muted', mutedText);
    setVar('--theme-text-inverse', textInverse);
    setVar('--theme-primary', primary);
    setVar('--theme-secondary', secondary);
    setVar('--theme-background', background);
    setVar('--theme-body', bodySurface);
    setVar('--theme-headers', headersSurface);
    setVar('--theme-sidebar', sidebarSurface);
    setVar('--theme-surface-body', bodySurface);
    setVar('--theme-surface-card', cardSurface);
    setVar('--theme-surface-headers', headersSurface);
    setVar('--theme-surface-sidebar', sidebarSurface);
    setVar('--theme-accent', accent);
    setVar('--theme-accent-text', accent);
    setVar('--theme-accent-contrast', accent);
    setVar('--theme-on-accent', onAccent);
    setVar('--theme-spinner-track', spinnerTrack);
    setVar('--theme-spinner-foreground', spinnerForeground);
    setVar('--theme-spinner-track-accent', spinnerAccentTrack);
    setVar('--theme-spinner-foreground-accent', spinnerAccentForeground);

    const statusFallbackHex = {
        danger: '#DC2626',
        info: '#F59E0B',
        warning: '#F97316',
        experimental: '#FACC15',
        success: normalizeColorHex(primary) ?? '#16A34A',
    } as const;

    const statusFallbackRgb = {
        danger: '220 38 38',
        info: '245 158 11',
        warning: '249 115 22',
        experimental: '250 204 21',
        success: colorToRgbString(statusFallbackHex.success) ?? '34 197 94',
    } as const;

    const statusValues = {
        danger: effectiveColors['danger'] ?? statusFallbackHex.danger,
        info: effectiveColors['info'] ?? statusFallbackHex.info,
        warning: effectiveColors['warning'] ?? statusFallbackHex.warning,
        experimental: effectiveColors['experimental'] ?? statusFallbackHex.experimental,
        success: effectiveColors['success'] ?? statusFallbackHex.success,
    } as const;

    (Object.keys(statusValues) as Array<keyof typeof statusValues>).forEach(key => {
        const variants = buildStatusVariants(statusValues[key], statusFallbackHex[key], mode);

        setVar(`--theme-${key}`, variants.base);
        setVar(`--theme-${key}-contrast`, variants.contrast);
        setVar(`--theme-${key}-soft`, variants.soft);
        setVar(`--theme-${key}-soft-alt`, variants.softAlt);
        setVar(`--theme-${key}-strong`, variants.strong);
        setVar(`--theme-${key}-soft-contrast`, variants.softContrast);

        const baseRgb = colorToRgbString(variants.base) ?? statusFallbackRgb[key];
        const softRgb = colorToRgbString(variants.soft) ?? baseRgb;
        const softAltRgb = colorToRgbString(variants.softAlt) ?? softRgb;
        const strongRgb = colorToRgbString(variants.strong) ?? baseRgb;
        const contrastRgb = colorToRgbString(variants.contrast) ?? baseRgb;
        const softContrastRgb = colorToRgbString(variants.softContrast) ?? contrastRgb;

        setVar(`--theme-${key}-rgb`, baseRgb);
        setVar(`--theme-${key}-soft-rgb`, softRgb);
        setVar(`--theme-${key}-soft-alt-rgb`, softAltRgb);
        setVar(`--theme-${key}-strong-rgb`, strongRgb);
        setVar(`--theme-${key}-contrast-rgb`, contrastRgb);
        setVar(`--theme-${key}-soft-contrast-rgb`, softContrastRgb);
    });

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

    setVar('--theme-background-rgb', backgroundRgb);
    setVar('--theme-body-rgb', bodySurfaceRgb);
    setVar('--theme-headers-rgb', headersSurfaceRgb);
    setVar('--theme-sidebar-rgb', sidebarSurfaceRgb);
    setVar('--theme-surface-card-rgb', surfaceCardRgb);
    setVar('--theme-secondary-rgb', secondaryRgb);
    setVar('--theme-text-primary-rgb', textPrimaryRgb);
    setVar('--theme-text-secondary-rgb', textSecondaryRgb);
    setVar('--theme-text-muted-rgb', mutedTextRgb);
    setVar('--theme-text-inverse-rgb', textInverseRgb);
    setVar('--theme-primary-rgb', primaryRgb);
    setVar('--theme-accent-rgb', accentRgb);
    setVar('--theme-on-accent-rgb', onAccentRgb);
    setVar('--theme-spinner-track-rgb', spinnerTrackRgb);
    setVar('--theme-spinner-foreground-rgb', spinnerForegroundRgb);
    setVar('--theme-spinner-track-accent-rgb', spinnerAccentTrackRgb);
    setVar('--theme-spinner-foreground-accent-rgb', spinnerAccentForegroundRgb);

    const bgImage = effectiveColors[`background_image_${mode}`] ?? effectiveColors['background_image'] ?? '';
    setVar('--theme-background-image', bgImage ? `url(${bgImage})` : '');

    const logos = {
        login: effectiveColors[`logo_login_${mode}`] ?? effectiveColors['logo_login'] ?? '',
        panel: effectiveColors[`logo_panel_${mode}`] ?? effectiveColors['logo_panel'] ?? '',
    };

    const emailPalette = {
        primary_color: primary,
        secondary_color: secondary,
        accent_color: accent,
        background_color: background,
        body_color: bodySurface,
        text_color: textPrimary,
        muted_text_color: mutedText,
        button_color: effectiveColors[`button_${mode}`] ?? effectiveColors['button'] ?? primary,
        button_text_color:
            effectiveColors[`button_text_${mode}`] ??
            effectiveColors['button_text'] ??
            ensureReadableText('#FFFFFF', primary, '#FFFFFF', 3),
    };

    return {
        cssVariables,
        logos,
        emailPalette,
        effectiveColors,
    };
};
