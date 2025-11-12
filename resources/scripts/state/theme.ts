import { action, Action } from 'easy-peasy';

export interface ThemePalette {
    primary: string;
    secondary: string;
    background: string;
    headers: string;
    body: string;
    sidebar: string;
    accent_primary: string;
    accent_secondary: string;
    text_primary: string;
    text_secondary: string;
    muted_text: string;
    text_inverse: string;
    button: string;
    button_text: string;
    [key: string]: string;
}

export interface ThemeTextPalette {
    primary: string;
    secondary: string;
    muted: string;
    inverse: string;
    on_accent: string;
    [key: string]: string;
}

export interface ThemeSurfacePalette {
    background: string;
    body: string;
    headers: string;
    sidebar: string;
    card: string;
    [key: string]: string;
}

export interface ThemeEmailPalette {
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    background_color: string;
    body_color: string;
    text_color: string;
    muted_text_color: string;
    button_color: string;
    button_text_color: string;
    [key: string]: string;
}

export interface SiteTheme {
    colors: {
        primary: string;
        secondary: string;

        background: string;
        headers: string;
        sidebar: string;
        [key: string]: string;
    };
    palettes?: Partial<Record<'light' | 'dark', ThemePalette>>;
    textPalettes?: Partial<Record<'light' | 'dark', ThemeTextPalette>>;
    surfacePalettes?: Partial<Record<'light' | 'dark', ThemeSurfacePalette>>;
    emailPalettes?: Partial<Record<'light' | 'dark', ThemeEmailPalette>>;
}

export interface ThemeStore {
    data?: SiteTheme;
    mode?: 'light' | 'dark';
    preference?: 'light' | 'dark' | 'system';
    sourceColors?: Record<string, string>;
    setTheme: Action<ThemeStore, SiteTheme>;
    setMode: Action<ThemeStore, 'light' | 'dark'>;
    setPreference: Action<ThemeStore, 'light' | 'dark' | 'system'>;
}

type ThemeStoreDraft = ThemeStore & Record<string, unknown>;

const applyPaletteForState = (state: ThemeStoreDraft): void => {
    if (!state.data) {
        return;
    }

    if (!state.sourceColors && state.data.colors) {
        state.sourceColors = { ...(state.data.colors as Record<string, string>) };
    }

    const mode = state.mode ?? 'dark';
    const source = state.sourceColors ?? ((state.data.colors ?? {}) as Record<string, string>);
    const colors = { ...source };

    const palette = state.data.palettes?.[mode];
    if (palette) {
        Object.entries(palette).forEach(([key, value]) => {
            colors[key] = value;
            if (key === 'button_text') {
                colors.buttonText = value;
            }
            if (key === 'text_primary') {
                colors.text = value;
            }
            if (key === 'muted_text') {
                colors.muted = value;
            }
            if (key === 'text_inverse') {
                colors.text_inverse = value;
            }
        });
    }

    const suffix = `_${mode}`;
    Object.keys(source).forEach(key => {
        if (key.endsWith(suffix)) {
            const base = key.slice(0, -suffix.length);
            const value = source[key];
            if (typeof value !== 'undefined') {
                colors[base] = value;
            }
        }
    });

    state.data = {
        ...state.data,
        colors: colors as SiteTheme['colors'],
    };
};

const theme: ThemeStore = {
    data: undefined,
    mode: 'dark',
    preference: 'system',
    sourceColors: undefined,

    setTheme: action((state, payload) => {
        state.data = {
            ...payload,
            colors: { ...(payload.colors as Record<string, string>) } as SiteTheme['colors'],
        };
        state.sourceColors = { ...(payload.colors as Record<string, string>) };
        applyPaletteForState(state as ThemeStoreDraft);
    }),

    setMode: action((state, payload) => {
        state.mode = payload;

        applyPaletteForState(state as ThemeStoreDraft);
    }),

    setPreference: action((state, payload) => {
        state.preference = payload;
    }),
};

export default theme;

export const resolveThemeMode = (
    preference: 'light' | 'dark' | 'system',
    fallback: 'light' | 'dark' = 'dark',
): 'light' | 'dark' => {
    if (preference === 'system') {
        if (
            typeof window !== 'undefined' &&
            window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches
        ) {
            return 'dark';
        }

        if (
            typeof window !== 'undefined' &&
            window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: light)').matches
        ) {
            return 'light';
        }

        return fallback;
    }

    return preference;
};
