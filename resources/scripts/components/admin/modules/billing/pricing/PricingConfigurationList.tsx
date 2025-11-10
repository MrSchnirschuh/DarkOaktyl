import { Link } from 'react-router-dom';
import { Button } from '@elements/button';
import AdminContentBlock from '@elements/AdminContentBlock';
import { Context, useGetPricingConfigurations } from '@/api/admin/billing/pricing';
import { faPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import FlashMessageRender from '@/components/FlashMessageRender';
import PricingConfigurationTable from './PricingConfigurationTable';

export default () => {
    return (
        <AdminContentBlock title={'Pricing Configurations'}>
            <div className={'w-full flex flex-row items-center mb-8'}>
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                    <h2 className={'text-2xl text-neutral-50 font-header font-medium'}>Pricing Configurations</h2>
                    <p className={'text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden'}>
                        Configure resource-based pricing for categories with dynamic product selection.
                    </p>
                </div>
                <div className={'ml-auto'}>
                    <Link to={'/admin/billing/pricing/new'}>
                        <Button>
                            <FontAwesomeIcon icon={faPlus} className={'mr-2'} />
                            New Configuration
                        </Button>
                    </Link>
                </div>
            </div>

            <FlashMessageRender byKey={'admin:billing:pricing'} className={'mb-4'} />

            <Context.Provider>
                <PricingConfigurationTable />
            </Context.Provider>
        </AdminContentBlock>
    );
};
