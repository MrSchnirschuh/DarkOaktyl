import { useCallback, useEffect, useMemo, useState } from 'react';
import AdminBox from '@elements/AdminBox';
import Spinner from '@elements/Spinner';
import { Button } from '@elements/button';
import useFlash from '@/plugins/useFlash';
import { getEmailTemplates, previewEmailTemplate, type EmailTemplatePreview } from '@/api/admin/emails/templates';
import type { EmailTemplate } from '@definitions/admin/models';
import type { ThemeMode } from '@/api/admin/theme/getPalette';
import { faEnvelope } from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from '@/state/hooks';
import { deriveThemeVariables, type DerivedThemeVariablesResult } from '@/theme/deriveThemeVariables';

const EMAIL_PREVIEW_FLASH_KEY = 'theme:emailPreview';

const normalizeHtmlForMode = (html: string, mode: ThemeMode): string =>
    html.replace(/data-color-mode="responsive"/gi, `data-color-mode="${mode}"`);

type EmailPaletteOverrides = Record<ThemeMode, DerivedThemeVariablesResult['emailPalette']>;

const buildModeCss = (mode: ThemeMode, palette: DerivedThemeVariablesResult['emailPalette']): string => {
    const headerText = mode === 'light' ? '#0f172a' : '#ffffff';

    return `
body[data-color-mode='${mode}'] {
    background-color: ${palette.background_color} !important;
    color: ${palette.text_color} !important;
}
body[data-color-mode='${mode}'] .email-wrapper {
    background-color: ${palette.background_color} !important;
}
body[data-color-mode='${mode}'] .email-card {
    background-color: ${palette.body_color} !important;
    color: ${palette.text_color} !important;
}
body[data-color-mode='${mode}'] .email-body {
    color: ${palette.text_color} !important;
}
body[data-color-mode='${mode}'] .email-footer {
    color: ${palette.muted_text_color} !important;
}
body[data-color-mode='${mode}'] .email-button {
    background-color: ${palette.button_color} !important;
    color: ${palette.button_text_color} !important;
}
body[data-color-mode='${mode}'] .email-header {
    background: linear-gradient(135deg, ${palette.primary_color}, ${palette.secondary_color}) !important;
    color: ${headerText} !important;
}
`;
};

const injectEmailPaletteOverrides = (html: string, palettes: EmailPaletteOverrides | null): string => {
    if (!palettes?.dark || !palettes?.light) {
        return html;
    }

    const overrides = `${buildModeCss('dark', palettes.dark)}${buildModeCss('light', palettes.light)}`;
    const styleTag = `<style data-theme-email-preview>${overrides}</style>`;

    return html.includes('</head>') ? html.replace('</head>', `${styleTag}</head>`) : `${styleTag}${html}`;
};

interface Props {
    mode: ThemeMode;
    paletteVersion: number;
    className?: string;
}

const pickInitialTemplate = (templates: EmailTemplate[]): EmailTemplate | null => {
    if (!templates.length) return null;

    const overview = templates.find(template => template.key.toLowerCase().includes('overview'));
    if (overview) {
        return overview;
    }

    return templates[0] ?? null;
};

export default ({ mode, paletteVersion, className }: Props) => {
    const { clearAndAddHttpError, clearFlashes } = useFlash();
    const theme = useStoreState(state => state.theme.data);

    const [templates, setTemplates] = useState<EmailTemplate[]>([]);
    const [selectedUuid, setSelectedUuid] = useState<string>('');
    const [loadingTemplates, setLoadingTemplates] = useState<boolean>(false);
    const [loadingPreview, setLoadingPreview] = useState<boolean>(false);
    const [preview, setPreview] = useState<EmailTemplatePreview | null>(null);

    const loadTemplates = useCallback(async () => {
        setLoadingTemplates(true);
        try {
            const list = await getEmailTemplates();
            setTemplates(list);

            setSelectedUuid(prev => {
                if (prev && list.some(template => template.uuid === prev)) {
                    return prev;
                }

                const initial = pickInitialTemplate(list);
                return initial ? initial.uuid : '';
            });
        } catch (error) {
            clearAndAddHttpError({ key: EMAIL_PREVIEW_FLASH_KEY, error });
        } finally {
            setLoadingTemplates(false);
        }
    }, [clearAndAddHttpError]);

    useEffect(() => {
        loadTemplates();
    }, [loadTemplates]);

    const selectedTemplate = useMemo(
        () => templates.find(template => template.uuid === selectedUuid) ?? null,
        [templates, selectedUuid],
    );

    const loadPreview = useCallback(
        async (template: EmailTemplate) => {
            setLoadingPreview(true);
            try {
                clearFlashes(EMAIL_PREVIEW_FLASH_KEY);
                const data = await previewEmailTemplate({
                    subject: template.subject,
                    content: template.content,
                    themeUuid: template.themeUuid ?? undefined,
                    metadata: template.metadata ?? {},
                    locale: template.locale,
                    name: template.name,
                });

                setPreview(data);
            } catch (error) {
                clearAndAddHttpError({ key: EMAIL_PREVIEW_FLASH_KEY, error });
            } finally {
                setLoadingPreview(false);
            }
        },
        [clearAndAddHttpError, clearFlashes],
    );

    useEffect(() => {
        if (selectedTemplate) {
            loadPreview(selectedTemplate);
        } else {
            setPreview(null);
        }
    }, [selectedTemplate, loadPreview, paletteVersion]);

    const emailPalettes = useMemo<EmailPaletteOverrides | null>(() => {
        if (!theme) {
            return null;
        }

        return {
            dark: deriveThemeVariables(theme, 'dark').emailPalette,
            light: deriveThemeVariables(theme, 'light').emailPalette,
        };
    }, [theme]);

    const iframeHtml = useMemo(() => {
        if (!preview) {
            return null;
        }

        const normalized = normalizeHtmlForMode(preview.html, mode);
        return injectEmailPaletteOverrides(normalized, emailPalettes);
    }, [preview, mode, emailPalettes]);

    return (
        <AdminBox title={'Email Preview'} icon={faEnvelope} className={className}>
            <div className={'mb-4 space-y-2'}>
                <div className={'flex items-center justify-between gap-3'}>
                    <label htmlFor={'theme-email-preview-template'} className={'text-sm text-theme-secondary'}>
                        Template
                    </label>
                    <div className={'flex items-center gap-2'}>
                        <select
                            id={'theme-email-preview-template'}
                            className={
                                'rounded bg-neutral-800 border border-neutral-700 px-3 py-2 text-sm text-theme-secondary'
                            }
                            value={selectedUuid}
                            onChange={event => setSelectedUuid(event.target.value)}
                            disabled={loadingTemplates || (!templates.length && !selectedUuid)}
                        >
                            {templates.map(template => (
                                <option key={template.uuid} value={template.uuid}>
                                    {template.name}
                                </option>
                            ))}
                            {!templates.length && <option value="">No templates available</option>}
                        </select>
                        <Button.Text type={'button'} disabled={loadingTemplates} onClick={loadTemplates}>
                            Reload
                        </Button.Text>
                    </div>
                </div>
                <p className={'text-xs text-theme-muted'}>
                    Preview renders the selected template using the current default email theme palette.
                </p>
            </div>

            <div className={'relative w-full overflow-hidden rounded-lg border border-neutral-700 bg-neutral-900'}>
                {(loadingTemplates || loadingPreview) && (
                    <div
                        className={
                            'absolute inset-0 z-10 flex items-center justify-center bg-neutral-950/70 backdrop-blur-sm'
                        }
                    >
                        <Spinner />
                    </div>
                )}
                {iframeHtml ? (
                    <iframe
                        title={'Email template preview'}
                        srcDoc={iframeHtml}
                        className={'h-[480px] w-full border-0 bg-white'}
                        sandbox={'allow-same-origin allow-popups allow-forms'}
                    />
                ) : (
                    <div className={'flex h-[240px] items-center justify-center text-sm text-theme-muted'}>
                        {loadingTemplates || loadingPreview
                            ? 'Loading previewâ€¦'
                            : 'Select a template to see the rendered email.'}
                    </div>
                )}
            </div>

            {preview && (
                <div className={'mt-3 text-xs text-theme-muted'}>
                    <span className={'font-semibold text-theme-secondary'}>Subject:</span> {preview.subject}
                </div>
            )}
        </AdminBox>
    );
};
