import Spinner from '@elements/Spinner';
import useFlash from '@/plugins/useFlash';
import { useEffect } from 'react';
import { useResourcePriceFromRoute } from '@/api/admin/billing/resourcePrices';
import ResourcePriceForm from './ResourcePriceForm';

export default () => {
    const { data, error } = useResourcePriceFromRoute();
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    useEffect(() => {
        if (!error) {
            clearFlashes('admin:billing:resources:load');
            return;
        }

        clearAndAddHttpError({ key: 'admin:billing:resources:load', error });
    }, [error, clearFlashes, clearAndAddHttpError]);

    if (!data) {
        return (
            <div className={'flex justify-center py-10'}>
                <Spinner size={'large'} />
            </div>
        );
    }

    return <ResourcePriceForm resource={data} />;
};
