import { useMemo, useState } from 'react';
import ContentBox from '@elements/ContentBox';
import UpdatePasswordForm from '@/components/dashboard/forms/UpdatePasswordForm';
import UpdateEmailAddressForm from '@/components/dashboard/forms/UpdateEmailAddressForm';
import ConfigureTwoFactorForm from '@/components/dashboard/forms/ConfigureTwoFactorForm';
import PageContentBlock from '@elements/PageContentBlock';
import tw from 'twin.macro';
import styled from 'styled-components';
import MessageBox from '@/components/MessageBox';
import { useLocation } from 'react-router-dom';
import { useStoreState } from '@/state/hooks';
import AccountPasskeyContainer from '@/components/dashboard/passkeys/AccountPasskeyContainer';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faIdCard, faShieldHalved } from '@fortawesome/free-solid-svg-icons';

const Section = styled.section`
    ${tw`mt-12`};

    &:first-of-type {
        ${tw`mt-8`};
    }
`;

const InfoList = styled.dl`
    ${tw`border border-theme-muted rounded-lg`};

    & > div + div {
        ${tw`border-t border-theme-muted`};
    }
`;

const InfoRow = styled.div`
    ${tw`flex flex-col sm:flex-row sm:items-center justify-between py-4 px-4`};

    &:first-of-type {
        ${tw`pt-5`};
    }

    &:last-of-type {
        ${tw`pb-5`};
    }
`;

const InfoLabel = styled.dt`
    ${tw`text-xs font-semibold uppercase tracking-wide text-theme-muted`};
`;

const InfoValue = styled.dd`
    ${tw`mt-1 sm:mt-0 text-base`};
`;

const TabsContainer = styled.div`
    ${tw`mt-6 flex flex-wrap gap-3`};
`;

const TabButton = styled.button<{ $active: boolean }>`
    ${tw`flex flex-col sm:flex-row sm:items-center gap-2 rounded-xl px-4 py-3 text-left transition-colors duration-150 border`};
    ${({ $active }) =>
        $active
            ? tw`bg-theme-surface text-theme-primary border-theme-muted shadow-lg`
            : tw`bg-transparent border-transparent text-theme-muted hover:bg-white/5`}
`;

const TabLabel = styled.span`
    ${tw`text-base font-semibold`};
`;

const TabDescription = styled.span<{ $active: boolean }>`
    ${tw`text-xs`};
    ${({ $active }) => ($active ? tw`text-theme-secondary` : tw`text-theme-muted`)}
`;

const TabIcon = styled.span<{ $active: boolean }>`
    ${tw`text-lg`};
    ${({ $active }) => ($active ? tw`text-theme-primary` : tw`text-theme-muted`)}
`;

export default () => {
    const { state } = useLocation();
    const user = useStoreState(store => store.user.data);

    const displayName = user?.username ?? 'Not provided';
    const username = user?.username ?? 'Not provided';
    const email = user?.email ?? 'Not provided';
    const [activeTab, setActiveTab] = useState<'personal' | 'security'>('personal');

    const tabs = useMemo(
        () => [
            {
                id: 'personal' as const,
                label: 'Personal',
                description: 'Profile & contact info',
                icon: faIdCard,
                content: (
                    <Section>
                        <div css={tw`grid gap-8 lg:grid-cols-2`}>
                            <ContentBox title="Profile Details">
                                <InfoList>
                                    <InfoRow>
                                        <InfoLabel>Name</InfoLabel>
                                        <InfoValue>{displayName}</InfoValue>
                                    </InfoRow>
                                    <InfoRow>
                                        <InfoLabel>Username</InfoLabel>
                                        <InfoValue>{username}</InfoValue>
                                    </InfoRow>
                                    <InfoRow>
                                        <InfoLabel>Email</InfoLabel>
                                        <InfoValue>{email}</InfoValue>
                                    </InfoRow>
                                </InfoList>
                            </ContentBox>
                            <ContentBox title="Update Email Address" showFlashes="account:email">
                                <UpdateEmailAddressForm />
                            </ContentBox>
                        </div>
                    </Section>
                ),
            },
            {
                id: 'security' as const,
                label: 'Security',
                description: 'Password, 2FA & passkeys',
                icon: faShieldHalved,
                content: (
                    <Section>
                        <div css={tw`grid gap-8 lg:grid-cols-2`}>
                            <ContentBox title="Update Password" showFlashes="account:password">
                                <UpdatePasswordForm />
                            </ContentBox>
                            <ContentBox title="Two-Step Verification">
                                <ConfigureTwoFactorForm />
                            </ContentBox>
                        </div>
                        <div css={tw`mt-10`}>
                            <AccountPasskeyContainer standalone={false} />
                        </div>
                    </Section>
                ),
            },
        ],
        [displayName, email, username],
    );

    const activeContent = tabs.find(tab => tab.id === activeTab)?.content;

    return (
        <PageContentBlock
            title="Account"
            header
            description={
                'Manage your personal information, login preferences, and security tools all from one organized view.'
            }
        >
            {state?.twoFactorRedirect && (
                <MessageBox title="2-Factor Required" type="error">
                    Your account must have two-factor authentication enabled in order to continue.
                </MessageBox>
            )}
            <TabsContainer>
                {tabs.map(tab => {
                    const isActive = tab.id === activeTab;
                    return (
                        <TabButton key={tab.id} $active={isActive} onClick={() => setActiveTab(tab.id)}>
                            <div css={tw`flex items-center gap-2`}>
                                <TabIcon $active={isActive}>
                                    <FontAwesomeIcon icon={tab.icon} />
                                </TabIcon>
                                <TabLabel>{tab.label}</TabLabel>
                            </div>
                            <TabDescription $active={isActive}>{tab.description}</TabDescription>
                        </TabButton>
                    );
                })}
            </TabsContainer>

            <div css={tw`mt-8`}>{activeContent}</div>
        </PageContentBlock>
    );
};
