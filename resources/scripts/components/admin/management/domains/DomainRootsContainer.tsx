import { useState } from 'react';
import AdminContentBlock from '@elements/AdminContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Button } from '@elements/button';
import DomainRootsTable from './DomainRootsTable';
import DomainRootDialog from './DomainRootDialog';
import DeleteDomainRootDialog from './DeleteDomainRootDialog';
import { DomainRoot } from '@/api/admin/domainRoots';

export type VisibleDialog = 'none' | 'create' | 'edit' | 'delete';

const DomainRootsContainer = () => {
    const [open, setOpen] = useState<VisibleDialog>('none');
    const [selected, setSelected] = useState<DomainRoot | null>(null);

    return (
        <AdminContentBlock title={'Domain Roots'}>
            {open === 'create' && <DomainRootDialog setOpen={setOpen} />}
            {open === 'edit' && selected && <DomainRootDialog root={selected} setOpen={setOpen} />}
            {open === 'delete' && selected && <DeleteDomainRootDialog root={selected} setOpen={setOpen} />}

            <FlashMessageRender byKey={'admin:domainRoots'} className={'mb-4'} />

            <div className={'w-full flex flex-row items-center mb-8'}>
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: 0 }}>
                    <h2 className={'text-2xl font-header font-medium text-theme-primary'}>Domain Roots</h2>
                    <p
                        className={
                            'hidden lg:block text-base text-theme-secondary whitespace-nowrap overflow-ellipsis overflow-hidden'
                        }
                    >
                        Manage customer-facing root domains and provider automation settings.
                    </p>
                </div>
                <div className={'flex ml-auto pl-4'}>
                    <Button
                        onClick={() => {
                            setSelected(null);
                            setOpen('create');
                        }}
                    >
                        New Domain Root
                    </Button>
                </div>
            </div>

            <DomainRootsTable setSelected={setSelected} setOpen={setOpen} />
        </AdminContentBlock>
    );
};

export default DomainRootsContainer;
