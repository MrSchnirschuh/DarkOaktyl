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
