import { useMemo } from 'react';
import ServerInstallSvgRaw from '@/assets/images/server_installing.svg?raw';
import ServerErrorSvg from '@/assets/images/server_error.svg';
import ServerRestoreSvgRaw from '@/assets/images/server_restore.svg?raw';
import ScreenBlock from '@elements/ScreenBlock';
import { createThemedSvgDataUri } from '@/helpers/themedSvg';
import { useStoreState } from '@/state/hooks';
import { ServerContext } from '@/state/server';

export default () => {
    const status = ServerContext.useStoreState(state => state.server.data?.status || null);
    const isTransferring = ServerContext.useStoreState(state => state.server.data?.isTransferring || false);
    const isNodeUnderMaintenance = ServerContext.useStoreState(
        state => state.server.data?.isNodeUnderMaintenance || false,
    );
    const themeColors = useStoreState(state => state.theme.data?.colors);
    const themeMode = useStoreState(state => state.theme.mode ?? 'dark');

    const serverInstallSvg = useMemo(
        () => createThemedSvgDataUri(ServerInstallSvgRaw, themeColors, themeMode),
        [themeColors, themeMode],
    );
    const serverRestoreSvg = useMemo(
        () => createThemedSvgDataUri(ServerRestoreSvgRaw, themeColors, themeMode),
        [themeColors, themeMode],
    );

    return status === 'installing' || status === 'install_failed' || status === 'reinstall_failed' ? (
        <ScreenBlock
            title={'Running Installer'}
            image={serverInstallSvg}
            message={'Your server should be ready soon, please try again in a few minutes.'}
        />
    ) : status === 'suspended' ? (
        <ScreenBlock
            title={'Server Suspended'}
            image={ServerErrorSvg}
            message={'This server is suspended and cannot be accessed.'}
        />
    ) : isNodeUnderMaintenance ? (
        <ScreenBlock
            title={'Node under Maintenance'}
            image={ServerErrorSvg}
            message={'The node of this server is currently under maintenance.'}
        />
    ) : (
        <ScreenBlock
            title={isTransferring ? 'Transferring' : 'Restoring from Backup'}
            image={serverRestoreSvg}
            message={
                isTransferring
                    ? 'Your server is being transferred to a new node, please check back later.'
                    : 'Your server is currently being restored from a backup, please check back in a few minutes.'
            }
        />
    );
};
