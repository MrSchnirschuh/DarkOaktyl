import http from '@/api/http';
import { SiteTheme } from '@/state/theme';

export interface ThemeDesignerToken {
    key: string;
    label: string;
    description: string;
}

export interface ThemeDesignerGroup {
    id: string;
    label: string;
    description: string;
    keys: ThemeDesignerToken[];
}

export type ThemeMode = 'light' | 'dark';

export interface ThemePaletteResponse {
    groups: ThemeDesignerGroup[];
    modes: Record<ThemeMode, Record<string, string>>;
    defaults: Record<ThemeMode, Record<string, string>>;
    overrides: Record<ThemeMode, Record<string, boolean>>;
    email: {
        defaults: Record<string, unknown>;
        palettes: Record<ThemeMode, Record<string, string>>;
    };
    theme: SiteTheme;
}

export const fetchThemePalette = async (): Promise<ThemePaletteResponse> => {
    const { data } = await http.get('/api/application/theme/palette');

    return data as ThemePaletteResponse;
};
