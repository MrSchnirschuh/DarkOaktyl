import http from './http';

export interface CrudApi<T> {
    getAll: () => Promise<{ data: T[] }>;
    getById: (id: string) => Promise<{ data: T }>;
    create: (data: Partial<T>) => Promise<{ data: T }>;
    update: (id: string, data: Partial<T>) => Promise<{ data: T }>;
    delete: (id: string) => Promise<void>;
}

export const createCrudApi = <T>(basePath: string): CrudApi<T> => ({
    getAll: () => http.get(basePath),
    getById: (id: string) => http.get(`${basePath}/${id}`),
    create: (data: Partial<T>) => http.post(basePath, data),
    update: (id: string, data: Partial<T>) => http.patch(`${basePath}/${id}`, data),
    delete: (id: string) => http.delete(`${basePath}/${id}`),
});
