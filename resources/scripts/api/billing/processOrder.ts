import http from '@/api/http';

export default (intent: string, renewal?: boolean): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/billing/process`, { intent, renewal })
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
