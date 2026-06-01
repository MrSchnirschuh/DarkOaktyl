import http from '@/api/http';

export interface EmailEnvironmentSettings {
    mailer: string;
    host: string;
    port: string;
    username: string;
    password: string;
    encryption: string;
    fromAddress: string;
    fromName: string;
}

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
    environment: EmailEnvironmentSettings;
    mailers: string[];
    counts: EmailModuleCounts;
}

export type EmailSettingKey = 'enabled' | 'default_theme';

type EmailSettingValue = string | boolean | null | undefined;

export const getEmailSettings = async (): Promise<EmailSettingsResponse> => {
    const { data } = await http.get('/api/application/emails/settings');

    const rawPort = data.environment?.port;
    const environment: EmailEnvironmentSettings = {
        mailer: data.environment?.mailer ?? 'smtp',
        host: data.environment?.host ?? '',
        port: rawPort === null || typeof rawPort === 'undefined' ? '' : String(rawPort),
        username: data.environment?.username ?? '',
        password: data.environment?.password ?? '',
        encryption: data.environment?.encryption ?? '',
        fromAddress: data.environment?.from_address ?? '',
        fromName: data.environment?.from_name ?? '',
    };

    return {
        enabled: Boolean(data.enabled),
        defaultTheme: data.default_theme ?? null,
        defaults: data.defaults as EmailThemeDefaults,
        environment,
        mailers: Array.isArray(data.mailers) ? data.mailers : [],
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

export const updateEmailEnvironment = async (payload: EmailEnvironmentSettings): Promise<void> => {
    await http.put('/api/application/emails/settings/environment', {
        mailer: payload.mailer,
        host: payload.host || null,
        port: payload.port === '' || payload.port === null ? null : String(payload.port),
        username: payload.username || null,
        password: payload.password || null,
        encryption: payload.encryption || null,
        from_address: payload.fromAddress || null,
        from_name: payload.fromName || null,
    });
};
