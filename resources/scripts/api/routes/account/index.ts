import http from '@/api/http';

const updateAccountEmail = (email: string, password: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/account/email', { email, password })
            .then(() => resolve())
            .catch(reject);
    });
};

const updateAccountPassword = ({
    current,
    password,
    confirmPassword,
}: {
    current: string;
    password: string;
    confirmPassword: string;
}): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/account/password', {
            current_password: current,
            password: password,
            password_confirmation: confirmPassword,
        })
            .then(() => resolve())
            .catch(reject);
    });
};

const setupAccount = (values: { username: string; password: string }): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/account/setup', values)
            .then(() => resolve())
            .catch(reject);
    });
};

const updateAccountAppearance = (mode: 'system' | 'light' | 'dark', lastMode: 'light' | 'dark'): Promise<void> => {
    return http
        .put('/api/client/account/appearance', {
            mode,
            last_mode: lastMode,
        })
        .then(() => undefined);
};

export { updateAccountPassword, updateAccountEmail, setupAccount, updateAccountAppearance };
