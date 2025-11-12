export interface EmailTemplate {
    id: number;
    key: string;
    name: string;
    subject: string;
    body_html: string;
    body_text: string | null;
    variables: string[];
    enabled: boolean;
    created_at: string;
    updated_at: string;
}

export interface ScheduledEmail {
    id: number;
    name: string;
    template_key: string;
    template: {
        key: string;
        name: string;
    } | null;
    trigger_type: 'cron' | 'date' | 'event';
    trigger_value: string | null;
    event_name: string | null;
    recipients: {
        type: 'all' | 'specific' | 'role' | 'servers';
        emails?: string[];
        role?: string;
    };
    template_data: Record<string, any>;
    enabled: boolean;
    last_run_at: string | null;
    next_run_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface CreateEmailTemplateRequest {
    key: string;
    name: string;
    subject: string;
    body_html: string;
    body_text?: string;
    variables?: string[];
    enabled?: boolean;
}

export interface UpdateEmailTemplateRequest {
    key?: string;
    name?: string;
    subject?: string;
    body_html?: string;
    body_text?: string;
    variables?: string[];
    enabled?: boolean;
}

export interface CreateScheduledEmailRequest {
    name: string;
    template_key: string;
    trigger_type: 'cron' | 'date' | 'event';
    trigger_value?: string;
    event_name?: string;
    recipients?: {
        type: 'all' | 'specific' | 'role' | 'servers';
        emails?: string[];
        role?: string;
    };
    template_data?: Record<string, any>;
    enabled?: boolean;
}

export interface UpdateScheduledEmailRequest {
    name?: string;
    template_key?: string;
    trigger_type?: 'cron' | 'date' | 'event';
    trigger_value?: string;
    event_name?: string;
    recipients?: {
        type: 'all' | 'specific' | 'role' | 'servers';
        emails?: string[];
        role?: string;
    };
    template_data?: Record<string, any>;
    enabled?: boolean;
}
