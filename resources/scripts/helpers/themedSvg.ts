const FALLBACK_PRIMARY = 'hsl(142 66% 45%)';
const FALLBACK_SECONDARY = 'hsl(32 94% 55%)';

type ThemeColorMap = Record<string, string | undefined> | undefined;
type ThemeMode = 'light' | 'dark';

const applyAccents = (svg: string, colors: ThemeColorMap): string => {
    const primary = colors?.['accent_primary'] ?? colors?.['primary'] ?? FALLBACK_PRIMARY;
    const secondary = colors?.['accent_secondary'] ?? colors?.['secondary'] ?? FALLBACK_SECONDARY;

    return svg.replaceAll('__ACCENT_PRIMARY__', primary).replaceAll('__ACCENT_SECONDARY__', secondary);
};

const applyLightModeNeutrals = (svg: string, colors: ThemeColorMap, mode: ThemeMode): string => {
    if (mode !== 'light') return svg;

    const strongNeutral = colors?.['text_light'] ?? '#1f2937';
    const softNeutral = colors?.['text_secondary_light'] ?? '#475569';

    const replacements: Array<[string, string | undefined]> = [
        ['#ffffff', strongNeutral],
        ['#fff', strongNeutral],
        ['#f1f1f1', softNeutral],
        ['#f2f2f2', softNeutral],
        ['#e6e6e6', softNeutral],
        ['#cbcbcb', softNeutral],
        ['#cccccc', softNeutral],
    ];

    let result = svg;
    for (const [target, replacement] of replacements) {
        if (!replacement) continue;
        result = result.replaceAll(target, replacement).replaceAll(target.toUpperCase(), replacement);
    }

    return result;
};

const toDataUri = (svg: string): string => `data:image/svg+xml,${encodeURIComponent(svg)}`;

export const createThemedSvgDataUri = (svg: string, colors: ThemeColorMap, mode: ThemeMode = 'dark'): string => {
    const withAccents = applyAccents(svg, colors);
    const withNeutrals = applyLightModeNeutrals(withAccents, colors, mode);
    return toDataUri(withNeutrals);
};

export const ensureThemedSvg = (svg: string, colors: ThemeColorMap, mode: ThemeMode = 'dark'): string => {
    if (svg.includes('__ACCENT_PRIMARY__') || svg.includes('__ACCENT_SECONDARY__')) {
        return createThemedSvgDataUri(svg, colors, mode);
    }

    return toDataUri(svg);
};
