import { PaginationDataSet } from '@/api/http';

const TFootPaginated = ({ pagination, span }: { span: number; pagination: PaginationDataSet }) => {
    const start = (pagination.currentPage - 1) * pagination.perPage;
    const end = (pagination.currentPage - 1) * pagination.perPage + pagination.count;

    return (
        <tfoot>
            <tr className={'bg-neutral-800'}>
                <td scope={'col'} colSpan={span} className={'px-4 py-2'}>
                    <p className={'text-sm text-theme-muted'}>
                        Showing{' '}
                        <span className={'font-semibold text-theme-muted'}>
                            {Math.max(start, Math.min(pagination.total, 1))}
                        </span>{' '}
                        to&nbsp;
                        <span className={'font-semibold text-theme-muted'}>{end}</span> of&nbsp;
                        <span className={'font-semibold text-theme-muted'}>{pagination.total}</span> results.
                    </p>
                </td>
            </tr>
        </tfoot>
    );
};

export default TFootPaginated;
