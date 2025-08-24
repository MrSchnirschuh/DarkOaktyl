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
            <div className={'w-full flex flex-row items-center mb-8'}>
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                    <h2 className={'text-2xl text-neutral-50 font-header font-medium'}>System Theme</h2>
                    <p
                        className={
                            'hidden lg:block text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden'
                        }
                    >
                        View and update the theme of this interface.
                    </p>
                </div>
                <div className={'flex ml-auto pl-4 items-center'}>
                    <div className={'mr-4 flex items-center'}>
                        <label className={'mr-3 text-sm text-neutral-400'}>Mode</label>
                        <div className={'flex items-center space-x-2'}>
                            <button
                                className={`px-3 py-1 rounded ${
                                    mode === 'light'
                                        ? 'bg-neutral-700 text-white'
                                        : 'bg-transparent text-neutral-400 border border-neutral-700'
                                }`}
                                onClick={() => setMode('light')}
                                type="button"
                            >
                                Light
                            </button>
                            <button
                                className={`px-3 py-1 rounded ${
                                    mode === 'dark'
                                        ? 'bg-neutral-700 text-white'
                                        : 'bg-transparent text-neutral-400 border border-neutral-700'
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
                                className={`px-2 py-1 rounded ${
                                    previewSize === 'small'
                                        ? 'bg-neutral-700 text-white'
                                        : 'bg-transparent text-neutral-400 border border-neutral-700'
                                }`}
                                onClick={() => setPreviewSize('small')}
                            >
                                S
                            </button>
                            <button
                                className={`px-2 py-1 rounded ${
                                    previewSize === 'medium'
                                        ? 'bg-neutral-700 text-white'
                                        : 'bg-transparent text-neutral-400 border border-neutral-700'
                                }`}
                                onClick={() => setPreviewSize('medium')}
                            >
                                M
                            </button>
                            <button
                                className={`px-2 py-1 rounded ${
                                    previewSize === 'large'
                                        ? 'bg-neutral-700 text-white'
                                        : 'bg-transparent text-neutral-400 border border-neutral-700'
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
                        className={'h-10 px-4 py-0 whitespace-nowrap'}
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
                            className={`px-3 py-1 rounded ${
                                activeTab === (t.id as any)
                                    ? 'bg-neutral-700 text-white'
                                    : 'bg-transparent text-neutral-400 border border-neutral-700'
                            }`}
                            type="button"
                        >
                            {t.label}
                        </button>
                    ))}
                </nav>
            </div>

            <div className={'grid md:grid-cols-3 gap-4'}>
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
