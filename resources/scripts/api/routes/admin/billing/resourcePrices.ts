import { createContext, createPaginatedHook } from '@/api';
import http from '@/api/http';
import { Transformers, type ResourcePrice } from '@/api/definitions/admin';
import { type ResourcePriceFilters, type ResourcePriceValues } from './types';
import { useParams } from 'react-router-dom';
import useSWR, { type SWRResponse } from 'swr';

export const Context = createContext<ResourcePriceFilters>();

export const useGetResourcePrices = createPaginatedHook<ResourcePrice, ResourcePriceFilters>({
    url: '/api/application/billing/resources',
    swrKey: 'billing:resource-prices',
    context: Context,
    transformer: Transformers.toResourcePrice,
});

const serializeScalingRules = (values: ResourcePriceValues['scalingRules']) =>
    values.map(rule => ({
        id: rule.id,
        threshold: Number(rule.threshold),
        multiplier: Number(rule.multiplier),
        mode: rule.mode,
        label: rule.label ?? null,
    }));

const serializePayload = (values: ResourcePriceValues) => ({
    resource: values.resource,
    display_name: values.displayName,
    description: values.description ?? null,
    unit: values.unit ?? null,
    base_quantity: Number(values.baseQuantity),
    price: Number(values.price),
    currency: values.currency ?? null,
    min_quantity: Number(values.minQuantity),
    max_quantity: values.maxQuantity !== undefined && values.maxQuantity !== null ? Number(values.maxQuantity) : null,
    default_quantity: Number(values.defaultQuantity),
    step_quantity: Number(values.stepQuantity),
    is_visible: values.isVisible,
    is_metered: values.isMetered,
    sort_order: values.sortOrder ?? null,
    metadata: values.metadata ?? null,
    scaling_rules: serializeScalingRules(values.scalingRules),
});

export const getResourcePrice = async (uuid: string): Promise<ResourcePrice> => {
    const { data } = await http.get(`/api/application/billing/resources/${uuid}`);

    return Transformers.toResourcePrice(data);
};

export const createResourcePrice = async (values: ResourcePriceValues): Promise<ResourcePrice> => {
    const { data } = await http.post('/api/application/billing/resources', serializePayload(values));

    return Transformers.toResourcePrice(data);
};

export const updateResourcePrice = async (uuid: string, values: ResourcePriceValues): Promise<void> => {
    await http.patch(`/api/application/billing/resources/${uuid}`, serializePayload(values));
};

export const deleteResourcePrice = async (uuid: string): Promise<void> => {
    await http.delete(`/api/application/billing/resources/${uuid}`);
};

export const useResourcePriceFromRoute = (): SWRResponse<ResourcePrice> => {
    const params = useParams<'uuid'>();

    return useSWR(`/api/application/billing/resources/${params.uuid}`, async () => getResourcePrice(params.uuid!));
};
