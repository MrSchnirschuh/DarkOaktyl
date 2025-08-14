import { useServerFromRoute } from '@/api/admin/server';
import OrdersTable from '@/components/admin/modules/billing/orders/OrdersTable';
import AdminBox from '@/components/elements/AdminBox';
import { Alert } from '@/components/elements/alert';
import Label from '@/components/elements/Label';
import Spinner from '@/components/elements/Spinner';
import { useStoreState } from '@/state/hooks';
import { faCashRegister } from '@fortawesome/free-solid-svg-icons';
import EditServerBillingDialog from './EditServerBillingDialog';

function futureDate(days: number): string {
    const today = new Date();
    const futureDate = new Date(today);

    futureDate.setDate(today.getDate() + days);

    return futureDate.toDateString();
}

export default () => {
    const { data: server } = useServerFromRoute();
    const billing = useStoreState(state => state.everest.data!.billing);

    if (!server) return null;

    const product = server.relationships.product;

    return (
        <div>
            {!billing.enabled && (
                <Alert type={'danger'}>
                    The Billing Module is currently disabled. Any changes made here will not have effect unless the
                    module is enabled again.
                </Alert>
            )}
            <div className={'mt-4 grid lg:grid-cols-4 gap-4'}>
                <AdminBox title={'Billing Details'} icon={faCashRegister} className={'relative'}>
                    <div className={'grid gap-y-4'}>
                        <div>
                            <Label>Plan Name and Cost</Label>
                            <p className={'text-gray-400'}>
                                {!server.billingProductId ? (
                                    'None'
                                ) : !product ? (
                                    <Spinner size={'small'} centered />
                                ) : (
                                    <>
                                        {product.name} - {billing.currency.symbol}
                                        {product.price} {billing.currency.code.toUpperCase()} every 30 days
                                    </>
                                )}
                            </p>
                        </div>
                        <div>
                            <Label>Next Renewal Due</Label>
                            <p className={'text-gray-400'}>
                                {!server.billingProductId ? (
                                    'None'
                                ) : (
                                    <>
                                        {futureDate(server.daysUntilRenewal!)} ({server.daysUntilRenewal} days)
                                    </>
                                )}
                            </p>
                        </div>
                        <div>
                            <Label>Resource Limits</Label>
                            <p className={'text-gray-400'}>
                                {!server.billingProductId ? (
                                    'None'
                                ) : !product ? (
                                    <Spinner size={'small'} centered />
                                ) : (
                                    <>
                                        {product.limits.cpu}% CPU &bull; {product.limits.memory / 1024} GiB RAM &bull;{' '}
                                        {product.limits.disk / 1024} GiB Storage
                                    </>
                                )}
                            </p>
                        </div>
                        <div className={'absolute top-2 right-2'}>
                            <EditServerBillingDialog server={server} product={product} />
                        </div>
                    </div>
                </AdminBox>
                <div className={'lg:col-span-3'}>
                    <OrdersTable minimal name={server.uuid.slice(0, 8)} />
                </div>
            </div>
        </div>
    );
};
