import http from '@/api/http';
import { Region, rawDataToRegion } from '@/api/routes/regions';

export interface RegionWithNodes extends Region {
    nodes: RegionNode[];
}

export interface RegionNode {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    fqdn: string;
    public: boolean;
    maintenance_mode: boolean;
    region_id?: number;
}

export interface RegionWithLatency extends Region {
    latency?: number | null;
}

/**
 * Get all regions with their nodes for server deployment.
 */
export const getRegionsWithNodes = (): Promise<RegionWithNodes[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/regions')
            .then(({ data }) => {
                const regions = (data.data || []).map((regionData: any) => {
                    const region = rawDataToRegion(regionData);
                    return {
                        ...region,
                        nodes:
                            regionData.relationships?.nodes?.data?.map((n: any) => ({
                                id: n.id,
                                uuid: n.uuid,
                                name: n.name,
                                description: n.description,
                                fqdn: n.fqdn,
                                public: n.public,
                                maintenance_mode: n.maintenance_mode,
                            })) || [],
                    };
                });
                resolve(regions);
            })
            .catch(reject);
    });
};

/**
 * Get regions filtered by product availability.
 */
export const getRegionsForProduct = (productId: number): Promise<RegionWithNodes[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/billing/regions/${productId}`)
            .then(({ data }) => {
                const regions = (data.data || []).map((regionData: any) => ({
                    ...rawDataToRegion(regionData),
                    nodes:
                        regionData.relationships?.nodes?.data?.map((n: any) => ({
                            id: n.id,
                            uuid: n.uuid,
                            name: n.name,
                            description: n.description,
                            fqdn: n.fqdn,
                            public: n.public,
                            maintenance_mode: n.maintenance_mode,
                        })) || [],
                }));
                resolve(regions);
            })
            .catch(reject);
    });
};

/**
 * Get the default region.
 */
export const getDefaultRegionWithNodes = (): Promise<RegionWithNodes | null> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/regions/default')
            .then(({ data }) => {
                if (!data.data) {
                    resolve(null);
                    return;
                }
                const region = rawDataToRegion(data.data);
                resolve({
                    ...region,
                    nodes:
                        data.data.relationships?.nodes?.data?.map((n: any) => ({
                            id: n.id,
                            uuid: n.uuid,
                            name: n.name,
                            description: n.description,
                            fqdn: n.fqdn,
                            public: n.public,
                            maintenance_mode: n.maintenance_mode,
                        })) || [],
                });
            })
            .catch(reject);
    });
};
