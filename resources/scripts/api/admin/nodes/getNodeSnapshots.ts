import http from '@/api/http';

export interface NodeSnapshotsResult {
    nodes: Array<{ id: number; name: string }>;
    bandwidth: {
        labels: string[];
        rx_bytes: number[];
        tx_bytes: number[];
        rx_speed_bps: number[];
        tx_speed_bps: number[];
    };
    io: {
        read_speed_bps: number[];
        write_speed_bps: number[];
    };
    storage: {
        percent: Array<number | null>;
    };
}

export default (nodeIds: number[] = [], start?: string, end?: string, buckets = 20) => {
    const params: Record<string, any> = { buckets, 'node_ids[]': nodeIds };
    if (start) params.start = start;
    if (end) params.end = end;

    return http.get<NodeSnapshotsResult>('/api/application/nodes/snapshots', { params }).then(r => r.data);
};
