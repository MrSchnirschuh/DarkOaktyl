import http from '@/api/http';

export interface NodeAggregatesResult {
    nodes: Array<{ id: number; name: string }>;
    backups: {
        total: number;
        labels: string[];
        series: number[];
    };
}

export default (nodeIds: number[] = [], start?: string, end?: string, buckets = 20) => {
    const params: Record<string, any> = { buckets, 'node_ids[]': nodeIds };
    if (start) params.start = start;
    if (end) params.end = end;

    return http.get<NodeAggregatesResult>('/api/application/nodes/aggregates', { params }).then(r => r.data);
};
