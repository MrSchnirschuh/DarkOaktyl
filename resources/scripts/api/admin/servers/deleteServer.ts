import http from '@/api/http';
import { handleApiError } from '@/api/errorHandler';

export default (id: number, force?: boolean, flashMessage?: (msg: string) => void): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/application/servers/${id}/delete`, { force })
            .then(() => resolve())
            .catch(error => {
                handleApiError(error, flashMessage);
                reject(error);
            });
    });
};
