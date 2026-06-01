import { lazy } from 'react';
import * as Icon from '@heroicons/react/outline';
import { route, type RouteDefinition } from '@/routers/routes/utils';

const AccountApiContainer = lazy(() => import('@/components/dashboard/AccountApiContainer'));
const AccountSSHContainer = lazy(() => import('@/components/dashboard/ssh/AccountSSHContainer'));
const AccountOverviewContainer = lazy(() => import('@/components/dashboard/AccountOverviewContainer'));

const TicketContainer = lazy(() => import('@/components/dashboard/tickets/TicketContainer'));
const ViewTicketContainer = lazy(() => import('@/components/dashboard/tickets/view/ViewTicketContainer'));

const ProductsContainer = lazy(() => import('@/components/billing/ProductsContainer'));
const OrderContainer = lazy(() => import('@/components/billing/order/OrderContainer'));
const OrdersContainer = lazy(() => import('@/components/billing/orders/OrdersContainer'));
const Processing = lazy(() => import('@/components/billing/order/summary/Processing'));
const Success = lazy(() => import('@/components/billing/order/summary/Success'));
const Cancel = lazy(() => import('@/components/billing/order/summary/Cancel'));

const account: RouteDefinition[] = [
    /**
     * Account - General Routes
     */
    route('', AccountOverviewContainer, { name: 'Account', end: true, icon: Icon.UserIcon }),
    route('api', AccountApiContainer, { name: 'API Credentials', icon: Icon.CodeIcon }),
    route('ssh', AccountSSHContainer, { name: 'SSH Keys', icon: Icon.TerminalIcon }),

    /**
     * Account - Ticket Routes
     */
    route('tickets', TicketContainer, {
        name: 'Tickets',
        icon: Icon.TicketIcon,
        condition: flags => flags.tickets.enabled,
    }),
    route('tickets/:id', ViewTicketContainer, { condition: flags => flags.tickets.enabled }),

    /**
     * Account - Billing Routes
     */
    route('billing/order', ProductsContainer, {
        name: 'Billing',
        icon: Icon.CashIcon,
        condition: flags => flags.billing.enabled,
    }),
    route('billing/order/:id', OrderContainer),
    route('billing/orders', OrdersContainer, {
        name: 'Orders',
        icon: Icon.ClipboardListIcon,
        condition: flags => flags.billing.enabled,
    }),
    route('billing/processing', Processing),
    route('billing/success', Success),
    route('billing/cancel', Cancel),
];

export default account;
