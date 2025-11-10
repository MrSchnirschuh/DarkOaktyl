import type { Actions } from 'easy-peasy';
import { useStoreActions } from 'easy-peasy';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import tw from 'twin.macro';
import { Button } from '@elements/button';
import { Dialog } from '@elements/dialog';
import type { ApplicationStore } from '@/state';
import { deletePricingConfiguration } from '@/api/admin/billing/pricing';

interface Props {
    configId: number;
}

export default ({ configId }: Props) => {
    const navigate = useNavigate();
    const [visible, setVisible] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const onDelete = () => {
        setIsLoading(true);
        clearFlashes('admin:billing:pricing');

        deletePricingConfiguration(configId)
            .then(() => {
                setIsLoading(false);
                navigate('/admin/billing/pricing');
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'admin:billing:pricing', error });
                setIsLoading(false);
                setVisible(false);
            });
    };

    return (
        <>
            <Dialog.Confirm
                open={visible}
                onClose={() => setVisible(false)}
                title={'Delete Pricing Configuration?'}
                confirm={'Delete'}
                onConfirmed={onDelete}
            >
                Are you sure you want to delete this pricing configuration? This action cannot be undone. Categories
                using this configuration will need to be updated.
            </Dialog.Confirm>
            <Button variant={'danger'} disabled={isLoading} onClick={() => setVisible(true)}>
                Delete Configuration
            </Button>
        </>
    );
};
