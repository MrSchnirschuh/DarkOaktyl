import { useEffect } from 'react';
import ContentBox from '@elements/ContentBox';
import SpinnerOverlay from '@elements/SpinnerOverlay';
import FlashMessageRender from '@/components/FlashMessageRender';
import PageContentBlock from '@elements/PageContentBlock';
import tw from 'twin.macro';
import GreyRowBox from '@elements/GreyRowBox';
import { useSSHKeys } from '@/api/account/ssh-keys';
import { useFlashKey } from '@/plugins/useFlash';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faKey } from '@fortawesome/free-solid-svg-icons';
import CreateSSHKeyForm from '@/components/dashboard/ssh/CreateSSHKeyForm';
import DeleteSSHKeyButton from '@/components/dashboard/ssh/DeleteSSHKeyButton';

export default () => {
    const { clearAndAddHttpError } = useFlashKey('account');
    const { data, isValidating, error } = useSSHKeys({
        revalidateOnMount: true,
        revalidateOnFocus: false,
    });

    useEffect(() => {
        if (error) {
            clearAndAddHttpError(error);
        }
    }, [error, clearAndAddHttpError]);

    return (
        <PageContentBlock title={'SSH Keys'} header description={'Create, use and delete SSH keys to access servers.'}>
            <FlashMessageRender byKey={'account'} />
            <div css={tw`md:flex flex-nowrap my-10`}>
                <ContentBox title={'Add SSH Key'} css={tw`flex-none w-full md:w-1/2`}>
                    <CreateSSHKeyForm />
                </ContentBox>
                <ContentBox title={'SSH Keys'} css={tw`flex-1 overflow-hidden mt-8 md:mt-0 md:ml-8`}>
                    <SpinnerOverlay visible={!data && isValidating} />
                    {!data || !data.length ? (
                        <p css={tw`text-center text-sm`}>
                            {!data ? 'Loading...' : 'No SSH Keys exist for this account.'}
                        </p>
                    ) : (
                        data.map((key, index) => (
                            <GreyRowBox
                                key={key.fingerprint}
                                css={[tw`bg-black/50 flex space-x-4 items-center`, index > 0 && tw`mt-2`]}
                            >
                                <FontAwesomeIcon icon={faKey} css={tw`text-theme-secondary`} />
                                <div css={tw`flex-1`}>
                                    <p css={tw`text-lg font-bold break-words`}>{key.name}</p>
                                    <p css={tw`text-xs mt-1 font-mono truncate text-theme-secondary`}>
                                        SHA256:{key.fingerprint}
                                    </p>
                                    <p css={tw`text-xs mt-1 text-theme-muted uppercase`}>
                                        Added on:&nbsp;
                                        {key.created_at.toLocaleString()}
                                    </p>
                                </div>
                                <DeleteSSHKeyButton name={key.name} fingerprint={key.fingerprint} />
                            </GreyRowBox>
                        ))
                    )}
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
