import http from '@/api/http';

export const getPublicKey = (id: number): Promise<{ key: string }> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/billing/products/${id}/key`)
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
