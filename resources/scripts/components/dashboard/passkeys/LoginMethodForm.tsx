import { FormEvent, useEffect, useMemo, useState } from 'react';
import tw from 'twin.macro';
import { Button } from '@elements/button';
import MessageBox from '@/components/MessageBox';
import { useFlashKey } from '@/plugins/useFlash';
import { updateAuthLoginMethod } from '@/api/account/passkeys';
import type { AuthLoginMethod } from '@definitions/user';
import { useStoreActions, useStoreState } from '@/state/hooks';

interface Props {
    passkeyCount: number;
}

const OPTIONS: { value: AuthLoginMethod; label: string; description: string }[] = [
    {
        value: 'password',
        label: 'Password only',
        description: 'Require your account password for every login attempt.',
    },
    {
        value: 'both',
        label: 'Password + Passkey',
        description: 'Allow either a password or a passkey when signing in.',
    },
    {
        value: 'passkey',
        label: 'Passkey only',
        description: 'Disable password logins and require a registered passkey.',
    },
];

export default function LoginMethodForm({ passkeyCount }: Props) {
    const { clearAndAddHttpError, addError } = useFlashKey('account:passkeys');
    const currentMethod = useStoreState(state => state.user.data?.authLoginMethod ?? 'password');
    const updateUserData = useStoreActions(actions => actions.user.updateUserData);
    const [selected, setSelected] = useState<AuthLoginMethod>(currentMethod);
    const [isSubmitting, setSubmitting] = useState(false);

    const passkeyOnlyDisabled = passkeyCount === 0;
    const requiresSave = selected !== currentMethod;

    useEffect(() => {
        setSelected(currentMethod);
    }, [currentMethod]);

    const options = useMemo(() => OPTIONS, []);

    const onSubmit = async (event: FormEvent) => {
        event.preventDefault();

        if (!requiresSave) {
            return;
        }

        if (selected === 'passkey' && passkeyOnlyDisabled) {
            addError('Add at least one passkey before enabling passkey-only logins.');
            return;
        }

        clearAndAddHttpError();
        setSubmitting(true);

        try {
            const method = await updateAuthLoginMethod(selected);
            updateUserData({ authLoginMethod: method });
        } catch (error) {
            clearAndAddHttpError(error as Error);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <form onSubmit={onSubmit}>
            {passkeyOnlyDisabled && (
                <MessageBox type="warning" title="Register a Passkey" css={tw`mb-4`}>
                    Register at least one passkey before switching to passkey-only logins.
                </MessageBox>
            )}
            <div css={tw`space-y-3`}>
                {options.map(option => {
                    const disabled = option.value === 'passkey' && passkeyOnlyDisabled;

                    return (
                        <label
                            key={option.value}
                            css={tw`flex items-start p-3 rounded border border-neutral-700/60 cursor-pointer bg-black/25`}
                        >
                            <input
                                type="radio"
                                name="auth-login-method"
                                value={option.value}
                                checked={selected === option.value}
                                onChange={() => setSelected(option.value)}
                                disabled={disabled || isSubmitting}
                                css={tw`mt-1 mr-3`}
                            />
                            <div>
                                <p css={tw`font-semibold`}>{option.label}</p>
                                <p css={tw`text-sm text-theme-muted`}>{option.description}</p>
                            </div>
                        </label>
                    );
                })}
            </div>
            <div css={tw`flex justify-end mt-6`}>
                <Button type="submit" disabled={!requiresSave || isSubmitting}>
                    Save Preference
                </Button>
            </div>
        </form>
    );
}
