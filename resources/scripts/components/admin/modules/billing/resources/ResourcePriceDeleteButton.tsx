import { deleteResourcePrice } from '@/api/admin/billing/resourcePrices';
import FlashMessageRender from '@/components/FlashMessageRender';
import Input from '@elements/Input';
import { Button } from '@elements/button';
import { Dialog } from '@elements/dialog';
import useFlash from '@/plugins/useFlash';
import { faTrash } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import type { ResourcePrice } from '@/api/definitions/admin';

interface Props {
    resource: ResourcePrice;
}

export default ({ resource }: Props) => {
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const [confirmation, setConfirmation] = useState('');
    const { clearFlashes, addFlash, clearAndAddHttpError } = useFlash();

    const handleDelete = () => {
        clearFlashes('admin:billing:resources:delete');

        if (confirmation !== resource.resource) {
            addFlash({
                key: 'admin:billing:resources:delete',
                message: 'The resource identifier does not match.',
                type: 'error',
            });
            return;
        }

        deleteResourcePrice(resource.uuid)
            .then(() => navigate('/admin/billing/pricing'))
            .catch(error => clearAndAddHttpError({ key: 'admin:billing:resources:delete', error }));
    };

    return (
        <>
            <Dialog.Confirm
                open={open}
                onClose={() => setOpen(false)}
                onConfirmed={handleDelete}
                title={'Delete resource price?'}
                confirm={'Delete'}
            >
                <FlashMessageRender byKey={'admin:billing:resources:delete'} className={'mb-2'} />
                <p className={'text-sm text-neutral-300'}>
                    Removing this entry will immediately detach this resource from the billing calculator. To confirm,
                    type{' '}
                    <span className={'px-1 py-0.5 bg-neutral-900 rounded font-mono text-xs'}>{resource.resource}</span>{' '}
                    below.
                </p>
                <Input className={'mt-3'} onChange={e => setConfirmation(e.currentTarget.value)} />
            </Dialog.Confirm>
            <Button.Danger type={'button'} onClick={() => setOpen(true)}>
                <FontAwesomeIcon icon={faTrash} className={'mr-2'} /> Delete
            </Button.Danger>
        </>
    );
};
