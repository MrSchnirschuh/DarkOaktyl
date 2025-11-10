import http from '@/api/http';
import {
    EmailTemplate,
    ScheduledEmail,
    CreateEmailTemplateRequest,
    UpdateEmailTemplateRequest,
    CreateScheduledEmailRequest,
    UpdateScheduledEmailRequest,
} from './types';

// Email Templates
export const getEmailTemplates = (): Promise<EmailTemplate[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/application/emails/templates')
            .then(({ data }) => resolve((data.data || []).map((item: any) => item.attributes)))
            .catch(reject);
    });
};

export const getEmailTemplate = (id: number): Promise<EmailTemplate> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/emails/templates/${id}`)
            .then(({ data }) => resolve(data.attributes))
            .catch(reject);
    });
};

export const createEmailTemplate = (template: CreateEmailTemplateRequest): Promise<EmailTemplate> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/emails/templates', template)
            .then(({ data }) => resolve(data.attributes))
            .catch(reject);
    });
};

export const updateEmailTemplate = (id: number, template: UpdateEmailTemplateRequest): Promise<EmailTemplate> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/emails/templates/${id}`, template)
            .then(({ data }) => resolve(data.attributes))
            .catch(reject);
    });
};

export const deleteEmailTemplate = (id: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/application/emails/templates/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};

export const testEmailTemplate = (id: number, email: string, data?: Record<string, any>): Promise<{ success: boolean; message: string }> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/application/emails/templates/${id}/test`, { email, data })
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};

// Scheduled Emails
export const getScheduledEmails = (): Promise<ScheduledEmail[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/application/emails/scheduled')
            .then(({ data }) => resolve((data.data || []).map((item: any) => item.attributes)))
            .catch(reject);
    });
};

export const getScheduledEmail = (id: number): Promise<ScheduledEmail> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/emails/scheduled/${id}`)
            .then(({ data }) => resolve(data.attributes))
            .catch(reject);
    });
};

export const createScheduledEmail = (scheduledEmail: CreateScheduledEmailRequest): Promise<ScheduledEmail> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/emails/scheduled', scheduledEmail)
            .then(({ data }) => resolve(data.attributes))
            .catch(reject);
    });
};

export const updateScheduledEmail = (id: number, scheduledEmail: UpdateScheduledEmailRequest): Promise<ScheduledEmail> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/emails/scheduled/${id}`, scheduledEmail)
            .then(({ data }) => resolve(data.attributes))
            .catch(reject);
    });
};

export const deleteScheduledEmail = (id: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/application/emails/scheduled/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};
