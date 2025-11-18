import { useStoreState } from 'easy-peasy';
import { NavLink, Route, Routes } from 'react-router-dom';
import Avatar from '@/components/Avatar';
import Sidebar from '@elements/Sidebar';
import AdminIndicators from '@admin/AdminIndicators';
import { usePersistedState } from '@/plugins/usePersistedState';
import MobileSidebar from '@elements/MobileSidebar';
import Pill from '@/components/elements/Pill';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import routes from './routes';
import Spinner from '@/components/elements/Spinner';
import { NotFound } from '@/components/elements/ScreenBlock';
import { PuzzleIcon, ReplyIcon } from '@heroicons/react/outline';
import { Fragment } from 'react';

function AdminRouter() {
    const theme = useStoreState(state => state.theme.data!);
    const currentMode = useStoreState(s => s.theme.mode ?? 'dark');
    const user = useStoreState(state => state.user.data!);
    const settings = useStoreState(state => state.settings.data!);

    const categories = ['general', 'modules', 'appearance', 'management', 'services'] as const;
    const [collapsed, setCollapsed] = usePersistedState<boolean>(`sidebar_admin_${user.uuid}`, false);

    return (
        <div className={'h-screen flex'}>
            {settings.indicators && <AdminIndicators />}
            <MobileSidebar>
                <MobileSidebar.Home />
                {routes.admin
                    .filter(x => x.name)
                    .map(route => (
                        <MobileSidebar.Link
                            key={route.route}
                            icon={route.icon ?? PuzzleIcon}
                            text={route.name}
                            linkTo={route.path}
                            end={route.end}
                        />
                    ))}
            </MobileSidebar>
            <Sidebar className={'flex-none'} $collapsed={collapsed} theme={theme}>
                <div
                    className={'h-16 w-full flex flex-col items-center justify-center my-6 select-none cursor-pointer'}
                    onClick={() => setCollapsed(!collapsed)}
                >
                    {!collapsed ? (
                        <h1 className={'text-2xl whitespace-nowrap font-medium'} style={{ color: 'var(--theme-text-primary, #111827)' }}>{settings.name}</h1>
                    ) : (
                        <img
                            src={
                                theme.colors[`logo_panel_${currentMode}`] ||
                                theme.colors['logo_panel'] ||
                                'https://avatars.githubusercontent.com/u/91636558'
                            }
                            className={'mt-4 w-12'}
                            alt={'Logo'}
                        />
                    )}
                </div>
                <Sidebar.Wrapper theme={theme} $admin>
                    <NavLink to="/" className={'mb-[18px]'}>
                        <Sidebar.Icon icon={ReplyIcon} />
                        <span>Return</span>
                    </NavLink>
                    {categories.map(category => {
                        const categoryRoutes = routes.admin.filter(route => route.category === category && route.name);
                        if (categoryRoutes.length === 0) return null;

                        return (
                            <Fragment key={category}>
                                <Sidebar.Section>{category[0]!.toUpperCase() + category.slice(1)}</Sidebar.Section>
                                {categoryRoutes.map(route => (
                                    <NavLink to={route.path} key={route.path} end={route.end}>
                                        <Sidebar.Icon icon={route.icon ?? PuzzleIcon} />
                                        <span>{route.name}</span>
                                    </NavLink>
                                ))}
                            </Fragment>
                        );
                    })}
                </Sidebar.Wrapper>
                <Sidebar.User className={'mt-auto py-3'}>
                    <span className="flex items-center">
                        <Avatar.User />
                    </span>
                    <div className={'flex flex-col ml-3'}>
                        <span className={'font-sans font-normal text-xs text-theme-secondary leading-tight select-none'}>
                            <div className={'w-full flex justify-between mb-1'}>
                                <p className={'text-sm text-[var(--theme-text-secondary)]'}>Welcome,</p>
                                <Pill size={'xsmall'} type={'info'}>
                                    {user.roleName === 'None' ? 'Root Admin' : user.roleName}
                                </Pill>
                            </div>
                            {user.username}
                        </span>
                    </div>
                </Sidebar.User>
            </Sidebar>
            <div className={'flex-1 overflow-x-hidden px-6 pt-6 lg:px-10 lg:pt-8 xl:px-16 xl:pt-12'}>
                <div className={'w-full flex flex-col mx-auto'} style={{ maxWidth: '86rem' }}>
                    <ErrorBoundary>
                        <Routes>
                            {routes.admin.map(({ route, component: Component }) => (
                                <Route
                                    key={route}
                                    path={route}
                                    element={
                                        <Spinner.Suspense>
                                            <Component />
                                        </Spinner.Suspense>
                                    }
                                />
                            ))}
                            <Route path={'*'} element={<NotFound />} />
                        </Routes>
                    </ErrorBoundary>
                </div>
            </div>
        </div>
    );
}

export default AdminRouter;
