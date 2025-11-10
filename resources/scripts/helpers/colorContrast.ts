const normalizeHex = (color?: string): string | null => {
    if (!color || typeof color !== 'string') return null;
    let hex = color.trim();
    if (!hex.startsWith('#')) return null;
    hex = hex.slice(1);
    if (hex.length === 3) {
        hex = hex
            .split('')
            .map(ch => ch + ch)
            .join('');
    }
    if (hex.length !== 6) return null;
    return `#${hex.toLowerCase()}`;
};

const hexToRgb = (color: string): { r: number; g: number; b: number } | null => {
    const hex = normalizeHex(color);
    if (!hex) return null;
    const parsed = parseInt(hex.slice(1), 16);
    return {
        r: (parsed >> 16) & 0xff,
        g: (parsed >> 8) & 0xff,
        b: parsed & 0xff,
    };
};

const rgbToHex = (r: number, g: number, b: number): string => {
    const clamp = (value: number) => Math.min(255, Math.max(0, Math.round(value)));
    const toHex = (value: number) => clamp(value).toString(16).padStart(2, '0');
    return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
};

const relativeLuminance = (color: string): number => {
    const rgb = hexToRgb(color);
    if (!rgb) return 0;
    const transform = (channel: number) => {
        const c = channel / 255;
        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    };
    const r = transform(rgb.r);
    const g = transform(rgb.g);
    const b = transform(rgb.b);
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

const contrastRatio = (colorA: string, colorB: string): number => {
    const a = relativeLuminance(colorA);
    const b = relativeLuminance(colorB);
    const brighter = Math.max(a, b);
    const darker = Math.min(a, b);
    return (brighter + 0.05) / (darker + 0.05);
};

const mixColors = (color: string, mixWith: string, amount: number): string => {
    const rgbA = hexToRgb(color);
    const rgbB = hexToRgb(mixWith);
    if (!rgbA || !rgbB) return color;
    const amt = Math.min(1, Math.max(0, amount));
    const lerp = (a: number, b: number) => a + (b - a) * amt;
    return rgbToHex(lerp(rgbA.r, rgbB.r), lerp(rgbA.g, rgbB.g), lerp(rgbA.b, rgbB.b));
};

export const ensureReadableAccent = (accent: string, background: string, fallback: string): string => {
    const baseAccent = normalizeHex(accent);
    const baseBackground = normalizeHex(background);
    const baseFallback = normalizeHex(fallback) ?? '#ffffff';
    if (!baseAccent || !baseBackground) return accent;
    let candidate = baseAccent;
    let ratio = contrastRatio(candidate, baseBackground);
    if (ratio >= 4.5) return candidate;
    const toward =
        contrastRatio('#000000', baseBackground) > contrastRatio('#ffffff', baseBackground) ? '#000000' : '#ffffff';
    let amount = 0.1;
    for (let i = 0; i < 10 && ratio < 4.5; i++) {
        candidate = mixColors(candidate, toward, amount);
        ratio = contrastRatio(candidate, baseBackground);
        amount += 0.1;
    }
    if (ratio < 3) {
        return contrastRatio(baseFallback, baseBackground) >= 4.5 ? baseFallback : toward;
    }
    return candidate;
};

export const accentForeground = (accent: string): string => {
    const normalized = normalizeHex(accent) ?? '#16a34a';
    const contrastToWhite = contrastRatio(normalized, '#ffffff');
    const contrastToBlack = contrastRatio(normalized, '#000000');
    return contrastToWhite >= contrastToBlack ? '#ffffff' : '#000000';
};
