import Stepper from '@elements/Stepper';
import { faArrowRight, faCheck, faEllipsis } from '@fortawesome/free-solid-svg-icons';
import { useEffect, useState } from 'react';
import Spinner from '@elements/Spinner';
import { useStoreState } from '@/state/hooks';
import ContentBox from '@elements/ContentBox';
import { differenceInDays, format, parseISO } from 'date-fns';
import SuccessChart from './SuccessChart';
import RevenueChart from './RevenueChart';
import Select from '@elements/Select';
import SetupStripe from '../guides/SetupStripe';
import { getBillingAnalytics } from '@/api/admin/billing';
import { BillingAnalytics, Coupon, Order } from '@/api/definitions/admin';
import { listActiveCoupons } from '@/api/admin/billing/coupons';
import { httpErrorToHuman } from '@/api/http';
import { Link } from 'react-router-dom';

export default () => {
    const now = new Date();
    const [history, setHistory] = useState<number>(14);
    const settings = useStoreState(s => s.DarkOak.data!.billing);
    const [analytics, setAnalytics] = useState<BillingAnalytics>();
    const [activeCoupons, setActiveCoupons] = useState<Coupon[] | null>(null);
    const [couponError, setCouponError] = useState<string | null>(null);

    useEffect(() => {
        getBillingAnalytics()
            .then(data => setAnalytics(data))
            .catch(error => console.error(error));
    }, []);

    useEffect(() => {
        listActiveCoupons()
            .then(coupons => setActiveCoupons(coupons))
            .catch(error => {
                console.error(error);
                setActiveCoupons([]);
                setCouponError(httpErrorToHuman(error));
            });
    }, []);

    if (!analytics || !analytics.orders) return <Spinner size={'large'} centered />;

    const hasProducts = analytics.products?.length ?? 0 >= 1;
    const hasOrders = analytics.orders?.length ?? 0 >= 1;

    const successfulOrders: Order[] = analytics.orders.filter(
        x => x.status === 'processed' && differenceInDays(now, parseISO(x.created_at.toString())) <= history,
    );
    const allOrders: Order[] = analytics.orders.filter(
        x => differenceInDays(now, parseISO(x.created_at.toString())) <= history,
    );
    const successRate: string = ((successfulOrders.length / allOrders.length) * 100).toFixed(1);

    const revenue: string = successfulOrders.reduce((total, order) => total + order.total, 0).toFixed(2);

    const describeCoupon = (coupon: Coupon): string => {
        if (coupon.type === 'percentage' && coupon.percentage) {
            return `${coupon.percentage}% off`;
        }

        if (coupon.type === 'amount' && coupon.value !== undefined && coupon.value !== null) {
            return `${settings.currency.symbol}${coupon.value.toFixed(2)} discount`;
        }

        if (coupon.type === 'duration' && coupon.value) {
            return `${coupon.value} bonus day${coupon.value === 1 ? '' : 's'}`;
        }

        if (coupon.type === 'resource') {
            const metadata = (coupon.metadata ?? {}) as Record<string, unknown>;
            const label =
                typeof metadata.resource_label === 'string'
                    ? metadata.resource_label
                    : typeof metadata.resource === 'string'
                    ? metadata.resource
                    : null;
            const rawQuantity = metadata.quantity;
            const quantity =
                typeof rawQuantity === 'number' ? rawQuantity : typeof rawQuantity === 'string' ? rawQuantity : null;

            if (label && quantity !== null) {
                return `${quantity} ${label}`;
            }

            if (label) {
                return label;
            }

            return 'Resource credit';
        }

        return coupon.name || 'Custom coupon';
    };

    const getUsageText = (coupon: Coupon): string => {
        if (coupon.maxUsages) {
            return `${coupon.usageCount}/${coupon.maxUsages} redemptions`;
        }

        return `${coupon.usageCount} redemptions`;
    };

    const getScheduleText = (coupon: Coupon): string => {
        if (coupon.startsAt && coupon.startsAt.getTime() > now.getTime()) {
            return coupon.expiresAt
                ? `Starts ${format(coupon.startsAt, 'MMM d, yyyy')} · Ends ${format(coupon.expiresAt, 'MMM d, yyyy')}`
                : `Starts ${format(coupon.startsAt, 'MMM d, yyyy')}`;
        }

        if (coupon.expiresAt) {
            return `Expires ${format(coupon.expiresAt, 'MMM d, yyyy')}`;
        }

        return 'No expiration';
    };

    return (
        <div className={'grid lg:grid-cols-5 gap-4'}>
            <SetupStripe />
            <ol className="space-y-4 w-full">
                <Select onChange={e => setHistory(Number(e.currentTarget.value))}>
                    <option value={7}>Last 7 days</option>
                    <option selected value={14}>
                        Last 14 days
                    </option>
                    <option value={30}>Last month</option>
                    <option value={60}>Last 2 months</option>
                    <option value={90}>Last 3 months</option>
                    <option value={180}>Last 6 months</option>
                    <option value={360}>Last year</option>
                </Select>
                <h2 className={'text-theme-secondary mb-4 px-4 text-2xl'}>Suggested Actions</h2>
                <Stepper className={'text-green-500'} icon={faCheck} content={'Enable billing module'} />
                <Stepper
                    className={hasProducts ? 'text-green-500' : 'text-blue-500'}
                    icon={hasProducts ? faCheck : faArrowRight}
                    content={'Add your first product'}
                    link={'/admin/billing/categories'}
                />
                <Stepper
                    className={hasOrders ? 'text-green-500' : hasProducts ? 'text-blue-500' : 'text-theme-muted'}
                    icon={hasOrders ? faCheck : faEllipsis}
                    content={'Secure your first sale'}
                    link={'/admin/billing/orders'}
                />
                <Stepper
                    className={settings.link || settings.paypal ? 'text-green-500' : 'text-blue-500'}
                    icon={settings.link || settings.paypal ? faCheck : faArrowRight}
                    content={'Add PayPal support'}
                    link={'/admin/billing/settings'}
                />
            </ol>
            <div className={'flex flex-col items-center rounded-lg shadow md:flex-row col-span-4'}>
                <div className={'w-full grid grid-cols-3 mb-auto gap-6'}>
                    <ContentBox>
                        <h1 className={'text-2xl font-bold'}>
                            <span className={'text-4xl'}>{successRate}</span>% conversion rate
                        </h1>
                        <p className={'text-theme-muted text-sm mt-2'}>
                            Out of {allOrders.length} orders, {successfulOrders.length} were processed.
                        </p>
                        <SuccessChart data={analytics} history={history} />
                    </ContentBox>
                    <ContentBox className={'col-span-2'}>
                        <h1 className={'text-2xl font-bold'}>
                            {settings.currency.symbol}
                            <span className={'text-4xl'}>{revenue}</span> total revenue
                        </h1>
                        <p className={'text-theme-muted text-sm mt-2'}>
                            Your {successfulOrders.length} successful orders have generated {settings.currency.symbol}
                            {revenue} {settings.currency.code} over the last {history} days.
                        </p>
                        <RevenueChart data={analytics} history={history} />
                    </ContentBox>
                    <ContentBox className={'col-span-3'}>
                        <div className={'flex items-start justify-between gap-4 mb-4'}>
                            <div>
                                <h1 className={'text-2xl font-bold'}>Active coupons</h1>
                                <p className={'text-theme-muted text-sm'}>
                                    Track the incentives currently available to your customers.
                                </p>
                            </div>
                            <Link to={'/admin/billing/coupons'} className={'text-theme-primary text-sm font-semibold'}>
                                Manage
                            </Link>
                        </div>
                        {couponError ? (
                            <p className={'text-red-400 text-sm'}>{couponError}</p>
                        ) : activeCoupons === null ? (
                            <div className={'flex justify-center py-8'}>
                                <Spinner size={'large'} />
                            </div>
                        ) : activeCoupons.length === 0 ? (
                            <p className={'text-theme-muted text-sm'}>No active coupons right now.</p>
                        ) : (
                            <ul className={'divide-y divide-black/20'}>
                                {activeCoupons.map(coupon => (
                                    <li key={coupon.uuid} className={'py-3 flex items-start justify-between gap-4'}>
                                        <div>
                                            <p className={'font-semibold text-theme-primary'}>
                                                {coupon.code}
                                                {coupon.name ? (
                                                    <span className={'ml-2 text-theme-muted text-sm font-normal'}>
                                                        {coupon.name}
                                                    </span>
                                                ) : null}
                                            </p>
                                            <p className={'text-theme-muted text-sm'}>
                                                {describeCoupon(coupon)}
                                                {coupon.term ? ` · ${coupon.term.name}` : ''}
                                            </p>
                                            <p className={'text-theme-muted text-xs mt-1'}>{getScheduleText(coupon)}</p>
                                        </div>
                                        <div className={'text-right text-sm'}>
                                            <p className={'font-semibold'}>{getUsageText(coupon)}</p>
                                            {coupon.perUserLimit ? (
                                                <p className={'text-theme-muted text-xs'}>
                                                    Limit {coupon.perUserLimit} per user
                                                </p>
                                            ) : null}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </ContentBox>
                </div>
            </div>
        </div>
    );
};
