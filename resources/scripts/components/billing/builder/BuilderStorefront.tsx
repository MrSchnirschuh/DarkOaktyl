import { useEffect, useMemo, useState } from 'react';
import PageContentBlock from '@elements/PageContentBlock';
import Spinner from '@elements/Spinner';
import ContentBox from '@elements/ContentBox';
import { Alert } from '@elements/alert';
import { Button } from '@elements/button';
import classNames from 'classnames';
import { useStoreState } from '@/state/hooks';
import { Elements } from '@stripe/react-stripe-js';
import { loadStripe, Stripe } from '@stripe/stripe-js';
import { useNavigate } from 'react-router-dom';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import NodeBox from '@/components/billing/order/NodeBox';
import VariableBox from '@/components/billing/order/VariableBox';
import getCategories, { Category } from '@/api/billing/getCategories';
import { Node } from '@/api/billing/getNodes';
import getProductVariables from '@/api/billing/getProductVariables';
import { EggVariable } from '@/api/definitions/server';
import { PaymentIntent } from '@/api/billing/intent';
import {
    BuilderIntentPayload,
    BuilderQuoteResult,
    BuilderResource,
    BuilderTerm,
    createBuilderIntent,
    getBuilderKey,
    getBuilderNodes,
    getBuilderQuote,
    getBuilderResources,
    getBuilderTerms,
    processBuilderFree,
} from '@/api/billing/builder';
import BuilderPaymentForm from './BuilderPaymentForm';

const resolveInitialQuantity = (resource: BuilderResource): number => {
    if (resource.default_quantity !== null && resource.default_quantity !== undefined) {
        return Number(resource.default_quantity);
    }

    if (resource.min_quantity !== null && resource.min_quantity !== undefined) {
        return Number(resource.min_quantity);
    }

    if (resource.base_quantity !== null && resource.base_quantity !== undefined) {
        return Number(resource.base_quantity);
    }

    return 0;
};

const mapSelections = (resources: Record<string, number>) =>
    Object.entries(resources).map(([resource, quantity]) => ({ resource, quantity }));

interface BuilderStorefrontProps {
    embedded?: boolean;
}

const BuilderStorefront = ({ embedded = false }: BuilderStorefrontProps) => {
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [resources, setResources] = useState<BuilderResource[]>([]);
    const [terms, setTerms] = useState<BuilderTerm[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [paidNodes, setPaidNodes] = useState<Node[]>([]);
    const [freeNodes, setFreeNodes] = useState<Node[]>([]);
    const [meteredNodes, setMeteredNodes] = useState<Node[]>([]);
    const [selectedCategory, setSelectedCategory] = useState<number | null>(null);
    const [selectedTerm, setSelectedTerm] = useState<BuilderTerm | null>(null);
    const [resourceSelections, setResourceSelections] = useState<Record<string, number>>({});
    const [selectedNode, setSelectedNode] = useState<number>(0);
    const [quote, setQuote] = useState<BuilderQuoteResult['quote'] | null>(null);
    const [quoteCoupons, setQuoteCoupons] = useState<BuilderQuoteResult['coupons']>([]);
    const [quoteLoading, setQuoteLoading] = useState(false);
    const [quoteError, setQuoteError] = useState<string | null>(null);
    const [couponInput, setCouponInput] = useState('');
    const [appliedCoupons, setAppliedCoupons] = useState<string[]>([]);
    const [eggVariables, setEggVariables] = useState<EggVariable[] | null>(null);
    const vars = useMemo(() => new Map<string, string>(), []);
    const [stripe, setStripe] = useState<Stripe | null>(null);
    const [hasStripeKey, setHasStripeKey] = useState(false);
    const [paymentIntent, setPaymentIntent] = useState<PaymentIntent | null>(null);
    const [paymentLoading, setPaymentLoading] = useState(false);
    const [quoteDeploymentType, setQuoteDeploymentType] = useState<'paid' | 'free' | 'metered' | null>(null);

    const settings = useStoreState(state => state.DarkOak.data!.billing);
    const { colors } = useStoreState(state => state.theme.data!);
    const { clearAndAddHttpError, addFlash, clearFlashes } = useFlash();
    const navigate = useNavigate();

    useEffect(() => {
        let isMounted = true;

        const fetchNodes = (type: 'paid' | 'free' | 'metered') =>
            getBuilderNodes(type).catch(error => {
                console.error(`Failed to load ${type} nodes for builder`, error);
                return [] as Node[];
            });

        Promise.all([
            getBuilderResources(),
            getBuilderTerms(),
            getCategories(),
            fetchNodes('paid'),
            fetchNodes('free'),
            fetchNodes('metered'),
        ])
            .then(([resourceData, termData, categoryData, paid, free, metered]) => {
                if (!isMounted) return;

                const sortedResources = [...resourceData].sort((a, b) => a.sort_order - b.sort_order);
                setResources(sortedResources);

                const defaults: Record<string, number> = {};
                sortedResources.forEach(resource => {
                    defaults[resource.resource] = resolveInitialQuantity(resource);
                });
                setResourceSelections(defaults);

                setTerms(termData);
                const preferredTerm = termData.find(term => term.is_default) ?? termData[0] ?? null;
                setSelectedTerm(preferredTerm ?? null);

                setCategories(categoryData);
                setSelectedCategory(categoryData[0] ? Number(categoryData[0].id) : null);

                setPaidNodes(paid);
                setFreeNodes(free);
                setMeteredNodes(metered);
                const defaultNode = paid[0] ?? metered[0] ?? free[0];
                setSelectedNode(defaultNode ? Number(defaultNode.id) : 0);
            })
            .catch(error => {
                console.error(error);
                if (isMounted) {
                    setLoadError('Unable to load builder configuration. Please try again later.');
                }
            })
            .finally(() => {
                if (isMounted) {
                    setLoading(false);
                }
            });

        return () => {
            isMounted = false;
        };
    }, []);

    const selectionsArray = useMemo(() => mapSelections(resourceSelections), [resourceSelections]);

    useEffect(() => {
        if (selectionsArray.length < 1 || !selectedNode) {
            setQuote(null);
            setQuoteCoupons([]);
            setQuoteDeploymentType(null);
            return;
        }

        let isMounted = true;
        setQuoteLoading(true);
        setQuoteError(null);
        setQuoteDeploymentType(null);

        getBuilderQuote({
            resources: selectionsArray,
            term: selectedTerm?.uuid,
            coupons: appliedCoupons,
            node_id: selectedNode,
            options: { node_id: selectedNode },
        })
            .then(response => {
                if (!isMounted) return;
                setQuote(response.quote);
                setQuoteCoupons(response.coupons);
                setQuoteDeploymentType(response.deployment_type ?? 'paid');
            })
            .catch(error => {
                console.error(error);
                if (isMounted) {
                    setQuote(null);
                    setQuoteCoupons([]);
                    setQuoteError('Unable to calculate a quote for the current selection.');
                    setQuoteDeploymentType(null);
                }
            })
            .finally(() => {
                if (isMounted) {
                    setQuoteLoading(false);
                }
            });

        return () => {
            isMounted = false;
        };
    }, [selectionsArray, selectedTerm?.uuid, appliedCoupons, selectedNode]);

    useEffect(() => {
        const category = categories.find(cat => Number(cat.id) === selectedCategory);
        if (!category?.eggId) {
            setEggVariables(null);
            return;
        }

        let isMounted = true;
        getProductVariables(category.eggId)
            .then(variables => {
                if (isMounted) {
                    setEggVariables(variables);
                }
            })
            .catch(error => console.error(error));

        return () => {
            isMounted = false;
        };
    }, [selectedCategory, categories]);

    const totalDue = quote ? quote.total_after_discount ?? quote.total : 0;
    const isFreeOrder = totalDue <= 0;

    const activeDeploymentType = useMemo(() => {
        if (quoteDeploymentType) {
            return quoteDeploymentType;
        }

        if (selectedNode) {
            if (paidNodes.some(node => Number(node.id) === selectedNode)) {
                return 'paid';
            }

            if (meteredNodes.some(node => Number(node.id) === selectedNode)) {
                return 'metered';
            }

            if (freeNodes.some(node => Number(node.id) === selectedNode)) {
                return 'free';
            }
        }

        if (paidNodes.length > 0) {
            return 'paid';
        }

        if (meteredNodes.length > 0) {
            return 'metered';
        }

        return 'free';
    }, [quoteDeploymentType, selectedNode, paidNodes, meteredNodes, freeNodes]);

    const availableNodes =
        activeDeploymentType === 'free' ? freeNodes : activeDeploymentType === 'metered' ? meteredNodes : paidNodes;
    const nodeTypeLabel =
        activeDeploymentType === 'free' ? 'free' : activeDeploymentType === 'metered' ? 'usage-billed' : 'paid';
    const freeCheckoutLabel = activeDeploymentType === 'metered' ? 'Deploy Usage-Billed Server' : 'Deploy Free Server';

    useEffect(() => {
        const fallbackNode = availableNodes[0];
        if (!fallbackNode) {
            setSelectedNode(0);
            return;
        }

        if (!selectedNode || !availableNodes.some(node => Number(node.id) === selectedNode)) {
            setSelectedNode(Number(fallbackNode.id));
        }
    }, [availableNodes, selectedNode]);

    useEffect(() => {
        setPaymentIntent(null);
    }, [selectionsArray, selectedTerm?.uuid, appliedCoupons, selectedCategory]);

    const handleQuantityChange = (resource: BuilderResource, value: number) => {
        const min = resource.min_quantity ?? 0;
        const max = resource.max_quantity ?? null;
        const safeValue = Math.max(min, max !== null && max !== undefined ? Math.min(value, max) : value);

        setResourceSelections(prev => ({
            ...prev,
            [resource.resource]: safeValue,
        }));
    };

    const adjustQuantity = (resource: BuilderResource, delta: number) => {
        const step = resource.step_quantity && resource.step_quantity > 0 ? resource.step_quantity : 1;
        const current = resourceSelections[resource.resource] ?? resolveInitialQuantity(resource);
        handleQuantityChange(resource, current + delta * step);
    };

    const addCoupon = () => {
        const code = couponInput.trim().toUpperCase();
        if (!code) return;
        if (appliedCoupons.includes(code)) {
            setCouponInput('');
            return;
        }

        setAppliedCoupons(prev => [...prev, code]);
        setCouponInput('');
    };

    const removeCoupon = (code: string) => {
        setAppliedCoupons(prev => prev.filter(existing => existing !== code));
    };

    const buildPayload = (): BuilderIntentPayload | null => {
        if (!selectedCategory || !selectedNode) {
            return null;
        }

        return {
            category_id: selectedCategory,
            node_id: selectedNode,
            resources: selectionsArray,
            term: selectedTerm?.uuid,
            coupons: appliedCoupons,
            variables: Array.from(vars.entries()).map(([key, value]) => ({ key, value })),
            deployment_type: quoteDeploymentType ?? activeDeploymentType,
        };
    };

    const prepareCheckout = async () => {
        const payload = buildPayload();
        if (!payload) {
            return;
        }

        setPaymentLoading(true);
        clearFlashes();

        try {
            if (!hasStripeKey) {
                const key = await getBuilderKey();
                const stripeInstance = await loadStripe(key.key);
                setStripe(stripeInstance);
                setHasStripeKey(true);
            }

            const intent = await createBuilderIntent(payload);
            setPaymentIntent(intent);
        } catch (error) {
            clearAndAddHttpError({ key: 'billing:builder', error });
        } finally {
            setPaymentLoading(false);
        }
    };

    const handleFreeCheckout = async () => {
        const payload = buildPayload();
        if (!payload) {
            return;
        }

        setPaymentLoading(true);
        clearFlashes();

        try {
            await processBuilderFree(payload);
            addFlash({
                key: 'billing:builder',
                type: 'success',
                message: 'Your server is being created. Redirecting you to the dashboard.',
            });
            navigate('/');
        } catch (error) {
            clearAndAddHttpError({ key: 'billing:builder', error });
        } finally {
            setPaymentLoading(false);
        }
    };

    if (!settings.keys.publishable) {
        return (
            <Alert type={'danger'}>
                Billing has not been configured. Please contact an administrator before attempting to place an order.
            </Alert>
        );
    }

    if (loading) {
        return (
            <div className={'flex justify-center my-12'}>
                <Spinner size={'large'} />
            </div>
        );
    }

    if (loadError) {
        return <Alert type={'danger'}>{loadError}</Alert>;
    }

    if (resources.length < 1) {
        return (
            <Alert type={'warning'}>
                The server builder has not been configured yet. Add resource prices in the admin area to continue.
            </Alert>
        );
    }

    const content = (
        <>
            <FlashMessageRender byKey={'billing:builder'} className={'mb-4'} />
            <div className={classNames('text-3xl lg:text-5xl font-bold mb-12', embedded ? 'mt-4' : 'mt-8')}>
                Configure Your Resources
                <p className={'text-theme-muted font-normal text-sm mt-1'}>
                    Pick a category, dial in the resources you need, choose a billing term, and continue to checkout.
                </p>
            </div>
            <div className={'grid lg:grid-cols-4 gap-4 lg:gap-8'}>
                <div>
                    <p className={'text-2xl text-theme-secondary mb-6 font-bold'}>Categories</p>
                    {categories.length < 1 ? (
                        <Alert type={'warning'}>No categories are visible yet.</Alert>
                    ) : (
                        categories.map(category => (
                            <button
                                key={category.id}
                                className={classNames(
                                    'font-semibold my-3 w-full text-left hover:brightness-150 duration-300 cursor-pointer line-clamp-1',
                                    Number(category.id) === selectedCategory && 'brightness-150',
                                )}
                                disabled={Number(category.id) === selectedCategory}
                                style={{ color: colors.primary }}
                                onClick={() => {
                                    setSelectedCategory(Number(category.id));
                                }}
                            >
                                {category.icon && (
                                    <img src={category.icon} className={'w-7 h-7 inline-flex rounded-full mr-3'} />
                                )}
                                {category.name}
                                <div className={'h-0.5 mt-4 bg-gray-600 mr-8 rounded-full'} />
                            </button>
                        ))
                    )}
                </div>
                <div className={'lg:col-span-3 space-y-6'}>
                    <ContentBox>
                        <div className={'p-4 lg:p-6'}>
                            <div className={'flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4'}>
                                <div>
                                    <p className={'text-xl lg:text-2xl font-semibold'}>Resources</p>
                                    <p className={'text-theme-muted text-sm'}>
                                        Adjust the sliders or inputs below to size your server.
                                    </p>
                                </div>
                                <div>
                                    <label className={'text-xs text-theme-muted uppercase tracking-wide'}>
                                        Billing Term
                                    </label>
                                    <select
                                        className={
                                            'mt-1 bg-zinc-900 border border-zinc-700 rounded px-3 py-2 w-full lg:w-64'
                                        }
                                        value={selectedTerm?.uuid ?? ''}
                                        onChange={event => {
                                            const term = terms.find(t => t.uuid === event.target.value) ?? null;
                                            setSelectedTerm(term);
                                        }}
                                    >
                                        {terms.map(term => (
                                            <option key={term.uuid} value={term.uuid}>
                                                {term.name} ({term.duration_days} days)
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            <div className={'mt-6 space-y-4'}>
                                {resources.map(resource => (
                                    <div key={resource.uuid} className={'border border-zinc-700 rounded-lg p-4'}>
                                        <div
                                            className={
                                                'flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4'
                                            }
                                        >
                                            <div>
                                                <p className={'font-semibold text-lg'}>{resource.display_name}</p>
                                                <p className={'text-theme-muted text-sm'}>{resource.description}</p>
                                            </div>
                                            <div className={'flex items-center gap-2'}>
                                                <Button.Text onClick={() => adjustQuantity(resource, -1)}>
                                                    -
                                                </Button.Text>
                                                <input
                                                    type={'number'}
                                                    className={
                                                        'w-24 text-center bg-zinc-900 border border-zinc-700 rounded px-2 py-1'
                                                    }
                                                    value={
                                                        resourceSelections[resource.resource] ??
                                                        resolveInitialQuantity(resource)
                                                    }
                                                    onChange={event =>
                                                        handleQuantityChange(resource, Number(event.target.value) || 0)
                                                    }
                                                />
                                                <span className={'text-theme-muted text-sm'}>{resource.unit}</span>
                                                <Button.Text onClick={() => adjustQuantity(resource, 1)}>+</Button.Text>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </ContentBox>
                    <ContentBox>
                        <div className={'p-4 lg:p-6'}>
                            <p className={'text-xl font-semibold mb-4'}>Coupons</p>
                            <div className={'flex flex-col lg:flex-row gap-3'}>
                                <input
                                    type={'text'}
                                    placeholder={'Enter coupon code'}
                                    className={'flex-1 bg-zinc-900 border border-zinc-700 rounded px-3 py-2'}
                                    value={couponInput}
                                    onChange={event => setCouponInput(event.target.value)}
                                />
                                <Button onClick={addCoupon}>Apply</Button>
                            </div>
                            {appliedCoupons.length > 0 && (
                                <div className={'mt-4 flex flex-wrap gap-2'}>
                                    {appliedCoupons.map(code => (
                                        <span
                                            key={code}
                                            className={
                                                'bg-zinc-800 border border-zinc-700 rounded-full px-3 py-1 text-sm'
                                            }
                                        >
                                            {code}
                                            <button
                                                className={'ml-2 text-xs text-red-400'}
                                                onClick={() => removeCoupon(code)}
                                            >
                                                Remove
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>
                    </ContentBox>
                    <ContentBox>
                        <div className={'p-4 lg:p-6'}>
                            <p className={'text-xl font-semibold mb-4'}>Choose a Location</p>
                            {availableNodes.length < 1 ? (
                                <Alert type={'danger'}>
                                    <span>There are no {nodeTypeLabel} nodes available right now.</span>
                                    <span>Please contact support.</span>
                                </Alert>
                            ) : (
                                <div className={'grid lg:grid-cols-2 gap-4'}>
                                    {availableNodes.map(node => (
                                        <NodeBox
                                            key={node.id}
                                            node={node}
                                            selected={selectedNode || undefined}
                                            setSelected={setSelectedNode}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </ContentBox>
                    {eggVariables && eggVariables.length > 0 && (
                        <ContentBox>
                            <div className={'p-4 lg:p-6'}>
                                <p className={'text-xl font-semibold mb-4'}>Variables</p>
                                <div className={'grid lg:grid-cols-2 gap-4'}>
                                    {eggVariables.map(variable => (
                                        <div key={variable.envVariable}>
                                            <VariableBox variable={variable} vars={vars} />
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </ContentBox>
                    )}
                    <ContentBox>
                        <div className={'p-4 lg:p-6 space-y-4'}>
                            <div className={'flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4'}>
                                <div>
                                    <p className={'text-xl font-semibold'}>Order Summary</p>
                                    {quoteError && <p className={'text-red-400 text-sm'}>{quoteError}</p>}
                                </div>
                                <div className={'text-right'}>
                                    <p className={'text-sm text-theme-muted'}>Estimated Total</p>
                                    <p className={'text-3xl font-bold'}>
                                        {settings.currency.symbol}
                                        {quoteLoading ? '—' : totalDue.toFixed(2)}{' '}
                                        {settings.currency.code.toUpperCase()}
                                    </p>
                                </div>
                            </div>
                            {quoteLoading && <Spinner centered />}
                            {!quoteLoading && quote && (
                                <div className={'space-y-2'}>
                                    {Object.values(quote.resources || {}).map(resource => (
                                        <div key={resource.resource} className={'flex justify-between text-sm'}>
                                            <span>
                                                {resource.display_name}: {resource.quantity} {resource.unit}
                                            </span>
                                            <span>
                                                {settings.currency.symbol}
                                                {resource.total.toFixed(2)}
                                            </span>
                                        </div>
                                    ))}
                                    {quote.discount && quote.discount > 0 && (
                                        <div className={'flex justify-between text-sm text-green-400'}>
                                            <span>Discounts</span>
                                            <span>
                                                -{settings.currency.symbol}
                                                {quote.discount.toFixed(2)}
                                            </span>
                                        </div>
                                    )}
                                    {quoteCoupons.length > 0 && (
                                        <div className={'text-xs text-theme-muted'}>
                                            Applied coupons:&nbsp;
                                            {quoteCoupons.map(coupon => coupon.code).join(', ')}
                                        </div>
                                    )}
                                </div>
                            )}
                            {isFreeOrder ? (
                                <Button disabled={!selectedNode || paymentLoading} onClick={handleFreeCheckout}>
                                    {freeCheckoutLabel}
                                </Button>
                            ) : paymentIntent ? (
                                stripe && paymentIntent ? (
                                    <Elements
                                        stripe={stripe}
                                        options={{
                                            clientSecret: paymentIntent.secret,
                                            appearance: { theme: 'night' },
                                        }}
                                    >
                                        <BuilderPaymentForm
                                            intent={paymentIntent}
                                            nodeId={selectedNode}
                                            variables={vars}
                                        />
                                        <div className={'text-right mt-3'}>
                                            <Button.Text onClick={() => setPaymentIntent(null)}>
                                                Cancel Payment
                                            </Button.Text>
                                        </div>
                                    </Elements>
                                ) : (
                                    <Alert type={'danger'}>Stripe could not be initialized.</Alert>
                                )
                            ) : (
                                <Button disabled={!selectedNode || paymentLoading} onClick={prepareCheckout}>
                                    {paymentLoading ? 'Preparing Checkout...' : 'Continue to Payment'}
                                </Button>
                            )}
                        </div>
                    </ContentBox>
                </div>
            </div>
        </>
    );

    if (embedded) {
        return content;
    }

    return <PageContentBlock title={'Build a Custom Server'}>{content}</PageContentBlock>;
};

export default BuilderStorefront;
