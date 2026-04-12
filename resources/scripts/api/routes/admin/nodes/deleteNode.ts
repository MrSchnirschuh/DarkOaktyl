import http from '@/api/http';
import { handleApiError } from '@/api/errorHandler';

export default (id: number, flashMessage?: (msg: string) => void): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/application/nodes/${id}`)
            .then(() => resolve())
            .catch(error => {
                handleApiError(error, flashMessage);
                reject(error);
            });
    });
};
