import { Route, Routes } from 'react-router-dom';
import AdminContentBlock from '@elements/AdminContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import ApiContainer from './ApiContainer';
import NewApiKeyContainer from './NewApiKeyContainer';
import { NotFound } from '@/components/elements/ScreenBlock';

export default () => (
    <AdminContentBlock title={'Application API'}>
        <div className={'w-full flex flex-row items-center mb-8'}>
            <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                <h2 className={'text-2xl text-theme-primary font-header font-medium'}>Application API</h2>
                <p
                    className={
                        'hidden lg:block text-base text-theme-muted whitespace-nowrap overflow-ellipsis overflow-hidden'
                    }
                >
                    Create, update and delete administrative API keys for this Panel.
                </p>
            </div>
        </div>

        <FlashMessageRender byKey={'admin:settings'} className={'mb-4'} />

        <Routes>
            <Route path={'/'} element={<ApiContainer />} />
            <Route path={'/new'} element={<NewApiKeyContainer />} />
            <Route path={'*'} element={<NotFound />} />
        </Routes>
    </AdminContentBlock>
);
