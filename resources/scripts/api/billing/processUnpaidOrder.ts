import http from '@/api/http';
import { Server } from '@definitions/server';

export default (
    product: number,
    node?: number,
    renewal?: boolean,
    variables?: { key: string; value: string }[],
    server_id?: number,
): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/billing/process/free`, { server_id, node, product, renewal, variables })
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
