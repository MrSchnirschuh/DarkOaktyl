import http from '@/api/http';
import { type EggVariable, Transformers } from '@definitions/server';

export default (id: number): Promise<EggVariable[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/billing/products/${id}/variables`)
            .then(({ data }) => resolve((data.data || []).map(Transformers.toEggVariable)))
            .catch(reject);
    });
};
