import { deleteBillingTerm } from '@/api/admin/billing/billingTerms';
import FlashMessageRender from '@/components/FlashMessageRender';
import Input from '@elements/Input';
import { Button } from '@elements/button';
import { Dialog } from '@elements/dialog';
import useFlash from '@/plugins/useFlash';
import { faTrash } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import type { BillingTerm } from '@/api/definitions/admin';

interface Props {
    term: BillingTerm;
}

export default ({ term }: Props) => {
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const [confirmation, setConfirmation] = useState('');
    const { clearFlashes, addFlash, clearAndAddHttpError } = useFlash();

    const handleDelete = () => {
        clearFlashes('admin:billing:terms:delete');

        if (confirmation !== term.slug && confirmation !== term.name) {
            addFlash({
                key: 'admin:billing:terms:delete',
                message: 'The confirmation text does not match the term name or slug.',
                type: 'error',
            });
            return;
        }

        deleteBillingTerm(term.uuid)
            .then(() => navigate('/admin/billing/terms'))
            .catch(error => clearAndAddHttpError({ key: 'admin:billing:terms:delete', error }));
    };

    return (
        <>
            <Dialog.Confirm
                open={open}
                onClose={() => setOpen(false)}
                onConfirmed={handleDelete}
                title={'Delete billing term?'}
                confirm={'Delete'}
            >
                <FlashMessageRender byKey={'admin:billing:terms:delete'} className={'mb-2'} />
                <p className={'text-sm text-neutral-300'}>
                    Removing this term will immediately remove it from selection in the configurator. To confirm, type{' '}
                    <span className={'px-1 py-0.5 bg-neutral-900 rounded font-mono text-xs'}>
                        {term.slug || term.name}
                    </span>{' '}
                    below.
                </p>
                <Input className={'mt-3'} onChange={e => setConfirmation(e.currentTarget.value.trim())} />
            </Dialog.Confirm>
            <Button.Danger type={'button'} onClick={() => setOpen(true)}>
                <FontAwesomeIcon icon={faTrash} className={'mr-2'} /> Delete
            </Button.Danger>
        </>
    );
};
