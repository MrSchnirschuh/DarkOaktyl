import { useState } from 'react';
import useFlash from '@/plugins/useFlash';
import Label from '@elements/Label';
import Input from '@elements/Input';
import AdminBox from '@elements/AdminBox';
import Spinner from '@elements/Spinner';
import { CheckCircleIcon, TrashIcon } from '@heroicons/react/outline';
import { toggleModule, updateModule } from '@/api/admin/auth/module';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Dialog } from '@elements/dialog';
import { faDoorOpen } from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from '@/state/hooks';

export default () => {
    const [confirm, setConfirm] = useState<boolean>(false);
    const [loading, setLoading] = useState<boolean>(false);
    const [success, setSuccess] = useState<boolean>(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const delay = useStoreState(state => state.everest.data!.auth.modules.jguard.delay);

    const update = async (key: string, value: number) => {
        clearFlashes();
        setLoading(true);
        setSuccess(false);

        try {
            await updateModule('jguard', key, value);
            setSuccess(true);
            setTimeout(() => setSuccess(false), 2000);
        } catch (error) {
            clearAndAddHttpError({ key: 'auth:modules:jguard', error });
        } finally {
            setLoading(false);
        }
    };

    const doDeletion = async () => {
        try {
            await toggleModule('disable', 'jguard');
            window.location.href = '/admin/auth';
        } catch (error) {
            clearAndAddHttpError({ key: 'auth:modules:jguard', error });
        }
    };

    const handleDelayChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = parseInt(e.target.value, 10);
        if (!isNaN(value) && value >= 0) {
            update('delay', value);
        }
    };

    return (
        <AdminBox title={'jGuard'} icon={faDoorOpen}>
            <FlashMessageRender byKey={'auth:modules:jguard'} className={'my-2'} />
            {loading && <Spinner className={'absolute top-0 right-8 m-3.5'} size={'small'} />}
            {success && <CheckCircleIcon className={'w-5 h-5 absolute top-0 right-8 m-3.5 text-green-500'} />}
            <Dialog.Confirm
                open={confirm}
                title={'Confirm module removal'}
                onConfirmed={() => doDeletion()}
                onClose={() => setConfirm(false)}
            >
                Are you sure you wish to delete this module?
            </Dialog.Confirm>
            <TrashIcon
                className={'w-5 h-5 absolute top-0 right-0 m-3.5 text-red-500 hover:text-red-300 duration-300'}
                onClick={() => setConfirm(true)}
            />
            <div>
                <Label>Automatic approval delay</Label>
                <Input
                    id={'delay'}
                    type={'number'}
                    name={'delay'}
                    min={0}
                    defaultValue={delay || 0}
                    onChange={handleDelayChange}
                />
                <p className={'text-xs text-gray-400 mt-1'}>
                    If you wish to automatically approve user signups, this variable can make it so that users cannot
                    access the Panel for a certain period of time in order to prevent bot attacks.
                </p>
            </div>
        </AdminBox>
    );
};
