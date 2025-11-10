import http, { FractalResponseData } from '@/api/http';

export interface Node {
    id: string;
    name: string;
    fqdn: string;
}

export const rawDataToNode = ({ attributes: data }: FractalResponseData): Node => ({
    id: data.id,
    name: data.name,
    fqdn: data.fqdn,
});

export default (productId: number): Promise<Node[]> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/billing/nodes/${productId}`)
            .then(({ data }) => resolve((data.data || []).map((datum: any) => rawDataToNode(datum))))
            .catch(reject);
    });
};
