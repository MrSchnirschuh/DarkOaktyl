import { Route, Routes } from 'react-router-dom';
import { useStoreState } from '@/state/hooks';
import AdminContentBlock from '@elements/AdminContentBlock';
import { NotFound } from '@elements/ScreenBlock';
import { SubNavigation, SubNavigationLink } from '@admin/SubNavigation';
import { CogIcon, CollectionIcon } from '@heroicons/react/outline';
import FlashMessageRender from '@/components/FlashMessageRender';
import EnablePresetsContainer from './EnablePresetsContainer';
import PresetsContainer from './PresetsContainer';
import PresetsSettings from './PresetsSettings';

const PresetsRouter = () => {
    const enabled = useStoreState(s => s.settings.data!.presets_module ?? false);
    const theme = useStoreState(s => s.theme.data!);

    if (!enabled) return <EnablePresetsContainer />;

    return (
        <AdminContentBlock title={'Presets'}>
            <FlashMessageRender byKey={'admin:presets'} className={'mb-4'} />
            <SubNavigation theme={theme}>
                <SubNavigationLink to={'/admin/presets'} name={'Overview'} base>
                    <CollectionIcon />
                </SubNavigationLink>
                <SubNavigationLink to={'/admin/presets/settings'} name={'Settings'}>
                    <CogIcon />
                </SubNavigationLink>
            </SubNavigation>

            <Routes>
                <Route path={'/'} element={<PresetsContainer />} />
                <Route path={'/settings'} element={<PresetsSettings />} />
                <Route path={'/*'} element={<NotFound />} />
            </Routes>
        </AdminContentBlock>
    );
};

export default PresetsRouter;
