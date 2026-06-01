import { FormEvent, useEffect, useMemo, useState } from 'react';
import tw from 'twin.macro';
import AdminBox from '@elements/AdminBox';
import FlashMessageRender from '@/components/FlashMessageRender';
import Switch from '@elements/Switch';
import Select from '@elements/Select';
import { Button } from '@elements/button';
import Spinner from '@elements/Spinner';
import Input from '@elements/Input';
import Label from '@elements/Label';
import useFlash from '@/plugins/useFlash';
import { useStoreActions, useStoreState } from '@/state/hooks';
import {
    getEmailSettings,
    getEmailThemes,
    updateEmailEnvironment,
    updateEmailSetting,
    type EmailEnvironmentSettings,
    type EmailSettingsResponse,
} from '@/api/admin/emails';
import type { DarkOakSettings } from '@/state/DarkOak';
import type { EmailTheme } from '@definitions/admin/models';

const SettingsContainer = () => {
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const updateDarkOak = useStoreActions(actions => actions.DarkOak.updateDarkOak);
    const DarkOakEmails = useStoreState(state => state.DarkOak.data?.emails);

    const [settings, setSettings] = useState<EmailSettingsResponse | null>(null);
    const [themes, setThemes] = useState<EmailTheme[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshingThemes, setRefreshingThemes] = useState(false);
    const [environment, setEnvironment] = useState<EmailEnvironmentSettings | null>(null);
    const [savingEnvironment, setSavingEnvironment] = useState(false);

    useEffect(() => {
        Promise.all([getEmailSettings(), getEmailThemes()])
            .then(([loadedSettings, loadedThemes]) => {
                setSettings(loadedSettings);
                setThemes(loadedThemes);
                setEnvironment(loadedSettings.environment);
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'admin:emails:settings', error });
            })
            .finally(() => setLoading(false));
    }, []);

    const currentDefaultUuid = useMemo(() => settings?.defaultTheme ?? null, [settings]);

    const syncDarkOakStore = (partial: Partial<DarkOakSettings['emails']>) => {
        if (!settings) return;

        const base: DarkOakSettings['emails'] = {
            enabled: DarkOakEmails?.enabled ?? settings.enabled,
            defaultTheme: DarkOakEmails?.defaultTheme ?? settings.defaultTheme,
            defaults: DarkOakEmails?.defaults ?? settings.defaults,
            environment: DarkOakEmails?.environment ?? settings.environment,
        };

        updateDarkOak({
            emails: {
                enabled: partial.enabled ?? base.enabled,
                defaultTheme: partial.defaultTheme ?? base.defaultTheme ?? null,
                defaults: partial.defaults ?? base.defaults,
                environment: partial.environment ?? base.environment ?? null,
            },
        });
    };

    const handleToggleModule = (value: boolean) => {
        if (!settings) return;

        clearFlashes('admin:emails:settings');
        updateEmailSetting('enabled', value)
            .then(() => {
                setSettings(prev => (prev ? { ...prev, enabled: value } : prev));
                syncDarkOakStore({ enabled: value });
                addFlash({
                    key: 'admin:emails:settings',
                    type: 'success',
                    message: value ? 'Email module enabled.' : 'Email module disabled.',
                });
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:settings', error }));
    };

    const handleDefaultThemeChange = (uuid: string | null) => {
        if (!settings) return;

        clearFlashes('admin:emails:settings');
        updateEmailSetting('default_theme', uuid ?? '')
            .then(() => {
                setSettings(prev => (prev ? { ...prev, defaultTheme: uuid } : prev));
                setThemes(prev => prev.map(theme => ({ ...theme, isDefault: uuid ? theme.uuid === uuid : false })));
                syncDarkOakStore({ defaultTheme: uuid ?? null });
                addFlash({
                    key: 'admin:emails:settings',
                    type: 'success',
                    message: 'Default email theme updated.',
                });
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:settings', error }));
    };

    const refreshThemeList = () => {
        setRefreshingThemes(true);
        getEmailThemes()
            .then(setThemes)
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:settings', error }))
            .finally(() => setRefreshingThemes(false));
    };

    const handleEnvironmentChange = (field: keyof EmailEnvironmentSettings, value: string) => {
        setEnvironment(prev => (prev ? { ...prev, [field]: value } : prev));
    };

    const handleEnvironmentSubmit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!environment) return;

        clearFlashes('admin:emails:settings');
        setSavingEnvironment(true);

        updateEmailEnvironment(environment)
            .then(() => {
                setSettings(prev => (prev ? { ...prev, environment } : prev));
                syncDarkOakStore({ environment });
                addFlash({
                    key: 'admin:emails:settings',
                    type: 'success',
                    message: 'Mail driver settings updated.',
                });
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:emails:settings', error }))
            .finally(() => setSavingEnvironment(false));
    };

    if (loading || !settings) {
        return (
            <div css={tw`flex items-center justify-center py-12`}>
                <Spinner />
            </div>
        );
    }

    return (
        <div>
            <FlashMessageRender byKey={'admin:emails:settings'} className={'mb-4'} />
            <div css={tw`grid gap-6 xl:grid-cols-2`}>
                <AdminBox title={'Email Delivery'}>
                    <div css={tw`space-y-4`}>
                        <Switch
                            name={'email-module-enabled'}
                            defaultChecked={settings.enabled}
                            label={'Enable outbound emails'}
                            description={
                                'Toggle this setting to allow the panel to dispatch emails and process triggers. Make sure your queue worker is running.'
                            }
                            onChange={event => handleToggleModule(event.currentTarget.checked)}
                        />
                        <div css={tw`text-xs text-theme-muted leading-relaxed`}>
                            The module honors your Laravel mail configuration. Scheduled triggers run from the panel
                            scheduler when enabled.
                        </div>
                    </div>
                </AdminBox>

                <AdminBox title={'Default Theme'}>
                    <label css={tw`text-sm text-theme-secondary mb-2 block`}>Theme applied when none selected</label>
                    <Select
                        value={currentDefaultUuid ?? ''}
                        onChange={event => handleDefaultThemeChange(event.currentTarget.value || null)}
                    >
                        <option value={''}>Automatic (first published theme)</option>
                        {themes.map(theme => (
                            <option key={theme.uuid} value={theme.uuid}>
                                {theme.name}
                            </option>
                        ))}
                    </Select>
                    <div css={tw`flex items-center justify-between mt-4`}>
                        <span css={tw`text-xs text-theme-muted`}>
                            Setting a default theme ensures consistent branding for every template preview and dispatch.
                        </span>
                        <Button.Text size={Button.Sizes.Small} disabled={refreshingThemes} onClick={refreshThemeList}>
                            {refreshingThemes ? 'Refreshing…' : 'Refresh list'}
                        </Button.Text>
                    </div>
                </AdminBox>
            </div>

            {environment && (
                <AdminBox title={'Mail Transport Configuration'}>
                    <form onSubmit={handleEnvironmentSubmit} css={tw`space-y-4`}>
                        <div>
                            <Label>Mailer</Label>
                            <Select
                                value={environment.mailer}
                                onChange={event => handleEnvironmentChange('mailer', event.currentTarget.value)}
                                disabled={savingEnvironment}
                            >
                                {(settings.mailers.length ? settings.mailers : ['smtp']).map(mailer => (
                                    <option key={mailer} value={mailer}>
                                        {mailer.toUpperCase()}
                                    </option>
                                ))}
                            </Select>
                        </div>
                        <div css={tw`grid gap-4 md:grid-cols-2`}>
                            <div>
                                <Label>Host</Label>
                                <Input
                                    value={environment.host}
                                    onChange={event => handleEnvironmentChange('host', event.currentTarget.value)}
                                    placeholder={'smtp.example.com'}
                                    disabled={savingEnvironment}
                                />
                            </div>
                            <div>
                                <Label>Port</Label>
                                <Input
                                    value={environment.port}
                                    onChange={event => handleEnvironmentChange('port', event.currentTarget.value)}
                                    placeholder={'465'}
                                    disabled={savingEnvironment}
                                />
                            </div>
                        </div>
                        <div css={tw`grid gap-4 md:grid-cols-2`}>
                            <div>
                                <Label>Username</Label>
                                <Input
                                    value={environment.username}
                                    onChange={event => handleEnvironmentChange('username', event.currentTarget.value)}
                                    autoComplete={'off'}
                                    disabled={savingEnvironment}
                                />
                            </div>
                            <div>
                                <Label>Password</Label>
                                <Input
                                    type={'password'}
                                    value={environment.password}
                                    onChange={event => handleEnvironmentChange('password', event.currentTarget.value)}
                                    autoComplete={'off'}
                                    disabled={savingEnvironment}
                                />
                            </div>
                        </div>
                        <div css={tw`grid gap-4 md:grid-cols-2`}>
                            <div>
                                <Label>Encryption</Label>
                                <Select
                                    value={environment.encryption || ''}
                                    onChange={event => handleEnvironmentChange('encryption', event.currentTarget.value)}
                                    disabled={savingEnvironment}
                                >
                                    <option value={''}>Automatic</option>
                                    <option value={'tls'}>TLS</option>
                                    <option value={'ssl'}>SSL</option>
                                    <option value={'starttls'}>STARTTLS</option>
                                </Select>
                            </div>
                            <div>
                                <Label>From Address</Label>
                                <Input
                                    value={environment.fromAddress}
                                    onChange={event =>
                                        handleEnvironmentChange('fromAddress', event.currentTarget.value)
                                    }
                                    placeholder={'noreply@example.com'}
                                    disabled={savingEnvironment}
                                />
                            </div>
                        </div>
                        <div>
                            <Label>From Name</Label>
                            <Input
                                value={environment.fromName}
                                onChange={event => handleEnvironmentChange('fromName', event.currentTarget.value)}
                                placeholder={'DarkOaktyl'}
                                disabled={savingEnvironment}
                            />
                            <p css={tw`text-xs text-theme-muted mt-2`}>
                                These values mirror the entries in your <code>.env</code> file and update it when saved.
                                Make sure queue workers are restarted after changing credentials.
                            </p>
                        </div>
                        <div css={tw`text-right`}>
                            <Button type={'submit'} disabled={savingEnvironment}>
                                {savingEnvironment ? 'Saving…' : 'Save changes'}
                            </Button>
                        </div>
                    </form>
                </AdminBox>
            )}
        </div>
    );
};

export default SettingsContainer;
