import { useEffect, useMemo, useState } from 'react';
import tw from 'twin.macro';
import AdminBox from '@elements/AdminBox';
import FlashMessageRender from '@/components/FlashMessageRender';
import Switch from '@elements/Switch';
import Select from '@elements/Select';
import { Button } from '@elements/button';
import Spinner from '@elements/Spinner';
import useFlash from '@/plugins/useFlash';
import { useStoreActions, useStoreState } from '@/state/hooks';
import { getEmailSettings, getEmailThemes, updateEmailSetting, type EmailSettingsResponse } from '@/api/admin/emails';
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

    useEffect(() => {
        Promise.all([getEmailSettings(), getEmailThemes()])
            .then(([loadedSettings, loadedThemes]) => {
                setSettings(loadedSettings);
                setThemes(loadedThemes);
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
        };

        updateDarkOak({
            emails: {
                enabled: partial.enabled ?? base.enabled,
                defaultTheme: partial.defaultTheme ?? base.defaultTheme ?? null,
                defaults: partial.defaults ?? base.defaults,
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
        </div>
    );
};

export default SettingsContainer;
