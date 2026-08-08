import http from '@/api/http';

export interface Region {
    id: number;
    uuid: string;
    name: string;
    code: string;
    display_name: string;
    description: string | null;
    timezone: string;
    coordinates: { lat: number; lng: number } | null;
    is_active: boolean;
    is_default: boolean;
    ping_endpoint: string | null;
    created_at: string;
    updated_at: string;
    relationships?: {
        nodes?: RegionNode[];
    };
}

export interface RegionNode {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    fqdn: string;
    public: boolean;
    maintenance_mode: boolean;
}

export interface LatencyInfo {
    id: number;
    uuid: string;
    code: string;
    ping_endpoint: string;
}

/**
 * Get all active regions.
 */
export const getRegions = (): Promise<Region[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/regions')
            .then(({ data }) => resolve((data.data || []).map(rawDataToRegion)))
            .catch(reject);
    });
};

/**
 * Get default region.
 */
export const getDefaultRegion = (): Promise<Region | null> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/regions/default')
            .then(({ data }) => resolve(data.data ? rawDataToRegion(data.data) : null))
            .catch(reject);
    });
};

/**
 * Get latency endpoints for client-side ping.
 */
export const getLatencyEndpoints = (): Promise<LatencyInfo[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/regions/latency')
            .then(({ data }) => resolve(data.data || []))
            .catch(reject);
    });
};

/**
 * Transform raw API data to Region type.
 */
export const rawDataToRegion = (data: any): Region => ({
    id: data.id,
    uuid: data.uuid,
    name: data.name,
    code: data.code,
    display_name: data.display_name,
    description: data.description,
    timezone: data.timezone,
    coordinates: data.coordinates,
    is_active: data.is_active,
    is_default: data.is_default,
    ping_endpoint: data.ping_endpoint,
    created_at: data.created_at,
    updated_at: data.updated_at,
    relationships: data.relationships
        ? {
              nodes: data.relationships.nodes?.data?.map((n: any) => ({
                  id: n.id,
                  uuid: n.uuid,
                  name: n.name,
                  description: n.description,
                  fqdn: n.fqdn,
                  public: n.public,
                  maintenance_mode: n.maintenance_mode,
              })),
          }
        : undefined,
});

/**
 * Measure latency to a specific endpoint.
 * Returns latency in milliseconds or null if failed.
 */
export const measureLatency = async (endpoint: string, timeout = 5000): Promise<number | null> => {
    return new Promise(resolve => {
        const startTime = performance.now();
        const img = new Image();
        let resolved = false;

        const cleanup = () => {
            if (!resolved) {
                resolved = true;
                img.onload = img.onerror = null;
            }
        };

        const timer = setTimeout(() => {
            cleanup();
            resolve(null);
        }, timeout);

        img.onload = () => {
            clearTimeout(timer);
            cleanup();
            resolve(Math.round(performance.now() - startTime));
        };

        img.onerror = () => {
            clearTimeout(timer);
            cleanup();
            // Even error means connection worked
            resolve(Math.round(performance.now() - startTime));
        };

        // Add cache-buster to avoid cached responses
        img.src = `${endpoint}?_=${Date.now()}`;
    });
};

/**
 * Measure latencies to all regions.
 */
export const measureAllLatencies = async (regions: LatencyInfo[]): Promise<Map<string, number | null>> => {
    const results = new Map<string, number | null>();

    // Test sequentially to avoid overwhelming the browser
    for (const region of regions) {
        if (region.ping_endpoint) {
            const latency = await measureLatency(region.ping_endpoint);
            results.set(region.code, latency);
        } else {
            results.set(region.code, null);
        }
    }

    return results;
};
