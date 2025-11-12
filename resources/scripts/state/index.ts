import { createStore } from 'easy-peasy';
import user, { UserStore } from '@/state/user';
import theme, { ThemeStore } from '@/state/theme';
import flashes, { FlashStore } from '@/state/flashes';
import DarkOak, { DarkOakStore } from '@/state/DarkOak';
import settings, { SettingsStore } from '@/state/settings';
import progress, { ProgressStore } from '@/state/progress';
import permissions, { GloablPermissionsStore } from '@/state/permissions';

export interface ApplicationStore {
    permissions: GloablPermissionsStore;
    flashes: FlashStore;
    user: UserStore;
    settings: SettingsStore;
    progress: ProgressStore;
    DarkOak: DarkOakStore;
    theme: ThemeStore;
}

const state: ApplicationStore = {
    permissions,
    flashes,
    user,
    settings,
    progress,
    DarkOak,
    theme,
};

export const store = createStore(state);

