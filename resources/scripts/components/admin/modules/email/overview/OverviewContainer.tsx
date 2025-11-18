import { useEffect, useMemo, useState } from 'react';
import tw from 'twin.macro';
import AdminBox from '@elements/AdminBox';
import Spinner from '@elements/Spinner';
import Pill from '@elements/Pill';
import { getEmailSettings, EmailSettingsResponse } from '@/api/admin/emails';

const colorTitles: Record<string, string> = {
    primary_color: 'Primary',
    secondary_color: 'Secondary',
    accent_color: 'Accent',
    background_color: 'Background',
    body_color: 'Body',
    text_color: 'Text',
    muted_text_color: 'Muted Text',
    button_color: 'Button',
    button_text_color: 'Button Text',
};

const OverviewContainer = () => {
    const [settings, setSettings] = useState<EmailSettingsResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        getEmailSettings()
            .then(setSettings)
            .catch(err => setError(err.message ?? 'Unable to load email settings.'))
            .finally(() => setLoading(false));
    }, []);

    const defaults = useMemo(() => settings?.defaults ?? null, [settings]);

    if (loading) {
        return (
            <div css={tw`flex items-center justify-center py-12`}>
                <Spinner />
            </div>
        );
    }

    if (error) {
        return (
            <div css={tw`bg-red-500/10 border border-red-500/40 rounded px-4 py-3 text-sm text-red-200`}>{error}</div>
        );
    }

    if (!settings) {
        return null;
    }

    return (
        <div css={tw`space-y-6`}>
            <div css={tw`grid gap-6 lg:grid-cols-2`}>
                <AdminBox title={'Module Status'}>
                    <div css={tw`flex items-center justify-between`}>
                        <span css={tw`text-sm text-theme-secondary`}>Email sending</span>
                        <Pill type={settings.enabled ? 'success' : 'danger'}>
                            {settings.enabled ? 'Enabled' : 'Disabled'}
                        </Pill>
                    </div>
                    <div css={tw`flex items-center justify-between mt-4`}>
                        <span css={tw`text-sm text-theme-secondary`}>Default theme UUID</span>
                        <span css={tw`text-xs text-theme-muted font-mono`}>{settings.defaultTheme || 'auto'}</span>
                    </div>
                    <p css={tw`text-xs text-theme-muted mt-4 leading-relaxed`}>
                        Emails are queued using your configured mail driver. Scheduled and event-driven triggers will
                        only run when the module is enabled.
                    </p>
                </AdminBox>
                <AdminBox title={'Library Summary'}>
                    <dl css={tw`grid grid-cols-2 gap-y-3 gap-x-4 text-sm`}>
                        <div>
                            <dt css={tw`text-theme-muted`}>Themes</dt>
                            <dd css={tw`text-theme-secondary font-semibold`}>{settings.counts.themes}</dd>
                        </div>
                        <div>
                            <dt css={tw`text-theme-muted`}>Templates</dt>
                            <dd css={tw`text-theme-secondary font-semibold`}>{settings.counts.templates}</dd>
                        </div>
                        <div>
                            <dt css={tw`text-theme-muted`}>Triggers</dt>
                            <dd css={tw`text-theme-secondary font-semibold`}>{settings.counts.triggers}</dd>
                        </div>
                    </dl>
                    <p css={tw`text-xs text-theme-muted mt-4 leading-relaxed`}>
                        Use themes to control branding, templates to craft messages, and triggers to automate delivery
                        for lifecycle events, scheduled broadcasts, coupons, or resource drops.
                    </p>
                </AdminBox>
            </div>

            {defaults && (
                <AdminBox title={'Default Styling'}>
                    <p css={tw`text-xs text-theme-muted mb-4 leading-relaxed`}>
                        These colors seed new themes and are applied when no custom theme has been selected. Update them
                        under the settings tab or by editing your default theme.
                    </p>
                    <div css={tw`grid sm:grid-cols-3 gap-4`}>
                        {Object.entries(defaults)
                            .filter(([key]) => key.endsWith('_color'))
                            .map(([key, value]) => (
                                <div key={key} css={tw`flex items-center space-x-3`}>
                                    <span
                                        css={tw`h-8 w-8 rounded border border-neutral-600 shadow-inner`}
                                        style={{ backgroundColor: value as string }}
                                    />
                                    <div>
                                        <p css={tw`text-sm text-theme-secondary font-semibold`}>
                                            {colorTitles[key] ?? key}
                                        </p>
                                        <p css={tw`text-xs text-theme-muted font-mono`}>{value}</p>
                                    </div>
                                </div>
                            ))}
                    </div>
                </AdminBox>
            )}

            <AdminBox title={'Getting Started'}>
                <ol css={tw`list-decimal list-inside space-y-2 text-sm text-theme-secondary`}>
                    <li>Create a theme that matches your brand colors.</li>
                    <li>Build or import email templates for onboarding, coupons, or resource notifications.</li>
                    <li>Configure triggers to dispatch templates on events or on a schedule.</li>
                    <li>Verify your mail driver credentials and ensure the queue worker is running.</li>
                </ol>
            </AdminBox>
        </div>
    );
};

export default OverviewContainer;
