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
    pricingConfigurationId?: number;
    useConfigurator?: boolean;
}

export interface PricingConfigurationValues {
    name: string;
    enabled: boolean;
    cpu_price: number;
    memory_price: number;
    disk_price: number;
    backup_price: number;
    database_price: number;
    allocation_price: number;
    small_package_factor: number;
    medium_package_factor: number;
    large_package_factor: number;
    small_package_threshold: number;
    large_package_threshold: number;
    durations?: PricingDurationValues[];
}

export interface PricingDurationValues {
    id?: number;
    duration_days: number;
    price_factor: number;
    enabled: boolean;
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

export interface PricingConfigurationFilters {
    id?: number;
    name?: string;
    enabled?: boolean;
}
