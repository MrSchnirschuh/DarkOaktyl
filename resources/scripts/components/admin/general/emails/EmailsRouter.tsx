import { MailIcon, ClockIcon } from '@heroicons/react/outline';
import { Route, Routes } from 'react-router-dom';
import tw from 'twin.macro';

import AdminContentBlock from '@elements/AdminContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import { SubNavigation, SubNavigationLink } from '@admin/SubNavigation';
import { useStoreState } from '@/state/hooks';
import EmailTemplatesList from './EmailTemplatesList';
import ScheduledEmailsList from './ScheduledEmailsList';

const EmailsRouter = () => {
    const theme = useStoreState(state => state.theme.data!);
    const appName = useStoreState(state => state.settings.data!.name);

    return (
        <AdminContentBlock title={'Emails'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Email Management</h2>
                    <p
                        css={tw`hidden lg:block text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}
                    >
                        Configure email templates and scheduled emails for {appName}.
                    </p>
                </div>
            </div>

            <FlashMessageRender byKey={'admin:emails'} css={tw`mb-4`} />

            <SubNavigation theme={theme}>
                <SubNavigationLink to="/admin/emails" name="Templates" base>
                    <MailIcon />
                </SubNavigationLink>
                <SubNavigationLink to="/admin/emails/scheduled" name="Scheduled">
                    <ClockIcon />
                </SubNavigationLink>
            </SubNavigation>

            <Routes>
                <Route path="/" element={<EmailTemplatesList />} />
                <Route path="/scheduled" element={<ScheduledEmailsList />} />
            </Routes>
        </AdminContentBlock>
    );
};

export default EmailsRouter;
