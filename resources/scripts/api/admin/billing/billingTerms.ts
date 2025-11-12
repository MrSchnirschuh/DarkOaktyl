import { createContext, createPaginatedHook } from '@/api';
import http from '@/api/http';
import { Transformers, type BillingTerm } from '@/api/definitions/admin';
import { type BillingTermFilters, type BillingTermValues } from './types';
import { useParams } from 'react-router-dom';
import useSWR, { type SWRResponse } from 'swr';

export const Context = createContext<BillingTermFilters>();

export const useGetBillingTerms = createPaginatedHook<BillingTerm, BillingTermFilters>({
    url: '/api/application/billing/terms',
    swrKey: 'billing:terms',
    context: Context,
    transformer: Transformers.toBillingTerm,
});

const serializePayload = (values: BillingTermValues) => {
    const payload: Record<string, unknown> = {
        name: values.name,
        duration_days: Number(values.durationDays),
        multiplier: Number(values.multiplier),
        is_active: values.isActive,
        is_default: values.isDefault,
    };

    if (values.slug && values.slug.trim().length > 0) {
        payload.slug = values.slug.trim().toLowerCase();
    }

    if (values.sortOrder !== undefined && values.sortOrder !== null) {
        payload.sort_order = Number(values.sortOrder);
    }

    if (values.metadata !== undefined) {
        payload.metadata = values.metadata;
    }

    return payload;
};

export const getBillingTerm = async (uuid: string): Promise<BillingTerm> => {
    const { data } = await http.get(`/api/application/billing/terms/${uuid}`);

    return Transformers.toBillingTerm(data);
};

export const createBillingTerm = async (values: BillingTermValues): Promise<BillingTerm> => {
    const { data } = await http.post('/api/application/billing/terms', serializePayload(values));

    return Transformers.toBillingTerm(data);
};

export const updateBillingTerm = async (uuid: string, values: BillingTermValues): Promise<void> => {
    await http.patch(`/api/application/billing/terms/${uuid}`, serializePayload(values));
};

export const deleteBillingTerm = async (uuid: string): Promise<void> => {
    await http.delete(`/api/application/billing/terms/${uuid}`);
};

export const useBillingTermFromRoute = (): SWRResponse<BillingTerm> => {
    const params = useParams<'uuid'>();

    return useSWR(`/api/application/billing/terms/${params.uuid}`, async () => getBillingTerm(params.uuid!));
};
