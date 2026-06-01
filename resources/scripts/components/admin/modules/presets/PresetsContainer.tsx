import { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { getPresets, deletePreset } from '@/api/admin/presets';
import { createServer } from '@/api/admin/servers/createServer';
import PresetSaveModal from '@/components/admin/management/servers/PresetSaveModal';
import { Button } from '@elements/button';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
// Toggle moved to PresetsSettings

export default () => {
    const [presets, setPresets] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [editingPreset, setEditingPreset] = useState<any | null>(null);
    const [showEditModal, setShowEditModal] = useState(false);
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();

    // settings are handled in PresetsSettings

    const load = async () => {
        setLoading(true);
        try {
            const p = await getPresets();
            const normalize = (x: any) => ({
                id: x.id ?? x.data?.id ?? x.attributes?.id,
                name: x.name ?? x.data?.name ?? x.attributes?.name ?? '',
                description: x.description ?? x.data?.description ?? x.attributes?.description ?? '',
                visibility: x.visibility ?? x.data?.visibility ?? x.attributes?.visibility ?? '',
                settings: x.settings ?? x.data?.settings ?? x.attributes?.settings ?? null,
                port_start: x.port_start ?? x.data?.port_start ?? x.attributes?.port_start ?? null,
                port_end: x.port_end ?? x.data?.port_end ?? x.attributes?.port_end ?? null,
            });

            setPresets((p as any[]).map(normalize));
        } catch (e) {
            setPresets([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    // Creation of presets is handled from the New Server flow (Save as preset modal).

    const handleDelete = async (id: number) => {
        clearFlashes('admin:presets');
        try {
            await deletePreset(id);
            addFlash({ key: 'admin:presets', type: 'success', message: 'Preset deleted.' });
            await load();
        } catch (e) {
            clearAndAddHttpError({ key: 'admin:presets', error: e });
        }
    };

    const handleDeploy = async (p: any) => {
        clearFlashes('admin:presets');
        try {
            // assume preset.settings conforms to CreateServerRequest
            await (createServer as any)(p.settings as any);
            addFlash({ key: 'admin:presets', type: 'success', message: 'Server deployed from preset.' });
        } catch (e) {
            clearAndAddHttpError({ key: 'admin:presets', error: e });
        }
    };

    // settings are handled in PresetsSettings

    return (
        <div className="p-4">
            <h2 css={tw`text-2xl text-theme-primary font-header font-medium mb-4`}>Server Presets</h2>

            <div className="flex items-center mb-2">
                <FlashMessageRender byKey={'admin:presets'} className={'mr-4'} />
            </div>

            {/* Settings are managed on the Settings page. */}

            <div>
                <h3 className="mb-2 text-theme-primary">Available Presets</h3>
                {loading && <div className="text-theme-muted">Loading presets…</div>}
                {!loading && presets.length === 0 && <div className="text-theme-muted">No presets found.</div>}

                <div className="space-y-2">
                    {presets.map(p => (
                        <div key={p.id} className="p-3 bg-neutral-900 rounded flex items-center justify-between">
                            <div className="text-theme-primary font-medium">{p.name}</div>
                            <div className="flex items-center space-x-2">
                                <div className="text-theme-muted text-sm">{p.visibility}</div>
                                <Button
                                    type="button"
                                    className="h-8"
                                    onClick={() => {
                                        setEditingPreset(p);
                                        setShowEditModal(true);
                                    }}
                                >
                                    Edit
                                </Button>
                                <Button type="button" className="h-8" onClick={() => handleDeploy(p)}>
                                    Apply
                                </Button>
                                <Button.Danger type="button" className="h-8" onClick={() => handleDelete(p.id)}>
                                    Delete
                                </Button.Danger>
                            </div>
                        </div>
                    ))}
                </div>

                {showEditModal && (
                    <PresetSaveModal
                        visible={showEditModal}
                        onDismissed={() => {
                            setShowEditModal(false);
                            setEditingPreset(null);
                        }}
                        values={editingPreset?.settings ?? {}}
                        preset={editingPreset}
                        onSaved={async () => {
                            setShowEditModal(false);
                            setEditingPreset(null);
                            await load();
                        }}
                    />
                )}
            </div>
        </div>
    );
};
