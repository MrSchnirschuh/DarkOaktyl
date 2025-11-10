import http, { FractalResponseData } from '@/api/http';

export interface Category {
    id: string;
    name: string;
    icon?: string;
    description?: string;
    pricingConfigurationId?: number;
    useConfigurator?: boolean;
}

export const rawDataToCategory = ({ attributes: data }: FractalResponseData): Category => ({
    id: data.id,
    name: data.name,
    icon: data.icon,
    description: data.description,
    pricingConfigurationId: data.pricing_configuration_id,
    useConfigurator: data.use_configurator,
});

export default (): Promise<Category[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/billing/categories`)
            .then(({ data }) => resolve((data.data || []).map((datum: any) => rawDataToCategory(datum))))
            .catch(reject);
    });
};
