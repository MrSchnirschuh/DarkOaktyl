const clampByte = (value: number): number => Math.min(255, Math.max(0, Math.round(value)));

const byteToHex = (value: number): string => clampByte(value).toString(16).padStart(2, '0');

const parseRgbComponent = (value: string): number | null => {
    const trimmed = value.trim();
    if (!trimmed) return null;
    if (trimmed.endsWith('%')) {
        const numeric = Number.parseFloat(trimmed.slice(0, -1));
        if (Number.isNaN(numeric)) return null;
        return clampByte((numeric / 100) * 255);
    }
    const numeric = Number.parseFloat(trimmed);
    if (Number.isNaN(numeric)) return null;
    return clampByte(numeric);
};

const parseRgbString = (color: string): { r: number; g: number; b: number } | null => {
    const value = color.trim();
    const lower = value.toLowerCase();
    if (!lower.startsWith('rgb')) return null;
    const open = value.indexOf('(');
    const close = value.lastIndexOf(')');
    if (open === -1 || close === -1 || close <= open + 1) return null;
    const body = value.slice(open + 1, close);
    const parts = body
        .split(',')
        .map(part => part.trim())
        .filter(Boolean);
    if (parts.length < 3) return null;
    const [first, second, third] = parts;
    if (!first || !second || !third) return null;
    const r = parseRgbComponent(first);
    const g = parseRgbComponent(second);
    const b = parseRgbComponent(third);
    if (r === null || g === null || b === null) return null;
    return { r, g, b };
};

const parseHslPercentage = (value: string): number | null => {
    const trimmed = value.trim();
    if (!trimmed.endsWith('%')) return null;
    const numeric = Number.parseFloat(trimmed.slice(0, -1));
    if (Number.isNaN(numeric)) return null;
    return Math.min(100, Math.max(0, numeric));
};

const parseHslString = (color: string): { r: number; g: number; b: number } | null => {
    const value = color.trim();
    const lower = value.toLowerCase();
    if (!lower.startsWith('hsl')) return null;
    const open = value.indexOf('(');
    const close = value.lastIndexOf(')');
    if (open === -1 || close === -1 || close <= open + 1) return null;
    const body = value.slice(open + 1, close);
    const parts = body
        .split(',')
        .map(part => part.trim())
        .filter(Boolean);
    if (parts.length < 3) return null;
    const [hueRaw, saturationRaw, lightnessRaw] = parts;
    if (!hueRaw || !saturationRaw || !lightnessRaw) return null;
    const hue = Number.parseFloat(hueRaw);
    const saturation = parseHslPercentage(saturationRaw);
    const lightness = parseHslPercentage(lightnessRaw);
    if (Number.isNaN(hue) || saturation === null || lightness === null) return null;

    const h = (((hue % 360) + 360) % 360) / 360;
    const s = saturation / 100;
    const l = lightness / 100;

    if (s === 0) {
        const gray = clampByte(l * 255);
        return { r: gray, g: gray, b: gray };
    }

    const hue2rgb = (p: number, q: number, t: number): number => {
        let temp = t;
        if (temp < 0) temp += 1;
        if (temp > 1) temp -= 1;
        if (temp < 1 / 6) return p + (q - p) * 6 * temp;
        if (temp < 1 / 2) return q;
        if (temp < 2 / 3) return p + (q - p) * (2 / 3 - temp) * 6;
        return p;
    };

    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;

    const r = clampByte(hue2rgb(p, q, h + 1 / 3) * 255);
    const g = clampByte(hue2rgb(p, q, h) * 255);
    const b = clampByte(hue2rgb(p, q, h - 1 / 3) * 255);

    return { r, g, b };
};

const normalizeHex = (color?: string): string | null => {
    if (!color || typeof color !== 'string') return null;
    const trimmed = color.trim();
    if (!trimmed) return null;

    if (trimmed.startsWith('#')) {
        let hex = trimmed.slice(1);
        if (hex.length === 3) {
            hex = hex
                .split('')
                .map(ch => ch + ch)
                .join('');
        }
        if (hex.length === 6 && /^[0-9a-fA-F]{6}$/.test(hex)) {
            return `#${hex.toLowerCase()}`;
        }
        return null;
    }

    const rgb = parseRgbString(trimmed) ?? parseHslString(trimmed);
    if (!rgb) return null;
    return `#${byteToHex(rgb.r)}${byteToHex(rgb.g)}${byteToHex(rgb.b)}`;
};

const hexToRgb = (color: string): { r: number; g: number; b: number } | null => {
    const hex = normalizeHex(color);
    if (!hex) return null;
    const parsed = Number.parseInt(hex.slice(1), 16);
    return {
        r: (parsed >> 16) & 0xff,
        g: (parsed >> 8) & 0xff,
        b: parsed & 0xff,
    };
};

const rgbToHex = (r: number, g: number, b: number): string => `#${byteToHex(r)}${byteToHex(g)}${byteToHex(b)}`;

const rgbToHsl = (r: number, g: number, b: number): { h: number; s: number; l: number } => {
    const rNorm = clampByte(r) / 255;
    const gNorm = clampByte(g) / 255;
    const bNorm = clampByte(b) / 255;

    const max = Math.max(rNorm, gNorm, bNorm);
    const min = Math.min(rNorm, gNorm, bNorm);
    let h = 0;
    let s = 0;
    const l = (max + min) / 2;

    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case rNorm:
                h = (gNorm - bNorm) / d + (gNorm < bNorm ? 6 : 0);
                break;
            case gNorm:
                h = (bNorm - rNorm) / d + 2;
                break;
            default:
                h = (rNorm - gNorm) / d + 4;
                break;
        }
        h /= 6;
    }

    return {
        h: h * 360,
        s: s * 100,
        l: l * 100,
    };
};

export const normalizeColorHex = (color?: string): string | null => normalizeHex(color);

export const colorToRgbString = (color?: string | null): string | null => {
    if (!color) return null;
    const rgb = hexToRgb(color);
    if (!rgb) return null;
    return `${rgb.r} ${rgb.g} ${rgb.b}`;
};

export const hexToRgbCss = (color: string): string | null => {
    const rgb = hexToRgb(color);
    if (!rgb) return null;
    return `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;
};

export const hexToHslCss = (color: string): string | null => {
    const rgb = hexToRgb(color);
    if (!rgb) return null;
    const { h, s, l } = rgbToHsl(rgb.r, rgb.g, rgb.b);
    const round = (value: number) => Math.round(value * 100) / 100;
    return `hsl(${Math.round(h)}, ${round(s)}%, ${round(l)}%)`;
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

export const mixColors = (color: string, mixWith: string, amount: number): string => {
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
    const normalized = normalizeHex(accent) ?? '#008000';
    const contrastToWhite = contrastRatio(normalized, '#ffffff');
    const contrastToBlack = contrastRatio(normalized, '#000000');
    return contrastToWhite >= contrastToBlack ? '#ffffff' : '#000000';
};

export const ensureReadableText = (
    text: string | undefined,
    background: string | undefined,
    fallback: string,
    ratio = 4.5,
): string => {
    const bg = normalizeHex(background) ?? normalizeHex(fallback) ?? '#111111';
    const fb = normalizeHex(fallback) ?? '#ffffff';

    if (!bg) return fb;

    const candidate = normalizeHex(text);
    if (candidate && contrastRatio(candidate, bg) >= ratio) {
        return candidate;
    }

    if (candidate) {
        const toward = contrastRatio('#000000', bg) >= contrastRatio('#ffffff', bg) ? '#000000' : '#ffffff';
        let adjusted = candidate;
        let amount = 0.1;
        for (let i = 0; i < 10; i++) {
            adjusted = mixColors(adjusted, toward, amount);
            if (contrastRatio(adjusted, bg) >= ratio) {
                return adjusted;
            }
            amount += 0.1;
        }
    }

    if (contrastRatio(fb, bg) >= ratio) {
        return fb;
    }

    return contrastRatio('#000000', bg) >= contrastRatio('#ffffff', bg) ? '#000000' : '#ffffff';
};
