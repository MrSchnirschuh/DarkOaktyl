import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR, { mutate } from 'swr';
import { createContext } from '@/api';

export interface DomainRootFilters {
    name?: string;
    root_domain?: string;
    provider?: string;
}

export interface DomainRootValues {
    name: string;
    root_domain: string;
    provider: string;
    is_active: boolean;
    provider_config: Record<string, any>;
}

export interface DomainRoot {
    id: number;
    name: string;
    rootDomain: string;
    provider: string;
    providerConfig: Record<string, any>;
    isActive: boolean;
    createdAt: Date | null;
    updatedAt: Date | null;
}

export const Context = createContext<DomainRootFilters>();

export const rawDataToDomainRoot = ({ attributes: data }: FractalResponseData): DomainRoot => ({
    id: data.id,
    name: data.name,
    rootDomain: data.root_domain,
    provider: data.provider,
    providerConfig: data.provider_config || {},
    isActive: data.is_active,
    createdAt: data.created_at ? new Date(data.created_at) : null,
    updatedAt: data.updated_at ? new Date(data.updated_at) : null,
});

export const getDomainRoots = () => {
    const { page, filters, sort, sortDirection } = useContext(Context);

    const params: Record<string, unknown> = {};
    if (filters) {
        Object.entries(filters).forEach(([key, value]) => {
            if (value === undefined || value === null || value === '') return;
            params[`filter[${key}]`] = value;
        });
    }

    if (sort) {
        params.sort = `${sortDirection ? '-' : ''}${sort}`;
    }

    params.page = page;

    return useSWR<PaginatedResult<DomainRoot>>(['domain-roots', page, filters, sort, sortDirection], async () => {
        const { data } = await http.get('/api/application/domains/roots', { params });

        return {
            items: (data.data || []).map(rawDataToDomainRoot),
            pagination: getPaginationSet(data.meta.pagination),
        };
    });
};

export const createDomainRoot = (values: DomainRootValues): Promise<DomainRoot> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/domains/roots', values)
            .then(({ data }) => resolve(rawDataToDomainRoot(data)))
            .catch(reject);
    });
};

export const updateDomainRoot = (id: number, values: DomainRootValues): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/domains/roots/${id}`, values)
            .then(() => resolve())
            .catch(reject);
    });
};

export const deleteDomainRoot = (id: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/application/domains/roots/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};

export const mutateDomainRoots = () => mutate(['domain-roots']);
