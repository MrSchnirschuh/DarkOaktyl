import http from '@/api/http';

export interface PriceCalculationRequest {
    pricing_configuration_id: number;
    cpu: number;
    memory: number;
    disk: number;
    backups: number;
    databases: number;
    allocations: number;
    duration_days?: number;
}

export interface PriceCalculationResponse {
    base_price: number;
    duration_factor: number;
    final_price: number;
    currency: string;
}

export const calculatePrice = async (request: PriceCalculationRequest): Promise<PriceCalculationResponse> => {
    const { data } = await http.post('/api/client/billing/calculate-price', request);
    return data;
};
