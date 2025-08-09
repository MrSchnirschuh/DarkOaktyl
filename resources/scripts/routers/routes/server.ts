import { lazy } from 'react';
import { route, type ServerRouteDefinition } from '@/routers/routes/utils';
import { ArchiveIcon, CashIcon, ClockIcon, CogIcon, DatabaseIcon, EyeIcon, FolderOpenIcon, PlayIcon, TerminalIcon, UsersIcon, WifiIcon } from '@heroicons/react/outline';

const ServerConsoleContainer = lazy(() => import('@/components/server/console/ServerConsoleContainer'));
const FileManagerContainer = lazy(() => import('@/components/server/files/FileManagerContainer'));
const FileEditContainer = lazy(() => import('@/components/server/files/FileEditContainer'));
const DatabasesContainer = lazy(() => import('@/components/server/databases/DatabasesContainer'));
const ScheduleContainer = lazy(() => import('@/components/server/schedules/ScheduleContainer'));
const ScheduleEditContainer = lazy(() => import('@/components/server/schedules/ScheduleEditContainer'));
const UsersContainer = lazy(() => import('@/components/server/users/UsersContainer'));
const BackupContainer = lazy(() => import('@/components/server/backups/BackupContainer'));
const NetworkContainer = lazy(() => import('@/components/server/network/NetworkContainer'));
const StartupContainer = lazy(() => import('@/components/server/startup/StartupContainer'));
const SettingsContainer = lazy(() => import('@/components/server/settings/SettingsContainer'));
const ServerActivityLogContainer = lazy(() => import('@/components/server/ServerActivityLogContainer'));
const ServerBillingContainer = lazy(() => import('@/components/server/billing/ServerBillingContainer'));

const server: ServerRouteDefinition[] = [
    route('', ServerConsoleContainer, { permission: 'control.console', name: 'Console', end: true, icon: TerminalIcon }),
    route('files/*', FileManagerContainer, { permission: 'file.*', name: 'Files', icon: FolderOpenIcon }),
    route('files/:action/*', FileEditContainer, { permission: 'file.*' }),
    route('databases/*', DatabasesContainer, { permission: 'database.*', name: 'Databases', icon: DatabaseIcon }),
    route('schedules/*', ScheduleContainer, { permission: 'schedule.*', name: 'Schedules', icon: ClockIcon }),
    route('schedules/:id/*', ScheduleEditContainer, { permission: 'schedule.*' }),
    route('users/*', UsersContainer, { permission: 'user.*', name: 'Users', icon: UsersIcon }),
    route('backups/*', BackupContainer, { permission: 'backup.*', name: 'Backups', icon: ArchiveIcon }),
    route('network/*', NetworkContainer, { permission: 'allocation.*', name: 'Network', icon: WifiIcon }),
    route('startup/*', StartupContainer, { permission: 'startup.*', name: 'Startup', icon: PlayIcon }),
    route('settings/*', SettingsContainer, { permission: ['settings.*', 'file.sftp'], name: 'Settings', icon: CogIcon }),
    route('activity/*', ServerActivityLogContainer, { permission: 'activity.*', name: 'Activity', icon: EyeIcon }),
    route('billing/*', ServerBillingContainer, {
        permission: 'billing.*',
        name: 'Billing',
        icon: CashIcon,
        condition: flags => flags.billable,
    }),
];

export default server;
