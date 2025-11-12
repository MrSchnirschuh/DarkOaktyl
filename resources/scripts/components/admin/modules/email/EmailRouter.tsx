import { Route, Routes } from 'react-router-dom';
import { useStoreState } from '@/state/hooks';
import AdminContentBlock from '@elements/AdminContentBlock';
import { NotFound } from '@elements/ScreenBlock';
import { SubNavigation, SubNavigationLink } from '@admin/SubNavigation';
import { ColorSwatchIcon, DocumentTextIcon, LightningBoltIcon, MailIcon, CogIcon } from '@heroicons/react/outline';
import FlashMessageRender from '@/components/FlashMessageRender';
import Unfinished from '@elements/Unfinished';
import EnableEmailsContainer from './EnableEmailsContainer';
import OverviewContainer from './overview/OverviewContainer';
import SettingsContainer from './settings/SettingsContainer';
import ThemesContainer from './themes/ThemesContainer';
import TemplatesContainer from './templates/TemplatesContainer';
import TriggersContainer from './triggers/TriggersContainer';

const EmailRouter = () => {
    const theme = useStoreState(state => state.theme.data!);
    const enabled = useStoreState(state => state.everest.data?.emails.enabled ?? false);

    if (!enabled) {
        return <EnableEmailsContainer />;
    }

    return (
        <AdminContentBlock title={'Emails'}>
            <Unfinished untested />
            <FlashMessageRender byKey={'admin:emails'} className={'mb-4'} />
            <SubNavigation theme={theme}>
                <SubNavigationLink to={'/admin/emails'} name={'Overview'} base>
                    <MailIcon />
                </SubNavigationLink>
                <SubNavigationLink to={'/admin/emails/themes'} name={'Themes'}>
                    <ColorSwatchIcon />
                </SubNavigationLink>
                <SubNavigationLink to={'/admin/emails/templates'} name={'Templates'}>
                    <DocumentTextIcon />
                </SubNavigationLink>
                <SubNavigationLink to={'/admin/emails/triggers'} name={'Triggers'}>
                    <LightningBoltIcon />
                </SubNavigationLink>
                <SubNavigationLink to={'/admin/emails/settings'} name={'Settings'}>
                    <CogIcon />
                </SubNavigationLink>
            </SubNavigation>

            <Routes>
                <Route path={'/'} element={<OverviewContainer />} />
                <Route path={'/themes'} element={<ThemesContainer />} />
                <Route path={'/templates'} element={<TemplatesContainer />} />
                <Route path={'/triggers'} element={<TriggersContainer />} />
                <Route path={'/settings'} element={<SettingsContainer />} />
                <Route path={'/*'} element={<NotFound />} />
            </Routes>
        </AdminContentBlock>
    );
};

export default EmailRouter;
