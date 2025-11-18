import { ModelWithRelationships, Model, UUID } from '@/api/definitions';
import { Server } from '@/api/admin/server';
import { OrderType } from '@/api/billing/orders/types';

type BillingExceptionType = 'payment' | 'deployment' | 'storefront';
type OrderStatus = 'pending' | 'expired' | 'failed' | 'processed';

type EmailAudienceType = 'all_users' | 'specific_users' | 'specific_emails' | 'event_recipient' | 'admins';
type EmailTriggerType = 'event' | 'schedule' | 'resource';
type EmailTriggerScheduleType = 'once' | 'recurring';

interface User extends ModelWithRelationships {
    id: number;
    uuid: UUID;
    externalId: string;
    username: string;
    email: string;
    language: string;
    admin_role_id: number | null;
    roleName: string;
    isRootAdmin: boolean;
    isUsingTwoFactor: boolean;
    avatarUrl: string;
    state: string;
    createdAt: Date;
    updatedAt: Date;
    relationships: {
        role: UserRole | null;
        // TODO: just use an API call, this is probably a bad idea for performance.
        servers?: Server[];
    };
}

interface UserRole extends ModelWithRelationships {
    id: number;
    name: string;
    description: string;
    color?: string | null;
    permissions: string[];
}

interface ApiKeyPermission extends Model {
    r_allocations: string;
    r_database_hosts: string;
    r_eggs: string;
    r_locations: string;
    r_nests: string;
    r_nodes: string;
    r_server_databases: string;
    r_servers: string;
    r_users: string;
}

interface ApiKey extends Model {
    id?: number;
    identifier: string;
    description: string;
    allowed_ips: string[];
    created_at: Date | null;
    last_used_at: Date | null;
}

interface BillingException extends Model {
    id: number;
    uuid: string;
    exception_type: BillingExceptionType;
    order_id?: number;
    title: string;
    description: string;
    created_at: Date;
    updated_at?: Date | null;
}

interface Ticket extends Model {
    id: number;
    title: string;
    user: User;
    assigned_to?: User | undefined;
    status: TicketStatus;
    created_at: Date;
    updated_at?: Date | null;
    relationships: {
        messages?: TicketMessage[];
    };
}

interface TicketMessage extends Model {
    id: number;
    message: string;
    author: User;
    created_at: Date;
    updated_at?: Date | null;
}

interface BillingAnalytics extends Model {
    orders: Order[];
    products: Product[];
    categories: Category[];
}

interface Order extends Model {
    id: number;
    name: string;
    user_id: number;
    description: string;
    total: number;
    status: OrderStatus;
    product_id: number;
    type: OrderType;
    threat_index: number;
    created_at: Date;
    updated_at?: Date | null;
}

interface Product extends Model {
    id: number;
    uuid: string;
    categoryUuid: number;

    name: string;
    icon?: string;
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

    createdAt: Date;
    updatedAt?: Date | null;

    relationships: {
        category?: Category;
    };
}

interface Category extends Model {
    id: number;
    uuid: string;
    name: string;
    icon: string;
    description: string;
    visible: boolean;
    nestId: number;
    eggId: number;

    createdAt: Date;
    updatedAt?: Date | null;

    relationships: {
        products?: Product[];
    };
}

interface AdminRolePermission extends Model {
    key: string;
    description: string;
}

interface EmailTheme extends Model {
    id: number;
    uuid: string;
    name: string;
    description?: string | null;
    variantMode: 'single' | 'dual';
    colors: {
        primary: string;
        secondary: string;
        accent: string;
        background: string;
        body: string;
        text: string;
        muted: string;
        button: string;
        buttonText: string;
    };
    lightColors?: {
        primary?: string;
        secondary?: string;
        accent?: string;
        background?: string;
        body?: string;
        text?: string;
        muted?: string;
        button?: string;
        buttonText?: string;
    } | null;
    logoUrl?: string | null;
    footerText?: string | null;
    isDefault: boolean;
    meta: Record<string, unknown>;
    createdAt: Date;
    updatedAt?: Date | null;
    relationships: Record<string, never>;
}

interface EmailTemplate extends ModelWithRelationships {
    id: number;
    uuid: string;
    key: string;
    name: string;
    description?: string | null;
    subject: string;
    content: string;
    locale: string;
    isEnabled: boolean;
    themeUuid?: string | null;
    metadata: Record<string, unknown>;
    createdAt: Date;
    updatedAt?: Date | null;
    relationships: {
        theme?: EmailTheme | null;
    };
}

interface EmailTriggerAudience extends Model {
    type: EmailAudienceType;
    ids?: number[];
    emails?: string[];
}

interface EmailTriggerPayload extends Model {
    audience?: EmailTriggerAudience;
    data?: Record<string, unknown>;
}

interface EmailTrigger extends ModelWithRelationships {
    id: number;
    uuid: string;
    name: string;
    description?: string | null;
    triggerType: EmailTriggerType;
    scheduleType?: EmailTriggerScheduleType | null;
    eventKey?: string | null;
    scheduleAt?: Date | null;
    cronExpression?: string | null;
    timezone: string;
    templateUuid?: string | null;
    payload?: EmailTriggerPayload | null;
    isActive: boolean;
    lastRunAt?: Date | null;
    nextRunAt?: Date | null;
    createdAt: Date;
    updatedAt?: Date | null;
    relationships: {
        template?: EmailTemplate | null;
    };
}

interface EmailTriggerEvent extends Model {
    key: string;
    label: string;
    description: string;
    context: string[];
}

type ResourceScalingMode = 'multiplier' | 'surcharge';

interface ResourceScalingRule extends Model {
    id: number;
    threshold: number;
    multiplier: number;
    mode: ResourceScalingMode;
    label?: string | null;
    metadata?: Record<string, unknown> | null;
    createdAt: Date;
    updatedAt?: Date | null;
}

interface ResourcePrice extends Model {
    id: number;
    uuid: string;
    resource: string;
    displayName: string;
    description?: string | null;
    unit?: string | null;
    baseQuantity: number;
    price: number;
    currency: string;
    minQuantity: number;
    maxQuantity?: number | null;
    defaultQuantity: number;
    stepQuantity: number;
    isVisible: boolean;
    isMetered: boolean;
    sortOrder: number;
    metadata?: Record<string, unknown> | null;
    scalingRules: ResourceScalingRule[];
    createdAt: Date;
    updatedAt?: Date | null;
}

interface BillingTerm extends Model {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    durationDays: number;
    multiplier: number;
    isActive: boolean;
    isDefault: boolean;
    sortOrder: number;
    metadata?: Record<string, unknown> | null;
    createdAt: Date;
    updatedAt?: Date | null;
}

type CouponType = 'amount' | 'percentage' | 'resource' | 'duration';

interface Coupon extends Model {
    id: number;
    uuid: string;
    code: string;
    name: string;
    description?: string | null;
    type: CouponType;
    value?: number | null;
    percentage?: number | null;
    maxUsages?: number | null;
    perUserLimit?: number | null;
    appliesToTermId?: number | null;
    parentCouponId?: number | null;
    parentCouponUuid?: string | null;
    personalizedForId?: number | null;
    personalizedFor?: {
        id: number;
        uuid: string;
        email: string;
    } | null;
    term?: {
        id: number;
        uuid: string;
        name: string;
    } | null;
    usageCount: number;
    isActive: boolean;
    startsAt?: Date | null;
    expiresAt?: Date | null;
    metadata?: Record<string, unknown> | null;
    createdAt: Date;
    updatedAt?: Date | null;
}

interface CouponRedemption extends Model {
    id: number;
    couponId: number;
    userId?: number | null;
    orderId?: number | null;
    amount: number;
    metadata?: Record<string, unknown> | null;
    redeemedAt: Date;
    createdAt: Date;
    updatedAt?: Date | null;
}
