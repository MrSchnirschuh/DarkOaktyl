import Spinner from '@elements/Spinner';
import useFlash from '@/plugins/useFlash';
import { useEffect } from 'react';
import { useCouponFromRoute } from '@/api/admin/billing/coupons';
import CouponForm from './CouponForm';

export default () => {
    const { data, error } = useCouponFromRoute();
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    useEffect(() => {
        if (!error) {
            clearFlashes('admin:billing:coupons:load');
            return;
        }

        clearAndAddHttpError({ key: 'admin:billing:coupons:load', error });
    }, [error, clearFlashes, clearAndAddHttpError]);

    if (!data) {
        return (
            <div className={'flex justify-center py-10'}>
                <Spinner size={'large'} />
            </div>
        );
    }

    return <CouponForm coupon={data} />;
};
