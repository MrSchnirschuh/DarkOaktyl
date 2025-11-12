import { action, Action } from 'easy-peasy';

export type AlertType = 'success' | 'warning' | 'danger' | 'info';
export type AlertPosition = 'top-center' | 'bottom-right' | 'bottom-left' | 'center';

export interface DarkOakSettings {
    auth: {
        registration: {
            enabled: boolean;
        };
        security: {
            force2fa: boolean;
            attempts: number;
        };
        modules: {
            jguard: {
                enabled: boolean;
                delay?: number;
            };
            discord: {
                enabled: boolean;
                clientId: boolean;
                clientSecret: boolean;
            };
            google: {
                enabled: boolean;
                clientId: boolean;
                clientSecret: boolean;
            };
            onboarding: {
                enabled: boolean;
                content?: string;
            };
        };
    };
    tickets: {
        enabled: boolean;
        maxCount: number;
    };
    billing: {
        enabled: boolean;
        paypal: boolean;
        link: boolean;
        keys: {
            publishable: boolean;
            secret: boolean;
        };
        currency: {
            symbol: string;
            code: string;
        };
    };
    emails: {
        enabled: boolean;
        defaultTheme?: string | null;
        defaults: {
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
            variant_mode?: 'single' | 'dual';
            light_palette?: {
                primary?: string;
                secondary?: string;
                accent?: string;
                background?: string;
                body?: string;
                text?: string;
                muted?: string;
                button?: string;
                button_text?: string;
            } | null;
        };
    };
    alert: {
        enabled: boolean;
        type: AlertType;
        position: AlertPosition;
        content: string;
        uuid: string;
    };
    ai: {
        enabled: boolean;
        key: boolean | string;
        user_access: boolean;
    };
    webhooks: {
        enabled: boolean;
        url: boolean;
    };
}

export interface DarkOakStore {
    data?: DarkOakSettings;
    setDarkOak: Action<DarkOakStore, DarkOakSettings>;
    updateDarkOak: Action<DarkOakStore, Partial<DarkOakSettings>>;
}

const DarkOak: DarkOakStore = {
    data: undefined,

    setDarkOak: action((state, payload) => {
        state.data = payload;
    }),

    updateDarkOak: action((state, payload) => {
        // @ts-expect-error limitation of Typescript, can't do much about that currently unfortunately.
        state.data = { ...state.data, ...payload };
    }),
};

export default DarkOak;

