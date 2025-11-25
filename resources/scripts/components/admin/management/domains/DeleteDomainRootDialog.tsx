import { Dispatch, SetStateAction, useState } from 'react';
import { Dialog } from '@elements/dialog';
import { DomainRoot, deleteDomainRoot, mutateDomainRoots } from '@/api/admin/domainRoots';
import { VisibleDialog } from './DomainRootsContainer';
import { useFlashKey } from '@/plugins/useFlash';

interface Props {
    root: DomainRoot;
    setOpen: Dispatch<SetStateAction<VisibleDialog>>;
}

const DeleteDomainRootDialog = ({ root, setOpen }: Props) => {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:domainRoots');

    const close = () => setOpen('none');

    const submit = async () => {
        setIsSubmitting(true);
        clearFlashes();

        try {
            await deleteDomainRoot(root.id);
            mutateDomainRoots();
            close();
        } catch (error) {
            clearAndAddHttpError(error);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog.Confirm
            confirm={'Delete'}
            open
            onClose={close}
            onConfirmed={submit}
            title={'Delete Domain Root'}
            danger
            disableConfirm={isSubmitting}
        >
            <p className={'text-sm text-theme-secondary'}>
                Are you sure you want to delete <strong>{root.name}</strong> ({root.rootDomain})? Existing server
                domains referencing this root will retain their hostname but automation will be disabled.
            </p>
        </Dialog.Confirm>
    );
};

export default DeleteDomainRootDialog;
