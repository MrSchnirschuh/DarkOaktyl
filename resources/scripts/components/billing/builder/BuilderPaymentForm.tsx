import { FormEvent, useState } from 'react';
import { PaymentElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { PaymentIntent } from '@/api/billing/intent';
import { updateBuilderIntent } from '@/api/billing/builder';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Button } from '@elements/button';

interface Props {
    intent: PaymentIntent;
    nodeId: number;
    variables: Map<string, string>;
    renewal?: boolean;
    serverId?: number;
    onProcessing?: () => void;
}

const BuilderPaymentForm = ({ intent, nodeId, variables, renewal, serverId, onProcessing }: Props) => {
    const stripe = useStripe();
    const elements = useElements();
    const [loading, setLoading] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        clearFlashes();

        if (!stripe || !elements || !nodeId) {
            return;
        }

        setLoading(true);

        const formattedVariables = Array.from(variables.entries()).map(([key, value]) => ({ key, value }));

        try {
            await updateBuilderIntent({
                intent: intent.id,
                node_id: nodeId,
                variables: formattedVariables,
                renewal,
                server_id: serverId,
            });

            onProcessing?.();

            await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: `${window.location.origin}/account/billing/processing`,
                },
            });
        } catch (error) {
            clearAndAddHttpError({ key: 'billing:builder', error });
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            <PaymentElement />
            <SpinnerOverlay visible={loading} />
            <FlashMessageRender byKey={'billing:builder'} className={'mb-4'} />
            <div className={'text-right'}>
                <Button disabled={!nodeId} className={'mt-4'} size={Button.Sizes.Large}>
                    Pay Now
                </Button>
            </div>
        </form>
    );
};

export default BuilderPaymentForm;
