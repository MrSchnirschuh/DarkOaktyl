import { useState, useEffect } from 'react';
import { Button } from '@elements/button';
import { Switch } from '@elements/Switch';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faExpand, faCompress, faHistory, faChartLine, faExclamationTriangle } from '@fortawesome/free-solid-svg-icons';
import Loader from '@elements/Loader';

interface AutoScalingRule {
    enabled: boolean;
    cpu_threshold: number;
    memory_threshold: number;
    disk_threshold: number;
    scale_up_step: number;
    scale_down_step: number;
    min_memory: number;
    max_memory: number;
    scale_up_cooldown: number;
    scale_down_cooldown: number;
    last_scale_up_at?: string;
    last_scale_down_at?: string;
}

interface ScalingHistory {
    id: number;
    action: 'up' | 'down';
    old_memory: number;
    new_memory: number;
    cpu_usage: number;
    memory_usage: number;
    memory_usage_percent: number;
    reason: string;
    created_at: string;
}

interface Props {
    serverId: string;
}

export default function AutoScalingPanel({ serverId }: Props) {
    const [rule, setRule] = useState<AutoScalingRule | null>(null);
    const [history, setHistory] = useState<ScalingHistory[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [showHistory, setShowHistory] = useState(false);

    const fetchRule = async () => {
        try {
            const response = await fetch(`/api/client/servers/${serverId}/autoscale`);
            if (response.ok) {
                const data = await response.json();
                setRule(data.data);
            }
        } catch (err) {
            setError('Failed to load auto-scaling settings');
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        fetchRule();
    }, [serverId]);

    const fetchHistory = async () => {
        try {
            const response = await fetch(`/api/client/servers/${serverId}/autoscale/history`);
            if (response.ok) {
                const data = await response.json();
                setHistory(data.data || []);
            }
        } catch (err) {
            console.error('Failed to load history:', err);
        }
    };

    const handleSave = async () => {
        if (!rule) return;

        setIsSaving(true);
        setError(null);
        setSuccess(null);

        try {
            const response = await fetch(`/api/client/servers/${serverId}/autoscale`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(rule),
            });

            if (!response.ok) throw new Error('Failed to save settings');

            setSuccess('Auto-scaling settings saved successfully');
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to save settings');
        } finally {
            setIsSaving(false);
        }
    };

    const handleToggleHistory = async () => {
        if (!showHistory) {
            await fetchHistory();
        }
        setShowHistory(!showHistory);
    };

    const handleEvaluate = async () => {
        try {
            const response = await fetch(`/api/client/servers/${serverId}/autoscale/evaluate`, {
                method: 'POST',
            });

            if (response.ok) {
                const data = await response.json();
                if (data.data) {
                    setSuccess(`Scaling ${data.data.action}: ${data.data.old_memory}MB → ${data.data.new_memory}MB`);
                    await fetchRule();
                    await fetchHistory();
                } else {
                    setSuccess('No scaling needed at this time');
                }
            }
        } catch (err) {
            setError('Failed to evaluate scaling');
        }
    };

    if (isLoading) {
        return (
            <div className="flex items-center justify-center h-32">
                <Loader size="small" />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-lg font-medium flex items-center gap-2">
                        <FontAwesomeIcon icon={faChartLine} className="text-blue-500" />
                        Auto-Scaling
                    </h3>
                    <p className="text-sm text-gray-500">Automatically adjust server resources based on usage</p>
                </div>
                <div className="flex items-center gap-3">
                    <Button variant="secondary" onClick={handleToggleHistory}>
                        <FontAwesomeIcon icon={faHistory} className="mr-2" />
                        {showHistory ? 'Hide' : 'Show'} History
                    </Button>
                    <Switch
                        checked={rule?.enabled || false}
                        onChange={enabled => setRule(prev => (prev ? { ...prev, enabled } : null))}
                    />
                </div>
            </div>

            {error && (
                <div className="p-3 bg-red-50 border border-red-200 rounded text-red-600 text-sm flex items-center gap-2">
                    <FontAwesomeIcon icon={faExclamationTriangle} />
                    {error}
                </div>
            )}

            {success && (
                <div className="p-3 bg-green-50 border border-green-200 rounded text-green-600 text-sm">{success}</div>
            )}

            {rule?.enabled && (
                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">CPU Threshold (%)</label>
                            <input
                                type="number"
                                min="1"
                                max="100"
                                value={rule.cpu_threshold}
                                onChange={e =>
                                    setRule(prev =>
                                        prev ? { ...prev, cpu_threshold: parseInt(e.target.value) } : null,
                                    )
                                }
                                className="w-full px-3 py-2 border rounded"
                            />
                            <p className="text-xs text-gray-500">Scale up when CPU exceeds this %</p>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Memory Threshold (%)</label>
                            <input
                                type="number"
                                min="1"
                                max="100"
                                value={rule.memory_threshold}
                                onChange={e =>
                                    setRule(prev =>
                                        prev ? { ...prev, memory_threshold: parseInt(e.target.value) } : null,
                                    )
                                }
                                className="w-full px-3 py-2 border rounded"
                            />
                            <p className="text-xs text-gray-500">Scale up when memory exceeds this %</p>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Scale Up Step (MB)</label>
                            <input
                                type="number"
                                min="64"
                                step="64"
                                value={rule.scale_up_step}
                                onChange={e =>
                                    setRule(prev =>
                                        prev ? { ...prev, scale_up_step: parseInt(e.target.value) } : null,
                                    )
                                }
                                className="w-full px-3 py-2 border rounded"
                            />
                            <p className="text-xs text-gray-500">Memory to add when scaling up</p>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Scale Down Step (MB)</label>
                            <input
                                type="number"
                                min="64"
                                step="64"
                                value={rule.scale_down_step}
                                onChange={e =>
                                    setRule(prev =>
                                        prev ? { ...prev, scale_down_step: parseInt(e.target.value) } : null,
                                    )
                                }
                                className="w-full px-3 py-2 border rounded"
                            />
                            <p className="text-xs text-gray-500">Memory to remove when scaling down</p>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Min Memory (MB)</label>
                            <input
                                type="number"
                                min="128"
                                value={rule.min_memory}
                                onChange={e =>
                                    setRule(prev => (prev ? { ...prev, min_memory: parseInt(e.target.value) } : null))
                                }
                                className="w-full px-3 py-2 border rounded"
                            />
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Max Memory (MB)</label>
                            <input
                                type="number"
                                min="256"
                                value={rule.max_memory}
                                onChange={e =>
                                    setRule(prev => (prev ? { ...prev, max_memory: parseInt(e.target.value) } : null))
                                }
                                className="w-full px-3 py-2 border rounded"
                            />
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Scale Up Cooldown (min)</label>
                            <input
                                type="number"
                                min="1"
                                value={rule.scale_up_cooldown}
                                onChange={e =>
                                    setRule(prev =>
                                        prev ? { ...prev, scale_up_cooldown: parseInt(e.target.value) } : null,
                                    )
                                }
                                className="w-full px-3 py-2 border rounded"
                            />
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">Scale Down Cooldown (min)</label>
                            <input
                                type="number"
                                min="1"
                                value={rule.scale_down_cooldown}
                                onChange={e =>
                                    setRule(prev =>
                                        prev ? { ...prev, scale_down_cooldown: parseInt(e.target.value) } : null,
                                    )
                                }
                                className="w-full px-3 py-2 border rounded"
                            />
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button variant="primary" onClick={handleSave} loading={isSaving}>
                            Save Settings
                        </Button>
                        <Button variant="secondary" onClick={handleEvaluate}>
                            Evaluate Now
                        </Button>
                    </div>
                </div>
            )}

            {showHistory && (
                <div className="mt-6">
                    <h4 className="font-medium mb-3">Scaling History</h4>
                    {history.length === 0 ? (
                        <p className="text-gray-500 text-sm">No scaling events yet</p>
                    ) : (
                        <div className="space-y-2">
                            {history.slice(0, 10).map(event => (
                                <div
                                    key={event.id}
                                    className="flex items-center justify-between p-3 bg-gray-50 rounded"
                                >
                                    <div className="flex items-center gap-3">
                                        <FontAwesomeIcon
                                            icon={event.action === 'up' ? faExpand : faCompress}
                                            className={event.action === 'up' ? 'text-green-500' : 'text-orange-500'}
                                        />
                                        <div>
                                            <span className="font-medium">
                                                {event.action === 'up' ? 'Scaled Up' : 'Scaled Down'}
                                            </span>
                                            <span className="text-gray-500 text-sm ml-2">
                                                {event.old_memory}MB → {event.new_memory}MB
                                            </span>
                                        </div>
                                    </div>
                                    <span className="text-xs text-gray-500">
                                        {new Date(event.created_at).toLocaleString()}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
