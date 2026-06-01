import { useEffect, useState } from 'react';
import Modal from '@elements/Modal';
import { Button } from '@elements/button';
import { getPresets } from '@/api/admin/presets';
import { createServer } from '@/api/admin/servers/createServer';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';

interface Props {
    visible: boolean;
    onDismissed: () => void;
}

export default ({ visible, onDismissed }: Props) => {
    const [presets, setPresets] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    const { addFlash, clearAndAddHttpError } = useFlash();

    useEffect(() => {
        if (!visible) return;
        (async () => {
            try {
                const p = await getPresets();
                const normalize = (x: any) => ({
                    id: x.id ?? x.data?.id ?? x.attributes?.id,
                    name: x.name ?? x.data?.name ?? x.attributes?.name ?? '',
                    description: x.description ?? x.data?.description ?? x.attributes?.description ?? '',
                    settings: x.settings ?? x.data?.settings ?? x.attributes?.settings ?? null,
                });

                setPresets((p as any[]).map(normalize));
            } catch (e) {
                setPresets([]);
            }
        })();
    }, [visible]);

    const deployPreset = async (p: any) => {
        setLoading(true);
        try {
            // Assuming preset.settings matches CreateServerRequest shape. If not, map accordingly.
            const created = await (createServer as any)(p.settings);
            addFlash({
                key: 'server:deploy',
                type: 'success',
                message: `Server ${created.name || created.id} deployed.`,
            });
            onDismissed();
        } catch (e) {
            clearAndAddHttpError({ key: 'server:deploy', error: e });
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal visible={visible} onDismissed={onDismissed} top>
            <div className="p-4">
                <h3 className="text-xl mb-3 text-theme-primary">Deploy Preset</h3>
                <FlashMessageRender byKey={'server:deploy'} className={'mb-2'} />

                <div className="max-h-64 overflow-auto space-y-2">
                    {presets.length === 0 && <div className="text-theme-muted">No presets available.</div>}
                    {presets.map(p => (
                        <div key={p.id} className="flex items-center justify-between p-2 bg-neutral-900 rounded">
                            <div>
                                <div className="font-medium text-theme-primary">{p.name}</div>
                                <div className="text-sm text-theme-muted">{p.description}</div>
                            </div>
                            <div>
                                <Button onClick={() => deployPreset(p)} disabled={loading} className="h-9">
                                    Deploy
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
                <div className="flex justify-end mt-4">
                    <Button variant="secondary" onClick={onDismissed}>
                        Close
                    </Button>
                </div>
            </div>
        </Modal>
    );
};
