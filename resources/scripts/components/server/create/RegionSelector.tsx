import { useEffect, useState, useCallback } from 'react';
import { Region, getRegions, getLatencyEndpoints, measureAllLatencies, LatencyInfo } from '@/api/routes/regions';
import { Alert } from '@elements/alert';
import Spinner from '@elements/Spinner';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faGlobe, faWifi, faSignal, faSignalGood, faSignalWeak } from '@fortawesome/free-solid-svg-icons';

interface RegionSelectorProps {
    selectedRegionId: number | null;
    onRegionSelect: (regionId: number) => void;
    showLatency?: boolean;
    disabled?: boolean;
}

interface RegionWithLatency extends Region {
    latency?: number | null;
}

/**
 * Get latency indicator color and icon based on latency value.
 */
const getLatencyIndicator = (latency: number | null | undefined): { color: string; icon: any; label: string } => {
    if (latency === null || latency === undefined) {
        return { color: 'text-gray-400', icon: faWifi, label: 'Unknown' };
    }
    if (latency < 50) {
        return { color: 'text-green-500', icon: faSignal, label: 'Excellent' };
    }
    if (latency < 100) {
        return { color: 'text-green-400', icon: faSignalGood, label: 'Good' };
    }
    if (latency < 200) {
        return { color: 'text-yellow-500', icon: faSignalWeak, label: 'Fair' };
    }
    return { color: 'text-red-500', icon: faSignalWeak, label: 'Poor' };
};

/**
 * Format latency value for display.
 */
const formatLatency = (latency: number | null | undefined): string => {
    if (latency === null || latency === undefined) return '-- ms';
    return `${latency} ms`;
};

/**
 * Region Selector Component
 *
 * Displays available regions with latency information for server creation.
 */
export default function RegionSelector({
    selectedRegionId,
    onRegionSelect,
    showLatency = true,
    disabled = false,
}: RegionSelectorProps) {
    const [regions, setRegions] = useState<RegionWithLatency[]>([]);
    const [loading, setLoading] = useState(true);
    const [measuringLatency, setMeasuringLatency] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Fetch regions on mount
    useEffect(() => {
        const fetchRegions = async () => {
            try {
                setLoading(true);
                setError(null);
                const data = await getRegions();
                setRegions(data);
            } catch (err) {
                setError('Failed to load regions. Please try again.');
                console.error('Error loading regions:', err);
            } finally {
                setLoading(false);
            }
        };

        fetchRegions();
    }, []);

    // Measure latency when regions are loaded
    useEffect(() => {
        if (!showLatency || regions.length === 0 || measuringLatency) return;

        const measure = async () => {
            try {
                setMeasuringLatency(true);
                const endpoints = await getLatencyEndpoints();

                if (endpoints.length === 0) return;

                const latencies = await measureAllLatencies(endpoints);

                setRegions(prev =>
                    prev.map(region => ({
                        ...region,
                        latency: latencies.get(region.code) ?? null,
                    })),
                );
            } catch (err) {
                console.error('Error measuring latency:', err);
            } finally {
                setMeasuringLatency(false);
            }
        };

        measure();
    }, [regions.length, showLatency]);

    // Get default region if none selected
    useEffect(() => {
        if (!selectedRegionId && regions.length > 0) {
            const defaultRegion = regions.find(r => r.is_default);
            if (defaultRegion) {
                onRegionSelect(defaultRegion.id);
            } else {
                onRegionSelect(regions[0].id);
            }
        }
    }, [regions, selectedRegionId, onRegionSelect]);

    if (loading) {
        return (
            <div className="flex items-center justify-center p-8">
                <Spinner size="large" />
                <span className="ml-3 text-gray-400">Loading regions...</span>
            </div>
        );
    }

    if (error) {
        return (
            <Alert type="danger" className="mb-4">
                {error}
            </Alert>
        );
    }

    if (regions.length === 0) {
        return (
            <Alert type="warning" className="mb-4">
                No regions available. Please contact an administrator.
            </Alert>
        );
    }

    // Sort regions: default first, then by latency
    const sortedRegions = [...regions].sort((a, b) => {
        if (a.is_default && !b.is_default) return -1;
        if (!a.is_default && b.is_default) return 1;

        // Sort by latency if available
        if (a.latency !== undefined && b.latency !== undefined) {
            if (a.latency === null && b.latency !== null) return 1;
            if (a.latency !== null && b.latency === null) return -1;
            if (a.latency !== null && b.latency !== null) {
                return a.latency - b.latency;
            }
        }

        return a.display_name.localeCompare(b.display_name);
    });

    return (
        <div className="space-y-3">
            {measuringLatency && (
                <div className="text-sm text-gray-400 flex items-center">
                    <Spinner size="small" className="mr-2" />
                    Measuring latency...
                </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                {sortedRegions.map(region => {
                    const isSelected = selectedRegionId === region.id;
                    const latencyInfo = getLatencyIndicator(region.latency);
                    const hasNodes = region.relationships?.nodes?.some(n => n.public && !n.maintenance_mode);

                    return (
                        <button
                            key={region.uuid}
                            onClick={() => !disabled && hasNodes && onRegionSelect(region.id)}
                            disabled={disabled || !hasNodes}
                            className={`
                                relative p-4 rounded-lg border-2 text-left transition-all duration-200
                                ${
                                    isSelected
                                        ? 'border-blue-500 bg-blue-500/10'
                                        : 'border-gray-700 hover:border-gray-600 bg-gray-800/50'
                                }
                                ${
                                    disabled || !hasNodes
                                        ? 'opacity-50 cursor-not-allowed'
                                        : 'cursor-pointer hover:bg-gray-800'
                                }
                            `}
                        >
                            {/* Default Badge */}
                            {region.is_default && (
                                <span className="absolute top-2 right-2 text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded">
                                    Default
                                </span>
                            )}

                            {/* No Nodes Warning */}
                            {!hasNodes && (
                                <span className="absolute top-2 right-2 text-xs bg-red-500/20 text-red-400 px-2 py-0.5 rounded">
                                    Full
                                </span>
                            )}

                            <div className="flex items-start gap-3">
                                <div className="mt-1">
                                    <FontAwesomeIcon
                                        icon={faGlobe}
                                        className={`text-xl ${isSelected ? 'text-blue-400' : 'text-gray-400'}`}
                                    />
                                </div>

                                <div className="flex-1 min-w-0">
                                    <div className="font-semibold text-gray-200 truncate">{region.display_name}</div>

                                    {region.description && (
                                        <div className="text-xs text-gray-500 mt-1 line-clamp-2">
                                            {region.description}
                                        </div>
                                    )}

                                    {/* Latency Display */}
                                    {showLatency && (
                                        <div className="flex items-center gap-2 mt-2">
                                            <FontAwesomeIcon
                                                icon={latencyInfo.icon}
                                                className={`${latencyInfo.color}`}
                                            />
                                            <span className={`text-sm ${latencyInfo.color}`}>
                                                {formatLatency(region.latency)}
                                            </span>
                                            {region.latency !== undefined && region.latency !== null && (
                                                <span className="text-xs text-gray-500">({latencyInfo.label})</span>
                                            )}
                                        </div>
                                    )}

                                    {/* Node Count */}
                                    <div className="text-xs text-gray-500 mt-2">
                                        {region.relationships?.nodes?.length || 0} nodes available
                                    </div>
                                </div>
                            </div>

                            {/* Selected Indicator */}
                            {isSelected && (
                                <div className="absolute bottom-2 right-2">
                                    <div className="w-4 h-4 rounded-full bg-blue-500 flex items-center justify-center">
                                        <svg className="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fillRule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            )}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

export { RegionSelector };
export type { RegionSelectorProps, RegionWithLatency };
