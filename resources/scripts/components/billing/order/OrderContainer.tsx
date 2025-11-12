import Spinner from '@elements/Spinner';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useStoreState } from '@/state/hooks';
import { Product } from '@/api/billing/products';
import { getProduct } from '@/api/billing/products';
import NodeBox from '@/components/billing/order/NodeBox';
import PageContentBlock from '@elements/PageContentBlock';
import VariableBox from '@/components/billing/order/VariableBox';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { IconDefinition } from '@fortawesome/fontawesome-svg-core';
import getProductVariables from '@/api/billing/getProductVariables';
import {
    faArchive,
    faCreditCard,
    faDatabase,
    faEthernet,
    faHdd,
    faIdBadge,
    faMemory,
    faMicrochip,
} from '@fortawesome/free-solid-svg-icons';
import getNodes, { Node } from '@/api/billing/getNodes';
import { Alert } from '@elements/alert';
import useFlash from '@/plugins/useFlash';
import { getIntent, PaymentIntent } from '@/api/billing/intent';
import PaymentButton from './PaymentButton';
import { Elements } from '@stripe/react-stripe-js';
import { loadStripe, Stripe } from '@stripe/stripe-js';
import { getPublicKey } from '@/api/billing/key';
import { EggVariable } from '@/api/definitions/server';
import { Button } from '@/components/elements/button';
import processUnpaidOrder from '@/api/billing/processUnpaidOrder';
import FlashMessageRender from '@/components/FlashMessageRender';

const LimitBox = ({ icon, content }: { icon: IconDefinition; content: string }) => {
    return (
        <div className={'my-1 font-semibold text-gray-400'}>
            <FontAwesomeIcon icon={icon} className={'mr-2 inline-flex h-4 w-4 '} />
            {content}
        </div>
    );
};

export default () => {
    const params = useParams<'id'>();

    const vars = new Map<string, string>();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const navigate = useNavigate();

    const [stripe, setStripe] = useState<Stripe | null>(null);
    const [intent, setIntent] = useState<PaymentIntent | null>(null);
    const [nodes, setNodes] = useState<Node[] | undefined>();
    const [selectedNode, setSelectedNode] = useState<number>(0);
    const [product, setProduct] = useState<Product | undefined>();
    const [eggs, setEggs] = useState<EggVariable[] | undefined>();

    const { colors } = useStoreState(state => state.theme.data!);

    const createFree = () => {
        if (product) {
            processUnpaidOrder(product.id, selectedNode)
                .then(() => navigate('/'))
                .catch(error => clearAndAddHttpError({ key: 'account:billing:order', error }));
        }
    };

    useEffect(() => {
        const fetchData = async () => {
            try {
                // Fetch product details
                const productData = await getProduct(Number(params.id));
                setProduct(productData);

                // Fetch nodes
                const nodesData = await getNodes(productData.id);
                setNodes(nodesData);
                setSelectedNode(Number(nodesData[0]?.id) ?? 0);

                if (productData.price !== 0) {
                    // Fetch payment intent
                    const intentData = await getIntent(Number(params.id));
                    setIntent({ id: intentData.id, secret: intentData.secret });

                    // Fetch Stripe public key and initialize Stripe
                    const stripePublicKey = await getPublicKey(Number(params.id));
                    const stripeInstance = await loadStripe(stripePublicKey.key);
                    setStripe(stripeInstance);
                }
            } catch (error) {
                console.error('Error fetching data:', error);
            }
        };

        fetchData();
    }, [params.id]);

    useEffect(() => {
        clearFlashes();

        if (!product || eggs) return;

        // Fetch product variables (egg data)
        getProductVariables(Number(product.eggId))
            .then(data => setEggs(data))
            .catch(error => console.error(error));
    }, [product]);

    if (!product) return <Spinner centered />;
    if (product.price !== 0 && (!intent || !stripe)) return <Spinner centered />;

    const options = {
        clientSecret: intent?.secret,
        appearance: {
            theme: 'night',
            variables: {
                colorText: '#ffffff',
            },
        },
    };

    return (
        <PageContentBlock title={'Your Order'}>
            <FlashMessageRender byKey={'account:billing:order'} className={'mb-4'} />
            {/* @ts-expect-error this is fine, stripe library is just weird */}
            <Elements stripe={stripe} options={options}>
                <div className={'mt-8 mb-12 text-3xl font-bold lg:text-5xl'}>
                    Your Order
                    <p className={'mt-1 text-sm font-normal text-gray-400'}>
                        Customize your selected plan and submit a payment.
                    </p>
                </div>
                <div className={'grid gap-4 lg:grid-cols-8 lg:gap-12'}>
                    <div className={'border-gray-500 lg:col-span-2 lg:border-r-4'}>
                        <p className={'my-4 text-2xl font-bold text-gray-300'}>
                            Selected Plan
                            {product.icon && <img src={product.icon} className={'ml-2 inline-flex h-8 w-8'} />}
                        </p>
                        <LimitBox icon={faIdBadge} content={product.name} />
                        <div className={'my-1 text-lg font-semibold text-gray-400'}>
                            <FontAwesomeIcon icon={faCreditCard} className={'mr-2 inline-flex h-4 w-4 '} />
                            <span style={{ color: colors.primary }} className={'mr-1'}>
                                ${product.price}
                            </span>
                            <span className={'text-sm'}>/ mo</span>
                        </div>
                        <div className={'my-4 mr-8 h-0.5 rounded-full bg-gray-600'} />
                        <LimitBox icon={faMicrochip} content={`${product.limits.cpu}% CPU`} />
                        <LimitBox icon={faMemory} content={`${(product.limits.memory / 1024).toFixed(1)} GiB Memory`} />
                        <LimitBox icon={faHdd} content={`${(product.limits.disk / 1024).toFixed(1)} GiB Disk`} />
                        <div className={'my-4 mr-8 h-0.5 rounded-full bg-gray-600'} />
                        <LimitBox icon={faArchive} content={`${product.limits.backup} Backup Slots`} />
                        <LimitBox icon={faDatabase} content={`${product.limits.database} Database Slots`} />
                        <LimitBox icon={faEthernet} content={`${product.limits.allocation} Network Ports`} />
                    </div>
                    <div className={'lg:col-span-6'}>
                        <div>
                            <div className={'my-10'}>
                                <div className={'mb-4 text-xl font-semibold lg:text-3xl'}>
                                    Choose a location
                                    <p className={'mt-1 text-sm font-normal text-gray-400'}>
                                        Select a location from our list to deploy your server to.
                                    </p>
                                </div>
                                <div className={'grid gap-4 lg:grid-cols-2'}>
                                    {(!nodes || nodes.length < 1) && (
                                        <Alert type={'danger'} className={'col-span-2'}>
                                            There are no nodes available for deployment. Please contact an
                                            administrator.
                                        </Alert>
                                    )}
                                    {nodes?.map(node => (
                                        <NodeBox
                                            node={node}
                                            key={node.id}
                                            selected={selectedNode}
                                            setSelected={setSelectedNode}
                                        />
                                    ))}
                                </div>
                            </div>
                            <div className={'h-px rounded-full bg-gray-700'} />
                            {eggs && eggs.length > 1 && (
                                <>
                                    <div className={'my-10'}>
                                        <div className={'mb-4 text-xl font-semibold lg:text-3xl'}>
                                            Plan Variables
                                            <p className={'mt-1 text-sm font-normal text-gray-400'}>
                                                Modify your server variables before your server is even created for ease
                                                of use.
                                            </p>
                                        </div>
                                        <div className={'grid gap-4 lg:grid-cols-2'}>
                                            {eggs?.map(variable => (
                                                <div key={variable.envVariable}>
                                                    {variable.isEditable && (
                                                        <VariableBox variable={variable} vars={vars} />
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                    <div className={'h-px rounded-full bg-gray-700'} />
                                </>
                            )}
                            {product.price !== 0 && intent ? (
                                <div className={'mt-8 w-full'}>
                                    <PaymentButton
                                        selectedNode={selectedNode}
                                        product={product}
                                        vars={vars}
                                        intent={intent}
                                    />
                                </div>
                            ) : (
                                <div className={'mt-8 flex w-full'}>
                                    <p className={'font-semibold text-gray-400'}>
                                        As this product is free, no purchase needs to be made via our payment gateways.
                                    </p>
                                    <Button className={'ml-auto'} onClick={createFree}>
                                        Create Server
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </Elements>
        </PageContentBlock>
    );
};
