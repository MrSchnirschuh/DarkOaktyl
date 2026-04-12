import http from '@/api/http';

export interface EmailModuleCounts {
    themes: number;
    templates: number;
    triggers: number;
}

export interface EmailThemeDefaults {
    name: string;
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    background_color: string;
    body_color: string;
    text_color: string;
    muted_text_color: string;
    button_color: string;
    button_text_color: string;
    footer_text: string;
}

export interface EmailSettingsResponse {
    enabled: boolean;
    defaultTheme: string | null;
    defaults: EmailThemeDefaults;
    counts: EmailModuleCounts;
}

export type EmailSettingKey = 'enabled' | 'default_theme';

type EmailSettingValue = string | boolean | null | undefined;

export const getEmailSettings = async (): Promise<EmailSettingsResponse> => {
    const { data } = await http.get('/api/application/emails/settings');

    return {
        enabled: Boolean(data.enabled),
        defaultTheme: data.default_theme ?? null,
        defaults: data.defaults as EmailThemeDefaults,
        counts: {
            themes: data.counts?.themes ?? 0,
            templates: data.counts?.templates ?? 0,
            triggers: data.counts?.triggers ?? 0,
        },
    };
};

export const updateEmailSetting = async (key: EmailSettingKey, value: EmailSettingValue): Promise<void> => {
    await http.patch('/api/application/emails/settings', { key, value });
};
