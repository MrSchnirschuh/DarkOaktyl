import http from '@/api/http';

export interface ValidateCouponRequest {
    code: string;
    total: number;
}

export interface ValidateCouponResponse {
    valid: boolean;
    error?: string;
    coupon?: {
        id: number;
        code: string;
        discountType: 'percentage' | 'fixed';
        discountValue: number;
    };
    discountAmount?: number;
    originalTotal?: number;
    newTotal?: number;
}

export const validateCoupon = async (request: ValidateCouponRequest): Promise<ValidateCouponResponse> => {
    const { data } = await http.post('/api/client/billing/coupons/validate', {
        code: request.code,
        total: request.total,
    });

    return data;
};
