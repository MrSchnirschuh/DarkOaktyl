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
import { TicketIcon } from '@heroicons/react/outline';

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
                <TicketIcon className={'w-10 h-10 mr-4 text-neutral-300'} />
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                    <h2 className={'text-2xl text-neutral-50 font-header font-medium'}>Coupons</h2>
                    <p
                        className={
                            'hidden lg:block text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden'
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
                                        coupons.items.map(coupon => (
                                            <TableRow key={coupon.uuid}>
                                                <td css={tw`pl-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <span
                                                        className={
                                                            'px-2 py-0.5 rounded bg-neutral-900 text-neutral-200 font-mono text-xs'
                                                        }
                                                    >
                                                        {coupon.id}
                                                    </span>
                                                </td>
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <code className={'font-mono bg-neutral-900 rounded py-1 px-2'}>
                                                        {coupon.code}
                                                    </code>
                                                </td>
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <NavLink
                                                        to={`/admin/billing/coupons/${coupon.uuid}`}
                                                        style={{ color: theme.colors.primary }}
                                                        className={'hover:brightness-125 duration-300'}
                                                    >
                                                        {coupon.name}
                                                    </NavLink>
                                                </td>
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    <span
                                                        className={`px-2 py-0.5 rounded-full text-xs font-medium ${getTypeColor(
                                                            coupon.type,
                                                        )}`}
                                                    >
                                                        {getTypeDisplay(coupon.type)}
                                                    </span>
                                                </td>
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    {coupon.type === 'amount' && coupon.value
                                                        ? `${coupon.value.toFixed(2)}`
                                                        : coupon.type === 'percentage' && coupon.percentage
                                                        ? `${coupon.percentage}%`
                                                        : '—'}
                                                </td>
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    {coupon.usageCount ?? 0}
                                                    {coupon.maxUsages ? ` / ${coupon.maxUsages}` : ''}
                                                </td>
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
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
                                                <td css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap`}>
                                                    {coupon.expiresAt
                                                        ? Math.abs(differenceInHours(coupon.expiresAt, new Date())) > 48
                                                            ? format(coupon.expiresAt, 'MMM do, yyyy')
                                                            : formatDistanceToNow(coupon.expiresAt, {
                                                                  addSuffix: true,
                                                              })
                                                        : '—'}
                                                </td>
                                                <td
                                                    css={tw`px-6 text-sm text-neutral-200 text-left whitespace-nowrap text-right`}
                                                >
                                                    <Link
                                                        to={`/admin/billing/coupons/${coupon.uuid}`}
                                                        className={'text-primary-400 hover:text-primary-300'}
                                                    >
                                                        Edit
                                                    </Link>
                                                </td>
                                            </TableRow>
                                        ))}
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
