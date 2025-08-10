import { useEffect, useState } from 'react';
import Label from '@elements/Label';
import { Link } from 'react-router-dom';
import ContentBox from '@elements/ContentBox';
import { ServerContext } from '@/state/server';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowRight } from '@fortawesome/free-solid-svg-icons';
import { getOrder } from '@/api/billing/orders';
import useFlash from '@/plugins/useFlash';
import { getProduct } from '@/api/billing/products';
import { Product } from '@/api/billing/products';
import SpinnerOverlay from '@elements/SpinnerOverlay';
import { Alert } from '@elements/alert';
import PaymentContainer from './PaymentContainer';
import { useStoreState } from '@/state/hooks';
import { Order } from '@/api/definitions/billing';
import PageContentBlock from '@/components/elements/PageContentBlock';

function futureDate(days: number): string {
    const today = new Date();
    const futureDate = new Date(today);

    futureDate.setDate(today.getDate() + days);

    return futureDate.toDateString();
}

export default () => {
    const [order, setOrder] = useState<Order>();
    const [product, setProduct] = useState<Product>();
    const [loading, setLoading] = useState<boolean>(true);

    const { clearFlashes } = useFlash();
    const settings = useStoreState(s => s.everest.data!.billing);
    const orderId = ServerContext.useStoreState(s => s.server.data!.orderId);
    const daysUntilRenewal = ServerContext.useStoreState(s => s.server.data!.daysUntilRenewal);

    useEffect(() => {
        clearFlashes();

        getOrder(orderId)
            .then(data => setOrder(data))
            .catch(error => {
                setLoading(false);
                console.error(error);
            });
    }, []);

    useEffect(() => {
        if (!order) return;

        console.log(order);

        getProduct(order.product_id)
            .then(data => setProduct(data))
            .then(() => setLoading(false))
            .catch(error => {
                setLoading(false);
                console.error(error);
            });
    }, [order]);

    return (
        <PageContentBlock
            title={'Server Billing'}
            header
            description={'Control your billing settings for this server.'}
        >
            {(!product || !order) && !loading && (
                <Alert type={'warning'} className={'mb-6'}>
                    The {!order ? 'order you made ' : 'plan you purchased '}
                    no longer exists, so some details may not be shown.
                </Alert>
            )}
            <div className={'grid lg:grid-cols-3 gap-4'}>
                <ContentBox title={'Summary'}>
                    <SpinnerOverlay visible={loading} />
                    <div>
                        <Label>Next renewal due</Label>
                        <p className={'text-gray-400 text-sm'}>
                            {futureDate(daysUntilRenewal)} ({daysUntilRenewal} days until due)
                        </p>
                    </div>
                    <div className={'my-6'}>
                        <Label>Your package</Label>
                        <p className={'text-gray-400 text-sm'}>{product ? product.name : 'Unknown'}</p>
                        <p className={'text-gray-500 text-xs'}>{product && product.description}</p>
                    </div>
                    <div>
                        <Label>Plan cost</Label>
                        <div className={'flex justify-between'}>
                            <p className={'text-gray-400 text-sm'}>
                                {settings.currency.symbol}
                                {product ? product.price : '...'} {settings.currency.code.toUpperCase()} every 30 days
                            </p>
                            <Link to={'/billing/orders'} className={'text-green-400 text-xs'}>
                                View order <FontAwesomeIcon icon={faArrowRight} />
                            </Link>
                        </div>
                    </div>
                </ContentBox>
                <ContentBox title={'Renew Server'} className={'lg:col-span-2'}>
                    <div className={'mb-4'}>
                        <p className={'text-gray-400 text-xs'}>
                            If you renew now, your server will be active for a further 30 days, making your next renewal
                            date {futureDate(daysUntilRenewal + 30)} ({daysUntilRenewal + 30} days).
                        </p>
                    </div>
                    {!product ? (
                        <Alert type={'danger'}>
                            The product package that the server was made with no longer exists. In order to renew your
                            server, you&apos;ll need to speak to an administrator.
                        </Alert>
                    ) : (
                        <PaymentContainer id={Number(product!.id)} />
                    )}
                </ContentBox>
            </div>
        </PageContentBlock>
    );
};
