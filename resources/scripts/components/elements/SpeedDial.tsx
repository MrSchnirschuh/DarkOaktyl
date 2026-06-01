import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@elements/button';
import { useStoreState } from '@/state/hooks';
import Tooltip from '@elements/tooltip/Tooltip';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faLayerGroup,
    faMagicWandSparkles,
    faPlus,
    faServer,
    faTicket,
    faUserPlus,
    IconDefinition,
} from '@fortawesome/free-solid-svg-icons';
import { getPresets as fetchPresets } from '@/api/admin/presets';
import PresetDeployModal from '@admin/management/servers/PresetDeployModal';

interface QuickActionProps {
    link: string;
    tooltip: string;
    icon: IconDefinition;
}

const QuickAction = ({ tooltip, icon, link }: QuickActionProps) => (
    <Tooltip placement={'left'} content={tooltip} arrow>
        <Link to={link}>
            <Button.Text className={'w-12 h-12'}>
                <FontAwesomeIcon icon={icon} />
            </Button.Text>
        </Link>
    </Tooltip>
);

export default () => {
    const [open, setOpen] = useState<boolean>(false);
    const ai = useStoreState(s => s.DarkOak.data!.ai.enabled);
    const enabled = useStoreState(s => s.settings.data!.speed_dial);
    const tickets = useStoreState(s => s.DarkOak.data!.tickets.enabled);
    const [presetsPreview, setPresetsPreview] = useState<JSX.Element | string>('');
    const [deployOpen, setDeployOpen] = useState(false);

    useEffect(() => {
        (async () => {
            try {
                const p = await fetchPresets();
                if (!p || p.length === 0) {
                    setPresetsPreview('No presets');
                    return;
                }
                const normalize = (x: any) => ({
                    id: x.id ?? x.data?.id ?? x.attributes?.id,
                    name: x.name ?? x.data?.name ?? x.attributes?.name ?? '',
                    visibility: x.visibility ?? x.data?.visibility ?? x.attributes?.visibility ?? '',
                });

                const items = (p as any[]).map(normalize);

                setPresetsPreview(
                    items
                        .slice(0, 8)
                        .map((x: any) => `${x.name || '<unnamed>'} (${x.visibility || 'private'})`)
                        .join('\n'),
                );
            } catch (e) {
                setPresetsPreview('Failed to load presets');
            }
        })();
    }, []);

    if (!enabled) return <></>;

    return (
        <div className="hidden md:block fixed bottom-6 right-6" style={{ zIndex: 9999 }}>
            {open && (
                <div className="flex flex-col items-center mb-4 space-y-2">
                    {ai && <QuickAction icon={faMagicWandSparkles} link={'/admin/ai'} tooltip={'Ask AI'} />}
                    <QuickAction icon={faLayerGroup} link={'/admin/nodes/new'} tooltip={'Create Node'} />
                    <QuickAction icon={faServer} link={'/admin/servers/new'} tooltip={'Create Server'} />
                    {/* Preset quick action opens modal to deploy presets */}
                    <Tooltip placement={'left'} content={presetsPreview} arrow>
                        <Button.Text className={'w-12 h-12'} onClick={() => setDeployOpen(true)}>
                            <FontAwesomeIcon icon={faLayerGroup} />
                        </Button.Text>
                    </Tooltip>
                    <PresetDeployModal visible={deployOpen} onDismissed={() => setDeployOpen(false)} />
                    <QuickAction icon={faUserPlus} link={'/admin/users/new'} tooltip={'New User'} />
                    {tickets && <QuickAction icon={faTicket} link={'/admin/tickets'} tooltip={'View Tickets'} />}
                </div>
            )}
            <Button className={'w-12 h-12'} onClick={() => setOpen(!open)}>
                <FontAwesomeIcon icon={faPlus} />
            </Button>
        </div>
    );
};
