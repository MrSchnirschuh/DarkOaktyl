import { useEffect, useState } from 'react';
import * as React from 'react';
import { Button } from '@elements/button/index';
import Can from '@elements/Can';
import { ServerContext } from '@/state/server';
import { PowerAction } from '@/components/server/console/ServerConsoleContainer';
import { Dialog } from '@elements/dialog';
import { PlayIcon, StopIcon, DotsCircleHorizontalIcon, BanIcon } from '@heroicons/react/outline';

interface PowerButtonProps {
    className?: string;
}

export default ({ className }: PowerButtonProps) => {
    const [open, setOpen] = useState(false);
    const status = ServerContext.useStoreState(state => state.status.value);
    const instance = ServerContext.useStoreState(state => state.socket.instance);

    const killable = status === 'stopping';
    const onButtonClick = (
        action: PowerAction | 'kill-confirmed',
        e: React.MouseEvent<HTMLButtonElement, MouseEvent>,
    ): void => {
        e.preventDefault();
        if (action === 'kill') {
            return setOpen(true);
        }

        if (instance) {
            setOpen(false);
            instance.send('set state', action === 'kill-confirmed' ? 'kill' : action);
        }
    };

    useEffect(() => {
        if (status === 'offline') {
            setOpen(false);
        }
    }, [status]);

    return (
        <div className={className}>
            <Dialog.Confirm
                open={open}
                hideCloseIcon
                onClose={() => setOpen(false)}
                title={'Forcibly Stop Process'}
                confirm={'Continue'}
                onConfirmed={onButtonClick.bind(this, 'kill-confirmed')}
            >
                Forcibly stopping a server can lead to data corruption.
            </Dialog.Confirm>
            <div className={'mx-6'}>
                <div className={'grid grid-cols-3 gap-4'}>
                    <Can action={'control.start'}>
                        <Button.Text disabled={status !== 'offline'} onClick={onButtonClick.bind(this, 'start')}>
                            <PlayIcon className={'w-6'} />
                        </Button.Text>
                    </Can>
                    <Can action={'control.restart'}>
                        <Button.Text disabled={!status} onClick={onButtonClick.bind(this, 'restart')}>
                            <DotsCircleHorizontalIcon className={'w-6'} />
                        </Button.Text>
                    </Can>
                    <Can action={'control.stop'}>
                        <Button.Danger
                            disabled={status === 'offline'}
                            onClick={onButtonClick.bind(this, killable ? 'kill' : 'stop')}
                        >
                            {killable ? (
                                <>
                                    <BanIcon className={'w-6'} />
                                </>
                            ) : (
                                <>
                                    <StopIcon className={'w-6'} />
                                </>
                            )}
                        </Button.Danger>
                    </Can>
                </div>
            </div>
        </div>
    );
};
