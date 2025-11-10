import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { Coupon } from '@/api/definitions/billing';
import { CouponValues, CouponFilters } from './types.d';

export const getCoupons = async (filters?: CouponFilters): Promise<PaginatedResult<Coupon>> => {
    const { data } = await http.get('/api/application/billing/coupons', {
        params: {
            ...filters,
        },
    });

    return {
        items: (data.data || []).map((d: any) => ({
            id: d.attributes.id,
            code: d.attributes.code,
            description: d.attributes.description,
            discountType: d.attributes.discount_type,
            discountValue: d.attributes.discount_value,
            maxUses: d.attributes.max_uses,
            uses: d.attributes.uses,
            expiresAt: d.attributes.expires_at,
            isActive: d.attributes.is_active,
            isValid: d.attributes.is_valid,
            createdAt: new Date(d.attributes.created_at),
            updatedAt: new Date(d.attributes.updated_at),
        })),
        pagination: getPaginationSet(data.meta?.pagination),
    };
};

export const getCoupon = async (id: number): Promise<Coupon> => {
    const { data } = await http.get(`/api/application/billing/coupons/${id}`);

    return {
        id: data.data.attributes.id,
        code: data.data.attributes.code,
        description: data.data.attributes.description,
        discountType: data.data.attributes.discount_type,
        discountValue: data.data.attributes.discount_value,
        maxUses: data.data.attributes.max_uses,
        uses: data.data.attributes.uses,
        expiresAt: data.data.attributes.expires_at,
        isActive: data.data.attributes.is_active,
        isValid: data.data.attributes.is_valid,
        createdAt: new Date(data.data.attributes.created_at),
        updatedAt: new Date(data.data.attributes.updated_at),
    };
};

export const createCoupon = async (values: CouponValues): Promise<Coupon> => {
    const { data } = await http.post('/api/application/billing/coupons', {
        code: values.code,
        description: values.description,
        discount_type: values.discountType,
        discount_value: values.discountValue,
        max_uses: values.maxUses,
        expires_at: values.expiresAt,
        is_active: values.isActive,
    });

    return {
        id: data.data.attributes.id,
        code: data.data.attributes.code,
        description: data.data.attributes.description,
        discountType: data.data.attributes.discount_type,
        discountValue: data.data.attributes.discount_value,
        maxUses: data.data.attributes.max_uses,
        uses: data.data.attributes.uses,
        expiresAt: data.data.attributes.expires_at,
        isActive: data.data.attributes.is_active,
        isValid: data.data.attributes.is_valid,
        createdAt: new Date(data.data.attributes.created_at),
        updatedAt: new Date(data.data.attributes.updated_at),
    };
};

export const updateCoupon = async (id: number, values: Partial<CouponValues>): Promise<void> => {
    await http.patch(`/api/application/billing/coupons/${id}`, {
        description: values.description,
        discount_type: values.discountType,
        discount_value: values.discountValue,
        max_uses: values.maxUses,
        expires_at: values.expiresAt,
        is_active: values.isActive,
    });
};

export const deleteCoupon = async (id: number): Promise<void> => {
    await http.delete(`/api/application/billing/coupons/${id}`);
};
