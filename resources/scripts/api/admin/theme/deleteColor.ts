import http from '@/api/http';

export default (key: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/application/theme/colors`, { data: { key } })
            .then(() => resolve())
            .catch(reject);
    });
};
