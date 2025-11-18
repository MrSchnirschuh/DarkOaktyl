import { useCallback, useEffect, useMemo, useState } from 'react';
import type { ThemeMode, ThemePaletteResponse } from '@/api/admin/theme/getPalette';
import { fetchThemePalette } from '@/api/admin/theme/getPalette';
import { updateThemePalette, type ThemePaletteDraft } from '@/api/admin/theme/updatePalette';
import resetTheme from '@/api/admin/theme/resetTheme';
import useFlash from '@/plugins/useFlash';
import ThemeDesigner from '@admin/modules/theme/ThemeDesigner';
import Presets from '@admin/modules/theme/Presets';
import LogoSettings from '@admin/modules/theme/LogoSettings';
import Preview from '@admin/modules/theme/Preview';
import EmailPreview from '@admin/modules/theme/EmailPreview';
import AdminContentBlock from '@elements/AdminContentBlock';
import { Button } from '@elements/button';
import { Dialog } from '@elements/dialog';
import Spinner from '@elements/Spinner';
import { useStoreActions } from '@/state/hooks';
import type { SiteTheme, ThemePalette } from '@/state/theme';

type DraftState = ThemePaletteDraft;

const MODE_ORDER: ThemeMode[] = ['dark', 'light'];

function buildPayload(data: ThemePaletteResponse, draft: DraftState): ThemePaletteDraft {
    const payload: ThemePaletteDraft = { dark: {}, light: {} };

    MODE_ORDER.forEach(modeKey => {
        const baseTokens = {
            ...(data.defaults[modeKey] ?? {}),
            ...(data.modes[modeKey] ?? {}),
        } as Record<string, string | null>;

        const pendingOverrides = draft[modeKey] ?? {};

        const tokenKeys = new Set<string>([...Object.keys(baseTokens), ...Object.keys(pendingOverrides)]);

        tokenKeys.forEach(tokenKey => {
            const pending = pendingOverrides[tokenKey];
            if (typeof pending !== 'undefined') {
                payload[modeKey][tokenKey] = pending;
                return;
            }

            const baseValue = baseTokens[tokenKey];
            payload[modeKey][tokenKey] = typeof baseValue === 'string' ? baseValue : null;
        });
    });

    return payload;
}

function buildPreviewTheme(data: ThemePaletteResponse): SiteTheme {
    const colors = { ...(data.theme.colors as Record<string, string>) };

    MODE_ORDER.forEach(modeKey => {
        const suffix = `_${modeKey}`;
        const tokens = data.modes[modeKey] ?? {};

        Object.entries(tokens).forEach(([token, value]) => {
            if (!value) {
                return;
            }

            colors[`${token}${suffix}`] = value;
            if (modeKey === 'dark' || !colors[token]) {
                colors[token] = value;
            }
        });
    });

    const palettes = data.theme.palettes ?? {};

    return {
        ...data.theme,
        colors: colors as SiteTheme['colors'],
        palettes: {
            ...palettes,
            dark: { ...(palettes.dark ?? ({} as ThemePalette)), ...data.modes.dark } as ThemePalette,
            light: { ...(palettes.light ?? ({} as ThemePalette)), ...data.modes.light } as ThemePalette,
        },
    };
}

export default () => {
    const [palette, setPalette] = useState<ThemePaletteResponse | null>(null);
    const [draft, setDraft] = useState<DraftState>({ dark: {}, light: {} });
    const [activeGroup, setActiveGroup] = useState<string>('brand');
    const [mode, setMode] = useState<ThemeMode>('dark');
    const [fetching, setFetching] = useState<boolean>(true);
    const [saving, setSaving] = useState<boolean>(false);
    const [confirmReset, setConfirmReset] = useState<boolean>(false);
    const [previewReload, setPreviewReload] = useState<boolean>(false);
    const [paletteVersion, setPaletteVersion] = useState<number>(0);
    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();
    const setTheme = useStoreActions(actions => actions.theme.setTheme);

    const loadPalette = useCallback(async () => {
        setFetching(true);
        try {
            const data = await fetchThemePalette();
            setPalette(data);
            setDraft({ dark: {}, light: {} });
            setPreviewReload(false);
        } catch (error) {
            clearAndAddHttpError({ key: 'theme:colors', error });
        } finally {
            setFetching(false);
        }
    }, [clearAndAddHttpError]);

    useEffect(() => {
        loadPalette();
    }, [loadPalette]);

    const dirty = useMemo(() => {
        return (['dark', 'light'] as ThemeMode[]).some(modeKey => {
            const values = draft[modeKey] ?? {};
            return Object.keys(values).length > 0;
        });
    }, [draft]);

    useEffect(() => {
        if (palette) {
            setTheme(buildPreviewTheme(palette));
        }
    }, [palette, setTheme]);

    useEffect(() => {
        if (!palette) {
            return;
        }

        setPaletteVersion(prev => prev + 1);
    }, [palette]);

    const handleColorChange = useCallback(
        (modeKey: ThemeMode, token: string, value: string) => {
            setPalette(prev => {
                if (!prev) {
                    return prev;
                }

                const defaults = prev.defaults[modeKey] ?? {};
                const defaultValue = defaults[token];
                const nextOverrides = { ...(prev.overrides[modeKey] ?? {}) };
                nextOverrides[token] = defaultValue ? value !== defaultValue : true;

                return {
                    ...prev,
                    modes: {
                        ...prev.modes,
                        [modeKey]: {
                            ...(prev.modes[modeKey] ?? {}),
                            [token]: value,
                        },
                    },
                    overrides: {
                        ...prev.overrides,
                        [modeKey]: nextOverrides,
                    },
                };
            });

            setDraft(prev => {
                const next = { ...prev } as DraftState;
                const modeDraft = { ...(next[modeKey] ?? {}) };
                const defaultValue = palette?.defaults?.[modeKey]?.[token] ?? null;
                const hadOverride = palette?.overrides?.[modeKey]?.[token] ?? false;

                if (defaultValue && value === defaultValue) {
                    if (hadOverride) {
                        modeDraft[token] = null;
                    } else {
                        delete modeDraft[token];
                    }
                } else {
                    modeDraft[token] = value;
                }

                next[modeKey] = modeDraft;
                return next;
            });
        },
        [palette],
    );

    const handleResetColor = useCallback(
        (modeKey: ThemeMode, token: string) => {
            if (!palette) {
                return;
            }

            const defaultValue = palette.defaults[modeKey]?.[token] ?? '#000000';

            setPalette(prev => {
                if (!prev) {
                    return prev;
                }

                const nextOverrides = { ...(prev.overrides[modeKey] ?? {}) };
                nextOverrides[token] = false;

                return {
                    ...prev,
                    modes: {
                        ...prev.modes,
                        [modeKey]: {
                            ...(prev.modes[modeKey] ?? {}),
                            [token]: defaultValue,
                        },
                    },
                    overrides: {
                        ...prev.overrides,
                        [modeKey]: nextOverrides,
                    },
                };
            });

            setDraft(prev => {
                const next = { ...prev } as DraftState;
                const modeDraft = { ...(next[modeKey] ?? {}) };
                const hadOverride = palette.overrides?.[modeKey]?.[token] ?? false;

                if (hadOverride) {
                    modeDraft[token] = null;
                } else {
                    delete modeDraft[token];
                }

                next[modeKey] = modeDraft;
                return next;
            });
        },
        [palette],
    );

    const handlePresetReload = useCallback(
        (value: boolean) => {
            setPreviewReload(value);

            if (!value) {
                loadPalette();
            }
        },
        [loadPalette],
    );

    const handleSave = async () => {
        if (!palette || !dirty) {
            return;
        }

        setSaving(true);
        clearFlashes('theme:colors');

        const payload = buildPayload(palette, draft);

        try {
            const next = await updateThemePalette(payload);
            setPalette(next);
            setDraft({ dark: {}, light: {} });
            addFlash({ key: 'theme:colors', type: 'success', message: 'Theme palette saved successfully.' });
        } catch (error) {
            clearAndAddHttpError({ key: 'theme:colors', error });
        } finally {
            setSaving(false);
        }
    };

    const handleResetTheme = () => {
        setConfirmReset(false);
        clearFlashes('theme:colors');

        resetTheme()
            .then(() => {
                // @ts-expect-error location assignment is fine in admin context
                window.location = '/admin/theme';
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'theme:colors', error });
            });
    };

    return (
        <AdminContentBlock showFlashKey={'theme:colors'}>
            <Dialog.Confirm
                title={'Reset to defaults?'}
                open={confirmReset}
                onClose={() => setConfirmReset(false)}
                onConfirmed={handleResetTheme}
            >
                Performing this action will immediately wipe all of your custom theming settings. Only do this if you
                wish to return to the stock appearance of the panel. This action cannot be reversed.
            </Dialog.Confirm>

            <div className={'mb-8 flex flex-wrap items-center gap-4'}>
                <div className={'min-w-0 flex-1'}>
                    <h2 className={'text-2xl font-header font-medium text-theme-primary'}>System Theme</h2>
                    <p className={'text-theme-secondary'}>Design panel and email colours from a single palette.</p>
                </div>

                <div className={'flex items-center gap-3'}>
                    <div className={'flex items-center gap-2 rounded border border-theme-muted px-3 py-1'}>
                        <span className={'text-sm text-theme-secondary'}>Preview</span>
                        {(['dark', 'light'] as ThemeMode[]).map(modeKey => (
                            <button
                                key={modeKey}
                                type={'button'}
                                onClick={() => setMode(modeKey)}
                                className={`rounded px-3 py-1 text-sm ${
                                    mode === modeKey
                                        ? 'bg-theme-surface text-theme-primary'
                                        : 'bg-transparent text-theme-muted border border-transparent'
                                }`}
                            >
                                {modeKey === 'dark' ? 'Dark' : 'Light'}
                            </button>
                        ))}
                    </div>

                    <Button
                        type={'button'}
                        size={Button.Sizes.Large}
                        onClick={() => setConfirmReset(true)}
                        className={'h-10 px-4 py-0 whitespace-nowrap'}
                    >
                        Reset to Defaults
                    </Button>
                </div>
            </div>

            {fetching && (
                <div className={'flex justify-center py-20'}>
                    <Spinner size={'large'} />
                </div>
            )}

            {!fetching && palette && (
                <>
                    <div className={'flex flex-col gap-6 xl:flex-row xl:items-start'}>
                        <div className={'flex-1 min-w-0'}>
                            <ThemeDesigner
                                groups={palette.groups}
                                modes={palette.modes}
                                defaults={palette.defaults}
                                overrides={palette.overrides}
                                activeGroup={activeGroup}
                                onGroupChange={setActiveGroup}
                                onColorChange={handleColorChange}
                                onResetColor={handleResetColor}
                            />
                        </div>
                        <div className={'w-full xl:max-w-[520px] space-y-6'}>
                            <Preview reload={previewReload} mode={mode} size={'large'} className={'h-full'} />
                            <EmailPreview mode={mode} paletteVersion={paletteVersion} />
                        </div>
                    </div>

                    <div className={'mt-6 flex items-center justify-end gap-3'}>
                        <Button.Text type={'button'} disabled={fetching || saving} onClick={loadPalette}>
                            Revert changes
                        </Button.Text>
                        <Button type={'button'} disabled={!dirty || saving} onClick={handleSave}>
                            {saving ? 'Saving…' : 'Save changes'}
                        </Button>
                    </div>

                    <div className={'mt-10 grid gap-6 lg:grid-cols-2'}>
                        <LogoSettings />
                        <Presets setReload={handlePresetReload} />
                    </div>
                </>
            )}
        </AdminContentBlock>
    );
};
