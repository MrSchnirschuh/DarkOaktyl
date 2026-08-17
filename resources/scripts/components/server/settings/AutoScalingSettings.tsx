import { useState, useCallback, useEffect } from 'react';
import tw from 'twin.macro';
import TitledGreyBox from '@/elements/TitledGreyBox';
import Spinner from '@/elements/Spinner';
import Button from '@/elements/Button';
import Input from '@/elements/Input';
import Toggle from '@/elements/Toggle';
import PageContentBlock from '@/elements/PageContentBlock';
import { ServerError } from '@/elements/ScreenBlock';
import { httpErrorToHuman } from '@/api/http';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import {
    getAutoScalingConfig,
    updateAutoScalingConfig,
    getAutoScalingHistory,
    triggerManualScaling,
} from '@/api/routes/server/autoscaling';

interface AutoScalingData {
    id: number;
    enabled: boolean;
    thresholds: {
        cpu: { up: number; down: number };
        ram: { up: number; down: number };
        disk: { up: number; down: number };
    };
    limits: {
        scale_up: number;
        scale_down: number;
    };
    steps: {
        up: number;
        down: number;
    };
    cooldown_minutes: number;
    is_in_cooldown: boolean;
    remaining_cooldown_minutes: number;
    last_scale_up_at: string | null;
    last_scale_down_at: string | null;
}

interface HistoryEntry {
    id: number;
    action: string;
    action_label: string;
    triggered_by: string | null;
    trigger_label: string | null;
    metrics: {
        before: { cpu: number; ram: number; disk: number };
        after: { cpu: number; ram: number; disk: number };
    };
    limits: {
        before: { memory: number; cpu: number; disk: number };
        after: { memory: number; cpu: number; disk: number };
    };
    reason: string;
    status: string;
    status_label: string;
    error_message: string | null;
    created_at: string;
}

const AutoScalingSettings = () => {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [data, setData] = useState<AutoScalingData | null>(null);
    const [history, setHistory] = useState<HistoryEntry[]>([]);
    const [error, setError] = useState<string | null>(null);
    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    const fetchData = useCallback(async () => {
        try {
            const [config, historyData] = await Promise.all([getAutoScalingConfig(uuid), getAutoScalingHistory(uuid)]);
            setData(config);
            setHistory(historyData.data);
            setError(null);
        } catch (e) {
            setError(httpErrorToHuman(e));
        } finally {
            setLoading(false);
        }
    }, [uuid]);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    const handleSave = useCallback(async () => {
        if (!data) return;

        setSaving(true);
        clearFlashes();

        try {
            await updateAutoScalingConfig(uuid, {
                enabled: data.enabled,
                cpu_threshold_up: data.thresholds.cpu.up,
                cpu_threshold_down: data.thresholds.cpu.down,
                ram_threshold_up: data.thresholds.ram.up,
                ram_threshold_down: data.thresholds.ram.down,
                disk_threshold_up: data.thresholds.disk.up,
                disk_threshold_down: data.thresholds.disk.down,
                scale_up_limit: data.limits.scale_up,
                scale_down_limit: data.limits.scale_down,
                scale_up_step: data.steps.up,
                scale_down_step: data.steps.down,
                cooldown_minutes: data.cooldown_minutes,
            });
            addFlash({ type: 'success', key: 'autoscaling', message: 'Auto-scaling settings saved successfully.' });
        } catch (e) {
            clearAndAddHttpError({ key: 'autoscaling', error: e });
        } finally {
            setSaving(false);
        }
    }, [uuid, data, clearFlashes, clearAndAddHttpError, addFlash]);

    const handleManualTrigger = useCallback(async () => {
        clearFlashes();
        try {
            await triggerManualScaling(uuid);
            addFlash({ type: 'success', key: 'autoscaling', message: 'Manual scaling check triggered.' });
        } catch (e) {
            clearAndAddHttpError({ key: 'autoscaling', error: e });
        }
    }, [uuid, clearFlashes, clearAndAddHttpError, addFlash]);

    const updateThreshold = (resource: 'cpu' | 'ram' | 'disk', direction: 'up' | 'down', value: number) => {
        if (!data) return;
        setData({
            ...data,
            thresholds: {
                ...data.thresholds,
                [resource]: {
                    ...data.thresholds[resource],
                    [direction]: value,
                },
            },
        });
    };

    if (loading) {
        return (
            <PageContentBlock title="Auto-Scaling">
                <Spinner centered size={Spinner.Size.LARGE} />
            </PageContentBlock>
        );
    }

    if (error) {
        return (
            <PageContentBlock title="Auto-Scaling">
                <ServerError title="Error" message={error} onRetry={fetchData} />
            </PageContentBlock>
        );
    }

    if (!data) {
        return (
            <PageContentBlock title="Auto-Scaling">
                <p>Failed to load auto-scaling configuration.</p>
            </PageContentBlock>
        );
    }

    return (
        <PageContentBlock
            title="Auto-Scaling Settings"
            description="Configure automatic resource scaling based on server usage."
            showFlashKey="autoscaling"
        >
            {/* Enable/Disable Toggle */}
            <TitledGreyBox title="Auto-Scaling Status" css={tw`mb-6`}>
                <div css={tw`flex items-center justify-between px-4 py-4`}>
                    <div>
                        <p css={tw`font-medium`}>Enable Auto-Scaling</p>
                        <p css={tw`text-sm text-gray-400 mt-1`}>
                            Automatically scale server resources based on usage thresholds.
                        </p>
                    </div>
                    <Toggle checked={data.enabled} onChange={() => setData({ ...data, enabled: !data.enabled })} />
                </div>
                {data.enabled && (
                    <>
                        {data.is_in_cooldown && (
                            <div css={tw`px-4 py-2 bg-yellow-900/50 text-yellow-200 text-sm`}>
                                Cooldown active: {data.remaining_cooldown_minutes} minutes remaining
                            </div>
                        )}
                        {(data.last_scale_up_at || data.last_scale_down_at) && (
                            <div css={tw`px-4 py-2 text-sm text-gray-400`}>
                                {data.last_scale_up_at && (
                                    <p>Last scale up: {new Date(data.last_scale_up_at).toLocaleString()}</p>
                                )}
                                {data.last_scale_down_at && (
                                    <p>Last scale down: {new Date(data.last_scale_down_at).toLocaleString()}</p>
                                )}
                            </div>
                        )}
                    </>
                )}
            </TitledGreyBox>

            {/* Thresholds */}
            <div css={tw`grid gap-6 md:grid-cols-3 mb-6`}>
                <TitledGreyBox title="CPU Thresholds">
                    <div css={tw`px-4 py-4 space-y-4`}>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Scale Up (%)</label>
                            <Input
                                type="number"
                                min={1}
                                max={100}
                                value={data.thresholds.cpu.up}
                                onChange={e => updateThreshold('cpu', 'up', parseInt(e.target.value))}
                            />
                        </div>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Scale Down (%)</label>
                            <Input
                                type="number"
                                min={0}
                                max={99}
                                value={data.thresholds.cpu.down}
                                onChange={e => updateThreshold('cpu', 'down', parseInt(e.target.value))}
                            />
                        </div>
                    </div>
                </TitledGreyBox>

                <TitledGreyBox title="RAM Thresholds">
                    <div css={tw`px-4 py-4 space-y-4`}>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Scale Up (%)</label>
                            <Input
                                type="number"
                                min={1}
                                max={100}
                                value={data.thresholds.ram.up}
                                onChange={e => updateThreshold('ram', 'up', parseInt(e.target.value))}
                            />
                        </div>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Scale Down (%)</label>
                            <Input
                                type="number"
                                min={0}
                                max={99}
                                value={data.thresholds.ram.down}
                                onChange={e => updateThreshold('ram', 'down', parseInt(e.target.value))}
                            />
                        </div>
                    </div>
                </TitledGreyBox>

                <TitledGreyBox title="Disk Thresholds">
                    <div css={tw`px-4 py-4 space-y-4`}>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Scale Up (%)</label>
                            <Input
                                type="number"
                                min={1}
                                max={100}
                                value={data.thresholds.disk.up}
                                onChange={e => updateThreshold('disk', 'up', parseInt(e.target.value))}
                            />
                        </div>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Scale Down (%)</label>
                            <Input
                                type="number"
                                min={0}
                                max={99}
                                value={data.thresholds.disk.down}
                                onChange={e => updateThreshold('disk', 'down', parseInt(e.target.value))}
                            />
                        </div>
                    </div>
                </TitledGreyBox>
            </div>

            {/* Scaling Limits & Steps */}
            <div css={tw`grid gap-6 md:grid-cols-2 mb-6`}>
                <TitledGreyBox title="Scaling Limits (MB)">
                    <div css={tw`px-4 py-4 space-y-4`}>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Max Scale Up (MB)</label>
                            <Input
                                type="number"
                                min={0}
                                value={data.limits.scale_up}
                                onChange={e =>
                                    setData({ ...data, limits: { ...data.limits, scale_up: parseInt(e.target.value) } })
                                }
                            />
                        </div>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Min Scale Down (MB)</label>
                            <Input
                                type="number"
                                min={0}
                                value={data.limits.scale_down}
                                onChange={e =>
                                    setData({
                                        ...data,
                                        limits: { ...data.limits, scale_down: parseInt(e.target.value) },
                                    })
                                }
                            />
                        </div>
                    </div>
                </TitledGreyBox>

                <TitledGreyBox title="Scaling Steps & Cooldown">
                    <div css={tw`px-4 py-4 space-y-4`}>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Scale Up Step (MB)</label>
                            <Input
                                type="number"
                                min={1}
                                value={data.steps.up}
                                onChange={e =>
                                    setData({ ...data, steps: { ...data.steps, up: parseInt(e.target.value) } })
                                }
                            />
                        </div>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Scale Down Step (MB)</label>
                            <Input
                                type="number"
                                min={1}
                                value={data.steps.down}
                                onChange={e =>
                                    setData({ ...data, steps: { ...data.steps, down: parseInt(e.target.value) } })
                                }
                            />
                        </div>
                        <div>
                            <label css={tw`block text-sm font-medium mb-1`}>Cooldown (minutes)</label>
                            <Input
                                type="number"
                                min={1}
                                max={1440}
                                value={data.cooldown_minutes}
                                onChange={e => setData({ ...data, cooldown_minutes: parseInt(e.target.value) })}
                            />
                        </div>
                    </div>
                </TitledGreyBox>
            </div>

            {/* Action Buttons */}
            <div css={tw`flex gap-4 mb-8`}>
                <Button disabled={saving} onClick={handleSave}>
                    {saving ? <Spinner size={Spinner.Size.SMALL} /> : 'Save Settings'}
                </Button>
                {data.enabled && (
                    <Button onClick={handleManualTrigger} variant="secondary">
                        Manual Check
                    </Button>
                )}
            </div>

            {/* History */}
            <h3 css={tw`text-xl font-semibold mb-4`}>Scaling History</h3>
            <TitledGreyBox title="Recent Actions">
                {history.length === 0 ? (
                    <div css={tw`px-4 py-8 text-center text-gray-400`}>No scaling history available.</div>
                ) : (
                    <div css={tw`divide-y divide-gray-700`}>
                        {history.slice(0, 10).map(entry => (
                            <div key={entry.id} css={tw`px-4 py-4`}>
                                <div css={tw`flex items-center justify-between mb-2`}>
                                    <span css={tw`font-medium`}>{entry.action_label}</span>
                                    <span css={tw`text-sm text-gray-400`}>
                                        {new Date(entry.created_at).toLocaleString()}
                                    </span>
                                </div>
                                <div css={tw`text-sm text-gray-400 mb-1`}>
                                    <span
                                        css={tw`inline-block px-2 py-0.5 rounded text-xs mr-2 ${
                                            entry.status === 'success'
                                                ? 'bg-green-900 text-green-200'
                                                : entry.status === 'failed'
                                                ? 'bg-red-900 text-red-200'
                                                : 'bg-yellow-900 text-yellow-200'
                                        }`}
                                    >
                                        {entry.status_label}
                                    </span>
                                    {entry.trigger_label && <span>Triggered by: {entry.trigger_label}</span>}
                                </div>
                                <p css={tw`text-sm text-gray-500`}>{entry.reason}</p>
                                {entry.action !== 'no_action' && entry.limits && (
                                    <div css={tw`mt-2 text-xs text-gray-400`}>
                                        Memory: {entry.limits.before.memory}MB → {entry.limits.after.memory}MB
                                    </div>
                                )}
                                {entry.error_message && (
                                    <div css={tw`mt-2 text-sm text-red-400`}>{entry.error_message}</div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </TitledGreyBox>
        </PageContentBlock>
    );
};

export default AutoScalingSettings;
