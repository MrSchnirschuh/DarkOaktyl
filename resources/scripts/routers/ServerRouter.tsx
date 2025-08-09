import TransferListener from '@/components/server/TransferListener';
import { Fragment, useEffect, useState } from 'react';
import { NavLink, Route, Routes, useParams } from 'react-router-dom';
import WebsocketHandler from '@/components/server/WebsocketHandler';
import { ServerContext } from '@/state/server';
import Spinner from '@elements/Spinner';
import { NotFound, ServerError, Suspended } from '@elements/ScreenBlock';
import { httpErrorToHuman } from '@/api/http';
import { useStoreState } from 'easy-peasy';
import InstallListener from '@/components/server/InstallListener';
import ErrorBoundary from '@elements/ErrorBoundary';
import { useLocation } from 'react-router-dom';
import ConflictStateRenderer from '@/components/server/ConflictStateRenderer';
import MobileSidebar from '@elements/MobileSidebar';
import PermissionRoute from '@elements/PermissionRoute';
import routes from '@/routers/routes';
import Sidebar from '@elements/Sidebar';
import { usePersistedState } from '@/plugins/usePersistedState';
import CollapsedIcon from '@/assets/images/logo.png';
import { CogIcon, DesktopComputerIcon, PuzzleIcon, ReplyIcon } from '@heroicons/react/outline';

function ServerRouter() {
    const params = useParams<'id'>();
    const location = useLocation();

    const rootAdmin = useStoreState(state => state.user.data!.rootAdmin);
    const [error, setError] = useState('');

    const user = useStoreState(state => state.user.data!);
    const theme = useStoreState(state => state.theme.data!);
    const name = useStoreState(state => state.settings.data!.name);
    const inConflictState = ServerContext.useStoreState(state => state.server.inConflictState);
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const clearServerState = ServerContext.useStoreActions(actions => actions.clearServerState);
    const [collapsed, setCollapsed] = usePersistedState<boolean>(`sidebar_user_${user.uuid}`, false);
    const server = ServerContext.useStoreState(state => state.server.data);
    const billable = server?.orderId;

    useEffect(() => {
        clearServerState();
    }, []);

    useEffect(() => {
        setError('');

        if (params.id === undefined) {
            return;
        }

        getServer(params.id).catch(error => {
            console.error(error);
            setError(httpErrorToHuman(error));
        });

        return () => {
            clearServerState();
        };
    }, [params.id]);

    if (server?.status === 'suspended' && billable)
        return <Suspended id={server.orderId} days={server.daysUntilRenewal ?? 0} />;

    return (
        <Fragment key={'server-router'}>
            <div className={'h-screen flex'}>
                <MobileSidebar>
                    <MobileSidebar.Home />
                    {routes.server
                        .filter(route => route.name && (!route.condition || route.condition({ billable })))
                        .map(route => (
                            <MobileSidebar.Link
                                key={route.route}
                                icon={route.icon ?? PuzzleIcon}
                                text={route.name}
                                linkTo={route.path}
                                end={route.end}
                            />
                        ))}
                    {(user.rootAdmin || user.admin_role_id) && (
                        <MobileSidebar.Link icon={CogIcon} text={'Admin'} linkTo={'/admin'} />
                    )}
                </MobileSidebar>
                <Sidebar className={'flex-none'} $collapsed={collapsed} theme={theme}>
                    <div
                        className={
                            'h-16 w-full flex flex-col items-center justify-center mt-1 mb-3 select-none cursor-pointer'
                        }
                        onClick={() => setCollapsed(!collapsed)}
                    >
                        {!collapsed ? (
                            <h1 className={'text-2xl text-neutral-50 whitespace-nowrap font-medium'}>{name}</h1>
                        ) : (
                            <img src={CollapsedIcon} className={'mt-4 w-12'} alt={'Everest Icon'} />
                        )}
                    </div>
                    <Sidebar.Wrapper theme={theme}>
                        <NavLink to={'/'} end className={'mb-[18px]'}>
                            <DesktopComputerIcon />
                            <span>Dashboard</span>
                        </NavLink>
                        <Sidebar.Section>Server {server?.uuid?.slice(0, 8)}</Sidebar.Section>
                        {routes.server
                            .filter(route => route.name && (!route.condition || route.condition({ billable })))
                            .map(route => (
                                <NavLink to={`/server/${server?.id}/${route.path}`} key={route.path} end={route.end}>
                                    <Sidebar.Icon icon={route.icon ?? PuzzleIcon} />
                                    <span>{route.name}</span>
                                </NavLink>
                            ))}
                        {user.rootAdmin && (
                            <NavLink to={`/admin/servers/${server?.id}`}>
                                <ReplyIcon />
                                <span>View as Admin</span>
                            </NavLink>
                        )}
                    </Sidebar.Wrapper>
                </Sidebar>
                {!server?.uuid || !server?.id ? (
                    error ? (
                        <ServerError message={error} />
                    ) : (
                        <Spinner size="large" centered />
                    )
                ) : (
                    <div className={'flex-1 overflow-x-hidden p-4 lg:p-8'}>
                        <InstallListener />
                        <TransferListener />
                        <WebsocketHandler />
                        {inConflictState &&
                        (!rootAdmin || (rootAdmin && !location.pathname.endsWith(`/server/${server?.id}`))) ? (
                            <ConflictStateRenderer />
                        ) : (
                            <ErrorBoundary>
                                <Routes location={location}>
                                    {routes.server.map(({ route, permission, component: Component }) => (
                                        <Route
                                            key={route}
                                            path={route}
                                            element={
                                                <PermissionRoute permission={permission}>
                                                    <Spinner.Suspense>
                                                        <Component />
                                                    </Spinner.Suspense>
                                                </PermissionRoute>
                                            }
                                        />
                                    ))}

                                    <Route path="*" element={<NotFound />} />
                                </Routes>
                            </ErrorBoundary>
                        )}
                    </div>
                )}
            </div>
        </Fragment>
    );
}

export default ServerRouter;
