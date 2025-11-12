import { useCallback, useEffect, useMemo, useState } from 'react';
import { Formik, Form } from 'formik';
import { object, string } from 'yup';
import tw from 'twin.macro';
import AdminBox from '@elements/AdminBox';
import { Button } from '@elements/button';
import Spinner from '@elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import Pill from '@elements/Pill';
import Field, { FieldRow, TextareaField } from '@elements/Field';
import Modal from '@elements/Modal';
import FormikSwitch from '@elements/FormikSwitch';
import SelectField, { type Option } from '@elements/SelectField';
import useFlash from '@/plugins/useFlash';
import {
    createEmailTemplate,
    deleteEmailTemplate,
    getEmailTemplates,
    getEmailThemes,
    previewEmailTemplate,
    sendTestEmailTemplate,
    updateEmailTemplate,
    type EmailTemplatePayload,
} from '@/api/admin/emails';
import type { EmailTemplate, EmailTheme } from '@definitions/admin/models';

interface TemplateFormValues {
    key: string;
    name: string;
    subject: string;
    description: string;
    content: string;
    locale: string;
    themeUuid: string;
    metadata: string;
    isEnabled: boolean;
}

interface TestEmailFormValues {
    email: string;
    data: string;
}

const formSchema = object({
    key: string()
        .required('A template key is required.')
        .matches(
            /^[A-Za-z0-9._/-]+$/,
            'Key may only contain letters, numbers, dots, slashes, underscores, and dashes.',
        ),
    name: string().required('A display name is required.'),
    subject: string().required('Subject is required.'),
    locale: string().required('Locale is required.'),
    content: string().required('Email content cannot be empty.'),
    description: string().max(191).nullable(),
});

const testFormSchema = object({
    email: string().email('Enter a valid email address.').required('Provide an email address to send the test.'),
    data: string().test('json', 'Context data must be valid JSON.', value => {
        if (!value || !value.trim()) return true;

        try {
            const parsed = JSON.parse(value);
            return typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed);
        } catch (error) {
            return false;
        }
    }),
});

const TemplatesContainer = () => {
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();

    const [templates, setTemplates] = useState<EmailTemplate[]>([]);
    const [themes, setThemes] = useState<EmailTheme[]>([]);
    const [loading, setLoading] = useState(true);
    const [modalVisible, setModalVisible] = useState(false);
    const [editing, setEditing] = useState<EmailTemplate | null>(null);
    const [previewVisible, setPreviewVisible] = useState(false);
    const [previewHtml, setPreviewHtml] = useState<string | null>(null);
    const [previewSubject, setPreviewSubject] = useState('');
    const [testModalVisible, setTestModalVisible] = useState(false);
    const [testTemplate, setTestTemplate] = useState<EmailTemplate | null>(null);
    const [working, setWorking] = useState(false);

    const themeOptions: Option[] = useMemo(
        () => [
            { value: '', label: 'Default theme' },
            ...themes.map(theme => ({ value: theme.uuid, label: theme.name })),
        ],
        [themes],
    );

    const fetchData = useCallback(() => {
        setLoading(true);
        Promise.all([getEmailTemplates(), getEmailThemes()])
            .then(([loadedTemplates, loadedThemes]) => {
                setTemplates(loadedTemplates);
                setThemes(loadedThemes);
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:templates', error }))
            .finally(() => setLoading(false));
    }, [clearAndAddHttpError]);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    const getInitialValues = (): TemplateFormValues => {
        if (editing) {
            return {
                key: editing.key,
                name: editing.name,
                subject: editing.subject,
                description: editing.description ?? '',
                content: editing.content,
                locale: editing.locale,
                themeUuid: editing.themeUuid ?? '',
                metadata: JSON.stringify(editing.metadata ?? {}, null, 2),
                isEnabled: editing.isEnabled,
            };
        }

        return {
            key: '',
            name: '',
            subject: '',
            description: '',
            content: `<p>Hello {{ user.username }},</p>
<p>Thanks for being part of our community!</p>`,
            locale: 'en',
            themeUuid: '',
            metadata: `{
  "example": true
}`,
            isEnabled: true,
        };
    };

    const handleModalClose = () => setModalVisible(false);

    const syncAfterSave = (saved: EmailTemplate) => {
        setTemplates(prev => {
            const without = prev.filter(template => template.uuid !== saved.uuid);
            return [...without, saved].sort((a, b) => a.name.localeCompare(b.name));
        });
    };

    const openCreate = () => {
        clearFlashes('admin:emails:templates');
        setEditing(null);
        setModalVisible(true);
    };

    const openEdit = (template: EmailTemplate) => {
        clearFlashes('admin:emails:templates');
        setEditing(template);
        setModalVisible(true);
    };

    const openTestModal = (template: EmailTemplate) => {
        clearFlashes('admin:emails:templates');
        setTestTemplate(template);
        setTestModalVisible(true);
    };

    const closeTestModal = () => {
        if (working) return;

        setTestModalVisible(false);
        setTestTemplate(null);
    };

    const handleDelete = (template: EmailTemplate) => {
        if (!window.confirm(`Delete the template "${template.name}"?`)) return;

        setWorking(true);
        deleteEmailTemplate(template.uuid)
            .then(() => {
                setTemplates(prev => prev.filter(item => item.uuid !== template.uuid));
                addFlash({
                    key: 'admin:emails:templates',
                    type: 'success',
                    message: 'Template deleted successfully.',
                });
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:templates', error }))
            .finally(() => setWorking(false));
    };

    const handlePreview = (template: EmailTemplate) => {
        setWorking(true);
        previewEmailTemplate({
            subject: template.subject,
            content: template.content,
            themeUuid: template.themeUuid ?? undefined,
            locale: template.locale,
            name: template.name,
            metadata: template.metadata ?? {},
        })
            .then(data => {
                setPreviewSubject(data.subject);
                setPreviewHtml(data.html);
                setPreviewVisible(true);
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:templates', error }))
            .finally(() => setWorking(false));
    };

    const getTestInitialValues = (): TestEmailFormValues => ({
        email: '',
        data:
            testTemplate && testTemplate.metadata && Object.keys(testTemplate.metadata).length > 0
                ? JSON.stringify(testTemplate.metadata, null, 2)
                : '{}',
    });

    const parseJsonField = (value: string, label: string, allowArrays = false): Record<string, unknown> => {
        if (!value.trim()) return {};

        try {
            const parsed = JSON.parse(value);
            if (typeof parsed !== 'object' || parsed === null || (!allowArrays && Array.isArray(parsed))) {
                throw new Error(`${label} must be a JSON object.`);
            }
            return parsed;
        } catch (error) {
            if (error instanceof Error && error.message.includes('JSON object')) {
                throw error;
            }

            throw new Error(`${label} must be valid JSON.`);
        }
    };

    const parseMetadata = (metadata: string): Record<string, unknown> => parseJsonField(metadata, 'Metadata', true);

    const parseContextData = (data: string): Record<string, unknown> => parseJsonField(data, 'Context data');

    return (
        <div>
            <FlashMessageRender byKey={'admin:emails:templates'} className={'mb-4'} />

            <div css={tw`flex flex-wrap items-center gap-3 mb-6`}>
                <Button onClick={openCreate}>New Template</Button>
                <Button.Text onClick={() => fetchData()} disabled={loading}>
                    Refresh
                </Button.Text>
            </div>

            {loading ? (
                <div css={tw`flex items-center justify-center py-12`}>
                    <Spinner />
                </div>
            ) : templates.length === 0 ? (
                <AdminBox title={'No templates yet'}>
                    <p css={tw`text-sm text-neutral-400`}>
                        Create templates to control onboarding journeys, coupon announcements, or resource drop alerts.
                    </p>
                    <Button className={'mt-4'} onClick={openCreate}>
                        Create Template
                    </Button>
                </AdminBox>
            ) : (
                <div css={tw`grid gap-6 md:grid-cols-2 xl:grid-cols-3`}>
                    {templates.map(template => (
                        <AdminBox key={template.uuid} title={template.name}>
                            <div css={tw`flex items-center justify-between mb-3`}>
                                <span css={tw`text-xs text-neutral-400`}>Key</span>
                                <code css={tw`text-[10px] bg-neutral-800 px-2 py-1 rounded`}>{template.key}</code>
                            </div>
                            <div css={tw`flex flex-wrap gap-2 mb-3`}>
                                <Pill type={template.isEnabled ? 'success' : 'danger'}>
                                    {template.isEnabled ? 'Enabled' : 'Disabled'}
                                </Pill>
                                <Pill type={'info'}>{template.locale}</Pill>
                                {template.themeUuid && (
                                    <Pill type={'warn'}>
                                        {themes.find(theme => theme.uuid === template.themeUuid)?.name ??
                                            'Custom theme'}
                                    </Pill>
                                )}
                            </div>
                            {template.description && (
                                <p css={tw`text-sm text-neutral-300 mb-3`}>{template.description}</p>
                            )}
                            <p css={tw`text-xs text-neutral-500 mb-4`}>
                                Last updated {template.updatedAt?.toLocaleString() ?? '—'}
                            </p>
                            <div css={tw`flex flex-wrap gap-2`}>
                                <Button onClick={() => openEdit(template)} disabled={working}>
                                    Edit
                                </Button>
                                <Button.Text onClick={() => handlePreview(template)} disabled={working}>
                                    Preview
                                </Button.Text>
                                <Button.Text onClick={() => openTestModal(template)} disabled={working}>
                                    Send Test
                                </Button.Text>
                                <Button.Danger onClick={() => handleDelete(template)} disabled={working}>
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
                    initialValues={getInitialValues()}
                    validationSchema={formSchema}
                    onSubmit={async (values, helpers) => {
                        helpers.setSubmitting(true);
                        setWorking(true);
                        clearFlashes('admin:emails:templates');

                        try {
                            const metadata = parseMetadata(values.metadata);
                            const payload: Required<EmailTemplatePayload> = {
                                key: values.key,
                                name: values.name,
                                subject: values.subject,
                                description: values.description ? values.description : null,
                                content: values.content,
                                locale: values.locale,
                                themeUuid: values.themeUuid || null,
                                metadata,
                                isEnabled: values.isEnabled,
                            };

                            const saved = editing
                                ? await updateEmailTemplate(editing.uuid, payload)
                                : await createEmailTemplate(payload);

                            syncAfterSave(saved);
                            addFlash({
                                key: 'admin:emails:templates',
                                type: 'success',
                                message: editing ? 'Template updated successfully.' : 'Template created successfully.',
                            });
                            setEditing(null);
                            setModalVisible(false);
                        } catch (error) {
                            if (error instanceof Error && error.message.includes('JSON')) {
                                helpers.setFieldError('metadata', error.message);
                            } else {
                                clearAndAddHttpError({ key: 'admin:emails:templates', error });
                            }
                        } finally {
                            helpers.setSubmitting(false);
                            setWorking(false);
                        }
                    }}
                >
                    {({ isSubmitting }) => (
                        <Form>
                            <h2 css={tw`text-2xl font-semibold text-neutral-100 mb-4`}>
                                {editing ? 'Edit Template' : 'Create Template'}
                            </h2>
                            <p css={tw`text-sm text-neutral-400 mb-6`}>
                                Define the content, subject, and optional metadata for this email template. Use Twig
                                variables just like in your Laravel views.
                            </p>

                            <FieldRow>
                                <Field
                                    id={'key'}
                                    name={'key'}
                                    label={'Template Key'}
                                    type={'text'}
                                    placeholder={'billing.invoice'}
                                />
                                <Field
                                    id={'name'}
                                    name={'name'}
                                    label={'Display Name'}
                                    type={'text'}
                                    placeholder={'Invoice Notice'}
                                />
                                <Field
                                    id={'subject'}
                                    name={'subject'}
                                    label={'Subject'}
                                    type={'text'}
                                    placeholder={'Your monthly invoice is ready'}
                                />
                                <Field
                                    id={'locale'}
                                    name={'locale'}
                                    label={'Locale'}
                                    type={'text'}
                                    placeholder={'en'}
                                />
                            </FieldRow>

                            <FieldRow>
                                <Field
                                    id={'description'}
                                    name={'description'}
                                    label={'Description'}
                                    type={'text'}
                                    placeholder={'Short summary for admins only'}
                                />
                                <SelectField
                                    id={'themeUuid'}
                                    name={'themeUuid'}
                                    label={'Theme'}
                                    options={themeOptions}
                                />
                            </FieldRow>

                            <TextareaField
                                id={'content'}
                                name={'content'}
                                label={'HTML Content'}
                                rows={10}
                                description={'Use Twig syntax and include inline styles for best client compatibility.'}
                            />

                            <TextareaField
                                id={'metadata'}
                                name={'metadata'}
                                label={'Metadata JSON'}
                                rows={6}
                                description={'Optional JSON payload merged into the template rendering context.'}
                            />

                            <FormikSwitch
                                name={'isEnabled'}
                                label={'Enable template'}
                                description={'Disabled templates remain in the library but cannot be used by triggers.'}
                            />

                            <div css={tw`flex flex-wrap justify-end gap-3 mt-8`}>
                                <Button.Text type={'button'} onClick={handleModalClose} disabled={isSubmitting}>
                                    Cancel
                                </Button.Text>
                                <Button type={'submit'} disabled={isSubmitting}>
                                    {editing ? 'Save changes' : 'Create template'}
                                </Button>
                            </div>
                        </Form>
                    )}
                </Formik>
            </Modal>

            <Modal
                visible={previewVisible}
                onDismissed={() => {
                    setPreviewVisible(false);
                    setPreviewHtml(null);
                    setPreviewSubject('');
                }}
                dismissable
                showSpinnerOverlay={false}
            >
                <div css={tw`space-y-4`}>
                    <div>
                        <h2 css={tw`text-2xl font-semibold text-neutral-100`}>{previewSubject}</h2>
                        <p css={tw`text-sm text-neutral-400`}>Preview renders with the current template metadata.</p>
                    </div>
                    <div css={tw`rounded bg-neutral-900/70 border border-neutral-700 p-4 overflow-y-auto max-h-[60vh]`}>
                        {previewHtml ? (
                            <div
                                css={[
                                    tw`text-sm leading-relaxed text-neutral-100 space-y-3`,
                                    {
                                        '& h1': tw`text-3xl font-semibold text-neutral-100`.style,
                                        '& h2': tw`text-2xl font-semibold text-neutral-100`.style,
                                        '& h3': tw`text-xl font-semibold text-neutral-100`.style,
                                        '& h4': tw`text-lg font-semibold text-neutral-100`.style,
                                        '& p': tw`mb-3 text-neutral-200`.style,
                                        '& a': tw`text-primary-300 underline`.style,
                                        '& ul': tw`list-disc list-inside mb-3 text-neutral-200`.style,
                                        '& ol': tw`list-decimal list-inside mb-3 text-neutral-200`.style,
                                        '& code': tw`font-mono bg-neutral-800 px-1 py-0.5 rounded text-xs`.style,
                                        '& pre': tw`
                                            bg-neutral-900
                                            border border-neutral-700
                                            rounded
                                            p-3
                                            overflow-auto
                                            text-xs
                                        `.style,
                                    },
                                ]}
                                dangerouslySetInnerHTML={{ __html: previewHtml }}
                            />
                        ) : (
                            <Spinner />
                        )}
                    </div>
                    <div css={tw`flex justify-end`}>
                        <Button.Text onClick={() => setPreviewVisible(false)}>Close</Button.Text>
                    </div>
                </div>
            </Modal>

            <Modal
                visible={testModalVisible}
                onDismissed={closeTestModal}
                dismissable={!working}
                showSpinnerOverlay={working}
            >
                {testTemplate && (
                    <Formik
                        enableReinitialize
                        initialValues={getTestInitialValues()}
                        validationSchema={testFormSchema}
                        onSubmit={async (values, helpers) => {
                            helpers.setSubmitting(true);
                            setWorking(true);
                            clearFlashes('admin:emails:templates');

                            try {
                                const context = parseContextData(values.data);

                                await sendTestEmailTemplate(testTemplate.uuid, {
                                    email: values.email,
                                    data: context,
                                });

                                addFlash({
                                    key: 'admin:emails:templates',
                                    type: 'success',
                                    message: 'Test email sent successfully.',
                                });

                                setTestModalVisible(false);
                                setTestTemplate(null);
                            } catch (error) {
                                if (error instanceof Error && error.message.includes('Context data')) {
                                    helpers.setFieldError('data', error.message);
                                } else {
                                    clearAndAddHttpError({ key: 'admin:emails:templates', error });
                                }
                            } finally {
                                helpers.setSubmitting(false);
                                setWorking(false);
                            }
                        }}
                    >
                        {({ isSubmitting }) => (
                            <Form>
                                <h2 css={tw`text-2xl font-semibold text-neutral-100 mb-4`}>Send Test Email</h2>
                                <p css={tw`text-sm text-neutral-400 mb-6`}>
                                    Deliver a real message using {testTemplate.name} to verify its layout and data.
                                </p>

                                <Field
                                    id={'test-email'}
                                    name={'email'}
                                    label={'Recipient email'}
                                    type={'email'}
                                    placeholder={'admin@example.com'}
                                />

                                <TextareaField
                                    id={'test-data'}
                                    name={'data'}
                                    label={'Context data JSON'}
                                    rows={6}
                                    description={'Optional JSON object merged into the rendering context.'}
                                />

                                <div css={tw`flex justify-end gap-3 mt-6`}>
                                    <Button.Text type={'button'} onClick={closeTestModal} disabled={isSubmitting}>
                                        Cancel
                                    </Button.Text>
                                    <Button type={'submit'} disabled={isSubmitting}>
                                        Send Test
                                    </Button>
                                </div>
                            </Form>
                        )}
                    </Formik>
                )}
            </Modal>
        </div>
    );
};

export default TemplatesContainer;
