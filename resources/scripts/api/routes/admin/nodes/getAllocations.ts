import http, { FractalResponseData } from '@/api/http';
import { rawDataToServer, Server } from '@/api/routes/admin/servers/getServers';

export interface Allocation {
    id: number;
    ip: string;
    port: number;
    alias: string | null;
    serverId: number | null;
    assigned: boolean;

    relations: {
        server?: Server;
    };

    getDisplayText(): string;
}

export const rawDataToAllocation = ({ attributes }: FractalResponseData): Allocation => ({
    id: attributes.id,
    ip: attributes.ip,
    port: attributes.port,
    alias: attributes.alias || null,
    serverId: attributes.server_id,
    assigned: attributes.assigned,

    relations: {
        server:
            attributes.relationships?.server?.object === 'server'
                ? rawDataToServer(attributes.relationships.server as FractalResponseData)
                : undefined,
    },

    // ponytail: IPv6 addresses wrapped in [] per RFC 3986
    getDisplayText(): string {
        const ip = attributes.ip.includes(':') ? `[${attributes.ip}]` : attributes.ip;
        if (attributes.alias !== null) {
            return `${ip}:${attributes.port} (${attributes.alias})`;
        }
        return `${ip}:${attributes.port}`;
    },
});

export interface Filters {
    search?: string;
    /* eslint-disable camelcase */
    server_id?: string;
    /* eslint-enable camelcase */
}

export default (id: string | number, filters: Filters = {}, include: string[] = []): Promise<Allocation[]> => {
    const params: Record<string, string> = {};
    if (filters !== null) {
        (Object.keys(filters) as (keyof Filters)[]).forEach(key => {
            const val = filters[key];
            if (val !== undefined) {
                params['filter[' + key + ']'] = val;
            }
        });
    }

    return new Promise((resolve, reject) => {
        http.get(`/api/application/nodes/${id}/allocations`, { params: { include: include.join(','), ...params } })
            .then(({ data }) => resolve((data.data || []).map(rawDataToAllocation)))
            .catch(reject);
    });
};
