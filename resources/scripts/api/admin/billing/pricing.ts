import { AxiosError } from 'axios';
import useSWR, { SWRResponse } from 'swr';
import { useParams } from 'react-router-dom';
import http from '@/api/http';
import { PricingConfiguration, Transformers } from '@/api/definitions/admin';
import { PricingConfigurationFilters, PricingConfigurationValues } from './types';
import { createPaginatedHook, createContext } from '@/api';

export const Context = createContext<PricingConfigurationFilters>();

export const useGetPricingConfigurations = createPaginatedHook<PricingConfiguration, PricingConfigurationFilters>({
    url: '/api/application/billing/pricing',
    swrKey: 'pricing_configurations',
    context: Context,
    transformer: Transformers.toPricingConfiguration,
});

export const getPricingConfigurations = (): Promise<PricingConfiguration[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/billing/pricing`)
            .then(({ data }) => resolve((data.data || []).map(Transformers.toPricingConfiguration)))
            .catch(reject);
    });
};

export const getPricingConfiguration = async (id: number): Promise<PricingConfiguration> => {
    const { data } = await http.get(`/api/application/billing/pricing/${id}`, {
        params: {
            include: 'durations',
        },
    });

    return Transformers.toPricingConfiguration(data);
};

export const createPricingConfiguration = (values: PricingConfigurationValues): Promise<PricingConfiguration> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/application/billing/pricing`, values)
            .then(({ data }) => resolve(Transformers.toPricingConfiguration(data)))
            .catch(reject);
    });
};

export const updatePricingConfiguration = (id: number, values: Partial<PricingConfigurationValues>): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/billing/pricing/${id}`, values)
            .then(() => resolve())
            .catch(reject);
    });
};

export const deletePricingConfiguration = (id: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/application/billing/pricing/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};

/**
 * Returns an SWR instance by automatically loading in the pricing configuration for the currently
 * loaded route match in the admin area.
 */
export const usePricingConfigurationFromRoute = (): SWRResponse<PricingConfiguration, AxiosError> => {
    const params = useParams<'id'>();

    return useSWR(`/api/application/billing/pricing/${params.id}`, async () => getPricingConfiguration(Number(params.id)));
};
