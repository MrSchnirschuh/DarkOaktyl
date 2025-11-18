import { deleteCoupon } from '@/api/admin/billing/coupons';
import FlashMessageRender from '@/components/FlashMessageRender';
import Input from '@elements/Input';
import { Button } from '@elements/button';
import { Dialog } from '@elements/dialog';
import useFlash from '@/plugins/useFlash';
import { faTrash } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import type { Coupon } from '@/api/definitions/admin';

interface Props {
    coupon: Coupon;
}

export default ({ coupon }: Props) => {
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const [confirmation, setConfirmation] = useState('');
    const { clearFlashes, addFlash, clearAndAddHttpError } = useFlash();

    const handleDelete = () => {
        clearFlashes('admin:billing:coupons:delete');

        if (confirmation !== coupon.code) {
            addFlash({
                key: 'admin:billing:coupons:delete',
                message: 'The coupon code does not match.',
                type: 'error',
            });
            return;
        }

        deleteCoupon(coupon.uuid)
            .then(() => navigate('/admin/billing/coupons'))
            .catch(error => clearAndAddHttpError({ key: 'admin:billing:coupons:delete', error }));
    };

    return (
        <>
            <Dialog.Confirm
                open={open}
                onClose={() => setOpen(false)}
                onConfirmed={handleDelete}
                title={'Delete coupon?'}
                confirm={'Delete'}
            >
                <FlashMessageRender byKey={'admin:billing:coupons:delete'} className={'mb-2'} />
                <p className={'text-sm text-theme-secondary'}>
                    Removing this coupon will immediately disable it for customers. Any existing redemptions will remain
                    valid. To confirm, type{' '}
                    <span className={'px-1 py-0.5 bg-neutral-900 rounded font-mono text-xs'}>{coupon.code}</span> below.
                </p>
                <Input className={'mt-3'} onChange={e => setConfirmation(e.currentTarget.value.trim())} />
            </Dialog.Confirm>
            <Button.Danger type={'button'} onClick={() => setOpen(true)}>
                <FontAwesomeIcon icon={faTrash} className={'mr-2'} /> Delete
            </Button.Danger>
        </>
    );
};
