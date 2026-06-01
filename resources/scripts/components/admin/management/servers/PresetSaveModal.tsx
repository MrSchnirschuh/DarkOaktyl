import { useCallback, useEffect, useMemo, useState } from 'react';
import Modal from '@elements/Modal';
import { Button } from '@elements/button';
import Label from '@elements/Label';
import Input from '@elements/Input';
import { Formik, Form } from 'formik';
import useFlash from '@/plugins/useFlash';
import { createPreset, updatePreset } from '@/api/admin/presets';
import { useStoreState } from '@/state/hooks';

interface Props {
    visible: boolean;
    onDismissed: () => void;
    values: Record<string, any>;
    preset?: Record<string, any> | null;
    onSaved?: (id: number) => void;
}

export default ({ visible, onDismissed, values, preset = null, onSaved }: Props) => {
    const [loading, setLoading] = useState(false);
    const { addFlash } = useFlash();

    const settingsState = useStoreState(s => s.settings.data!);

    const [serverSettings, setServerSettings] = useState<Record<string, any>>({});
    const [environmentRows, setEnvironmentRows] = useState<Array<{ id: number; key: string; value: string }>>([]);

    const buildEnvironmentRows = useCallback((env: any): Array<{ id: number; key: string; value: string }> => {
        if (!env || typeof env !== 'object') {
            return [];
        }

        return Object.entries(env).map(([key, value]) => ({
            id: Math.random(),
            key,
            value: typeof value === 'string' || typeof value === 'number' ? String(value) : '',
        }));
    }, []);

    const hydrateSettings = useCallback(() => {
        const source = preset?.settings ?? values ?? {};
        let cloned: Record<string, any> = {};
        try {
            cloned = JSON.parse(JSON.stringify(source ?? {}));
        } catch (e) {
            cloned = { ...(source ?? {}) };
        }

        setServerSettings(cloned ?? {});
        setEnvironmentRows(buildEnvironmentRows(cloned?.environment ?? {}));
    }, [preset, values, buildEnvironmentRows]);

    useEffect(() => {
        if (visible) {
            hydrateSettings();
        }
    }, [visible, hydrateSettings]);

    const updateServerSetting = useCallback((updater: (current: Record<string, any>) => Record<string, any>) => {
        setServerSettings(prev => {
            const base = prev ?? {};
            return updater(base);
        });
    }, []);

    const updateNestedValue = (path: string[], value: any) => {
        updateServerSetting(current => {
            const next = { ...current };
            let cursor = next;

            path.forEach((segment, index) => {
                if (index === path.length - 1) {
                    cursor[segment] = value;
                    return;
                }

                cursor[segment] = { ...(cursor[segment] ?? {}) };
                cursor = cursor[segment];
            });

            return next;
        });
    };

    const parseNumber = (value: string): number | null => {
        if (value === '' || value === null || typeof value === 'undefined') return null;
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : null;
    };

    const additionalAllocationsDisplay = useMemo(() => {
        const list = serverSettings?.allocation?.additional ?? [];
        if (!Array.isArray(list)) {
            return '';
        }

        return list.join(', ');
    }, [serverSettings?.allocation?.additional]);

    const handleEnvironmentChange = (id: number, field: 'key' | 'value', value: string) => {
        setEnvironmentRows(prev => prev.map(row => (row.id === id ? { ...row, [field]: value } : row)));
    };

    const handleEnvironmentAdd = () => {
        setEnvironmentRows(prev => [...prev, { id: Math.random(), key: '', value: '' }]);
    };

    const handleEnvironmentRemove = (id: number) => {
        setEnvironmentRows(prev => prev.filter(row => row.id !== id));
    };

    const submit = async (payload: any) => {
        setLoading(true);
        try {
            // build minimal settings object from current form values
            const parsePort = (val: any, fallback: any) => {
                if (val === '' || val === null || typeof val === 'undefined') return fallback ?? null;
                const n = Number(val);
                return Number.isFinite(n) ? n : fallback ?? null;
            };

            const environment = environmentRows.reduce<Record<string, string>>((acc, row) => {
                const key = row.key.trim();
                if (!key) {
                    return acc;
                }

                acc[key] = row.value;
                return acc;
            }, {});

            const finalSettings = {
                ...(serverSettings ?? {}),
                environment,
            };

            const settings = {
                name: payload.name,
                description: payload.description,
                settings: finalSettings,
                // treat empty inputs as optional and fall back to global settings when available
                port_start: parsePort(payload.port_start, settingsState.presets_global_port_start),
                port_end: parsePort(payload.port_end, settingsState.presets_global_port_end),
                visibility: payload.visibility || 'private',
                naming: { template: payload.naming || null },
            };

            if (preset && preset.id) {
                const updated = await updatePreset(preset.id, settings as any);
                addFlash({ key: 'server:presets', type: 'success', message: 'Preset updated.' });
                onSaved && onSaved((updated as any).id);
                onDismissed();
            } else {
                const created = await createPreset(settings as any);
                addFlash({ key: 'server:presets', type: 'success', message: 'Preset saved.' });
                onSaved && onSaved((created as any).id);
                onDismissed();
            }
        } catch (e) {
            addFlash({ key: 'server:presets', type: 'error', message: 'Failed to save preset.' });
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal visible={visible} onDismissed={onDismissed} top>
            <div className="p-2">
                <h3 className="text-xl mb-2">Save preset</h3>
                <Formik
                    initialValues={{
                        name: preset?.name ?? '',
                        description: preset?.description ?? '',
                        visibility: preset?.visibility ?? 'private',
                        port_start: preset?.port_start ?? '',
                        port_end: preset?.port_end ?? '',
                        naming: preset?.naming?.template ?? '',
                    }}
                    onSubmit={v => submit(v)}
                >
                    {({ values, handleChange }) => (
                        <Form>
                            <div className="space-y-3">
                                <div>
                                    <Label>Preset name</Label>
                                    <Input name="name" value={values.name} onChange={handleChange} />
                                </div>
                                <div>
                                    <Label>Description (optional)</Label>
                                    <Input name="description" value={values.description} onChange={handleChange} />
                                </div>
                                <div>
                                    <Label>Visibility</Label>
                                    <select
                                        name="visibility"
                                        value={values.visibility}
                                        onChange={handleChange}
                                        className="w-full p-2 rounded bg-neutral-900 border border-neutral-800"
                                    >
                                        <option value="private">Private (only you)</option>
                                        <option value="global">Global (all admins)</option>
                                    </select>
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <Label>Start port (optional)</Label>
                                        <Input name="port_start" value={values.port_start} onChange={handleChange} />

                                        <div className="mt-4 border-t border-neutral-800 pt-4">
                                            <h4 className="text-lg text-theme-primary mb-2">Server defaults</h4>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <Label>Server name</Label>
                                                    <Input
                                                        value={serverSettings?.name ?? ''}
                                                        onChange={e => updateNestedValue(['name'], e.target.value)}
                                                        placeholder={'Default server name'}
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Server description</Label>
                                                    <Input
                                                        value={serverSettings?.description ?? ''}
                                                        onChange={e =>
                                                            updateNestedValue(['description'], e.target.value)
                                                        }
                                                        placeholder={'Shown in admin lists'}
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Node ID</Label>
                                                    <Input
                                                        type="number"
                                                        value={serverSettings?.node_id ?? ''}
                                                        onChange={e =>
                                                            updateNestedValue(['node_id'], parseNumber(e.target.value))
                                                        }
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Egg ID</Label>
                                                    <Input
                                                        type="number"
                                                        value={serverSettings?.egg_id ?? ''}
                                                        onChange={e =>
                                                            updateNestedValue(['egg_id'], parseNumber(e.target.value))
                                                        }
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Docker image</Label>
                                                    <Input
                                                        value={serverSettings?.image ?? ''}
                                                        onChange={e => updateNestedValue(['image'], e.target.value)}
                                                        placeholder={'ghcr.io/...:latest'}
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Startup command</Label>
                                                    <Input
                                                        value={serverSettings?.startup ?? ''}
                                                        onChange={e => updateNestedValue(['startup'], e.target.value)}
                                                        placeholder={'./start.sh --memory={{SERVER_MEMORY}}'}
                                                    />
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Input
                                                        type="checkbox"
                                                        id="skipScripts"
                                                        checked={Boolean(serverSettings?.skip_scripts)}
                                                        onChange={e =>
                                                            updateNestedValue(['skip_scripts'], e.target.checked)
                                                        }
                                                    />
                                                    <Label htmlFor="skipScripts">Skip install scripts</Label>
                                                </div>
                                                <div className="flex items-center space-x-2">
                                                    <Input
                                                        type="checkbox"
                                                        id="startOnCompletion"
                                                        checked={serverSettings?.start_on_completion ?? true}
                                                        onChange={e =>
                                                            updateNestedValue(['start_on_completion'], e.target.checked)
                                                        }
                                                    />
                                                    <Label htmlFor="startOnCompletion">Start after installation</Label>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="border-t border-neutral-800 pt-4 mt-4">
                                            <h4 className="text-lg text-theme-primary mb-2">Resource limits</h4>
                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                                {[
                                                    { key: 'memory', label: 'Memory (MB)' },
                                                    { key: 'disk', label: 'Disk (MB)' },
                                                    { key: 'cpu', label: 'CPU (%)' },
                                                    { key: 'swap', label: 'Swap (MB)' },
                                                    { key: 'io', label: 'IO weight' },
                                                    { key: 'threads', label: 'Threads' },
                                                ].map(field => (
                                                    <div key={field.key}>
                                                        <Label>{field.label}</Label>
                                                        <Input
                                                            type="number"
                                                            value={serverSettings?.limits?.[field.key] ?? ''}
                                                            onChange={e =>
                                                                updateNestedValue(
                                                                    ['limits', field.key],
                                                                    field.key === 'threads'
                                                                        ? e.target.value
                                                                        : parseNumber(e.target.value),
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                            <div className="flex items-center space-x-2 mt-3">
                                                <Input
                                                    type="checkbox"
                                                    id="oomKiller"
                                                    checked={serverSettings?.limits?.oom_killer ?? true}
                                                    onChange={e =>
                                                        updateNestedValue(['limits', 'oom_killer'], e.target.checked)
                                                    }
                                                />
                                                <Label htmlFor="oomKiller">OOM killer enabled</Label>
                                            </div>
                                        </div>

                                        <div className="border-t border-neutral-800 pt-4 mt-4">
                                            <h4 className="text-lg text-theme-primary mb-2">Feature limits</h4>
                                            <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                                {[
                                                    { key: 'allocations', label: 'Allocations' },
                                                    { key: 'backups', label: 'Backups' },
                                                    { key: 'databases', label: 'Databases' },
                                                    { key: 'subusers', label: 'Subusers' },
                                                ].map(field => (
                                                    <div key={field.key}>
                                                        <Label>{field.label}</Label>
                                                        <Input
                                                            type="number"
                                                            value={serverSettings?.feature_limits?.[field.key] ?? ''}
                                                            onChange={e =>
                                                                updateNestedValue(
                                                                    ['feature_limits', field.key],
                                                                    parseNumber(e.target.value),
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="border-t border-neutral-800 pt-4 mt-4">
                                            <h4 className="text-lg text-theme-primary mb-2">Allocations</h4>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <Label>Primary allocation ID</Label>
                                                    <Input
                                                        type="number"
                                                        value={serverSettings?.allocation?.default ?? ''}
                                                        onChange={e =>
                                                            updateNestedValue(
                                                                ['allocation', 'default'],
                                                                parseNumber(e.target.value),
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div>
                                                    <Label>Additional allocation IDs</Label>
                                                    <Input
                                                        value={additionalAllocationsDisplay}
                                                        onChange={e =>
                                                            updateNestedValue(
                                                                ['allocation', 'additional'],
                                                                e.target.value
                                                                    .split(',')
                                                                    .map(part => part.trim())
                                                                    .filter(Boolean)
                                                                    .map(val => Number(val))
                                                                    .filter(num => Number.isFinite(num)),
                                                            )
                                                        }
                                                        placeholder={'e.g. 12, 15, 18'}
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="border-t border-neutral-800 pt-4 mt-4">
                                            <div className="flex items-center justify-between mb-2">
                                                <h4 className="text-lg text-theme-primary">Environment variables</h4>
                                                <Button
                                                    type="button"
                                                    size={Button.Sizes.Small}
                                                    onClick={handleEnvironmentAdd}
                                                >
                                                    Add variable
                                                </Button>
                                            </div>
                                            <div className="space-y-3">
                                                {environmentRows.length === 0 && (
                                                    <p className="text-sm text-theme-muted">No variables configured.</p>
                                                )}
                                                {environmentRows.map(row => (
                                                    <div
                                                        key={row.id}
                                                        className="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-2"
                                                    >
                                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                            <div>
                                                                <Label>Key</Label>
                                                                <Input
                                                                    value={row.key}
                                                                    onChange={e =>
                                                                        handleEnvironmentChange(
                                                                            row.id,
                                                                            'key',
                                                                            e.target.value,
                                                                        )
                                                                    }
                                                                    placeholder={'EGG_PORT'}
                                                                />
                                                            </div>
                                                            <div>
                                                                <Label>Value</Label>
                                                                <Input
                                                                    value={row.value}
                                                                    onChange={e =>
                                                                        handleEnvironmentChange(
                                                                            row.id,
                                                                            'value',
                                                                            e.target.value,
                                                                        )
                                                                    }
                                                                    placeholder={'25565'}
                                                                />
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center">
                                                            <Button.Danger
                                                                type="button"
                                                                onClick={() => handleEnvironmentRemove(row.id)}
                                                            >
                                                                Remove
                                                            </Button.Danger>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <Label>End port (optional)</Label>
                                        <Input name="port_end" value={values.port_end} onChange={handleChange} />
                                    </div>
                                </div>
                                <div>
                                    <Label>Naming template (optional)</Label>
                                    <Input
                                        name="naming"
                                        value={values.naming}
                                        onChange={handleChange}
                                        placeholder={'e.g. project-{date}-{random}'}
                                    />
                                </div>
                                <div className="flex justify-end space-x-2">
                                    <Button type="button" onClick={onDismissed} variant="secondary">
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={!values.name || loading}>
                                        Save
                                    </Button>
                                </div>
                            </div>
                        </Form>
                    )}
                </Formik>
            </div>
        </Modal>
    );
};
