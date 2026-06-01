import http from '@/api/http';
import { Transformers } from '@definitions/admin';
import type { EmailTheme } from '@definitions/admin/models';

export interface EmailThemeColors {
    primary?: string;
    secondary?: string;
    accent?: string;
    background?: string;
    body?: string;
    text?: string;
    muted?: string;
    button?: string;
    buttonText?: string;
}

export interface EmailThemePayload {
    name?: string;
    description?: string | null;
    colors?: EmailThemeColors;
    logoUrl?: string | null;
    footerText?: string | null;
    setDefault?: boolean;
    variantMode?: 'single' | 'dual';
    lightColors?: EmailThemeColors | null;
}

const mapThemePayload = (payload: EmailThemePayload): Record<string, unknown> => {
    const body: Record<string, unknown> = {};

    if (payload.name !== undefined) body.name = payload.name;
    if (payload.description !== undefined) body.description = payload.description;
    if (payload.logoUrl !== undefined) body.logo_url = payload.logoUrl;
    if (payload.footerText !== undefined) body.footer_text = payload.footerText;
    if (payload.setDefault !== undefined) body.set_default = payload.setDefault;
    if (payload.variantMode !== undefined) body.variant_mode = payload.variantMode;

    if (payload.colors) {
        if (payload.colors.primary !== undefined) body.primary_color = payload.colors.primary;
        if (payload.colors.secondary !== undefined) body.secondary_color = payload.colors.secondary;
        if (payload.colors.accent !== undefined) body.accent_color = payload.colors.accent;
        if (payload.colors.background !== undefined) body.background_color = payload.colors.background;
        if (payload.colors.body !== undefined) body.body_color = payload.colors.body;
        if (payload.colors.text !== undefined) body.text_color = payload.colors.text;
        if (payload.colors.muted !== undefined) body.muted_text_color = payload.colors.muted;
        if (payload.colors.button !== undefined) body.button_color = payload.colors.button;
        if (payload.colors.buttonText !== undefined) body.button_text_color = payload.colors.buttonText;
    }

    if (payload.lightColors) {
        const lightPalette: Record<string, unknown> = {};
        if (payload.lightColors.primary !== undefined) lightPalette.primary = payload.lightColors.primary;
        if (payload.lightColors.secondary !== undefined) lightPalette.secondary = payload.lightColors.secondary;
        if (payload.lightColors.accent !== undefined) lightPalette.accent = payload.lightColors.accent;
        if (payload.lightColors.background !== undefined) lightPalette.background = payload.lightColors.background;
        if (payload.lightColors.body !== undefined) lightPalette.body = payload.lightColors.body;
        if (payload.lightColors.text !== undefined) lightPalette.text = payload.lightColors.text;
        if (payload.lightColors.muted !== undefined) lightPalette.muted = payload.lightColors.muted;
        if (payload.lightColors.button !== undefined) lightPalette.button = payload.lightColors.button;
        if (payload.lightColors.buttonText !== undefined) lightPalette.button_text = payload.lightColors.buttonText;

        body.light_palette = lightPalette;
    } else if (payload.variantMode === 'single') {
        body.light_palette = null;
    }

    return body;
};

export const getEmailThemes = async (): Promise<EmailTheme[]> => {
    const { data } = await http.get('/api/application/emails/themes', {
        params: {
            per_page: 100,
            sort: 'name',
        },
    });

    return (data.data || []).map(Transformers.toEmailTheme);
};

export const createEmailTheme = async (payload: Required<EmailThemePayload>): Promise<EmailTheme> => {
    const { data } = await http.post('/api/application/emails/themes', mapThemePayload(payload));

    return Transformers.toEmailTheme(data);
};

export const updateEmailTheme = async (uuid: string, payload: EmailThemePayload): Promise<EmailTheme> => {
    const { data } = await http.patch(`/api/application/emails/themes/${uuid}`, mapThemePayload(payload));

    return Transformers.toEmailTheme(data);
};

export const deleteEmailTheme = async (uuid: string): Promise<void> => {
    await http.delete(`/api/application/emails/themes/${uuid}`);
};
