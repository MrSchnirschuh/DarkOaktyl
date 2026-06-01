import { getIntent, PaymentIntent } from '@/api/billing/intent';
import { getPublicKey } from '@/api/billing/key';
import Spinner from '@elements/Spinner';
import { Elements } from '@stripe/react-stripe-js';
import { loadStripe, Stripe } from '@stripe/stripe-js';
import { useEffect, useState } from 'react';
import PaymentForm from './PaymentForm';
import { ServerContext } from '@/state/server';

export default ({ id }: { id?: number }) => {
    const [stripe, setStripe] = useState<Stripe | null>(null);
    const [intent, setIntent] = useState<PaymentIntent | null>(null);

    const serverId = ServerContext.useStoreState(state => state.server.data!.internalId);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const intentData = await getIntent(id!);
                setIntent({ id: intentData.id, secret: intentData.secret });

                const stripePublicKey = await getPublicKey(id!);
                const stripeInstance = await loadStripe(stripePublicKey.key);
                setStripe(stripeInstance);
            } catch (error) {
                console.error('Error fetching data:', error);
            }
        };

        fetchData();
    }, [id]);

    if (!id || !intent || !stripe) return <Spinner size={'large'} centered />;

    const options = {
        clientSecret: intent.secret,
        appearance: {
            theme: 'night',
            variables: {
                colorText: '#ffffff',
            },
        },
    };

    return (
        <>
            {/* @ts-expect-error this is fine, stripe library is just weird */}
            <Elements stripe={stripe} options={options}>
                <PaymentForm id={id} serverId={Number(serverId)} intent={intent.id} renewal />
            </Elements>
        </>
    );
};
