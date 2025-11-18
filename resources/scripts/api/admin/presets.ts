import http from '@/api/http';

export interface ServerPreset {
    id: number;
    uuid: string;
    name: string;
    description?: string | null;
    settings?: Record<string, any> | null;
    port_start?: number | null;
    port_end?: number | null;
    visibility: 'global' | 'private';
    user_id?: number | null;
}

export const getPresets = (): Promise<ServerPreset[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/application/presets')
            .then(({ data }) => resolve(data.data || []))
            .catch(reject);
    });
};

export const createPreset = (payload: Partial<ServerPreset>): Promise<ServerPreset> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/presets', payload)
            .then(({ data }) => resolve(data.data))
            .catch(reject);
    });
};

export const updatePreset = (id: number, payload: Partial<ServerPreset>): Promise<ServerPreset> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/presets/${id}`, payload)
            .then(({ data }) => resolve(data.data))
            .catch(reject);
    });
};

export const deletePreset = (id: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/application/presets/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};

export default {
    getPresets,
    createPreset,
    deletePreset,
};
