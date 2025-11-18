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
import { Context, useGetResourcePrices } from '@/api/admin/billing/resourcePrices';
import { type ResourcePriceFilters } from '@/api/admin/billing/types';
import { TagIcon } from '@heroicons/react/outline';

const ResourcePriceTable = () => {
    const { data: resources, error } = useGetResourcePrices();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const theme = useStoreState(state => state.theme.data!);
    const { setPage, setFilters, sort, setSort, sortDirection, setSortDirection } = useContext(Context);

    useEffect(() => {
        if (!error) {
            clearFlashes('admin:billing:resources');
            return;
        }

        clearAndAddHttpError({ key: 'admin:billing:resources', error });
    }, [error, clearFlashes, clearAndAddHttpError]);

    const onSearch = (query: string): Promise<void> => {
        return new Promise(resolve => {
            if (query.trim().length < 2) {
                setFilters(null);
            } else {
                setFilters({ resource: query.trim() });
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
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                    <h2 className={'text-2xl text-theme-primary font-header font-medium'}>Resource Pricing</h2>
                    <p
                        className={
                            'hidden lg:block text-base text-theme-muted whitespace-nowrap overflow-ellipsis overflow-hidden'
                        }
                    >
                        Manage the core resource pricing model for CPU, memory, disk, and more.
                    </p>
                </div>
                <div className={'flex ml-auto pl-4'}>
                    <Link to={'/admin/billing/pricing/new'}>
                        <Button>Add Resource</Button>
                    </Link>
                </div>
            </div>
            <AdminTable>
                <ContentWrapper onSearch={onSearch}>
                    <Pagination data={resources} onPageSelect={setPage}>
                        <div css={tw`overflow-x-auto`}>
                            <table css={tw`w-full table-auto`}>
                                <TableHead>
                                    <TableHeader />
                                    <TableHeader
                                        name={'Resource'}
                                        direction={sort === 'resource' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('resource')}
                                    />
                                    <TableHeader
                                        name={'Display Name'}
                                        direction={sort === 'display_name' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('display_name')}
                                    />
                                    <TableHeader name={'Base Qty'} />
                                    <TableHeader
                                        name={'Price'}
                                        direction={sort === 'price' ? (sortDirection ? 1 : 2) : null}
                                        onClick={() => toggleSort('price')}
                                    />
                                    <TableHeader name={'Unit'} />
                                    <TableHeader name={'Visible'} />
                                    <TableHeader name={'Updated'} />
                                    <TableHeader />
                                </TableHead>
                                <TableBody>
                                    {resources !== undefined &&
                                        resources.items.length > 0 &&
                                        resources.items.map(resource => (
                                            <TableRow key={resource.uuid}>
                                                <td css={tw`pl-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    <TagIcon className={'w-5 h-5'} />
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    <code className={'font-mono bg-neutral-900 rounded py-1 px-2'}>
                                                        {resource.resource}
                                                    </code>
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    <NavLink
                                                        to={`/admin/billing/pricing/${resource.uuid}`}
                                                        style={{ color: theme.colors.primary }}
                                                        className={'hover:brightness-125 duration-300'}
                                                    >
                                                        {resource.displayName}
                                                    </NavLink>
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    {resource.baseQuantity}
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    {resource.price.toFixed(4)} {resource.currency}
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    {resource.unit ?? '—'}
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    <span
                                                        className={`px-2 py-0.5 rounded-full text-xs font-medium ${
                                                            resource.isVisible
                                                                ? 'bg-green-200 text-green-900'
                                                                : 'bg-red-200 text-red-900'
                                                        }`}
                                                    >
                                                        {resource.isVisible ? 'Visible' : 'Hidden'}
                                                    </span>
                                                </td>
                                                <td css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap`}>
                                                    {resource.updatedAt
                                                        ? Math.abs(differenceInHours(resource.updatedAt, new Date())) >
                                                          48
                                                            ? format(resource.updatedAt, 'MMM do, yyyy h:mma')
                                                            : formatDistanceToNow(resource.updatedAt, {
                                                                  addSuffix: true,
                                                              })
                                                        : '—'}
                                                </td>
                                                <td
                                                    css={tw`px-6 text-sm text-theme-secondary text-left whitespace-nowrap text-right`}
                                                >
                                                    <Link
                                                        to={`/admin/billing/pricing/${resource.uuid}`}
                                                        className={'text-primary-400 hover:text-primary-300'}
                                                    >
                                                        Edit
                                                    </Link>
                                                </td>
                                            </TableRow>
                                        ))}
                                </TableBody>
                            </table>
                            {resources === undefined ? <Loading /> : resources.items.length < 1 ? <NoItems /> : null}
                        </div>
                    </Pagination>
                </ContentWrapper>
            </AdminTable>
        </>
    );
};

export default () => {
    const hooks = useTableHooks<ResourcePriceFilters>();

    return (
        <Context.Provider value={hooks}>
            <ResourcePriceTable />
        </Context.Provider>
    );
};
