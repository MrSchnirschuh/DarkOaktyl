import http from '@/api/http';
import { ThemePaletteResponse, ThemeMode } from '@/api/admin/theme/getPalette';

export type ThemePaletteDraft = Record<ThemeMode, Record<string, string | null>>;

export const updateThemePalette = async (modes: ThemePaletteDraft): Promise<ThemePaletteResponse> => {
    const { data } = await http.put('/api/application/theme/palette', { modes });

    return data as ThemePaletteResponse;
};
