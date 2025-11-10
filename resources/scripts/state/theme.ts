import { action, Action } from 'easy-peasy';

export interface SiteTheme {
    colors: {
        primary: string;
        secondary: string;

        background: string;
        headers: string;
        sidebar: string;
        [key: string]: string;
    };
}

export interface ThemeStore {
    data?: SiteTheme;
    mode?: 'light' | 'dark';
    setTheme: Action<ThemeStore, SiteTheme>;
    setMode: Action<ThemeStore, 'light' | 'dark'>;
}

const theme: ThemeStore = {
    data: undefined,
    mode: 'dark',

    setTheme: action((state, payload) => {
        state.data = payload;
    }),

    setMode: action((state, payload) => {
        state.mode = payload;

        if (!state.data) {
            return;
        }

        const original = { ...(state.data.colors as Record<string, string>) };
        const colors = { ...original };
        const suffix = `_${payload}`;

        // Apply any per-mode keys found in the colors object. e.g. primary_dark => primary
        Object.keys(colors).forEach(k => {
            if (k.endsWith(suffix)) {
                const base = k.slice(0, -suffix.length);
                const val = colors[k];
                if (typeof val !== 'undefined') {
                    colors[base] = val as string;
                }
            }
        });

        // Preserve base accent hues regardless of mode overrides so light & dark stay consistent.
        ['primary', 'accent_primary', 'accent_secondary'].forEach(key => {
            if (typeof original[key] !== 'undefined') {
                colors[key] = original[key];
            }
        });

        state.data = { colors } as SiteTheme;
    }),
};

export default theme;
