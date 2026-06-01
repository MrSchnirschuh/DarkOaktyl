import http from '@/api/http';
import { TicketMessageFilters } from '@/api/admin/tickets/types';
import { TicketMessage, Transformers } from '@/api/definitions/admin';
import { createPaginatedHook, createContext } from '@/api';

export const Context = createContext<TicketMessageFilters>();

export const getTicketMessages = (id: number) =>
    createPaginatedHook<TicketMessage, TicketMessageFilters>({
        url: `/api/application/tickets/${id}/messages`,
        swrKey: `ticket_messages:${id}`,
        context: Context,
        transformer: Transformers.toTicketMessage,
    })();

export const createMessage = (id: number, values: { message: string }): Promise<TicketMessage> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/application/tickets/${id}/messages`, values)
            .then(({ data }) => resolve(Transformers.toTicketMessage(data)))
            .catch(reject);
    });
};
