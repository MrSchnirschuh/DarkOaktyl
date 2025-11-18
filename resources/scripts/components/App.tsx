import { lazy } from 'react';
import '@/assets/tailwind.css';
import { store } from '@/state';
import { SiteTheme, resolveThemeMode } from '@/state/theme';
import { StoreProvider } from 'easy-peasy';
import { AdminContext } from '@/state/admin';
import { ServerContext } from '@/state/server';
import { SiteSettings } from '@/state/settings';
import Spinner from '@elements/Spinner';
import ProgressBar from '@elements/ProgressBar';
import GlobalStylesheet from '@/assets/css/GlobalStylesheet';
import ThemeVars from '@/components/ThemeVars';
import AppearanceSync from '@/components/AppearanceSync';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import AuthenticatedRoute from '@elements/AuthenticatedRoute';
import { NotFound } from '@elements/ScreenBlock';
import { DarkOakSettings } from '@/state/DarkOak';
import Onboarding from '@/components/Onboarding';
import SpeedDial from '@elements/SpeedDial';
import SetupContainer from './setup/SetupContainer';

let hasHydratedAppearance = false;

const AdminRouter = lazy(() => import('@/routers/AdminRouter'));
const AuthenticationRouter = lazy(() => import('@/routers/AuthenticationRouter'));
const DashboardRouter = lazy(() => import('@/routers/DashboardRouter'));
const ServerRouter = lazy(() => import('@/routers/ServerRouter'));

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
    ThemeConfiguration?: SiteTheme;
    DarkOakConfiguration?: DarkOakSettings;
    DarkOaktylUser?: {
        uuid: string;
        username: string;
        email: string;
        root_admin: boolean;
        use_totp: boolean;
        language: string;
        avatar_url: string;
        admin_role_name: string;
        admin_role_id?: number;
        state: string;
        updated_at: string;
        created_at: string;
        appearance_mode?: 'system' | 'light' | 'dark';
        appearance_last_mode?: 'light' | 'dark';
    };
}

function App() {
    const { DarkOaktylUser, SiteConfiguration, DarkOakConfiguration, ThemeConfiguration } = window as ExtendedWindow;

    if (DarkOaktylUser && !store.getState().user.data) {
        store.getActions().user.setUserData({
            uuid: DarkOaktylUser.uuid,
            username: DarkOaktylUser.username,
            email: DarkOaktylUser.email,
            language: DarkOaktylUser.language,
            rootAdmin: DarkOaktylUser.root_admin,
            avatarURL: DarkOaktylUser.avatar_url,
            roleName: DarkOaktylUser.admin_role_name,
            admin_role_id: DarkOaktylUser.admin_role_id,
            state: DarkOaktylUser.state,
            useTotp: DarkOaktylUser.use_totp,
            createdAt: new Date(DarkOaktylUser.created_at),
            updatedAt: new Date(DarkOaktylUser.updated_at),
            appearanceMode: DarkOaktylUser.appearance_mode ?? 'system',
            appearanceLastMode: DarkOaktylUser.appearance_last_mode ?? 'dark',
        });
    }

    if (!store.getState().settings.data) {
        store.getActions().settings.setSettings(SiteConfiguration!);
    }

    if (!store.getState().theme.data) {
        store.getActions().theme.setTheme(ThemeConfiguration!);
    }

    const appearance = store.getState().user.data;
    const preference = appearance?.appearanceMode ?? 'system';
    const lastMode = appearance?.appearanceLastMode ?? 'dark';
    const resolvedMode = resolveThemeMode(preference, lastMode);
    const themeState = store.getState().theme;

    if (!hasHydratedAppearance) {
        if (themeState.preference !== preference) {
            store.getActions().theme.setPreference(preference);
        }

        if (themeState.mode !== resolvedMode) {
            store.getActions().theme.setMode(resolvedMode);
        }

        hasHydratedAppearance = true;
    }

    if (!store.getState().DarkOak.data) {
        store.getActions().DarkOak.setDarkOak(DarkOakConfiguration!);
    }

    if (DarkOaktylUser?.state === 'suspended') {
        return (
            <div style={{ color: 'white', fontWeight: 'bold', marginTop: '10px', marginLeft: '10px' }}>
                Your account has been suspended and blocked by an administrator.
            </div>
        );
    }

    const hasAdminRole: boolean = (DarkOaktylUser?.root_admin || Boolean(DarkOaktylUser?.admin_role_id)) ?? false;

    return (
        <>
            <GlobalStylesheet />
            <StoreProvider store={store}>
                <ThemeVars />
                <AppearanceSync />
                <ProgressBar />
                {DarkOaktylUser?.root_admin && !SiteConfiguration?.setup ? (
                    <SetupContainer />
                ) : (
                    <>
                        {' '}
                        {DarkOaktylUser?.username.startsWith('null_user_') &&
                        DarkOakConfiguration?.auth.modules.onboarding.enabled ? (
                            <Onboarding />
                        ) : (
                            <div className="mx-auto w-auto">
                                <BrowserRouter>
                                    <Routes>
                                        <Route
                                            path="/auth/*"
                                            element={
                                                <Spinner.Suspense>
                                                    <AuthenticationRouter />
                                                </Spinner.Suspense>
                                            }
                                        />

                                        <Route
                                            path="/server/:id/*"
                                            element={
                                                <AuthenticatedRoute>
                                                    <Spinner.Suspense>
                                                        <ServerContext.Provider>
                                                            {hasAdminRole && <SpeedDial />}
                                                            <ServerRouter />
                                                        </ServerContext.Provider>
                                                    </Spinner.Suspense>
                                                </AuthenticatedRoute>
                                            }
                                        />

                                        <Route
                                            path="/admin/*"
                                            element={
                                                <Spinner.Suspense>
                                                    <AdminContext.Provider>
                                                        <AdminRouter />
                                                    </AdminContext.Provider>
                                                </Spinner.Suspense>
                                            }
                                        />

                                        <Route
                                            path="/*"
                                            element={
                                                <AuthenticatedRoute>
                                                    <Spinner.Suspense>
                                                        {hasAdminRole && <SpeedDial />}
                                                        <DashboardRouter />
                                                    </Spinner.Suspense>
                                                </AuthenticatedRoute>
                                            }
                                        />

                                        <Route path="*" element={<NotFound />} />
                                    </Routes>
                                </BrowserRouter>
                            </div>
                        )}
                    </>
                )}
            </StoreProvider>
        </>
    );
}

export { App };
