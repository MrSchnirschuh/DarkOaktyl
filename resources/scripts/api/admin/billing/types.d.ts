export type OrderStatus = 'pending' | 'expired' | 'failed' | 'processed';

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

export interface ResourceScalingRuleInput {
    id?: number;
    threshold: number;
    multiplier: number;
    mode: 'multiplier' | 'surcharge';
    label?: string | null;
}

export interface ResourcePriceValues {
    resource: string;
    displayName: string;
    description?: string | null;
    unit?: string | null;
    baseQuantity: number;
    price: number;
    currency?: string | null;
    minQuantity: number;
    maxQuantity?: number | null;
    defaultQuantity: number;
    stepQuantity: number;
    isVisible: boolean;
    isMetered: boolean;
    sortOrder?: number | null;
    metadata?: Record<string, unknown> | null;
    scalingRules: ResourceScalingRuleInput[];
}

export interface ResourcePriceFilters {
    resource?: string;
    display_name?: string;
    is_visible?: boolean;
}

export interface BillingTermValues {
    name: string;
    slug?: string | null;
    durationDays: number;
    multiplier: number;
    isActive: boolean;
    isDefault: boolean;
    sortOrder?: number | null;
    metadata?: Record<string, unknown> | null;
}

export interface BillingTermFilters {
    name?: string;
    slug?: string;
    is_active?: boolean;
    is_default?: boolean;
}

export interface CouponValues {
    code: string;
    name: string;
    description?: string | null;
    type: 'amount' | 'percentage' | 'resource' | 'duration';
    value?: number | null;
    percentage?: number | null;
    maxUsages?: number | null;
    perUserLimit?: number | null;
    appliesToTermId?: number | null;
    isActive: boolean;
    startsAt?: string | null;
    expiresAt?: string | null;
    metadata?: Record<string, unknown> | null;
}

export interface CouponFilters {
    code?: string;
    name?: string;
    type?: string;
    is_active?: boolean;
}
