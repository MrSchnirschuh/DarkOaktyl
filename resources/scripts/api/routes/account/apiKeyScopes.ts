import http from '@/api/http';

interface ScopeGroup {
    [key: string]: {
        label: string;
        scopes: Record<string, string>;
    };
}

/**
 * Lädt alle verfügbaren Scopes für API-Keys.
 */
export const getApiKeyScopes = (): Promise<ScopeGroup> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/api-keys/scopes')
            .then(({ data }) => resolve(data.data || {}))
            .catch(reject);
    });
};

/**
 * Aktualisiert die Scopes eines API-Keys.
 */
export const updateApiKeyScopes = (identifier: string, scopes: string[]): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/client/account/api-keys/${identifier}/scopes`, { scopes })
            .then(() => resolve())
            .catch(reject);
    });
};

export default { getApiKeyScopes, updateApiKeyScopes };
