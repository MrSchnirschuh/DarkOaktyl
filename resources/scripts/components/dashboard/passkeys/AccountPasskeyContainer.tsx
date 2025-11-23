import { useEffect, useMemo } from 'react';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFingerprint } from '@fortawesome/free-solid-svg-icons';
import ContentBox from '@elements/ContentBox';
import PageContentBlock from '@elements/PageContentBlock';
import SpinnerOverlay from '@elements/SpinnerOverlay';
import FlashMessageRender from '@/components/FlashMessageRender';
import GreyRowBox from '@elements/GreyRowBox';
import MessageBox from '@/components/MessageBox';

import { usePasskeys } from '@/api/account/passkeys';
import { useFlashKey } from '@/plugins/useFlash';
import { useStoreState } from '@/state/hooks';

import CreatePasskeyForm from '@/components/dashboard/passkeys/CreatePasskeyForm';
import DeletePasskeyButton from '@/components/dashboard/passkeys/DeletePasskeyButton';
import LoginMethodForm from '@/components/dashboard/passkeys/LoginMethodForm';

interface Props {
    standalone?: boolean;
}

export default function AccountPasskeyContainer({ standalone = true }: Props) {
    const { data, error, mutate, isValidating } = usePasskeys({ revalidateOnFocus: false });
    const { clearAndAddHttpError } = useFlashKey('account:passkeys');
    const passkeyLimit = useStoreState(state => state.DarkOak.data?.auth.modules.passkeys.max ?? 5);
    const passkeys = data ?? [];

    useEffect(() => {
        if (error) {
            clearAndAddHttpError(error);
        }
    }, [error, clearAndAddHttpError]);

    const emptyState = useMemo(
        () => (
            <p css={tw`text-center text-sm`}>
                {!data && isValidating ? 'Loading passkeys...' : 'No passkeys have been registered yet.'}
            </p>
        ),
        [data, isValidating],
    );

    const stackStyles = standalone
        ? tw`flex flex-col xl:flex-row gap-8 my-10`
        : tw`flex flex-col xl:flex-row gap-6 mt-8`;

    const content = (
        <>
            <FlashMessageRender byKey="account:passkeys" />
            <div css={stackStyles}>
                <ContentBox title="Register a Passkey" css={tw`flex-1`}>
                    <CreatePasskeyForm mutate={mutate} limit={passkeyLimit} currentCount={passkeys.length} />
                </ContentBox>
                <ContentBox title="Login Method" css={tw`flex-1`}>
                    <LoginMethodForm passkeyCount={passkeys.length} />
                </ContentBox>
            </div>
            <ContentBox title="Registered Passkeys">
                <SpinnerOverlay visible={!data && isValidating} />
                {!passkeys.length
                    ? emptyState
                    : passkeys.map(passkey => (
                          <GreyRowBox key={passkey.uuid} css={tw`mb-3 last:mb-0 flex items-center space-x-4`}>
                              <FontAwesomeIcon icon={faFingerprint} css={tw`text-theme-secondary text-lg`} />
                              <div css={tw`flex-1`}>
                                  <p css={tw`text-base font-semibold`}>{passkey.name}</p>
                                  <p css={tw`text-xs text-theme-muted`}>
                                      Registered on {passkey.createdAt.toLocaleString()}
                                  </p>
                                  <p css={tw`text-xs text-theme-muted`}>
                                      Last used: {passkey.lastUsedAt ? passkey.lastUsedAt.toLocaleString() : 'Never'}
                                  </p>
                                  {passkey.transports.length > 0 && (
                                      <p css={tw`text-xs text-theme-muted`}>
                                          Transports: {passkey.transports.join(', ')}
                                      </p>
                                  )}
                              </div>
                              <DeletePasskeyButton uuid={passkey.uuid} name={passkey.name} mutate={mutate} />
                          </GreyRowBox>
                      ))}
                {data && passkeys.length === 0 && (
                    <MessageBox type="info" title="What is a passkey?" css={tw`mt-6`}>
                        Passkeys allow you to sign in with the biometric or PIN that protects your device. Register one
                        above to try it out.
                    </MessageBox>
                )}
            </ContentBox>
        </>
    );

    if (standalone) {
        return (
            <PageContentBlock title="Passkeys" header description="Manage passkey logins for your account.">
                {content}
            </PageContentBlock>
        );
    }

    return (
        <div>
            <div css={tw`mb-4`}>
                <h3 css={tw`text-2xl font-semibold`}>Passkeys</h3>
                <p css={tw`text-sm text-theme-muted`}>Manage biometric logins registered to your account.</p>
            </div>
            {content}
        </div>
    );
}
