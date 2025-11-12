import Spinner from '@elements/Spinner';
import useFlash from '@/plugins/useFlash';
import { useEffect } from 'react';
import { useBillingTermFromRoute } from '@/api/admin/billing/billingTerms';
import BillingTermForm from './BillingTermForm';

export default () => {
    const { data, error } = useBillingTermFromRoute();
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    useEffect(() => {
        if (!error) {
            clearFlashes('admin:billing:terms:load');
            return;
        }

        clearAndAddHttpError({ key: 'admin:billing:terms:load', error });
    }, [error, clearFlashes, clearAndAddHttpError]);

    if (!data) {
        return (
            <div className={'flex justify-center py-10'}>
                <Spinner size={'large'} />
            </div>
        );
    }

    return <BillingTermForm term={data} />;
};
