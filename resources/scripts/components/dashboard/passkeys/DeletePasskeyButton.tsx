import { useState } from 'react';
import type { KeyedMutator } from 'swr';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import tw from 'twin.macro';

import type { Passkey } from '@definitions/user';
import { deletePasskey } from '@/api/account/passkeys';
import { Dialog } from '@elements/dialog';
import Code from '@elements/Code';
import { useFlashKey } from '@/plugins/useFlash';

interface Props {
    uuid: string;
    name: string;
    mutate: KeyedMutator<Passkey[] | undefined>;
}

export default function DeletePasskeyButton({ uuid, name, mutate }: Props) {
    const { clearAndAddHttpError } = useFlashKey('account:passkeys');
    const [open, setOpen] = useState(false);

    const handleDelete = async () => {
        setOpen(false);
        clearAndAddHttpError();

        try {
            await Promise.all([
                mutate(data => data?.filter(passkey => passkey.uuid !== uuid), false),
                deletePasskey(uuid),
            ]);
        } catch (error) {
            try {
                await mutate(undefined, true);
            } catch (mutateError) {
                console.error(mutateError);
            }

            clearAndAddHttpError(error instanceof Error ? error : String(error));
        }
    };

    return (
        <>
            <Dialog.Confirm
                open={open}
                title="Delete Passkey"
                confirm="Delete"
                onConfirmed={handleDelete}
                onClose={() => setOpen(false)}
            >
                Removing the <Code>{name}</Code> passkey will prevent it from being used to access your account.
            </Dialog.Confirm>
            <button css={tw`ml-2 p-2 text-sm`} onClick={() => setOpen(true)}>
                <FontAwesomeIcon
                    icon={faTrashAlt}
                    css={tw`text-theme-muted hover:text-red-400 transition-colors duration-150`}
                />
            </button>
        </>
    );
}
