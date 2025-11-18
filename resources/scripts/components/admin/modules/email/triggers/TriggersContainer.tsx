import { useCallback, useEffect, useMemo, useState } from 'react';
import { Formik, Form } from 'formik';
import { object, string, mixed } from 'yup';
import tw from 'twin.macro';
import AdminBox from '@elements/AdminBox';
import { Button } from '@elements/button';
import Spinner from '@elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import Field, { FieldRow, TextareaField } from '@elements/Field';
import SelectField, { type Option } from '@elements/SelectField';
import FormikSwitch from '@elements/FormikSwitch';
import Modal from '@elements/Modal';
import Pill from '@elements/Pill';
import {
    createEmailTrigger,
    deleteEmailTrigger,
    getEmailTriggerEvents,
    getEmailTriggers,
    runEmailTrigger,
    updateEmailTrigger,
} from '@/api/admin/emails';
import { getEmailTemplates } from '@/api/admin/emails/templates';
import type { EmailTemplate, EmailTrigger, EmailTriggerEvent } from '@definitions/admin/models';

interface TriggerFormValues {
    name: string;
    description: string;
    triggerType: EmailTrigger['triggerType'];
    scheduleType: EmailTrigger['scheduleType'] | '';
    eventKey: string;
    cronExpression: string;
    scheduleAt: string;
    timezone: string;
    templateUuid: string;
    payload: string;
    isActive: boolean;
}

const triggerTypes: Option[] = [
    { value: 'event', label: 'Event driven' },
    { value: 'schedule', label: 'Scheduled' },
    { value: 'resource', label: 'Resource change' },
];

const scheduleTypes: Option[] = [
    { value: 'once', label: 'Send once at a specific time' },
    { value: 'recurring', label: 'Recurring (cron)' },
];

const formSchema = object({
    name: string().required('Name is required.'),
    triggerType: mixed<TriggerFormValues['triggerType']>().oneOf(['event', 'schedule', 'resource']).required(),
    scheduleType: mixed<TriggerFormValues['scheduleType']>().oneOf(['once', 'recurring', '']).defined(),
    eventKey: string().when('triggerType', {
        is: 'event',
        then: (schema: any) => schema.required('Event trigger is required.'),
        otherwise: (schema: any) => schema,
    }),
    cronExpression: string().when(['triggerType', 'scheduleType'], {
        is: (triggerType: TriggerFormValues['triggerType'], scheduleType: TriggerFormValues['scheduleType']) =>
            triggerType === 'schedule' && scheduleType === 'recurring',
        then: (schema: any) => schema.required('Cron expression is required for recurring schedules.'),
        otherwise: (schema: any) => schema,
    }),
    scheduleAt: string().when(['triggerType', 'scheduleType'], {
        is: (triggerType: TriggerFormValues['triggerType'], scheduleType: TriggerFormValues['scheduleType']) =>
            triggerType === 'schedule' && scheduleType === 'once',
        then: (schema: any) => schema.required('A send date is required.'),
        otherwise: (schema: any) => schema,
    }),
    timezone: string().required('Timezone is required.'),
    payload: string().test('is-json', 'Payload must be valid JSON.', value => {
        if (!value?.trim()) return true;
        try {
            const parsed = JSON.parse(value);
            return typeof parsed === 'object' && parsed !== null;
        } catch {
            return false;
        }
    }),
});

const resolveTemplateLabel = (templates: EmailTemplate[], uuid?: string | null) => {
    if (!uuid) return 'Default template';
    const template = templates.find(item => item.uuid === uuid);
    return template ? template.name : 'Unknown template';
};

const formatDateTimeLocal = (date?: Date | null) => {
    if (!date) return '';
    const d = new Date(date);
    const iso = new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString();
    return iso.slice(0, 16);
};

const parseDateTimeLocal = (value: string): string | null => {
    if (!value) return null;
    try {
        const date = new Date(value);
        return date.toISOString();
    } catch {
        return null;
    }
};

const defaultPayload = `{
  "audience": {
    "type": "all_users"
  }
}`;

const TriggersContainer = () => {
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();

    const [loading, setLoading] = useState(true);
    const [working, setWorking] = useState(false);
    const [triggers, setTriggers] = useState<EmailTrigger[]>([]);
    const [templates, setTemplates] = useState<EmailTemplate[]>([]);
    const [events, setEvents] = useState<EmailTriggerEvent[]>([]);
    const [modalVisible, setModalVisible] = useState(false);
    const [editing, setEditing] = useState<EmailTrigger | null>(null);

    const templateOptions: Option[] = useMemo(
        () => [
            { label: 'Default template', value: '' },
            ...templates.map(template => ({ value: template.uuid, label: template.name })),
        ],
        [templates],
    );

    const eventOptions: Option[] = useMemo(
        () => events.map(event => ({ value: event.key, label: event.label })),
        [events],
    );

    const fetchData = useCallback(() => {
        setLoading(true);
        Promise.all([getEmailTriggers(), getEmailTemplates(), getEmailTriggerEvents()])
            .then(([loadedTriggers, loadedTemplates, loadedEvents]) => {
                setTriggers(loadedTriggers);
                setTemplates(loadedTemplates);
                setEvents(loadedEvents);
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:triggers', error }))
            .finally(() => setLoading(false));
    }, [clearAndAddHttpError]);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    const openCreate = () => {
        clearFlashes('admin:emails:triggers');
        setEditing(null);
        setModalVisible(true);
    };

    const openEdit = (trigger: EmailTrigger) => {
        clearFlashes('admin:emails:triggers');
        setEditing(trigger);
        setModalVisible(true);
    };

    const handleDelete = (trigger: EmailTrigger) => {
        if (!window.confirm(`Delete the trigger "${trigger.name}"?`)) return;
        setWorking(true);
        deleteEmailTrigger(trigger.uuid)
            .then(() => {
                setTriggers(prev => prev.filter(item => item.uuid !== trigger.uuid));
                addFlash({
                    key: 'admin:emails:triggers',
                    type: 'success',
                    message: 'Trigger deleted successfully.',
                });
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:triggers', error }))
            .finally(() => setWorking(false));
    };

    const handleRun = (trigger: EmailTrigger) => {
        setWorking(true);
        runEmailTrigger(trigger.uuid)
            .then(() =>
                addFlash({
                    key: 'admin:emails:triggers',
                    type: 'success',
                    message: 'Trigger queued for delivery.',
                }),
            )
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:triggers', error }))
            .finally(() => setWorking(false));
    };

    const handleToggleActive = (trigger: EmailTrigger) => {
        setWorking(true);
        updateEmailTrigger(trigger.uuid, { isActive: !trigger.isActive })
            .then(saved => {
                setTriggers(prev => prev.map(item => (item.uuid === saved.uuid ? saved : item)));
                addFlash({
                    key: 'admin:emails:triggers',
                    type: 'success',
                    message: `Trigger ${saved.isActive ? 'activated' : 'paused'}.`,
                });
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:triggers', error }))
            .finally(() => setWorking(false));
    };

    const syncAfterSave = (saved: EmailTrigger) => {
        setTriggers(prev => {
            const without = prev.filter(trigger => trigger.uuid !== saved.uuid);
            return [...without, saved].sort((a, b) => a.name.localeCompare(b.name));
        });
    };

    const initialValues = (): TriggerFormValues => {
        if (editing) {
            return {
                name: editing.name,
                description: editing.description ?? '',
                triggerType: editing.triggerType,
                scheduleType: editing.scheduleType ?? '',
                eventKey: editing.eventKey ?? '',
                scheduleAt: formatDateTimeLocal(editing.scheduleAt ?? null),
                cronExpression: editing.cronExpression ?? '',
                timezone: editing.timezone ?? 'UTC',
                templateUuid: editing.templateUuid ?? '',
                payload: editing.payload ? JSON.stringify(editing.payload, null, 2) : '',
                isActive: editing.isActive,
            };
        }

        return {
            name: '',
            description: '',
            triggerType: 'event',
            scheduleType: 'once',
            eventKey: '',
            scheduleAt: '',
            cronExpression: '',
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone ?? 'UTC',
            templateUuid: '',
            payload: defaultPayload,
            isActive: true,
        };
    };

    return (
        <div>
            <FlashMessageRender byKey={'admin:emails:triggers'} className={'mb-4'} />

            <div css={tw`flex flex-wrap items-center gap-3 mb-6`}>
                <Button onClick={openCreate}>New Trigger</Button>
                <Button.Text onClick={() => fetchData()} disabled={loading}>
                    Refresh
                </Button.Text>
            </div>

            {loading ? (
                <div css={tw`flex items-center justify-center py-12`}>
                    <Spinner />
                </div>
            ) : triggers.length === 0 ? (
                <AdminBox title={'No triggers defined yet'}>
                    <p css={tw`text-sm text-theme-muted`}>
                        Configure triggers to send emails on automation events, scheduled reminders, or resource
                        updates.
                    </p>
                    <Button className={'mt-4'} onClick={openCreate}>
                        Create trigger
                    </Button>
                </AdminBox>
            ) : (
                <div css={tw`grid gap-6 lg:grid-cols-2 xl:grid-cols-3`}>
                    {triggers.map(trigger => (
                        <AdminBox key={trigger.uuid} title={trigger.name}>
                            <div css={tw`flex flex-wrap gap-2 mb-3`}>
                                <Pill type={trigger.isActive ? 'success' : 'danger'}>
                                    {trigger.isActive ? 'Active' : 'Paused'}
                                </Pill>
                                <Pill type={'info'}>{trigger.triggerType}</Pill>
                                {trigger.scheduleType && <Pill type={'info'}>{trigger.scheduleType}</Pill>}
                            </div>
                            {trigger.description && (
                                <p css={tw`text-sm text-theme-secondary mb-3`}>{trigger.description}</p>
                            )}
                            {trigger.eventKey && (
                                <p css={tw`text-xs text-theme-muted mb-1`}>
                                    Event:{' '}
                                    {events.find(event => event.key === trigger.eventKey)?.label ?? trigger.eventKey}
                                </p>
                            )}
                            <p css={tw`text-xs text-theme-muted mb-1`}>
                                Template: {resolveTemplateLabel(templates, trigger.templateUuid)}
                            </p>
                            {trigger.nextRunAt && (
                                <p css={tw`text-xs text-theme-muted mb-1`}>
                                    Next run: {trigger.nextRunAt.toLocaleString()}
                                </p>
                            )}
                            {trigger.lastRunAt && (
                                <p css={tw`text-xs text-theme-muted mb-4`}>
                                    Last run: {trigger.lastRunAt.toLocaleString()}
                                </p>
                            )}
                            <div css={tw`flex flex-wrap gap-2`}>
                                <Button onClick={() => handleRun(trigger)} disabled={working}>
                                    Run now
                                </Button>
                                <Button.Text onClick={() => openEdit(trigger)} disabled={working}>
                                    Edit
                                </Button.Text>
                                <Button.Text onClick={() => handleToggleActive(trigger)} disabled={working}>
                                    {trigger.isActive ? 'Pause' : 'Activate'}
                                </Button.Text>
                                <Button.Danger onClick={() => handleDelete(trigger)} disabled={working}>
                                    Delete
                                </Button.Danger>
                            </div>
                        </AdminBox>
                    ))}
                </div>
            )}

            <Modal
                visible={modalVisible}
                onDismissed={() => setEditing(null)}
                dismissable={!working}
                showSpinnerOverlay={working}
            >
                <Formik
                    enableReinitialize
                    initialValues={initialValues()}
                    validationSchema={formSchema}
                    onSubmit={async (values, helpers) => {
                        helpers.setSubmitting(true);
                        setWorking(true);
                        clearFlashes('admin:emails:triggers');

                        try {
                            const payloadObject = values.payload.trim() ? JSON.parse(values.payload) : null;
                            const scheduleAtIso = values.scheduleAt ? parseDateTimeLocal(values.scheduleAt) : null;
                            const basePayload = {
                                name: values.name,
                                description: values.description ? values.description : null,
                                triggerType: values.triggerType,
                                scheduleType: values.triggerType === 'schedule' ? values.scheduleType || null : null,
                                eventKey: values.triggerType === 'event' ? values.eventKey || null : null,
                                scheduleAt:
                                    values.triggerType === 'schedule' && values.scheduleType === 'once'
                                        ? scheduleAtIso
                                        : null,
                                cronExpression:
                                    values.triggerType === 'schedule' && values.scheduleType === 'recurring'
                                        ? values.cronExpression || null
                                        : null,
                                timezone: values.triggerType === 'schedule' ? values.timezone : 'UTC',
                                templateUuid: values.templateUuid || null,
                                payload: payloadObject,
                                isActive: values.isActive,
                            };

                            const saved = editing
                                ? await updateEmailTrigger(editing.uuid, basePayload)
                                : await createEmailTrigger(basePayload as Required<typeof basePayload>);

                            syncAfterSave(saved);
                            setModalVisible(false);
                            setEditing(null);
                            addFlash({
                                key: 'admin:emails:triggers',
                                type: 'success',
                                message: editing ? 'Trigger updated successfully.' : 'Trigger created successfully.',
                            });
                        } catch (error) {
                            if (error instanceof SyntaxError) {
                                helpers.setFieldError('payload', 'Payload must be valid JSON.');
                            } else {
                                clearAndAddHttpError({ key: 'admin:emails:triggers', error });
                            }
                        } finally {
                            helpers.setSubmitting(false);
                            setWorking(false);
                        }
                    }}
                >
                    {({ values, isSubmitting }) => (
                        <Form>
                            <h2 css={tw`text-2xl font-semibold text-theme-primary mb-4`}>
                                {editing ? 'Edit trigger' : 'Create trigger'}
                            </h2>
                            <p css={tw`text-sm text-theme-muted mb-6`}>
                                Automate outbound messages when specific events occur or on a defined schedule.
                            </p>

                            <FieldRow>
                                <Field
                                    id={'name'}
                                    name={'name'}
                                    label={'Trigger name'}
                                    type={'text'}
                                    placeholder={'Welcome drip campaign'}
                                />
                                <Field
                                    id={'description'}
                                    name={'description'}
                                    label={'Description'}
                                    type={'text'}
                                    placeholder={'Optional helper text for admins'}
                                />
                            </FieldRow>

                            <FieldRow>
                                <SelectField
                                    id={'triggerType'}
                                    name={'triggerType'}
                                    label={'Trigger type'}
                                    options={triggerTypes}
                                />
                                <SelectField
                                    id={'templateUuid'}
                                    name={'templateUuid'}
                                    label={'Template'}
                                    options={templateOptions}
                                />
                            </FieldRow>

                            {values.triggerType === 'event' && (
                                <SelectField
                                    id={'eventKey'}
                                    name={'eventKey'}
                                    label={'Event trigger'}
                                    options={eventOptions}
                                />
                            )}

                            {values.triggerType === 'schedule' && (
                                <div css={tw`space-y-4`}>
                                    <SelectField
                                        id={'scheduleType'}
                                        name={'scheduleType'}
                                        label={'Schedule type'}
                                        options={scheduleTypes}
                                    />
                                    {values.scheduleType === 'once' && (
                                        <Field
                                            id={'scheduleAt'}
                                            name={'scheduleAt'}
                                            label={'Send at'}
                                            type={'datetime-local'}
                                        />
                                    )}
                                    {values.scheduleType === 'recurring' && (
                                        <Field
                                            id={'cronExpression'}
                                            name={'cronExpression'}
                                            label={'Cron expression'}
                                            type={'text'}
                                            placeholder={'0 9 * * *'}
                                        />
                                    )}
                                    <Field
                                        id={'timezone'}
                                        name={'timezone'}
                                        label={'Timezone'}
                                        type={'text'}
                                        placeholder={'UTC'}
                                    />
                                </div>
                            )}

                            <TextareaField
                                id={'payload'}
                                name={'payload'}
                                label={'Payload JSON'}
                                rows={6}
                                description={'Audience and merge data JSON. Leave empty for default behaviour.'}
                            />

                            <FormikSwitch
                                name={'isActive'}
                                label={'Trigger active'}
                                description={'Paused triggers stay configured but stop sending until re-enabled.'}
                            />

                            <div css={tw`flex flex-wrap justify-end gap-3 mt-8`}>
                                <Button.Text
                                    type={'button'}
                                    onClick={() => setModalVisible(false)}
                                    disabled={isSubmitting}
                                >
                                    Cancel
                                </Button.Text>
                                <Button type={'submit'} disabled={isSubmitting}>
                                    {editing ? 'Save changes' : 'Create trigger'}
                                </Button>
                            </div>
                        </Form>
                    )}
                </Formik>
            </Modal>
        </div>
    );
};

export default TriggersContainer;
