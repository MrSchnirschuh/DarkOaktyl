import { useContext } from 'react';
import { Link } from 'react-router-dom';
import tw from 'twin.macro';
import { Context, useGetPricingConfigurations } from '@/api/admin/billing/pricing';
import FlashMessageRender from '@/components/FlashMessageRender';
import Spinner from '@elements/Spinner';
import Pagination from '@elements/Pagination';
import Pill from '@elements/Pill';

export default () => {
    const { page: _page, setPage } = useContext(Context);

    const { data: configurations, isValidating } = useGetPricingConfigurations();

    return (
        <>
            <FlashMessageRender byKey={'admin:billing:pricing'} />
            {!configurations || (configurations.items.length === 0 && isValidating) ? (
                <Spinner size={'large'} centered />
            ) : configurations.items.length === 0 ? (
                <p css={tw`text-center text-sm text-theme-muted`}>
                    There are no pricing configurations configured. Create one to get started.
                </p>
            ) : (
                <div>
                    <div className={'overflow-x-auto'}>
                        <table className={'min-w-full'}>
                            <thead>
                                <tr>
                                    <th css={tw`px-6 py-3 bg-neutral-800`}>
                                        <span
                                            css={tw`text-xs leading-4 font-medium text-theme-secondary uppercase tracking-wider`}
                                        >
                                            Name
                                        </span>
                                    </th>
                                    <th css={tw`px-6 py-3 bg-neutral-800`}>
                                        <span
                                            css={tw`text-xs leading-4 font-medium text-theme-secondary uppercase tracking-wider`}
                                        >
                                            Status
                                        </span>
                                    </th>
                                    <th css={tw`px-6 py-3 bg-neutral-800`}>
                                        <span
                                            css={tw`text-xs leading-4 font-medium text-theme-secondary uppercase tracking-wider`}
                                        >
                                            CPU Price
                                        </span>
                                    </th>
                                    <th css={tw`px-6 py-3 bg-neutral-800`}>
                                        <span
                                            css={tw`text-xs leading-4 font-medium text-theme-secondary uppercase tracking-wider`}
                                        >
                                            Memory Price
                                        </span>
                                    </th>
                                    <th css={tw`px-6 py-3 bg-neutral-800`}>
                                        <span
                                            css={tw`text-xs leading-4 font-medium text-theme-secondary uppercase tracking-wider`}
                                        >
                                            Disk Price
                                        </span>
                                    </th>
                                    <th css={tw`px-6 py-3 bg-neutral-800`} />
                                </tr>
                            </thead>
                            <tbody>
                                {configurations.items.map((config, i) => (
                                    <tr
                                        key={config.id}
                                        css={[
                                            tw`hover:bg-neutral-700 cursor-pointer`,
                                            i % 2 === 0 ? tw`bg-neutral-800` : tw`bg-neutral-700`,
                                        ]}
                                    >
                                        <td css={tw`px-6 py-4 whitespace-nowrap`}>
                                            <Link
                                                to={`/admin/billing/pricing/${config.id}`}
                                                css={tw`text-sm leading-5 text-theme-secondary hover:text-theme-primary`}
                                            >
                                                {config.name}
                                            </Link>
                                        </td>
                                        <td css={tw`px-6 py-4 whitespace-nowrap`}>
                                            {config.enabled ? (
                                                <Pill type={'success'} size={'xsmall'}>
                                                    Enabled
                                                </Pill>
                                            ) : (
                                                <Pill type={'danger'} size={'xsmall'}>
                                                    Disabled
                                                </Pill>
                                            )}
                                        </td>
                                        <td css={tw`px-6 py-4 whitespace-nowrap text-sm text-theme-secondary`}>
                                            ${config.cpuPrice.toFixed(4)}
                                        </td>
                                        <td css={tw`px-6 py-4 whitespace-nowrap text-sm text-theme-secondary`}>
                                            ${config.memoryPrice.toFixed(6)} / MB
                                        </td>
                                        <td css={tw`px-6 py-4 whitespace-nowrap text-sm text-theme-secondary`}>
                                            ${config.diskPrice.toFixed(6)} / MB
                                        </td>
                                        <td css={tw`px-6 py-4 whitespace-nowrap text-right text-sm font-medium`}>
                                            <Link
                                                to={`/admin/billing/pricing/${config.id}`}
                                                css={tw`text-primary-400 hover:text-primary-300`}
                                            >
                                                Edit
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {configurations.pagination.total > 1 && (
                        <Pagination data={configurations.pagination} onPageSelect={setPage}>
                            {({ isFirstPage, isLastPage, nextPage, prevPage }) => (
                                <div css={tw`flex items-center justify-center mt-4`}>
                                    <button
                                        disabled={isFirstPage}
                                        onClick={prevPage}
                                        css={tw`px-4 py-2 border border-neutral-600 rounded-l text-sm text-theme-secondary hover:bg-neutral-700 disabled:opacity-50 disabled:cursor-not-allowed`}
                                    >
                                        Previous
                                    </button>
                                    <button
                                        disabled={isLastPage}
                                        onClick={nextPage}
                                        css={tw`px-4 py-2 border border-neutral-600 rounded-r text-sm text-theme-secondary hover:bg-neutral-700 disabled:opacity-50 disabled:cursor-not-allowed`}
                                    >
                                        Next
                                    </button>
                                </div>
                            )}
                        </Pagination>
                    )}
                </div>
            )}
        </>
    );
};
