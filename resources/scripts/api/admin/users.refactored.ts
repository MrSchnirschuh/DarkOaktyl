import { createPromiseCrudApi } from '@/api/createCrudApi';
import { handleApiError } from '@/api/errorHandler';
import http, { getPaginationSet, PaginatedResult, QueryBuilderParams, withQueryBuilderParams } from '@/api/http';
import { Transformers, User } from '@definitions/admin';
import useSWR from 'swr';

export interface UpdateUserValues {
    externalId: string;
    username: string;
    email: string;
    password: string;
    admin_role_id: number | null;
    rootAdmin: boolean;
    state: string;
}

/**
 * CRUD API for users (as proof of concept for factory pattern).
 * These are low-level direct calls.
 */
export const userCrudApi = createPromiseCrudApi<User>('/api/application/users');

/**
 * Helper to convert camelCase to snake_case for user data.
 */
const transformKeys = (values: Partial<UpdateUserValues>): Record<string, any> => {
    const data: Record<string, any> = {};
    Object.keys(values).forEach(k => {
        if (k === 'password' && values[k as keyof UpdateUserValues] === '') {
            return;
        }
        data[k.replace(/[A-Z]/g, l => `_${l.toLowerCase()}`)] = values[k as keyof UpdateUserValues];
    });
    return data;
};

/**
 * Extended user API with custom operations.
 * Shows how the CRUD factory can be extended.
 */
export const userApi = {
    ...userCrudApi,

    /**
     * Search user accounts with query builder.
     */
    search: async (params: QueryBuilderParams<'username' | 'email'>): Promise<User[]> => {
        const { data } = await http.get('/api/application/users', {
            params: withQueryBuilderParams(params),
        });
        return data.data.map(Transformers.toUser);
    },

    /**
     * Create user with proper key transformation.
     */
    create: async (values: UpdateUserValues, include: string[] = [], flashMessage?: (msg: string) => void): Promise<User> => {
        try {
            const { data } = await http.post('/api/application/users', transformKeys(values), {
                params: { include: include.join(',') },
            });
            return Transformers.toUser(data);
        } catch (error) {
            handleApiError(error, flashMessage);
            throw error;
        }
    },

    /**
     * Update user with proper key transformation.
     */
    update: async (
        id: number,
        values: Partial<UpdateUserValues>,
        include: string[] = [],
        flashMessage?: (msg: string) => void,
    ): Promise<User> => {
        try {
            const { data } = await http.patch(`/api/application/users/${id}`, transformKeys(values), {
                params: { include: include.join(',') },
            });
            return Transformers.toUser(data);
        } catch (error) {
            handleApiError(error, flashMessage);
            throw error;
        }
    },

    /**
     * Suspend a user.
     */
    suspend: async (id: number, flashMessage?: (msg: string) => void): Promise<void> => {
        try {
            await http.post(`/api/application/users/${id}/suspend`);
        } catch (error) {
            handleApiError(error, flashMessage);
            throw error;
        }
    },
};

// Re-export for backwards compatibility
export const { getAll, getById, delete: deleteUser } = userCrudApi;
export const { search: searchUserAccounts, create: createUser, update: updateUser, suspend: suspendUser } = userApi;
export default userApi;
