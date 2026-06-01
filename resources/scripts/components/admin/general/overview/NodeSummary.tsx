import { useEffect, useMemo, useState } from 'react';
import AdminBox from '@elements/AdminBox';
import Spinner from '@elements/Spinner';
import { Line } from 'react-chartjs-2';
import getNodes from '@/api/admin/nodes/getNodes';
import getNodeUtilization, { NodeUtilization } from '@/api/admin/nodes/getNodeUtilization';
import { useChart } from '@/components/server/console/chart';
// no local store state used

const POLL_MS = 15000;

const formatPercent = (v: number) => `${v.toFixed(1)}%`;

export default function NodeSummary() {
    const { data: nodesData } = getNodes();
    const nodes = nodesData?.items ?? [];
    const [selected, setSelected] = useState<number[]>([]);
    const [loading, setLoading] = useState(false);
    const [metric, setMetric] = useState<'cpu' | 'memory' | 'disk' | 'swap' | 'backups' | 'bandwidth' | 'io'>('cpu');
    const [timeframe, setTimeframe] = useState<'1h' | '6h' | '24h' | '7d' | '1M'>('24h');

    const cpuChart = useChart('CPU', { sets: 1, options: 100 });
    const memChart = useChart('Memory', { sets: 1, options: 100 });

    useEffect(() => {
        // default select all nodes when loaded
        if (nodes.length && selected.length === 0) {
            setSelected(nodes.map(n => n.id));
        }
    }, [nodes]);

    const fetchAndPush = async () => {
        if (!selected.length) return;
        setLoading(true);
        try {
            const results: NodeUtilization[] = [];
            for (const id of selected) {
                try {
                    // eslint-disable-next-line no-await-in-loop
                    const util = await getNodeUtilization(id);
                    results.push(util);
                } catch (e) {
                    // ignore fetch failure for single node
                }
            }

            if (results.length) {
                // compute the metric value per selected metric
                const values = results.map(r => {
                    switch (metric) {
                        case 'cpu':
                            return r.cpu;
                        case 'memory':
                            return (r.memory.used / Math.max(1, r.memory.total)) * 100;
                        case 'disk':
                            return (r.disk.used / Math.max(1, r.disk.total)) * 100;
                        case 'swap':
                            return (r.swap.used / Math.max(1, r.swap.total)) * 100;
                        default:
                            return null;
                    }
                });

                const avg = values.reduce((s, v) => s + (typeof v === 'number' ? v : 0), 0) / values.length;

                // push to chart(s). When metric is cpu we use cpuChart, otherwise reuse memChart for percent-based metrics.
                if (metric === 'cpu') {
                    cpuChart.push(avg);
                } else {
                    memChart.push(avg);
                }
            } else {
                // no results (network error / nothing selected)
                if (metric === 'cpu') cpuChart.push(null);
                else memChart.push(null);
            }
        } finally {
            setLoading(false);
        }
    };

    // Fetch backup / snapshots aggregates from server when metric is a historical type
    useEffect(() => {
        if (metric !== 'backups' && metric !== 'bandwidth' && metric !== 'io' && metric !== 'disk') return;
        if (!selected.length) return;

        const buckets = 20;
        const end = new Date();
        const start = new Date();
        switch (timeframe) {
            case '1h':
                start.setHours(end.getHours() - 1);
                break;
            case '6h':
                start.setHours(end.getHours() - 6);
                break;
            case '7d':
                start.setDate(end.getDate() - 7);
                break;
            case '1M':
                start.setMonth(end.getMonth() - 1);
                break;
            default:
                start.setDate(end.getDate() - 1);
        }

        setLoading(true);

        if (metric === 'backups') {
            import('@/api/admin/nodes/getNodeAggregates').then(({ default: getNodeAggregates }) =>
                getNodeAggregates(selected, start.toISOString(), end.toISOString(), buckets)
                    .then(data => {
                        const series = data.backups.series.map(v => (typeof v === 'number' ? v : null));
                        memChart.clear();
                        series.forEach(s => memChart.push(s));
                    })
                    .finally(() => setLoading(false)),
            );
            return;
        }

        // bandwidth / io / disk (historical)
        import('@/api/admin/nodes/getNodeSnapshots').then(({ default: getNodeSnapshots }) =>
            getNodeSnapshots(selected, start.toISOString(), end.toISOString(), buckets)
                .then(data => {
                    if (metric === 'bandwidth') {
                        const rx = data.bandwidth.rx_speed_bps ?? data.bandwidth.rx_bytes ?? [];
                        const tx = data.bandwidth.tx_speed_bps ?? data.bandwidth.tx_bytes ?? [];
                        const series = rx.map((v: any, i: number) => {
                            const a = typeof v === 'number' ? v : 0;
                            const b = typeof tx[i] === 'number' ? tx[i] : 0;
                            return a + b > 0 ? a + b : null;
                        });
                        memChart.clear();
                        series.forEach((s: number | null) => memChart.push(s));
                    } else if (metric === 'io') {
                        const read = data.io.read_speed_bps ?? [];
                        const write = data.io.write_speed_bps ?? [];
                        const series = read.map((v: any, i: number) => {
                            const a = typeof v === 'number' ? v : 0;
                            const b = typeof write[i] === 'number' ? write[i] : 0;
                            return a + b > 0 ? a + b : null;
                        });
                        memChart.clear();
                        series.forEach((s: number | null) => memChart.push(s));
                    } else if (metric === 'disk') {
                        // show storage percent series for historical disk view
                        const series = data.storage.percent.map((v: any) => (typeof v === 'number' ? v : null));
                        memChart.clear();
                        series.forEach((s: number | null) => memChart.push(s));
                    }
                })
                .finally(() => setLoading(false)),
        );
    }, [metric, timeframe, selected.join(',')]);

    useEffect(() => {
        // immediate fetch
        fetchAndPush();
        const iv = setInterval(fetchAndPush, POLL_MS);
        return () => clearInterval(iv);
    }, [selected.join(',')]);

    const current = useMemo(() => {
        // derive current totals from last dataset point; treat negative placeholders as no-data
        const cpuData = cpuChart.props.data.datasets[0].data;
        const memData = memChart.props.data.datasets[0].data;
        const lastCpuRaw = cpuData[cpuData.length - 1];
        const lastMemRaw = memData[memData.length - 1];
        const lastCpu = typeof lastCpuRaw === 'number' && lastCpuRaw >= 0 ? lastCpuRaw : null;
        const lastMem = typeof lastMemRaw === 'number' && lastMemRaw >= 0 ? lastMemRaw : null;
        // also derive last metric value from memChart (used for backups/counts or bytes/sec depending on metric)
        const metricData = memChart.props.data.datasets[0].data;
        const lastMetricRaw = metricData[metricData.length - 1];
        const lastMetric = typeof lastMetricRaw === 'number' && lastMetricRaw >= 0 ? lastMetricRaw : null;

        return {
            cpu: lastCpu,
            memoryPercent: lastMem,
            metricValue: lastMetric,
        };
    }, [cpuChart.props.data, memChart.props.data]);

    const formatBytesPerSecond = (b?: number | null) => {
        if (b === null || typeof b === 'undefined') return '—';
        const abs = Math.abs(b);
        const KB = 1024;
        const MB = KB * 1024;
        const GB = MB * 1024;
        if (abs >= GB) return `${(b / GB).toFixed(2)} GB/s`;
        if (abs >= MB) return `${(b / MB).toFixed(2)} MB/s`;
        if (abs >= KB) return `${(b / KB).toFixed(2)} KB/s`;
        return `${b.toFixed(0)} B/s`;
    };

    const toggleNode = (id: number) => {
        setSelected(prev => (prev.includes(id) ? prev.filter(x => x !== id) : prev.concat(id)));
    };

    return (
        <AdminBox title={'Node Performance Summary'} icon={undefined as any} className={'mt-6'}>
            <div className={'grid gap-4 lg:grid-cols-3'}>
                <div className={'col-span-1'}>
                    <div className={'text-sm text-theme-muted mb-2'}>Select nodes</div>
                    <div className={'max-h-64 overflow-auto space-y-2'}>
                        {nodes.length === 0 && <div className={'text-theme-muted'}>No nodes available.</div>}
                        {nodes.map(node => (
                            <label key={node.id} className={'flex items-center space-x-2'}>
                                <input
                                    type="checkbox"
                                    checked={selected.includes(node.id)}
                                    onChange={() => toggleNode(node.id)}
                                />
                                <span className={'text-theme-primary'}>{node.name}</span>
                                <span className={'text-xs text-theme-muted ml-auto'}>
                                    {node.memoryUsedPercent ?? 0}% mem
                                </span>
                            </label>
                        ))}
                    </div>
                    <div className={'mt-4'}>
                        <button
                            type={'button'}
                            className={'px-3 py-1 rounded bg-theme-surface text-theme-primary'}
                            onClick={() => setSelected(nodes.map(n => n.id))}
                        >
                            Select all
                        </button>
                        <button
                            type={'button'}
                            className={
                                'ml-2 px-3 py-1 rounded bg-transparent text-theme-muted border border-theme-muted'
                            }
                            onClick={() => setSelected([])}
                        >
                            Clear
                        </button>
                    </div>
                    <div className={'mt-4'}>
                        <div className={'text-sm text-theme-muted mb-2'}>Metric</div>
                        <div className={'flex items-center space-x-2'}>
                            <button
                                type="button"
                                className={`px-2 py-1 rounded ${
                                    metric === 'cpu'
                                        ? 'bg-theme-surface text-theme-primary'
                                        : 'bg-transparent text-theme-muted border border-theme-muted'
                                }`}
                                onClick={() => setMetric('cpu')}
                            >
                                CPU
                            </button>
                            <button
                                type="button"
                                className={`px-2 py-1 rounded ${
                                    metric === 'memory'
                                        ? 'bg-theme-surface text-theme-primary'
                                        : 'bg-transparent text-theme-muted border border-theme-muted'
                                }`}
                                onClick={() => setMetric('memory')}
                            >
                                Memory
                            </button>
                            <button
                                type="button"
                                className={`px-2 py-1 rounded ${
                                    metric === 'disk'
                                        ? 'bg-theme-surface text-theme-primary'
                                        : 'bg-transparent text-theme-muted border border-theme-muted'
                                }`}
                                onClick={() => setMetric('disk')}
                            >
                                Disk
                            </button>
                            <button
                                type="button"
                                className={`px-2 py-1 rounded ${
                                    metric === 'swap'
                                        ? 'bg-theme-surface text-theme-primary'
                                        : 'bg-transparent text-theme-muted border border-theme-muted'
                                }`}
                                onClick={() => setMetric('swap')}
                            >
                                Swap
                            </button>
                            <button
                                type="button"
                                className={`px-2 py-1 rounded ${
                                    metric === 'backups'
                                        ? 'bg-theme-surface text-theme-primary'
                                        : 'bg-transparent text-theme-muted border border-theme-muted'
                                }`}
                                onClick={() => setMetric('backups')}
                            >
                                Backups
                            </button>
                            <button
                                type="button"
                                className={`px-2 py-1 rounded ${
                                    metric === 'bandwidth'
                                        ? 'bg-theme-surface text-theme-primary'
                                        : 'bg-transparent text-theme-muted border border-theme-muted'
                                }`}
                                onClick={() => setMetric('bandwidth')}
                            >
                                Bandwidth
                            </button>
                            <button
                                type="button"
                                className={`px-2 py-1 rounded ${
                                    metric === 'io'
                                        ? 'bg-theme-surface text-theme-primary'
                                        : 'bg-transparent text-theme-muted border border-theme-muted'
                                }`}
                                onClick={() => setMetric('io')}
                            >
                                I/O
                            </button>
                        </div>
                        <div className={'text-xs text-theme-muted mt-2'}>
                            Backups and bandwidth (if your Wings instances report network counters) are available via
                            aggregation.
                        </div>
                        <div className={'mt-3'}>
                            <div className={'text-sm text-theme-muted mb-2'}>Timeframe</div>
                            <div className={'flex items-center space-x-2'}>
                                <button
                                    type="button"
                                    className={`px-2 py-1 rounded ${
                                        timeframe === '1h'
                                            ? 'bg-theme-surface text-theme-primary'
                                            : 'bg-transparent text-theme-muted border border-theme-muted'
                                    }`}
                                    onClick={() => setTimeframe('1h')}
                                >
                                    1h
                                </button>
                                <button
                                    type="button"
                                    className={`px-2 py-1 rounded ${
                                        timeframe === '6h'
                                            ? 'bg-theme-surface text-theme-primary'
                                            : 'bg-transparent text-theme-muted border border-theme-muted'
                                    }`}
                                    onClick={() => setTimeframe('6h')}
                                >
                                    6h
                                </button>
                                <button
                                    type="button"
                                    className={`px-2 py-1 rounded ${
                                        timeframe === '24h'
                                            ? 'bg-theme-surface text-theme-primary'
                                            : 'bg-transparent text-theme-muted border border-theme-muted'
                                    }`}
                                    onClick={() => setTimeframe('24h')}
                                >
                                    24h
                                </button>
                                <button
                                    type="button"
                                    className={`px-2 py-1 rounded ${
                                        timeframe === '7d'
                                            ? 'bg-theme-surface text-theme-primary'
                                            : 'bg-transparent text-theme-muted border border-theme-muted'
                                    }`}
                                    onClick={() => setTimeframe('7d')}
                                >
                                    7d
                                </button>
                                <button
                                    type="button"
                                    className={`px-2 py-1 rounded ${
                                        timeframe === '1M'
                                            ? 'bg-theme-surface text-theme-primary'
                                            : 'bg-transparent text-theme-muted border border-theme-muted'
                                    }`}
                                    onClick={() => setTimeframe('1M')}
                                >
                                    1M
                                </button>
                            </div>
                        </div>
                    </div>
                    <div className={'mt-4 text-sm text-theme-muted'}>
                        Current CPU:{' '}
                        <span className={'font-semibold'}>{current.cpu ? formatPercent(current.cpu) : '—'}</span>
                        <br />
                        Current Memory:{' '}
                        <span className={'font-semibold'}>
                            {current.memoryPercent ? formatPercent(current.memoryPercent) : '—'}
                        </span>
                        <br />
                        {metric === 'bandwidth' && (
                            <>
                                Current Bandwidth:{' '}
                                <span className={'font-semibold'}>{formatBytesPerSecond(current.metricValue)}</span>
                            </>
                        )}
                        {metric === 'io' && (
                            <>
                                Current Disk I/O:{' '}
                                <span className={'font-semibold'}>{formatBytesPerSecond(current.metricValue)}</span>
                            </>
                        )}
                        {metric === 'disk' && timeframe !== '24h' && (
                            <>
                                Current Storage:{' '}
                                <span className={'font-semibold'}>
                                    {current.metricValue ? formatPercent(current.metricValue) : '—'}
                                </span>
                            </>
                        )}
                    </div>
                </div>

                <div className={'col-span-2'}>
                    <div className={'grid gap-4'}>
                        <div className={'bg-black/25 p-3 rounded'}>
                            <div className={'text-xs text-theme-muted mb-2'}>
                                {metric === 'cpu'
                                    ? 'CPU utilization (avg across selected nodes)'
                                    : `${metric.charAt(0).toUpperCase() + metric.slice(1)} utilization % (avg)`}
                            </div>
                            {metric === 'cpu' ? <Line {...cpuChart.props} /> : <Line {...memChart.props} />}
                        </div>
                    </div>
                </div>
            </div>
            {loading && (
                <div className={'mt-3'}>
                    <Spinner centered size={'small'} />
                </div>
            )}
        </AdminBox>
    );
}
