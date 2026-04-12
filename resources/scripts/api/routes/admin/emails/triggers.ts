import http from '@/api/http';
import { Transformers } from '@definitions/admin';
import type { EmailTrigger, EmailTriggerEvent } from '@definitions/admin/models';

export interface EmailTriggerRequestPayload {
    name?: string;
    description?: string | null;
    triggerType?: EmailTrigger['triggerType'];
    scheduleType?: EmailTrigger['scheduleType'];
    eventKey?: string | null;
    scheduleAt?: string | null;
    cronExpression?: string | null;
    timezone?: string;
    templateUuid?: string | null;
    payload?: EmailTrigger['payload'];
    isActive?: boolean;
}

const mapTriggerPayload = (payload: EmailTriggerRequestPayload): Record<string, unknown> => {
    const body: Record<string, unknown> = {};

    if (payload.name !== undefined) body.name = payload.name;
    if (payload.description !== undefined) body.description = payload.description;
    if (payload.triggerType !== undefined) body.trigger_type = payload.triggerType;
    if (payload.scheduleType !== undefined) body.schedule_type = payload.scheduleType;
    if (payload.eventKey !== undefined) body.event_key = payload.eventKey;
    if (payload.scheduleAt !== undefined) body.schedule_at = payload.scheduleAt;
    if (payload.cronExpression !== undefined) body.cron_expression = payload.cronExpression;
    if (payload.timezone !== undefined) body.timezone = payload.timezone;
    if (payload.templateUuid !== undefined) body.template_uuid = payload.templateUuid;
    if (payload.payload !== undefined) body.payload = payload.payload;
    if (payload.isActive !== undefined) body.is_active = payload.isActive;

    return body;
};

export const getEmailTriggers = async (): Promise<EmailTrigger[]> => {
    const { data } = await http.get('/api/application/emails/triggers', {
        params: {
            per_page: 100,
            include: 'template',
            sort: '-created_at',
        },
    });

    return (data.data || []).map(Transformers.toEmailTrigger);
};

export const createEmailTrigger = async (payload: Required<EmailTriggerRequestPayload>): Promise<EmailTrigger> => {
    const { data } = await http.post('/api/application/emails/triggers', mapTriggerPayload(payload));

    return Transformers.toEmailTrigger(data);
};

export const updateEmailTrigger = async (uuid: string, payload: EmailTriggerRequestPayload): Promise<EmailTrigger> => {
    const { data } = await http.patch(`/api/application/emails/triggers/${uuid}`, mapTriggerPayload(payload));

    return Transformers.toEmailTrigger(data);
};

export const deleteEmailTrigger = async (uuid: string): Promise<void> => {
    await http.delete(`/api/application/emails/triggers/${uuid}`);
};

export const runEmailTrigger = async (uuid: string): Promise<void> => {
    await http.post(`/api/application/emails/triggers/${uuid}/run`);
};

export const getEmailTriggerEvents = async (): Promise<EmailTriggerEvent[]> => {
    const { data } = await http.get('/api/application/emails/triggers/events');

    return (data.data || []).map(Transformers.toEmailTriggerEvent);
};
