import type { AxiosError } from 'axios';
import type { SWRConfiguration } from 'swr';
import useSWR from 'swr';

import http, { FractalResponseList } from '@/api/http';
import { Passkey, Transformers, AuthLoginMethod } from '@definitions/user';
import { useUserSWRKey } from '@/plugins/useSWRKey';

export interface PasskeyRegistrationOptions {
    token: string;
    options: Record<string, unknown>;
}

export const usePasskeys = (config?: SWRConfiguration<Passkey[] | undefined, AxiosError>) => {
    const key = useUserSWRKey(['account', 'passkeys']);

    return useSWR<Passkey[] | undefined, AxiosError>(
        key,
        async () => {
            const { data } = await http.get('/api/client/account/passkeys');

            return (data as FractalResponseList).data.map(Transformers.toPasskey);
        },
        { revalidateOnMount: false, ...(config || {}) },
    );
};

export const fetchPasskeyRegistrationOptions = async (): Promise<PasskeyRegistrationOptions> => {
    const { data } = await http.post('/api/client/account/passkeys/options');

    return data.data;
};

export const registerPasskey = async (
    name: string,
    token: string,
    credential: Record<string, unknown>,
): Promise<Passkey> => {
    const { data } = await http.post('/api/client/account/passkeys', {
        name,
        token,
        credential,
    });

    return Transformers.toPasskey(data);
};

export const deletePasskey = async (uuid: string): Promise<void> => {
    await http.delete(`/api/client/account/passkeys/${uuid}`);
};

export const updateAuthLoginMethod = async (method: AuthLoginMethod): Promise<AuthLoginMethod> => {
    const { data } = await http.patch('/api/client/account/auth-login-method', { method });

    return data.data.method as AuthLoginMethod;
};
