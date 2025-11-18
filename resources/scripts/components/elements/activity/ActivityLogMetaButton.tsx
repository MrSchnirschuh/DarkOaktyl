import { useState } from 'react';
import { ClipboardListIcon } from '@heroicons/react/outline';
import { Dialog } from '@elements/dialog';
import { Button } from '@elements/button/index';

export default ({ meta }: { meta: Record<string, unknown> }) => {
    const [open, setOpen] = useState(false);

    return (
        <div className={'self-center md:px-4'}>
            <Dialog open={open} onClose={() => setOpen(false)} hideCloseIcon title={'Metadata'}>
                <pre
                    className={
                        'overflow-x-scroll whitespace-pre-wrap rounded bg-slate-900 p-2 font-mono text-sm leading-relaxed'
                    }
                >
                    {JSON.stringify(meta, null, 2)}
                </pre>
                <Dialog.Footer>
                    <Button.Text onClick={() => setOpen(false)}>Close</Button.Text>
                </Dialog.Footer>
            </Dialog>
            <button
                aria-describedby={'View additional event metadata'}
                className={
                    'p-2 text-theme-muted transition-colors duration-100 group-hover:text-theme-secondary group-hover:hover:text-theme-primary'
                }
                onClick={() => setOpen(true)}
            >
                <ClipboardListIcon className={'h-5 w-5'} />
            </button>
        </div>
    );
};
