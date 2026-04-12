import http from '@/api/http';
import { Transformers } from '@definitions/admin';
import type { EmailTemplate, EmailTheme } from '@definitions/admin/models';

export interface EmailTemplatePayload {
    key?: string;
    name?: string;
    subject?: string;
    description?: string | null;
    content?: string;
    locale?: string;
    isEnabled?: boolean;
    themeUuid?: string | null;
    metadata?: Record<string, unknown>;
}

export interface EmailTemplatePreviewPayload {
    subject: string;
    content: string;
    themeUuid?: string | null;
    data?: Record<string, unknown>;
    metadata?: Record<string, unknown>;
    locale?: string;
    name?: string;
}

export interface EmailTemplatePreview {
    subject: string;
    html: string;
    text?: string | null;
    theme: EmailTheme;
}

export interface EmailTemplateTestPayload {
    email?: string;
    userId?: number;
    data?: Record<string, unknown>;
}

const mapTemplatePayload = (payload: EmailTemplatePayload): Record<string, unknown> => {
    const body: Record<string, unknown> = {};

    if (payload.key !== undefined) body.key = payload.key;
    if (payload.name !== undefined) body.name = payload.name;
    if (payload.subject !== undefined) body.subject = payload.subject;
    if (payload.description !== undefined) body.description = payload.description;
    if (payload.content !== undefined) body.content = payload.content;
    if (payload.locale !== undefined) body.locale = payload.locale;
    if (payload.isEnabled !== undefined) body.is_enabled = payload.isEnabled;
    if (payload.themeUuid !== undefined) body.theme_uuid = payload.themeUuid;
    if (payload.metadata !== undefined) body.metadata = payload.metadata ?? {};

    return body;
};

export const getEmailTemplates = async (): Promise<EmailTemplate[]> => {
    const { data } = await http.get('/api/application/emails/templates', {
        params: {
            per_page: 100,
            include: 'theme',
            sort: 'name',
        },
    });

    return (data.data || []).map(Transformers.toEmailTemplate);
};

export const createEmailTemplate = async (payload: Required<EmailTemplatePayload>): Promise<EmailTemplate> => {
    const { data } = await http.post('/api/application/emails/templates', mapTemplatePayload(payload));

    return Transformers.toEmailTemplate(data);
};

export const updateEmailTemplate = async (uuid: string, payload: EmailTemplatePayload): Promise<EmailTemplate> => {
    const { data } = await http.patch(`/api/application/emails/templates/${uuid}`, mapTemplatePayload(payload));

    return Transformers.toEmailTemplate(data);
};

export const deleteEmailTemplate = async (uuid: string): Promise<void> => {
    await http.delete(`/api/application/emails/templates/${uuid}`);
};

export const previewEmailTemplate = async (payload: EmailTemplatePreviewPayload): Promise<EmailTemplatePreview> => {
    const { data } = await http.post('/api/application/emails/templates/preview', {
        subject: payload.subject,
        content: payload.content,
        theme_uuid: payload.themeUuid,
        data: payload.data,
        metadata: payload.metadata,
        locale: payload.locale,
        name: payload.name,
    });

    return {
        subject: data.subject,
        html: data.html,
        text: data.text ?? null,
        theme: Transformers.normalizeEmailTheme(data.theme),
    };
};

export const sendTestEmailTemplate = async (uuid: string, payload: EmailTemplateTestPayload): Promise<void> => {
    await http.post(`/api/application/emails/templates/${uuid}/test`, {
        email: payload.email,
        user_id: payload.userId,
        data: payload.data,
    });
};
