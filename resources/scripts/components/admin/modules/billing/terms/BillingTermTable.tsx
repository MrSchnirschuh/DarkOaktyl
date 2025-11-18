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
import { Context, useGetBillingTerms } from '@/api/admin/billing/billingTerms';
import { type BillingTermFilters } from '@/api/admin/billing/types';
import { ClockIcon } from '@heroicons/react/outline';

const BillingTermTable = () => {
    const { data: terms, error } = useGetBillingTerms();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const theme = useStoreState(state => state.theme.data!);
    const { setPage, setFilters, sort, setSort, sortDirection, setSortDirection } = useContext(Context);

    useEffect(() => {
        if (!error) {
            clearFlashes('admin:billing:terms');
            return;
        }

        clearAndAddHttpError({ key: 'admin:billing:terms', error });
    }, [error, clearFlashes, clearAndAddHttpError]);

    const onSearch = (query: string): Promise<void> => {
        return new Promise(resolve => {
            if (query.trim().length < 2) {
                setFilters(null);
            } else {
                setFilters({ name: query.trim() });
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

    return (
        <>
            <div className={'w-full flex flex-row items-center my-8 px-8'}>
                <ClockIcon className={'w-10 h-10 mr-4 text-theme-secondary'} />
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                    <h2 className={'text-2xl text-theme-primary font-header font-medium'}>Billing Terms</h2>
                    <p
                        className={
                            'hidden lg:block text-base text-theme-muted whitespace-nowrap overflow-ellipsis overflow-hidden'
                        }
                    >
                        Configure runtime tiers, multipliers, and duration factors for billing.
                    </p>
                </div>
                <div className={'flex ml-auto pl-4'}>
                    <Link to={'/admin/billing/terms/new'}>
                        <Button>Add Term</Button>
                    </Link>
                </div>
            </div>
            <AdminTable>
                <ContentWrapper onSearch={onSearch}>
                    <Pagination data={terms} onPageSelect={setPage}>
                        <div css={tw`overflow-x-auto`}>
                            <table css={tw`w-full table-auto`}>
                                <TableHead>
                                    <TableHeader />
                                    <TableHeader
                                        name={'Name'}
                                        direction={sort === 'name' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('name')}
                                    />
                                    <TableHeader
                                        name={'Slug'}
                                        direction={sort === 'slug' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('slug')}
                                    />
                                    <TableHeader
                                        name={'Duration (days)'}
                                        direction={sort === 'duration_days' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('duration_days')}
                                    />
                                    <TableHeader
                                        name={'Multiplier'}
                                        direction={sort === 'multiplier' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('multiplier')}
                                    />
                                    <TableHeader name={'Active'} />
                                    <TableHeader name={'Default'} />
                                    <TableHeader name={'Updated'} />
                                    <TableHeader />
                                </TableHead>
                                <TableBody>
                                    {terms !== undefined &&
                                        terms.items.length > 0 &&
                                        terms.items.map(term => (
                                            <TableRow key={term.uuid}>
                                                <td css={tw`pl-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    <span
                                                        className={
                                                            'px-2 py-0.5 rounded bg-neutral-900 text-theme-secondary font-mono text-xs'
                                                        }
                                                    >
                                                        {term.id}
                                                    </span>
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    <NavLink
                                                        to={`/admin/billing/terms/${term.uuid}`}
                                                        style={{ color: theme.colors.primary }}
                                                        className={'hover:brightness-125 duration-300'}
                                                    >
                                                        {term.name}
                                                    </NavLink>
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    <code className={'font-mono bg-neutral-900 rounded py-1 px-2'}>
                                                        {term.slug ?? 'auto'}
                                                    </code>
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    {term.durationDays}
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    {term.multiplier.toFixed(4)}x
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    <span
                                                        className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                                                            term.isActive
                                                                ? 'bg-green-200 text-green-900'
                                                                : 'bg-red-200 text-red-900'
                                                        }`}
                                                    >
                                                        {term.isActive ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    <span
                                                        className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                                                            term.isDefault
                                                                ? 'bg-primary-200 text-primary-900'
                                                                : 'bg-neutral-700 text-theme-secondary'
                                                        }`}
                                                    >
                                                        {term.isDefault ? 'Default' : '—'}
                                                    </span>
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    {term.updatedAt
                                                        ? Math.abs(differenceInHours(term.updatedAt, new Date())) > 48
                                                            ? format(term.updatedAt, 'MMM do, yyyy h:mma')
                                                            : formatDistanceToNow(term.updatedAt, {
                                                                  addSuffix: true,
                                                              })
                                                        : '—'}
                                                </td>
                                                <td
                                                    css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap text-right`}
                                                >
                                                    <Link
                                                        to={`/admin/billing/terms/${term.uuid}`}
                                                        className={'text-primary-400 hover:text-primary-300'}
                                                    >
                                                        Edit
                                                    </Link>
                                                </td>
                                            </TableRow>
                                        ))}
                                </TableBody>
                            </table>
                            {terms === undefined ? <Loading /> : terms.items.length < 1 ? <NoItems /> : null}
                        </div>
                    </Pagination>
                </ContentWrapper>
            </AdminTable>
        </>
    );
};

export default () => {
    const hooks = useTableHooks<BillingTermFilters>();

    return (
        <Context.Provider value={hooks}>
            <BillingTermTable />
        </Context.Provider>
    );
};
