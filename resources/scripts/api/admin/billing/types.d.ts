export type OrderStatus = 'pending' | 'expired' | 'failed' | 'processed';
export type CouponDiscountType = 'percentage' | 'fixed';

export interface ProductValues {
    categoryUuid: string;

    name: string;
    icon: string | undefined;
    price: number;
    description: string;

    limits: {
        cpu: number;
        memory: number;
        disk: number;
        backup: number;
        database: number;
        allocation: number;
    };
}

export interface CategoryValues {
    name: string;
    icon: string;
    description: string;
    visible: boolean;
    eggId: number;
}

export interface ProductFilters {
    id?: string;
    name?: string;
    price?: number;
}

export interface CategoryFilters {
    id?: number;
    name?: string;
}

export interface OrderFilters {
    id?: number;
    name?: string;
    description?: string;
    total?: number;
}

export interface BillingExceptionFilters {
    id?: number;
    title?: string;
}

export interface CouponValues {
    code: string;
    description?: string;
    discountType: CouponDiscountType;
    discountValue: number;
    maxUses?: number;
    expiresAt?: string;
    isActive: boolean;
}

export interface CouponFilters {
    code?: string;
    discountType?: CouponDiscountType;
    isActive?: boolean;
}
