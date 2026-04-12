import { createContext, createPaginatedHook } from '@/api';
import http from '@/api/http';
import { Transformers, type Coupon } from '@/api/definitions/admin';
import { type CouponFilters, type CouponValues } from './types';
import { useParams } from 'react-router-dom';
import useSWR, { type SWRResponse } from 'swr';

export const Context = createContext<CouponFilters>();

export const useGetCoupons = createPaginatedHook<Coupon, CouponFilters>({
    url: '/api/application/billing/coupons',
    swrKey: 'billing:coupons',
    context: Context,
    transformer: Transformers.toCoupon,
});

const serializePayload = (values: CouponValues) => {
    const payload: Record<string, unknown> = {
        code: values.code.trim().toUpperCase(),
        name: values.name.trim(),
        type: values.type,
        is_active: values.isActive,
    };

    if (values.description && values.description.trim().length > 0) {
        payload.description = values.description.trim();
    }

    if (values.value !== undefined && values.value !== null) {
        payload.value = Number(values.value);
    }

    if (values.percentage !== undefined && values.percentage !== null) {
        payload.percentage = Number(values.percentage);
    }

    if (values.maxUsages !== undefined && values.maxUsages !== null) {
        payload.max_usages = Number(values.maxUsages);
    }

    if (values.perUserLimit !== undefined && values.perUserLimit !== null) {
        payload.per_user_limit = Number(values.perUserLimit);
    }

    if (values.appliesToTermId !== undefined && values.appliesToTermId !== null) {
        payload.applies_to_term_id = Number(values.appliesToTermId);
    }

    if (values.startsAt && values.startsAt.trim().length > 0) {
        payload.starts_at = values.startsAt;
    }

    if (values.expiresAt && values.expiresAt.trim().length > 0) {
        payload.expires_at = values.expiresAt;
    }

    if (values.metadata !== undefined) {
        payload.metadata = values.metadata;
    }

    return payload;
};

export const getCoupon = async (uuid: string): Promise<Coupon> => {
    const { data } = await http.get(`/api/application/billing/coupons/${uuid}`);

    return Transformers.toCoupon(data);
};

export const createCoupon = async (values: CouponValues): Promise<Coupon> => {
    const { data } = await http.post('/api/application/billing/coupons', serializePayload(values));

    return Transformers.toCoupon(data);
};

export const updateCoupon = async (uuid: string, values: CouponValues): Promise<void> => {
    await http.patch(`/api/application/billing/coupons/${uuid}`, serializePayload(values));
};

export const deleteCoupon = async (uuid: string): Promise<void> => {
    await http.delete(`/api/application/billing/coupons/${uuid}`);
};

export const useCouponFromRoute = (): SWRResponse<Coupon> => {
    const params = useParams<'uuid'>();

    return useSWR(`/api/application/billing/coupons/${params.uuid}`, async () => getCoupon(params.uuid!));
};
