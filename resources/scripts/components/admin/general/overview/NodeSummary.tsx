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
                // compute average CPU and memory percent used (used/total)
                const avgCpu = results.reduce((s, r) => s + r.cpu, 0) / results.length;
                const avgMemPercent =
                    (results.reduce((s, r) => s + (r.memory.used / Math.max(1, r.memory.total)), 0) / results.length) *
                    100;

                cpuChart.push(avgCpu);
                memChart.push(avgMemPercent);
            } else {
                cpuChart.push(null);
                memChart.push(null);
            }
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        // immediate fetch
        fetchAndPush();
        const iv = setInterval(fetchAndPush, POLL_MS);
        return () => clearInterval(iv);
    }, [selected.join(',')]);

    const current = useMemo(() => {
        // derive current totals from last dataset point
        const cpuData = cpuChart.props.data.datasets[0].data;
        const memData = memChart.props.data.datasets[0].data;
        const lastCpu = cpuData[cpuData.length - 1] ?? null;
        const lastMem = memData[memData.length - 1] ?? null;
        return {
            cpu: typeof lastCpu === 'number' ? lastCpu : null,
            memoryPercent: typeof lastMem === 'number' ? lastMem : null,
        };
    }, [cpuChart.props.data, memChart.props.data]);

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
                            className={'ml-2 px-3 py-1 rounded bg-transparent text-theme-muted border border-theme-muted'}
                            onClick={() => setSelected([])}
                        >
                            Clear
                        </button>
                    </div>
                    <div className={'mt-4 text-sm text-theme-muted'}>
                        Current CPU: <span className={'font-semibold'}>{current.cpu ? formatPercent(current.cpu) : '—'}</span>
                        <br />
                        Current Memory: <span className={'font-semibold'}>{current.memoryPercent ? formatPercent(current.memoryPercent) : '—'}</span>
                    </div>
                </div>

                <div className={'col-span-2'}>
                    <div className={'grid gap-4'}>
                        <div className={'bg-black/25 p-3 rounded'}>
                            <div className={'text-xs text-theme-muted mb-2'}>CPU utilization (avg across selected nodes)</div>
                            <Line {...cpuChart.props} />
                        </div>

                        <div className={'bg-black/25 p-3 rounded'}>
                            <div className={'text-xs text-theme-muted mb-2'}>Memory utilization % (avg)</div>
                            <Line {...memChart.props} />
                        </div>
                    </div>
                </div>
            </div>
            {loading && <div className={'mt-3'}><Spinner centered size={'small'} /></div>}
        </AdminBox>
    );
}
