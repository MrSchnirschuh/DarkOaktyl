import { useState } from 'react';
// preview mode should be local only; do not touch global store here
import useFlash from '@/plugins/useFlash';
import { Button } from '@elements/button';
import { Dialog } from '@elements/dialog';
import resetTheme from '@/api/admin/theme/resetTheme';
import Preview from '@admin/modules/theme/Preview';
import Presets from '@admin/modules/theme/Presets';
import LogoSettings from '@admin/modules/theme/LogoSettings';
import AdminContentBlock from '@elements/AdminContentBlock';
import ColorSelect from '@admin/modules/theme/ColorSelect';

export default () => {
    const [reload, setReload] = useState<boolean>(false);
    const [visible, setVisible] = useState<boolean>(false);
    const [mode, setMode] = useState<'light' | 'dark'>('dark');
    const [activeTab, setActiveTab] = useState<'text' | 'accent' | 'components' | 'presets'>('accent');
    const [previewSize, setPreviewSize] = useState<'small' | 'medium' | 'large'>('medium');
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const submit = () => {
        clearFlashes('theme:colors');

        resetTheme()
            .then(() => {
                // @ts-expect-error this is fine
                window.location = '/admin/theme';
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'theme:colors', error });
            });
    };

    return (
        <AdminContentBlock showFlashKey={'theme:colors'}>
            <Dialog.Confirm
                title={'Are you sure?'}
                open={visible}
                onClose={() => setVisible(false)}
                onConfirmed={submit}
            >
                Performing this action will immediately wipe all of your custom theming settings. Only do this if you
                wish to return to the stock appearance of Jexactyl. This action cannot be reversed.
            </Dialog.Confirm>
            <div className={'mb-8 flex w-full flex-row items-center'}>
                <div className={'flex flex-shrink flex-col'} style={{ minWidth: '0' }}>
                    <h2 className={'font-header text-2xl font-medium text-neutral-50'}>System Theme</h2>
                    <p
                        className={
                            'hidden overflow-hidden overflow-ellipsis whitespace-nowrap text-neutral-400 lg:block'
                        }
                    >
                        View and update the theme of this interface.
                    </p>
                </div>
                <div className={'ml-auto flex items-center pl-4'}>
                    <div className={'mr-4 flex items-center'}>
                        <label className={'mr-3 text-sm text-neutral-400'}>Mode</label>
                        <div className={'flex items-center space-x-2'}>
                            <button
                                className={`rounded px-3 py-1 ${
                                    mode === 'light'
                                        ? 'bg-neutral-700 text-white'
                                        : 'border border-neutral-700 bg-transparent text-neutral-400'
                                }`}
                                onClick={() => setMode('light')}
                                type="button"
                            >
                                Light
                            </button>
                            <button
                                className={`rounded px-3 py-1 ${
                                    mode === 'dark'
                                        ? 'bg-neutral-700 text-white'
                                        : 'border border-neutral-700 bg-transparent text-neutral-400'
                                }`}
                                onClick={() => setMode('dark')}
                                type="button"
                            >
                                Dark
                            </button>
                        </div>
                    </div>

                    <div className={'mr-4 flex items-center'}>
                        <label className={'mr-3 text-sm text-neutral-400'}>Preview</label>
                        <div className={'flex items-center space-x-2'}>
                            <button
                                className={`rounded px-2 py-1 ${
                                    previewSize === 'small'
                                        ? 'bg-neutral-700 text-white'
                                        : 'border border-neutral-700 bg-transparent text-neutral-400'
                                }`}
                                onClick={() => setPreviewSize('small')}
                            >
                                S
                            </button>
                            <button
                                className={`rounded px-2 py-1 ${
                                    previewSize === 'medium'
                                        ? 'bg-neutral-700 text-white'
                                        : 'border border-neutral-700 bg-transparent text-neutral-400'
                                }`}
                                onClick={() => setPreviewSize('medium')}
                            >
                                M
                            </button>
                            <button
                                className={`rounded px-2 py-1 ${
                                    previewSize === 'large'
                                        ? 'bg-neutral-700 text-white'
                                        : 'border border-neutral-700 bg-transparent text-neutral-400'
                                }`}
                                onClick={() => setPreviewSize('large')}
                            >
                                L
                            </button>
                        </div>
                    </div>

                    <Button
                        type={'button'}
                        size={Button.Sizes.Large}
                        onClick={() => setVisible(true)}
                        className={'h-10 whitespace-nowrap px-4 py-0'}
                    >
                        Reset to Defaults
                    </Button>
                </div>
            </div>
            <div className={'mb-4'}>
                <nav className={'flex space-x-2'} aria-label="Theme Tabs">
                    {(
                        [
                            { id: 'text', label: 'Text' },
                            { id: 'accent', label: 'Accents' },
                            { id: 'components', label: 'Components' },
                            { id: 'presets', label: 'Presets' },
                        ] as { id: string; label: string }[]
                    ).map(t => (
                        <button
                            key={t.id}
                            onClick={() => setActiveTab(t.id as any)}
                            aria-selected={activeTab === (t.id as any)}
                            className={`rounded px-3 py-1 ${
                                activeTab === (t.id as any)
                                    ? 'bg-neutral-700 text-white'
                                    : 'border border-neutral-700 bg-transparent text-neutral-400'
                            }`}
                            type="button"
                        >
                            {t.label}
                        </button>
                    ))}
                </nav>
            </div>

            <div className={'grid gap-4 md:grid-cols-3'}>
                <div className={'md:col-span-1'}>
                    {activeTab !== 'presets' && (
                        <>
                            <ColorSelect setReload={setReload} mode={mode} category={activeTab as any} />
                            {activeTab === 'components' && <LogoSettings />}
                        </>
                    )}
                    {activeTab === 'presets' && <Presets setReload={setReload} />}
                </div>

                <div className={'md:col-span-2'}>
                    <Preview reload={reload} mode={mode} size={previewSize} />
                </div>
            </div>
        </AdminContentBlock>
    );
};
