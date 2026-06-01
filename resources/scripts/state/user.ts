import { updateAccountEmail, updateAccountAppearance } from '@/api/account';
import type { AuthLoginMethod } from '@definitions/user';
import { Action, action, Thunk, thunk } from 'easy-peasy';

export interface UserData {
    uuid: string;
    username: string;
    email: string;
    language: string;
    rootAdmin: boolean;
    useTotp: boolean;
    avatarURL: string;
    roleName: string;
    admin_role_id?: number;
    state: string;
    createdAt: Date;
    updatedAt: Date;
    appearanceMode: 'light' | 'dark' | 'system';
    appearanceLastMode: 'light' | 'dark';
    authLoginMethod: AuthLoginMethod;
}

export interface UserStore {
    data?: UserData;
    setUserData: Action<UserStore, UserData>;
    updateUserData: Action<UserStore, Partial<UserData>>;
    updateUserEmail: Thunk<UserStore, { email: string; password: string }, any, UserStore, Promise<void>>;
    updateAppearance: Thunk<
        UserStore,
        { mode: 'light' | 'dark' | 'system'; lastMode: 'light' | 'dark' },
        any,
        UserStore,
        Promise<void>
    >;
}

const user: UserStore = {
    data: undefined,
    setUserData: action((state, payload) => {
        state.data = payload;
    }),

    updateUserData: action((state, payload) => {
        // @ts-expect-error limitation of Typescript, can't do much about that currently unfortunately.
        state.data = { ...state.data, ...payload };
    }),

    updateUserEmail: thunk(async (actions, payload) => {
        await updateAccountEmail(payload.email, payload.password);

        actions.updateUserData({ email: payload.email });
    }),

    updateAppearance: thunk(async (actions, payload) => {
        await updateAccountAppearance(payload.mode, payload.lastMode);

        actions.updateUserData({
            appearanceMode: payload.mode,
            appearanceLastMode: payload.lastMode,
        });
    }),
};

export default user;
