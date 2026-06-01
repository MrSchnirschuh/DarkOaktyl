import { useCallback, useEffect, useMemo, useState } from 'react';
import { Formik, Form } from 'formik';
import { object, string } from 'yup';
import tw from 'twin.macro';
import AdminBox from '@elements/AdminBox';
import { Button } from '@elements/button';
import Spinner from '@elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import Pill from '@elements/Pill';
import Field, { FieldRow } from '@elements/Field';
import SelectField, { type Option } from '@elements/SelectField';
import Modal from '@elements/Modal';
import FormikSwitch from '@elements/FormikSwitch';
import useFlash from '@/plugins/useFlash';
import { useStoreActions, useStoreState } from '@/state/hooks';
import {
    createEmailTheme,
    deleteEmailTheme,
    getEmailThemes,
    updateEmailTheme,
    type EmailThemePayload,
} from '@/api/admin/emails';
import type { EmailTheme } from '@definitions/admin/models';
import { faPalette } from '@fortawesome/free-solid-svg-icons';

interface ThemeFormValues {
    name: string;
    description: string;
    logoUrl: string;
    footerText: string;
    variantMode: 'single' | 'dual';
    colors: {
        primary: string;
        secondary: string;
        accent: string;
        background: string;
        body: string;
        text: string;
        muted: string;
        button: string;
        buttonText: string;
    };
    lightColors: {
        primary: string;
        secondary: string;
        accent: string;
        background: string;
        body: string;
        text: string;
        muted: string;
        button: string;
        buttonText: string;
    };
    setDefault: boolean;
}

const colorFields: Array<{ key: keyof ThemeFormValues['colors']; label: string }> = [
    { key: 'primary', label: 'Primary' },
    { key: 'secondary', label: 'Secondary' },
    { key: 'accent', label: 'Accent' },
    { key: 'background', label: 'Background' },
    { key: 'body', label: 'Body' },
    { key: 'text', label: 'Text' },
    { key: 'muted', label: 'Muted Text' },
    { key: 'button', label: 'Button' },
    { key: 'buttonText', label: 'Button Text' },
];

const variantOptions: Option[] = [
    { value: 'single', label: 'Single palette (dark only)' },
    { value: 'dual', label: 'Dual palette (light & dark)' },
];

const formSchema = object({
    name: string().required('A theme name is required.'),
    description: string().max(191, 'Description is too long.').nullable(),
    logoUrl: string().max(191).nullable(),
    footerText: string().max(191).nullable(),
    variantMode: string().oneOf(['single', 'dual']).required(),
});

const fallbackColors: ThemeFormValues['colors'] = {
    primary: '#6366f1',
    secondary: '#312e81',
    accent: '#f97316',
    background: '#111827',
    body: '#1f2937',
    text: '#f9fafb',
    muted: '#9ca3af',
    button: '#4f46e5',
    buttonText: '#ffffff',
};

const fallbackLightColors: ThemeFormValues['lightColors'] = {
    primary: '#4338ca',
    secondary: '#c7d2fe',
    accent: '#f97316',
    background: '#ffffff',
    body: '#f3f4f6',
    text: '#111827',
    muted: '#6b7280',
    button: '#4338ca',
    buttonText: '#ffffff',
};

const mapFormToPayload = (values: ThemeFormValues): Required<EmailThemePayload> => ({
    name: values.name,
    description: values.description ? values.description : null,
    logoUrl: values.logoUrl ? values.logoUrl : null,
    footerText: values.footerText ? values.footerText : null,
    setDefault: values.setDefault,
    variantMode: values.variantMode,
    colors: {
        primary: values.colors.primary,
        secondary: values.colors.secondary,
        accent: values.colors.accent,
        background: values.colors.background,
        body: values.colors.body,
        text: values.colors.text,
        muted: values.colors.muted,
        button: values.colors.button,
        buttonText: values.colors.buttonText,
    },
    lightColors:
        values.variantMode === 'dual'
            ? {
                  primary: values.lightColors.primary,
                  secondary: values.lightColors.secondary,
                  accent: values.lightColors.accent,
                  background: values.lightColors.background,
                  body: values.lightColors.body,
                  text: values.lightColors.text,
                  muted: values.lightColors.muted,
                  button: values.lightColors.button,
                  buttonText: values.lightColors.buttonText,
              }
            : null,
});

const ThemesContainer = () => {
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const DarkOakEmails = useStoreState(state => state.DarkOak.data?.emails);
    const updateDarkOak = useStoreActions(actions => actions.DarkOak.updateDarkOak);

    const [customThemes, setCustomThemes] = useState<EmailTheme[]>([]);
    const [defaultTheme, setDefaultTheme] = useState<EmailTheme | null>(null);
    const [loading, setLoading] = useState(true);
    const [modalVisible, setModalVisible] = useState(false);
    const [editing, setEditing] = useState<EmailTheme | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const defaults = useMemo(() => DarkOakEmails?.defaults, [DarkOakEmails]);

    const fetchThemes = useCallback(() => {
        setLoading(true);
        getEmailThemes()
            .then(fetched => {
                const currentDefault = fetched.find(theme => theme.isDefault) ?? null;
                setDefaultTheme(currentDefault);
                setCustomThemes(fetched.filter(theme => !theme.isDefault).sort((a, b) => a.name.localeCompare(b.name)));
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:themes', error }))
            .finally(() => setLoading(false));
    }, [clearAndAddHttpError]);

    useEffect(() => {
        fetchThemes();
    }, [fetchThemes]);

    const colorFieldToDefaultKey: Record<keyof ThemeFormValues['colors'], string> = {
        primary: 'primary_color',
        secondary: 'secondary_color',
        accent: 'accent_color',
        background: 'background_color',
        body: 'body_color',
        text: 'text_color',
        muted: 'muted_text_color',
        button: 'button_color',
        buttonText: 'button_text_color',
    };

    const lightFieldToDefaultKey: Record<keyof ThemeFormValues['colors'], string> = {
        primary: 'primary',
        secondary: 'secondary',
        accent: 'accent',
        background: 'background',
        body: 'body',
        text: 'text',
        muted: 'muted',
        button: 'button',
        buttonText: 'button_text',
    };

    const getDefaultColor = (key: keyof ThemeFormValues['colors']) => {
        if (!defaults) return undefined;
        const mapKey = colorFieldToDefaultKey[key] as keyof typeof defaults;
        return defaults[mapKey] as string | undefined;
    };

    const getDefaultLightColor = (key: keyof ThemeFormValues['colors']) => {
        if (!defaults?.light_palette) return undefined;
        const mapKey = lightFieldToDefaultKey[key] as keyof typeof defaults.light_palette;
        return defaults.light_palette?.[mapKey] as string | undefined;
    };

    const getInitialValues = (): ThemeFormValues => {
        if (editing) {
            return {
                name: editing.name,
                description: editing.description ?? '',
                logoUrl: editing.logoUrl ?? '',
                footerText: editing.footerText ?? '',
                variantMode: editing.variantMode ?? 'single',
                colors: {
                    primary: editing.colors.primary,
                    secondary: editing.colors.secondary,
                    accent: editing.colors.accent,
                    background: editing.colors.background,
                    body: editing.colors.body,
                    text: editing.colors.text,
                    muted: editing.colors.muted,
                    button: editing.colors.button,
                    buttonText: editing.colors.buttonText,
                },
                lightColors: {
                    primary: editing.lightColors?.primary ?? fallbackLightColors.primary,
                    secondary: editing.lightColors?.secondary ?? fallbackLightColors.secondary,
                    accent: editing.lightColors?.accent ?? fallbackLightColors.accent,
                    background: editing.lightColors?.background ?? fallbackLightColors.background,
                    body: editing.lightColors?.body ?? fallbackLightColors.body,
                    text: editing.lightColors?.text ?? fallbackLightColors.text,
                    muted: editing.lightColors?.muted ?? fallbackLightColors.muted,
                    button: editing.lightColors?.button ?? fallbackLightColors.button,
                    buttonText: editing.lightColors?.buttonText ?? fallbackLightColors.buttonText,
                },
                setDefault: editing.isDefault,
            };
        }

        return {
            name: '',
            description: '',
            logoUrl: '',
            footerText: '',
            variantMode: defaults?.variant_mode === 'dual' ? 'dual' : 'single',
            colors: {
                primary: getDefaultColor('primary') ?? fallbackColors.primary,
                secondary: getDefaultColor('secondary') ?? fallbackColors.secondary,
                accent: getDefaultColor('accent') ?? fallbackColors.accent,
                background: getDefaultColor('background') ?? fallbackColors.background,
                body: getDefaultColor('body') ?? fallbackColors.body,
                text: getDefaultColor('text') ?? fallbackColors.text,
                muted: getDefaultColor('muted') ?? fallbackColors.muted,
                button: getDefaultColor('button') ?? fallbackColors.button,
                buttonText: getDefaultColor('buttonText') ?? fallbackColors.buttonText,
            },
            lightColors: {
                primary: getDefaultLightColor('primary') ?? fallbackLightColors.primary,
                secondary: getDefaultLightColor('secondary') ?? fallbackLightColors.secondary,
                accent: getDefaultLightColor('accent') ?? fallbackLightColors.accent,
                background: getDefaultLightColor('background') ?? fallbackLightColors.background,
                body: getDefaultLightColor('body') ?? fallbackLightColors.body,
                text: getDefaultLightColor('text') ?? fallbackLightColors.text,
                muted: getDefaultLightColor('muted') ?? fallbackLightColors.muted,
                button: getDefaultLightColor('button') ?? fallbackLightColors.button,
                buttonText: getDefaultLightColor('buttonText') ?? fallbackLightColors.buttonText,
            },
            setDefault: customThemes.length === 0,
        };
    };

    const handleModalClose = () => {
        setModalVisible(false);
    };

    const syncAfterSave = (saved: EmailTheme) => {
        if (saved.isDefault) {
            setDefaultTheme(saved);
            setCustomThemes(prev => prev.filter(theme => theme.uuid !== saved.uuid));
            updateDarkOak({
                emails: {
                    enabled: DarkOakEmails?.enabled ?? true,
                    defaultTheme: saved.uuid,
                    defaults: {
                        name: saved.name,
                        primary_color: saved.colors.primary,
                        secondary_color: saved.colors.secondary,
                        accent_color: saved.colors.accent,
                        background_color: saved.colors.background,
                        body_color: saved.colors.body,
                        text_color: saved.colors.text,
                        muted_text_color: saved.colors.muted,
                        button_color: saved.colors.button,
                        button_text_color: saved.colors.buttonText,
                        footer_text: saved.footerText ?? DarkOakEmails?.defaults.footer_text ?? '',
                        variant_mode: saved.variantMode,
                        light_palette: saved.lightColors
                            ? {
                                  primary: saved.lightColors.primary ?? saved.colors.primary,
                                  secondary: saved.lightColors.secondary ?? saved.colors.secondary,
                                  accent: saved.lightColors.accent ?? saved.colors.accent,
                                  background: saved.lightColors.background ?? saved.colors.background,
                                  body: saved.lightColors.body ?? saved.colors.body,
                                  text: saved.lightColors.text ?? saved.colors.text,
                                  muted: saved.lightColors.muted ?? saved.colors.muted,
                                  button: saved.lightColors.button ?? saved.colors.button,
                                  button_text: saved.lightColors.buttonText ?? saved.colors.buttonText,
                              }
                            : null,
                    },
                },
            });
            return;
        }

        setCustomThemes(prev => {
            const withoutSaved = prev.filter(theme => theme.uuid !== saved.uuid);
            return [...withoutSaved, saved].sort((a, b) => a.name.localeCompare(b.name));
        });
    };

    const openCreate = () => {
        clearFlashes('admin:emails:themes');
        setEditing(null);
        setModalVisible(true);
    };

    const openEdit = (theme: EmailTheme) => {
        clearFlashes('admin:emails:themes');
        setEditing(theme);
        setModalVisible(true);
    };

    const handleDelete = (theme: EmailTheme) => {
        if (!window.confirm(`Delete the "${theme.name}" theme?`)) return;

        setSubmitting(true);
        deleteEmailTheme(theme.uuid)
            .then(() => {
                setCustomThemes(prev => prev.filter(item => item.uuid !== theme.uuid));
                addFlash({
                    key: 'admin:emails:themes',
                    type: 'success',
                    message: 'Theme deleted successfully.',
                });
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:themes', error }))
            .finally(() => setSubmitting(false));
    };

    const handleSetDefault = (theme: EmailTheme) => {
        setSubmitting(true);
        updateEmailTheme(theme.uuid, { setDefault: true })
            .then(updated => {
                syncAfterSave(updated);
                addFlash({
                    key: 'admin:emails:themes',
                    type: 'success',
                    message: 'Default theme updated.',
                });
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:themes', error }))
            .finally(() => setSubmitting(false));
    };

    return (
        <div>
            <FlashMessageRender byKey={'admin:emails:themes'} className={'mb-4'} />

            <div css={tw`flex flex-wrap items-center gap-3 mb-6`}>
                <Button onClick={openCreate}>New Theme</Button>
                <Button.Text onClick={() => fetchThemes()} disabled={loading}>
                    Refresh
                </Button.Text>
            </div>

            <div css={tw`grid gap-6 md:grid-cols-2 xl:grid-cols-3 mb-6`}>
                <AdminBox title={defaultTheme?.name ?? defaults?.name ?? 'Default Email Theme'} icon={faPalette}>
                    <div css={tw`flex flex-wrap gap-2 mb-4`}>
                        <Pill type={'success'}>Synchronized</Pill>
                        <Pill type={defaults?.variant_mode === 'dual' ? 'info' : 'warn'}>
                            {defaults?.variant_mode === 'dual' ? 'Dual palette' : 'Single palette'}
                        </Pill>
                        {defaultTheme?.updatedAt ? (
                            <Pill type={'info'}>Updated {defaultTheme.updatedAt.toLocaleDateString()}</Pill>
                        ) : null}
                    </div>
                    <p css={tw`text-sm text-theme-secondary mb-4`}>
                        Automatically mirrors the panel theme. Create a new mail theme to customize colors without
                        altering this default.
                    </p>
                    <div css={tw`grid grid-cols-2 gap-3 mb-4`}>
                        {colorFields.map(({ key, label }) => {
                            const darkValue = getDefaultColor(key) ?? '#000000';
                            return (
                                <div key={`default-${key}`} css={tw`flex items-center gap-2`}>
                                    <span
                                        css={tw`h-6 w-6 rounded border border-neutral-600`}
                                        style={{ backgroundColor: darkValue }}
                                    />
                                    <div>
                                        <p css={tw`text-xs text-theme-muted`}>{label}</p>
                                        <p css={tw`text-xs font-mono text-theme-secondary`}>{darkValue}</p>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                    {defaults?.variant_mode === 'dual' && defaults?.light_palette ? (
                        <div>
                            <p css={tw`text-xs uppercase tracking-wide text-theme-muted mb-2`}>Light mode</p>
                            <div css={tw`grid grid-cols-2 gap-3`}>
                                {colorFields.map(({ key, label }) => {
                                    const lightValue = getDefaultLightColor(key) ?? getDefaultColor(key) ?? '#000000';
                                    return (
                                        <div key={`default-light-${key}`} css={tw`flex items-center gap-2`}>
                                            <span
                                                css={tw`h-6 w-6 rounded border border-neutral-600`}
                                                style={{ backgroundColor: lightValue }}
                                            />
                                            <div>
                                                <p css={tw`text-xs text-theme-muted`}>{label}</p>
                                                <p css={tw`text-xs font-mono text-theme-secondary`}>{lightValue}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    ) : null}
                </AdminBox>
            </div>

            {loading ? (
                <div css={tw`flex items-center justify-center py-12`}>
                    <Spinner />
                </div>
            ) : customThemes.length === 0 ? (
                <AdminBox title={'No themes yet'} icon={faPalette}>
                    <p css={tw`text-sm text-theme-muted`}>
                        Create your first theme to control colors, branding, and footer copy for all outbound emails.
                    </p>
                    <Button className={'mt-4'} onClick={openCreate}>
                        Create Theme
                    </Button>
                </AdminBox>
            ) : (
                <div css={tw`grid gap-6 md:grid-cols-2 xl:grid-cols-3`}>
                    {customThemes.map(theme => (
                        <AdminBox key={theme.uuid} title={theme.name} icon={faPalette}>
                            <div css={tw`flex items-center justify-between mb-3`}>
                                <span css={tw`text-xs text-theme-muted`}>UUID</span>
                                <code css={tw`text-[10px] bg-neutral-800 px-2 py-1 rounded`}>{theme.uuid}</code>
                            </div>
                            {theme.description && (
                                <p css={tw`text-sm text-theme-secondary mb-3`}>{theme.description}</p>
                            )}
                            <div css={tw`flex flex-wrap gap-2 mb-4`}>
                                {theme.isDefault ? <Pill type={'success'}>Default</Pill> : null}
                                <Pill type={theme.variantMode === 'dual' ? 'info' : 'warn'}>
                                    {theme.variantMode === 'dual' ? 'Dual palette' : 'Single palette'}
                                </Pill>
                                <Pill type={'info'}>Updated {theme.updatedAt?.toLocaleDateString() || '—'}</Pill>
                            </div>
                            <div css={tw`grid grid-cols-2 gap-3 mb-4`}>
                                {colorFields.map(({ key, label }) => (
                                    <div key={key} css={tw`flex items-center gap-2`}>
                                        <span
                                            css={tw`h-6 w-6 rounded border border-neutral-600`}
                                            style={{ backgroundColor: theme.colors[key] }}
                                        />
                                        <div>
                                            <p css={tw`text-xs text-theme-muted`}>{label}</p>
                                            <p css={tw`text-xs font-mono text-theme-secondary`}>{theme.colors[key]}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            {theme.variantMode === 'dual' && theme.lightColors ? (
                                <div css={tw`mt-4`}>
                                    <p css={tw`text-xs uppercase tracking-wide text-theme-muted mb-2`}>Light mode</p>
                                    <div css={tw`grid grid-cols-2 gap-3`}>
                                        {colorFields.map(({ key, label }) => (
                                            <div key={`light-${key}`} css={tw`flex items-center gap-2`}>
                                                <span
                                                    css={tw`h-6 w-6 rounded border border-neutral-600`}
                                                    style={{
                                                        backgroundColor: theme.lightColors?.[key] ?? theme.colors[key],
                                                    }}
                                                />
                                                <div>
                                                    <p css={tw`text-xs text-theme-muted`}>{label}</p>
                                                    <p css={tw`text-xs font-mono text-theme-secondary`}>
                                                        {theme.lightColors?.[key] ?? theme.colors[key] ?? '—'}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ) : null}
                            <div css={tw`flex flex-wrap gap-2`}>
                                <Button onClick={() => openEdit(theme)} disabled={submitting}>
                                    Edit
                                </Button>
                                <Button.Text
                                    onClick={() => handleSetDefault(theme)}
                                    disabled={theme.isDefault || submitting}
                                >
                                    Set as Default
                                </Button.Text>
                                <Button.Danger onClick={() => handleDelete(theme)} disabled={submitting}>
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
                dismissable={!submitting}
                showSpinnerOverlay={submitting}
            >
                <Formik
                    enableReinitialize
                    initialValues={getInitialValues()}
                    validationSchema={formSchema}
                    onSubmit={async (values, helpers) => {
                        helpers.setSubmitting(true);
                        setSubmitting(true);
                        clearFlashes('admin:emails:themes');

                        try {
                            const payload = mapFormToPayload(values);
                            const saved = editing
                                ? await updateEmailTheme(editing.uuid, payload)
                                : await createEmailTheme(payload);

                            syncAfterSave(saved);
                            addFlash({
                                key: 'admin:emails:themes',
                                type: 'success',
                                message: editing ? 'Theme updated successfully.' : 'Theme created successfully.',
                            });
                            setEditing(null);
                            setModalVisible(false);
                        } catch (error) {
                            clearAndAddHttpError({ key: 'admin:emails:themes', error });
                        } finally {
                            helpers.setSubmitting(false);
                            setSubmitting(false);
                        }
                    }}
                >
                    {({ handleChange, isSubmitting, values }) => (
                        <Form>
                            <h2 css={tw`text-2xl font-semibold text-theme-primary mb-4`}>
                                {editing ? 'Edit Theme' : 'Create Theme'}
                            </h2>
                            <p css={tw`text-sm text-theme-muted mb-6`}>
                                Define brand colors, optional logo, and footer text applied to email templates using
                                this theme.
                            </p>

                            <FieldRow>
                                <Field
                                    id={'name'}
                                    name={'name'}
                                    label={'Name'}
                                    type={'text'}
                                    placeholder={'Brand theme'}
                                />
                                <Field
                                    id={'description'}
                                    name={'description'}
                                    label={'Description'}
                                    type={'text'}
                                    placeholder={'Optional helper text'}
                                />
                                <Field
                                    id={'logoUrl'}
                                    name={'logoUrl'}
                                    label={'Logo URL'}
                                    type={'text'}
                                    placeholder={'https://example.com/logo.png'}
                                />
                                <Field
                                    id={'footerText'}
                                    name={'footerText'}
                                    label={'Footer Text'}
                                    type={'text'}
                                    placeholder={'Shown under email content'}
                                />
                            </FieldRow>

                            <SelectField
                                id={'variantMode'}
                                name={'variantMode'}
                                label={'Palette Mode'}
                                options={variantOptions}
                                className={'mb-6'}
                                placeholder={'Select palette mode'}
                                description={
                                    'Choose whether this theme provides a single dark palette or a dedicated light variant for recipients who prefer light mode.'
                                }
                            />

                            <div css={tw`grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6`}>
                                {colorFields.map(({ key, label }) => (
                                    <Field
                                        key={key}
                                        id={`colors.${key}`}
                                        name={`colors.${key}`}
                                        label={label}
                                        type={'color'}
                                        onChange={handleChange}
                                    />
                                ))}
                            </div>

                            {values.variantMode === 'dual' ? (
                                <>
                                    <h3 css={tw`text-lg font-semibold text-theme-secondary mb-3`}>
                                        Light Mode Palette
                                    </h3>
                                    <div css={tw`grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6`}>
                                        {colorFields.map(({ key, label }) => (
                                            <Field
                                                key={`light-${key}`}
                                                id={`lightColors.${key}`}
                                                name={`lightColors.${key}`}
                                                label={`${label} (light)`}
                                                type={'color'}
                                                onChange={handleChange}
                                            />
                                        ))}
                                    </div>
                                </>
                            ) : null}

                            <FormikSwitch
                                name={'setDefault'}
                                label={'Make default theme'}
                                description={
                                    'Applies this theme to all emails unless a template selects another theme.'
                                }
                            />

                            <div css={tw`flex flex-wrap justify-end gap-3 mt-8`}>
                                <Button.Text type={'button'} onClick={handleModalClose} disabled={isSubmitting}>
                                    Cancel
                                </Button.Text>
                                <Button type={'submit'} disabled={isSubmitting}>
                                    {editing ? 'Save changes' : 'Create theme'}
                                </Button>
                            </div>
                        </Form>
                    )}
                </Formik>
            </Modal>
        </div>
    );
};

export default ThemesContainer;
