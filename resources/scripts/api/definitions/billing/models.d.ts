import { Model } from '@definitions';
import { OrderType } from '@/api/billing/orders/types';

interface Order extends Model {
    id: number;
    name: string;
    user_id: number;
    description: string;
    total: number;
    product_id: number;
    status: OrderStatus;
    type: OrderType;
    created_at: Date;
}

export interface Coupon extends Model {
    id: number;
    code: string;
    description?: string;
    discountType: 'percentage' | 'fixed';
    discountValue: number;
    maxUses?: number;
    uses: number;
    expiresAt?: string;
    isActive: boolean;
    isValid: boolean;
    createdAt: Date;
    updatedAt: Date;
}
