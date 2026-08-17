import http from '@/api/http';

export interface TransferableRegion {
    id: number;
    uuid: string;
    name: string;
    code: string;
    display_name: string;
    description: string | null;
    timezone: string;
    coordinates: { lat: number; lng: number } | null;
    available_nodes: number;
}

export interface TransferStatus {
    is_transferring: boolean;
    status: string | null;
    old_node?: string;
    new_node?: string;
    created_at?: string;
}

/**
 * Get available regions for server transfer.
 */
export const getAvailableRegions = (serverId: string): Promise<TransferableRegion[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${serverId}/regions`)
            .then(({ data }) => resolve(data.data || []))
            .catch(reject);
    });
};

/**
 * Get server transfer status.
 */
export const getTransferStatus = (serverId: string): Promise<TransferStatus> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${serverId}/transfer-status`)
            .then(({ data }) => resolve(data.data))
            .catch(reject);
    });
};

/**
 * Transfer server to a different region.
 */
export const transferServer = (serverId: string, regionId: number, nodeId?: number): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${serverId}/transfer`, {
            region_id: regionId,
            node_id: nodeId,
        })
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
