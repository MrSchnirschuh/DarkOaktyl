import http, { FractalResponseData } from '@/api/http';

export interface Category {
    id: string;
    name: string;
    icon?: string;
    description?: string;
    eggId?: number;
    nestId?: number;
}

export const rawDataToCategory = ({ attributes: data }: FractalResponseData): Category => ({
    id: data.id,
    name: data.name,
    icon: data.icon,
    description: data.description,
    eggId: data.egg_id ? Number(data.egg_id) : undefined,
    nestId: data.nest_id ? Number(data.nest_id) : undefined,
});

export default (): Promise<Category[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/billing/categories`)
            .then(({ data }) => resolve((data.data || []).map((datum: any) => rawDataToCategory(datum))))
            .catch(reject);
    });
};
