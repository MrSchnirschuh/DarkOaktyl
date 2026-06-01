import { Dispatch, SetStateAction, useContext } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPencil, faTrash } from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from '@/state/hooks';
import Pill from '@/components/elements/Pill';
import { Button } from '@elements/button';
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
import { Context, DomainRoot, DomainRootFilters, getDomainRoots } from '@/api/admin/domainRoots';
import tw from 'twin.macro';
import { VisibleDialog } from './DomainRootsContainer';

interface Props {
    setOpen: Dispatch<SetStateAction<VisibleDialog>>;
    setSelected: Dispatch<SetStateAction<DomainRoot | null>>;
}

const Table = ({ setOpen, setSelected }: Props) => {
    const { data, error, isValidating } = getDomainRoots();
    const { colors } = useStoreState(state => state.theme.data!);
    const { setPage, sort, sortDirection, setSort, setFilters } = useContext(Context);

    const length = data?.items?.length || 0;

    const onSearch = (query: string): Promise<void> =>
        new Promise(resolve => {
            if (query.length < 2) {
                setFilters(null);
            } else {
                setPage(1);
                setFilters({ name: query } as DomainRootFilters);
            }

            resolve();
        });

    return (
        <AdminTable>
            <ContentWrapper onSearch={onSearch}>
                <Pagination data={data} onPageSelect={setPage}>
                    <div css={tw`overflow-x-auto`}>
                        <table css={tw`w-full table-auto`}>
                            <TableHead>
                                <TableHeader
                                    name={'ID'}
                                    direction={sort === 'id' ? (sortDirection ? 1 : 2) : null}
                                    onClick={() => setSort('id')}
                                />
                                <TableHeader
                                    name={'Name'}
                                    direction={sort === 'name' ? (sortDirection ? 1 : 2) : null}
                                    onClick={() => setSort('name')}
                                />
                                <TableHeader
                                    name={'Root Domain'}
                                    direction={sort === 'root_domain' ? (sortDirection ? 1 : 2) : null}
                                    onClick={() => setSort('root_domain')}
                                />
                                <TableHeader
                                    name={'Provider'}
                                    direction={sort === 'provider' ? (sortDirection ? 1 : 2) : null}
                                    onClick={() => setSort('provider')}
                                />
                                <TableHeader
                                    name={'Status'}
                                    direction={sort === 'is_active' ? (sortDirection ? 1 : 2) : null}
                                    onClick={() => setSort('is_active')}
                                />
                                <TableHeader name={'Actions'} />
                            </TableHead>
                            <TableBody>
                                {data !== undefined &&
                                    !error &&
                                    !isValidating &&
                                    length > 0 &&
                                    data.items.map(root => (
                                        <TableRow key={root.id}>
                                            <td css={tw`px-6 text-sm text-theme-secondary whitespace-nowrap`}>
                                                <code css={tw`font-mono bg-neutral-900 rounded py-1 px-2`}>
                                                    {root.id}
                                                </code>
                                            </td>
                                            <td
                                                css={tw`px-6 text-sm text-theme-secondary whitespace-nowrap font-semibold`}
                                                style={{ color: colors.primary }}
                                            >
                                                {root.name}
                                            </td>
                                            <td css={tw`px-6 text-sm text-theme-secondary whitespace-nowrap font-mono`}>
                                                {root.rootDomain}
                                            </td>
                                            <td
                                                css={tw`px-6 text-sm text-theme-secondary whitespace-nowrap capitalize`}
                                            >
                                                {root.provider}
                                            </td>
                                            <td css={tw`px-6 text-sm text-theme-secondary whitespace-nowrap`}>
                                                {root.isActive ? (
                                                    <Pill type={'success'}>Active</Pill>
                                                ) : (
                                                    <Pill type={'danger'}>Disabled</Pill>
                                                )}
                                            </td>
                                            <td className={'px-6 py-4 space-x-3'}>
                                                <Button
                                                    onClick={() => {
                                                        setSelected(root);
                                                        setOpen('edit');
                                                    }}
                                                >
                                                    <FontAwesomeIcon icon={faPencil} className={'text-white'} />
                                                </Button>
                                                <Button.Danger
                                                    onClick={() => {
                                                        setSelected(root);
                                                        setOpen('delete');
                                                    }}
                                                >
                                                    <FontAwesomeIcon icon={faTrash} />
                                                </Button.Danger>
                                            </td>
                                        </TableRow>
                                    ))}
                            </TableBody>
                        </table>
                        {data === undefined || (error && isValidating) ? <Loading /> : length < 1 ? <NoItems /> : null}
                    </div>
                </Pagination>
            </ContentWrapper>
        </AdminTable>
    );
};

const DomainRootsTable = ({ setOpen, setSelected }: Props) => {
    const hooks = useTableHooks<DomainRootFilters>();

    return (
        <Context.Provider value={hooks}>
            <Table setOpen={setOpen} setSelected={setSelected} />
        </Context.Provider>
    );
};

export default DomainRootsTable;
