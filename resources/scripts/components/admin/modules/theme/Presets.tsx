import { useState } from 'react';
import AdminBox from '@elements/AdminBox';
import Label from '@elements/Label';
import Input from '@elements/Input';
import { Button } from '@elements/button';
import { useStoreState } from '@/state/hooks';
import updateColors from '@/api/admin/theme/updateColors';
import deleteColor from '@/api/admin/theme/deleteColor';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import { faSave } from '@fortawesome/free-solid-svg-icons';

export default ({ setReload }: { setReload: (v: boolean) => void }) => {
    const colors = useStoreState(s => s.theme.data!.colors) as Record<string, string>;
    const [name, setName] = useState<string>('');
    const [saving, setSaving] = useState(false);
    const [startAt, setStartAt] = useState<string>('');
    const [endAt, setEndAt] = useState<string>('');
    const [isDefault, setIsDefault] = useState(false);
    const [applying, setApplying] = useState<string | null>(null);

    // collect stored presets from colors: keys beginning with presets:
    const presets: { key: string; value: string }[] = Object.keys(colors)
        .filter(k => k.startsWith('presets:'))
        .map(k => ({ key: k, value: (colors as any)[k] }))
        .filter(p => p.value && p.value.length > 0);

    const savePreset = async () => {
        if (!name) return;
        setSaving(true);

        // Only persist a small set of keys per requirements: primary, accent_primary, secondary and background
        const keysToSave = ['primary', 'accent_primary', 'secondary', 'background'];

        const buildMode = (mode: 'light' | 'dark') => {
            const out: Record<string, string> = {};
            keysToSave.forEach(k => {
                const modeKey = `${k}_${mode}`;
                out[k] = (colors as any)[modeKey] ?? (colors as any)[k] ?? '';
            });
            return out;
        };

        const presetObj = {
            name,
            createdAt: new Date().toISOString(),
            default: isDefault,
            schedule: {
                start: startAt ? new Date(startAt).toISOString() : null,
                end: endAt ? new Date(endAt).toISOString() : null,
            },
            modes: {
                light: {
                    ...buildMode('light'),
                    logo_panel: (colors as any)['logo_panel_light'] ?? (colors as any)['logo_panel'] ?? null,
                    logo_login: (colors as any)['logo_login_light'] ?? (colors as any)['logo_login'] ?? null,
                    background_image:
                        (colors as any)['background_image_light'] ?? (colors as any)['background_image'] ?? null,
                },
                dark: {
                    ...buildMode('dark'),
                    logo_panel: (colors as any)['logo_panel_dark'] ?? (colors as any)['logo_panel'] ?? null,
                    logo_login: (colors as any)['logo_login_dark'] ?? (colors as any)['logo_login'] ?? null,
                    background_image:
                        (colors as any)['background_image_dark'] ?? (colors as any)['background_image'] ?? null,
                },
            },
            // include logos so presets capture configured images
            logo_panel: (colors as any)['logo_panel'] ?? null,
            logo_login: (colors as any)['logo_login'] ?? null,
        } as const;

        try {
            // If setting this preset as default, clear existing default flags on other presets.
            if (isDefault) {
                const existing = Object.keys(colors).filter(k => k.startsWith('presets:'));
                for (const k of existing) {
                    try {
                        const parsed = JSON.parse((colors as any)[k]);
                        if (parsed && parsed.default) {
                            parsed.default = false;
                            // write back the modified preset
                            // eslint-disable-next-line no-await-in-loop
                            await updateColors(k, JSON.stringify(parsed));
                        }
                    } catch (e) {
                        // ignore malformed legacy presets
                    }
                }
            }

            await updateColors(`presets:${name}`, JSON.stringify(presetObj));
            setName('');
            setStartAt('');
            setEndAt('');
            setIsDefault(false);
            // trigger preview reload briefly, then reset so iframe doesn't stay on the placeholder
            setReload(true);
            setTimeout(() => setReload(false), 300);
        } finally {
            setSaving(false);
        }
    };

    const applyPreset = async (key: string, value: string) => {
        setApplying(key);
        try {
            const parsed = JSON.parse(value);

            // If preset uses the newer schema with modes, apply only the allowed keys and their mode variants.
            const keysToApply = ['primary', 'accent_primary', 'secondary', 'background'];

            if (parsed && parsed.modes && (parsed.modes.light || parsed.modes.dark)) {
                // For each key, write light, dark and canonical value (canonical we'll set to light fallback)
                for (const k of keysToApply) {
                    const lightVal = parsed.modes.light?.[k] ?? '';
                    const darkVal = parsed.modes.dark?.[k] ?? '';
                    // write mode-specific keys if present
                    if (lightVal) {
                        // eslint-disable-next-line no-await-in-loop
                        await updateColors(`${k}_light`, lightVal);
                        // eslint-disable-next-line no-await-in-loop
                        await updateColors(k, lightVal);
                    }
                    if (darkVal) {
                        // eslint-disable-next-line no-await-in-loop
                        await updateColors(`${k}_dark`, darkVal);
                    }
                }
                // apply logos and background images per-mode if present in the preset
                const panelLight = parsed.modes.light?.logo_panel ?? null;
                const panelDark = parsed.modes.dark?.logo_panel ?? null;
                const loginLight = parsed.modes.light?.logo_login ?? null;
                const loginDark = parsed.modes.dark?.logo_login ?? null;
                if (panelLight) {
                    // eslint-disable-next-line no-await-in-loop
                    await updateColors('logo_panel_light', panelLight);
                    // canonical fallback
                    // eslint-disable-next-line no-await-in-loop
                    await updateColors('logo_panel', panelLight);
                }
                if (panelDark) {
                    // eslint-disable-next-line no-await-in-loop
                    await updateColors('logo_panel_dark', panelDark);
                }
                if (loginLight) {
                    // eslint-disable-next-line no-await-in-loop
                    await updateColors('logo_login_light', loginLight);
                    // eslint-disable-next-line no-await-in-loop
                    await updateColors('logo_login', loginLight);
                }
                if (loginDark) {
                    // eslint-disable-next-line no-await-in-loop
                    await updateColors('logo_login_dark', loginDark);
                }
                const bgLight = parsed.modes.light?.background_image ?? null;
                const bgDark = parsed.modes.dark?.background_image ?? null;
                if (bgLight) {
                    // eslint-disable-next-line no-await-in-loop
                    await updateColors('background_image_light', bgLight);
                    // eslint-disable-next-line no-await-in-loop
                    await updateColors('background_image', bgLight);
                }
                if (bgDark) {
                    // eslint-disable-next-line no-await-in-loop
                    await updateColors('background_image_dark', bgDark);
                }
            } else if (parsed && typeof parsed === 'object') {
                // Legacy preset format (saved full color map) — only apply a small subset to avoid timeouts.
                for (const k of keysToApply) {
                    const light = parsed[`${k}_light`] ?? parsed[k] ?? null;
                    const dark = parsed[`${k}_dark`] ?? null;
                    if (light) {
                        // eslint-disable-next-line no-await-in-loop
                        await updateColors(`${k}_light`, light);
                        // eslint-disable-next-line no-await-in-loop
                        await updateColors(k, light);
                    }
                    if (dark) {
                        // eslint-disable-next-line no-await-in-loop
                        await updateColors(`${k}_dark`, dark);
                    }
                }
            }

            setReload(true);
            setTimeout(() => setReload(false), 300);
        } catch (e) {
            // ignore and surface error through flash if needed
        } finally {
            setApplying(null);
        }
    };

    const { addFlash } = useFlash();

    const deletePreset = async (key: string) => {
        try {
            await deleteColor(key.replace('presets:', ''));
            setReload(true);
            setTimeout(() => setReload(false), 300);
            addFlash({ key: 'theme:presets', type: 'success', message: 'Preset deleted.' });
        } catch (e) {
            addFlash({ key: 'theme:presets', type: 'error', message: 'Failed to delete preset.' });
        }
    };

    return (
        <AdminBox title={'Presets'} icon={faSave}>
            <FlashMessageRender byKey={'theme:colors'} className={'my-2'} />
            <FlashMessageRender byKey={'theme:presets'} className={'my-2'} />

            <div>
                <Label>Save current as preset</Label>
                <div className={'flex items-center space-x-2'}>
                    <Input value={name} onChange={e => setName(e.target.value)} placeholder={'holiday-2025'} />
                    <Button onClick={savePreset} className={'h-10'} disabled={saving || !name}>
                        Save
                    </Button>
                </div>

                <div className={'mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2'}>
                    <div>
                        <Label>Activate at</Label>
                        <Input type={'datetime-local'} value={startAt} onChange={e => setStartAt(e.target.value)} />
                    </div>
                    <div>
                        <Label>Deactivate at</Label>
                        <Input type={'datetime-local'} value={endAt} onChange={e => setEndAt(e.target.value)} />
                    </div>
                </div>

                <div className={'mt-3 flex items-center'}>
                    <input
                        id={'preset_default'}
                        type={'checkbox'}
                        checked={isDefault}
                        onChange={e => setIsDefault(e.target.checked)}
                        className={'mr-2'}
                    />
                    <label htmlFor={'preset_default'} className={'text-sm text-gray-300 select-none'}>
                        Set as default preset (only one can be default)
                    </label>
                </div>
                <p className={'text-xs text-gray-400 mt-2'}>
                    Save the current theme colors as a named preset. You can later apply or delete presets.
                </p>
            </div>

            <div className={'mt-6'}>
                <Label>Available presets</Label>
                {presets.length === 0 && <p className={'text-sm text-gray-400'}>No presets saved yet.</p>}
                {presets.map(p => {
                    let meta: any = null;
                    try {
                        meta = JSON.parse(p.value);
                    } catch (e) {
                        meta = null;
                    }

                    return (
                        <div key={p.key} className={'flex items-center justify-between mt-2'}>
                            <div>
                                <div className={'font-medium'}>{p.key.replace('presets:', '')}</div>
                                <div className={'text-xs text-gray-400'}>
                                    {meta && meta.modes ? (
                                        <>
                                            {meta.default && <span className={'mr-2'}>[Default]</span>}
                                            {meta.schedule?.start && (
                                                <span className={'mr-2'}>
                                                    Start: {new Date(meta.schedule.start).toLocaleString()}
                                                </span>
                                            )}
                                            {meta.schedule?.end && (
                                                <span className={'mr-2'}>
                                                    End: {new Date(meta.schedule.end).toLocaleString()}
                                                </span>
                                            )}
                                        </>
                                    ) : (
                                        'Legacy preset'
                                    )}
                                </div>
                            </div>
                            <div className={'flex items-center space-x-2'}>
                                <Button
                                    onClick={() => applyPreset(p.key, p.value)}
                                    className={'h-8'}
                                    disabled={!!applying}
                                >
                                    Apply
                                </Button>
                                <Button.Danger onClick={() => deletePreset(p.key)} className={'h-8'}>
                                    Delete
                                </Button.Danger>
                            </div>
                        </div>
                    );
                })}
            </div>
        </AdminBox>
    );
};
