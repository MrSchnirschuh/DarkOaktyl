import http from '@/api/http';
import { createCrudApi } from '@/api/createCrudApi';
import { handleApiError } from '@/api/errorHandler';
import { Server, rawDataToServer, Filters, rawDataToServerVariable, ServerVariable } from './getServers';
import { CreateServerRequest } from './createServer';
import { Values as UpdateServerValues } from './updateServer';
import { FractalPaginatedResponse, FractalResponseData, PaginatedResult } from '@/api/http';

const BASE_PATH = '/api/application/servers';

// Core CRUD API für Server
coreApi = createCrudApi<Server>(BASE_PATH);

// Extended API mit spezifischen Server-Operationen und Error Handling
export const serversApi = {
    // Liste alle Server mit Pagination und Filtern
    getAll: async (params?: {
        page?: number;
        filters?: Filters;
        include?: string[];
    }, flashMessage?: (msg: string) => void): Promise<PaginatedResult<Server>> => {
        try {
            const queryParams: Record<string, any> = {};
            
            if (params?.include?.length) {
                queryParams.include = params.include.join(',');
            }
            if (params?.page) {
                queryParams.page = params.page;
            }
            if (params?.filters) {
                Object.keys(params.filters).forEach(key => {
                    const value = params.filters?.[key as keyof Filters];
                    if (value) {
                        queryParams[`filter[${key}]`] = value;
                    }
                });
            }

            const { data } = await http.get<FractalPaginatedResponse>(BASE_PATH, {
                params: queryParams,
            });

            return {
                items: (data.data || []).map(rawDataToServer),
                pagination: {
                    total: data.meta.pagination.total,
                    count: data.meta.pagination.count,
                    perPage: data.meta.pagination.per_page,
                    currentPage: data.meta.pagination.current_page,
                    totalPages: data.meta.pagination.total_pages,
                },
            };
        } catch (error) {
            handleApiError(error, flashMessage);
            throw error;
        }
    },

    // Einzelnen Server abrufen
    getById: async (id: number, include: string[] = [], flashMessage?: (msg: string) => void): Promise<Server> => {
        try {
            const { data } = await http.get<{ data: FractalResponseData }>(
                `${BASE_PATH}/${id}`,
                { params: { include: include.join(',') } }
            );
            return rawDataToServer(data);
        } catch (error) {
            handleApiError(error, flashMessage);
            throw error;
        }
    },

    // Neuen Server erstellen
    create: async (request: CreateServerRequest, include: string[] = [], flashMessage?: (msg: string) => void): Promise<Server> => {
        try {
            const { data } = await http.post(
                BASE_PATH,
                {
                    externalId: request.externalId,
                    name: request.name,
                    description: request.description,
                    owner_id: request.ownerId,
                    node_id: request.nodeId,
                    limits: {
                        cpu: request.limits.cpu,
                        disk: request.limits.disk,
                        io: request.limits.io,
                        memory: request.limits.memory,
                        swap: request.limits.swap,
                        threads: request.limits.threads,
                        oom_killer: request.limits.oomKiller,
                    },
                    feature_limits: {
                        allocations: request.featureLimits.allocations,
                        backups: request.featureLimits.backups,
                        databases: request.featureLimits.databases,
                        subusers: request.featureLimits.subusers,
                    },
                    allocation: {
                        default: request.allocation.default,
                        additional: request.allocation.additional,
                    },
                    startup: request.startup,
                    environment: request.environment,
                    egg_id: request.eggId,
                    image: request.image,
                    skip_scripts: request.skipScripts,
                    start_on_completion: request.startOnCompletion,
                },
                { params: { include: include.join(',') } }
            );
            return rawDataToServer(data);
        } catch (error) {
            handleApiError(error, flashMessage);
            throw error;
        }
    },

    // Server aktualisieren
    update: async (id: number, server: Partial<UpdateServerValues>, include: string[] = [], flashMessage?: (msg: string) => void): Promise<Server> => {
        try {
            const { data } = await http.patch(
                `${BASE_PATH}/${id}`,
                {
                    external_id: server.externalId,
                    name: server.name,
                    owner_id: server.ownerId,
                    limits: {
                        memory: server.limits?.memory,
                        swap: server.limits?.swap,
                        disk: server.limits?.disk,
                        io: server.limits?.io,
                        cpu: server.limits?.cpu,
                        threads: server.limits?.threads,
                        oom_killer: server.limits?.oomKiller,
                    },
                    feature_limits: {
                        allocations: server.featureLimits?.allocations,
                        backups: server.featureLimits?.backups,
                        databases: server.featureLimits?.databases,
                        subusers: server.featureLimits?.subusers,
                    },
                    renewal_date: server.renewalDate,
                    billing_product_id: server.billingProductId,
                    allocation_id: server.allocationId,
                    add_allocations: server.addAllocations,
                    remove_allocations: server.removeAllocations,
                },
                { params: { include: include.join(',') } }
            );
            return rawDataToServer(data);
        } catch (error) {
            handleApiError(error, flashMessage);
            throw error;
        }
    },

    // Server löschen
    delete: async (id: number, force?: boolean, flashMessage?: (msg: string) => void): Promise<void> => {
        try {
            await http.post(`${BASE_PATH}/${id}/delete`, { force });
        } catch (error) {
            handleApiError(error, flashMessage);
            throw error;
        }
    },

    // Server Startup aktualisieren
    updateStartup: async (id: number, startup: string, environment: Record<string, string>, image: string, flashMessage?: (msg: string) => void): Promise<void> => {
        try {
            await http.put(`${BASE_PATH}/${id}/startup`, {
                startup,
                environment,
                image,
            });
        } catch (error) {
            handleApiError(error, flashMessage);
            throw error;
        }
    },
};

// Re-exports für Kompatibilität
export { Server, rawDataToServer, ServerVariable, rawDataToServerVariable, Filters };

export default serversApi;