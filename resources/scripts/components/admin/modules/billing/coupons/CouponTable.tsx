import { useContext, useEffect } from 'react';
import { Link, NavLink } from 'react-router-dom';
import tw from 'twin.macro';
import { differenceInHours, format, formatDistanceToNow } from 'date-fns';
import AdminTable, {
    ContentWrapper,
    Loading,
    NoItems,
    Pagination,
    TableBody,
    TableHead,
    TableHeader,
    TableRow,
    useTableHooks,
} from '@elements/AdminTable';
import { Button } from '@elements/button';
import { useStoreState } from '@/state/hooks';
import useFlash from '@/plugins/useFlash';
import { Context, useGetCoupons } from '@/api/admin/billing/coupons';
import { type CouponFilters } from '@/api/admin/billing/types';
import type { Coupon } from '@/api/definitions/admin';
import { TicketIcon } from '@heroicons/react/outline';

interface ResourceDetails {
    resource: string | null;
    label: string | null;
    quantity: number | null;
}

const formatQuantity = (quantity: number | null): string => {
    if (quantity === null || quantity === undefined) {
        return '—';
    }

    return Number.isInteger(quantity) ? quantity.toString() : quantity.toFixed(2);
};

const getResourceDetails = (coupon: Coupon): ResourceDetails | null => {
    if (coupon.type !== 'resource') {
        return null;
    }

    const metadata = (coupon.metadata ?? null) as Record<string, unknown> | null;

    const resource =
        metadata && typeof metadata.resource === 'string' && metadata.resource.trim().length > 0
            ? (metadata.resource as string)
            : null;

    const label =
        metadata && typeof metadata.resource_label === 'string' && metadata.resource_label.trim().length > 0
            ? (metadata.resource_label as string)
            : resource;

    let quantity: number | null = null;

    if (typeof coupon.value === 'number') {
        quantity = coupon.value;
    } else if (metadata) {
        if (typeof metadata.quantity === 'number') {
            quantity = metadata.quantity;
        } else if (
            typeof metadata.quantity === 'string' &&
            metadata.quantity.trim() !== '' &&
            !isNaN(Number(metadata.quantity))
        ) {
            quantity = Number(metadata.quantity);
        }
    }

    if (!resource && !label && quantity === null) {
        return null;
    }

    return {
        resource,
        label,
        quantity,
    };
};

const formatCouponValue = (coupon: Coupon, resourceDetails: ResourceDetails | null): string => {
    if (coupon.type === 'amount' && typeof coupon.value === 'number') {
        return coupon.value.toFixed(2);
    }

    if (coupon.type === 'percentage' && typeof coupon.percentage === 'number') {
        return `${coupon.percentage}%`;
    }

    if (coupon.type === 'duration' && typeof coupon.value === 'number') {
        const value = Number.isInteger(coupon.value) ? coupon.value : coupon.value.toFixed(2);
        return `${value} days`;
    }

    if (coupon.type === 'resource' && resourceDetails) {
        const label = resourceDetails.label ?? resourceDetails.resource ?? 'resource';

        if (resourceDetails.quantity !== null) {
            return `${formatQuantity(resourceDetails.quantity)} × ${label}`;
        }

        return `Free ${label}`;
    }

    return '—';
};

const CouponTable = () => {
    const { data: coupons, error } = useGetCoupons();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const theme = useStoreState(state => state.theme.data!);
    const { setPage, setFilters, sort, setSort, sortDirection, setSortDirection } = useContext(Context);

    useEffect(() => {
        if (!error) {
            clearFlashes('admin:billing:coupons');
            return;
        }

        clearAndAddHttpError({ key: 'admin:billing:coupons', error });
    }, [error, clearFlashes, clearAndAddHttpError]);

    const onSearch = (query: string): Promise<void> => {
        return new Promise(resolve => {
            if (query.trim().length < 2) {
                setFilters(null);
            } else {
                setFilters({ code: query.trim() });
            }
            resolve();
        });
    };

    const toggleSort = (column: string) => {
        if (sort === column) {
            setSortDirection(prev => !prev);
        } else {
            setSort(column);
            setSortDirection(false);
        }
    };

    const getTypeDisplay = (type: string) => {
        switch (type) {
            case 'amount':
                return 'Fixed Amount';
            case 'percentage':
                return 'Percentage';
            case 'resource':
                return 'Resource Credit';
            case 'duration':
                return 'Free Time';
            default:
                return type;
        }
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'amount':
                return 'bg-green-200 text-green-900';
            case 'percentage':
                return 'bg-blue-200 text-blue-900';
            case 'resource':
                return 'bg-purple-200 text-purple-900';
            case 'duration':
                return 'bg-yellow-200 text-yellow-900';
            default:
                return 'bg-gray-200 text-gray-900';
        }
    };

    return (
        <>
            <div className={'w-full flex flex-row items-center my-8 px-8'}>
                <TicketIcon className={'w-10 h-10 mr-4 text-theme-secondary'} />
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                    <h2 className={'text-2xl text-theme-primary font-header font-medium'}>Coupons</h2>
                    <p
                        className={
                            'hidden lg:block text-base text-theme-muted whitespace-nowrap overflow-ellipsis overflow-hidden'
                        }
                    >
                        Manage discount codes, free resources, and promotional offers for customers.
                    </p>
                </div>
                <div className={'flex ml-auto pl-4'}>
                    <Link to={'/admin/billing/coupons/new'}>
                        <Button>Add Coupon</Button>
                    </Link>
                </div>
            </div>
            <AdminTable>
                <ContentWrapper onSearch={onSearch}>
                    <Pagination data={coupons} onPageSelect={setPage}>
                        <div css={tw`overflow-x-auto`}>
                            <table css={tw`w-full table-auto`}>
                                <TableHead>
                                    <TableHeader />
                                    <TableHeader
                                        name={'Code'}
                                        direction={sort === 'code' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('code')}
                                    />
                                    <TableHeader
                                        name={'Name'}
                                        direction={sort === 'name' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('name')}
                                    />
                                    <TableHeader
                                        name={'Type'}
                                        direction={sort === 'type' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('type')}
                                    />
                                    <TableHeader name={'Value'} />
                                    <TableHeader name={'Usage'} />
                                    <TableHeader name={'Active'} />
                                    <TableHeader name={'Expires'} />
                                    <TableHeader />
                                </TableHead>
                                <TableBody>
                                    {coupons !== undefined &&
                                        coupons.items.length > 0 &&
                                        coupons.items.map(coupon => {
                                            const resourceDetails = getResourceDetails(coupon);

                                            return (
                                                <TableRow key={coupon.uuid}>
                                                    <td
                                                        css={tw`pl-6 text-sm text-theme-secondary text-left whitespace-nowrap`}
                                                    >
                                                        <span
                                                            className={
                                                                'px-2 py-0.5 rounded bg-neutral-900 text-theme-secondary font-mono text-xs'
                                                            }
                                                        >
                                                            {coupon.id}
                                                        </span>
                                                    </td>
                                                    <td
                                                        css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}
                                                    >
                                                        <code className={'font-mono bg-neutral-900 rounded py-1 px-2'}>
                                                            {coupon.code}
                                                        </code>
                                                    </td>
                                                    <td
                                                        css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}
                                                    >
                                                        <NavLink
                                                            to={`/admin/billing/coupons/${coupon.uuid}`}
                                                            style={{ color: theme.colors.primary }}
                                                            className={'hover:brightness-125 duration-300'}
                                                        >
                                                            {coupon.name}
                                                        </NavLink>
                                                    </td>
                                                    <td
                                                        css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}
                                                    >
                                                        <span
                                                            className={`px-2 py-0.5 rounded-full text-xs font-medium ${getTypeColor(
                                                                coupon.type,
                                                            )}`}
                                                        >
                                                            {getTypeDisplay(coupon.type)}
                                                        </span>
                                                        {resourceDetails && (
                                                            <div className={'text-xs text-theme-muted mt-1'}>
                                                                {resourceDetails.label ??
                                                                    resourceDetails.resource ??
                                                                    'Resource'}
                                                                {resourceDetails.quantity !== null
                                                                    ? ` • Free ${formatQuantity(
                                                                          resourceDetails.quantity,
                                                                      )}`
                                                                    : ''}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td
                                                        css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}
                                                    >
                                                        {formatCouponValue(coupon, resourceDetails)}
                                                    </td>
                                                    <td
                                                        css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}
                                                    >
                                                        {coupon.usageCount ?? 0}
                                                        {coupon.maxUsages ? ` / ${coupon.maxUsages}` : ''}
                                                    </td>
                                                    <td
                                                        css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}
                                                    >
                                                        <span
                                                            className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                                                                coupon.isActive
                                                                    ? 'bg-green-200 text-green-900'
                                                                    : 'bg-red-200 text-red-900'
                                                            }`}
                                                        >
                                                            {coupon.isActive ? 'Active' : 'Inactive'}
                                                        </span>
                                                    </td>
                                                    <td
                                                        css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}
                                                    >
                                                        {coupon.expiresAt
                                                            ? Math.abs(
                                                                  differenceInHours(coupon.expiresAt, new Date()),
                                                              ) > 48
                                                                ? format(coupon.expiresAt, 'MMM do, yyyy')
                                                                : formatDistanceToNow(coupon.expiresAt, {
                                                                      addSuffix: true,
                                                                  })
                                                            : '—'}
                                                    </td>
                                                    <td
                                                        css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap text-right`}
                                                    >
                                                        <Link
                                                            to={`/admin/billing/coupons/${coupon.uuid}`}
                                                            className={'text-primary-400 hover:text-primary-300'}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </td>
                                                </TableRow>
                                            );
                                        })}
                                </TableBody>
                            </table>
                            {coupons === undefined ? <Loading /> : coupons.items.length < 1 ? <NoItems /> : null}
                        </div>
                    </Pagination>
                </ContentWrapper>
            </AdminTable>
        </>
    );
};

export default () => {
    const hooks = useTableHooks<CouponFilters>();

    return (
        <Context.Provider value={hooks}>
            <CouponTable />
        </Context.Provider>
    );
};
