import { useState, type ChangeEvent } from 'react';
import AdminBox from '@elements/AdminBox';
import Label from '@elements/Label';
import Input from '@elements/Input';
import { Button } from '@elements/button';
import updateColors from '@/api/admin/theme/updateColors';
import { useStoreState, useStoreActions } from '@/state/hooks';
import useFlash from '@/plugins/useFlash';

export default () => {
    const colors = useStoreState(s => s.theme.data!.colors) as Record<string, string>;
    const setTheme = useStoreActions(a => a.theme.setTheme);
    const { addFlash, clearFlashes } = useFlash();

    const [panelLogoLight, setPanelLogoLight] = useState<string>(
        colors['logo_panel_light'] ?? colors['logo_panel'] ?? '',
    );
    const [panelLogoDark, setPanelLogoDark] = useState<string>(colors['logo_panel_dark'] ?? colors['logo_panel'] ?? '');
    const [loginLogoLight, setLoginLogoLight] = useState<string>(
        colors['logo_login_light'] ?? colors['logo_login'] ?? '',
    );
    const [loginLogoDark, setLoginLogoDark] = useState<string>(colors['logo_login_dark'] ?? colors['logo_login'] ?? '');
    const [backgroundLight, setBackgroundLight] = useState<string>(
        colors['background_image_light'] ?? colors['background_image'] ?? '',
    );
    const [backgroundDark, setBackgroundDark] = useState<string>(
        colors['background_image_dark'] ?? colors['background_image'] ?? '',
    );

    const [savingPanelLight, setSavingPanelLight] = useState(false);
    const [savingPanelDark, setSavingPanelDark] = useState(false);
    const [savingLoginLight, setSavingLoginLight] = useState(false);
    const [savingLoginDark, setSavingLoginDark] = useState(false);
    const [savingBackgroundLight, setSavingBackgroundLight] = useState(false);
    const [savingBackgroundDark, setSavingBackgroundDark] = useState(false);

    const save = async (key: string, value: string, setSaving: (v: boolean) => void, successMessage = 'Saved.') => {
        setSaving(true);
        clearFlashes('theme:logos');
        try {
            await updateColors(key, value || '');
            const newColors = { ...(colors as Record<string, string>) };
            newColors[key] = value || '';
            setTheme({ colors: newColors } as any);
            addFlash({ key: 'theme:logos', type: 'success', message: successMessage });
        } catch (e) {
            addFlash({ key: 'theme:logos', type: 'error', message: 'Failed to save.' });
        } finally {
            setSaving(false);
        }
    };

    const handleFile = (
        key: string,
        file: File | null | undefined,
        setSaving: (v: boolean) => void,
        setState: (v: string) => void,
    ) => {
        if (!file) return;
        const reader = new FileReader();
        setSaving(true);
        reader.onload = async e => {
            const dataUrl = e.target?.result as string;
            try {
                await save(key, dataUrl, setSaving, 'Image uploaded.');
                setState(dataUrl);
            } catch (err) {
                // save already handles flashes
            } finally {
                setSaving(false);
            }
        };
        reader.onerror = () => {
            addFlash({ key: 'theme:logos', type: 'error', message: 'Failed to read file.' });
            setSaving(false);
        };
        reader.readAsDataURL(file);
    };

    const createFileHandler =
        (key: string, setSavingState: (value: boolean) => void, setState: (value: string) => void) =>
        (event: ChangeEvent<HTMLInputElement>) => {
            const file = event.target.files ? event.target.files[0] : null;
            handleFile(key, file, setSavingState, setState);
        };

    const FilePicker = ({
        id,
        accept,
        onSelect,
        hint,
    }: {
        id: string;
        accept: string;
        onSelect: (event: ChangeEvent<HTMLInputElement>) => void;
        hint?: string;
    }) => (
        <div className={'flex flex-col items-start gap-1'}>
            <input id={id} type={'file'} accept={accept} className={'sr-only'} onChange={onSelect} />
            <label
                htmlFor={id}
                className={
                    'inline-flex cursor-pointer items-center gap-2 rounded border border-theme-muted bg-theme-surface px-3 py-2 text-sm text-theme-primary transition hover:bg-theme-surface-card'
                }
            >
                <span>Choose File</span>
            </label>
            {hint && <span className={'text-xs text-theme-muted'}>{hint}</span>}
        </div>
    );

    return (
        <AdminBox title={'Logos & Backgrounds'}>
            <div className={'mb-3'}>
                <Label>Panel Logo (small, used in sidebar)</Label>
                <div className={'grid grid-cols-1 md:grid-cols-2 gap-4'}>
                    <div>
                        <Label>Light</Label>
                        <div className={'flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3'}>
                            <div className={'flex-1 min-w-0'}>
                                <Input
                                    value={panelLogoLight}
                                    onChange={e => setPanelLogoLight(e.target.value)}
                                    placeholder={'https://.../logo.png'}
                                    className={'w-full truncate font-mono text-xs'}
                                    title={panelLogoLight || undefined}
                                />
                            </div>
                            <div className={'flex-shrink-0'}>
                                <FilePicker
                                    id={'panel_logo_light_file'}
                                    accept={'image/*'}
                                    onSelect={createFileHandler(
                                        'logo_panel_light',
                                        setSavingPanelLight,
                                        setPanelLogoLight,
                                    )}
                                    hint={'SVG, PNG, JPG, or WEBP'}
                                />
                            </div>
                        </div>
                        {panelLogoLight && (
                            <div className={'mt-2'}>
                                <img src={panelLogoLight} alt={'Panel logo (light) preview'} className={'max-h-12'} />
                            </div>
                        )}
                        <div className={'mt-2'}>
                            <Button
                                onClick={() =>
                                    save(
                                        'logo_panel_light',
                                        panelLogoLight,
                                        setSavingPanelLight,
                                        'Panel logo (light) saved.',
                                    )
                                }
                                disabled={savingPanelLight}
                            >
                                Save
                            </Button>
                        </div>
                    </div>

                    <div>
                        <Label>Dark</Label>
                        <div className={'flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3'}>
                            <div className={'flex-1 min-w-0'}>
                                <Input
                                    value={panelLogoDark}
                                    onChange={e => setPanelLogoDark(e.target.value)}
                                    placeholder={'https://.../logo.png'}
                                    className={'w-full truncate font-mono text-xs'}
                                    title={panelLogoDark || undefined}
                                />
                            </div>
                            <div className={'flex-shrink-0'}>
                                <FilePicker
                                    id={'panel_logo_dark_file'}
                                    accept={'image/*'}
                                    onSelect={createFileHandler(
                                        'logo_panel_dark',
                                        setSavingPanelDark,
                                        setPanelLogoDark,
                                    )}
                                    hint={'SVG, PNG, JPG, or WEBP'}
                                />
                            </div>
                        </div>
                        {panelLogoDark && (
                            <div className={'mt-2'}>
                                <img src={panelLogoDark} alt={'Panel logo (dark) preview'} className={'max-h-12'} />
                            </div>
                        )}
                        <div className={'mt-2'}>
                            <Button
                                onClick={() =>
                                    save(
                                        'logo_panel_dark',
                                        panelLogoDark,
                                        setSavingPanelDark,
                                        'Panel logo (dark) saved.',
                                    )
                                }
                                disabled={savingPanelDark}
                            >
                                Save
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div className={'mt-4 mb-3'}>
                <Label>Login Screen Logo (displayed on login)</Label>
                <div className={'grid grid-cols-1 md:grid-cols-2 gap-4'}>
                    <div>
                        <Label>Light</Label>
                        <div className={'flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3'}>
                            <div className={'flex-1 min-w-0'}>
                                <Input
                                    value={loginLogoLight}
                                    onChange={e => setLoginLogoLight(e.target.value)}
                                    placeholder={'https://.../logo.png'}
                                    className={'w-full truncate font-mono text-xs'}
                                    title={loginLogoLight || undefined}
                                />
                            </div>
                            <div className={'flex-shrink-0'}>
                                <FilePicker
                                    id={'login_logo_light_file'}
                                    accept={'image/*'}
                                    onSelect={createFileHandler(
                                        'logo_login_light',
                                        setSavingLoginLight,
                                        setLoginLogoLight,
                                    )}
                                    hint={'SVG, PNG, JPG, or WEBP'}
                                />
                            </div>
                        </div>
                        {loginLogoLight && (
                            <div className={'mt-2'}>
                                <img
                                    src={loginLogoLight}
                                    alt={'Login logo (light) preview'}
                                    className={'max-h-28 mx-auto'}
                                />
                            </div>
                        )}
                        <div className={'mt-2'}>
                            <Button
                                onClick={() =>
                                    save(
                                        'logo_login_light',
                                        loginLogoLight,
                                        setSavingLoginLight,
                                        'Login logo (light) saved.',
                                    )
                                }
                                disabled={savingLoginLight}
                            >
                                Save
                            </Button>
                        </div>
                    </div>

                    <div>
                        <Label>Dark</Label>
                        <div className={'flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3'}>
                            <div className={'flex-1 min-w-0'}>
                                <Input
                                    value={loginLogoDark}
                                    onChange={e => setLoginLogoDark(e.target.value)}
                                    placeholder={'https://.../logo.png'}
                                    className={'w-full truncate font-mono text-xs'}
                                    title={loginLogoDark || undefined}
                                />
                            </div>
                            <div className={'flex-shrink-0'}>
                                <FilePicker
                                    id={'login_logo_dark_file'}
                                    accept={'image/*'}
                                    onSelect={createFileHandler(
                                        'logo_login_dark',
                                        setSavingLoginDark,
                                        setLoginLogoDark,
                                    )}
                                    hint={'SVG, PNG, JPG, or WEBP'}
                                />
                            </div>
                        </div>
                        {loginLogoDark && (
                            <div className={'mt-2'}>
                                <img
                                    src={loginLogoDark}
                                    alt={'Login logo (dark) preview'}
                                    className={'max-h-28 mx-auto'}
                                />
                            </div>
                        )}
                        <div className={'mt-2'}>
                            <Button
                                onClick={() =>
                                    save(
                                        'logo_login_dark',
                                        loginLogoDark,
                                        setSavingLoginDark,
                                        'Login logo (dark) saved.',
                                    )
                                }
                                disabled={savingLoginDark}
                            >
                                Save
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div className={'mt-4'}>
                <Label>Background Image (used on login / panels as configured)</Label>
                <div className={'grid grid-cols-1 md:grid-cols-2 gap-4'}>
                    <div>
                        <Label>Light</Label>
                        <div className={'flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3'}>
                            <div className={'flex-1 min-w-0'}>
                                <Input
                                    value={backgroundLight}
                                    onChange={e => setBackgroundLight(e.target.value)}
                                    placeholder={'https://.../bg.png'}
                                    className={'w-full truncate font-mono text-xs'}
                                    title={backgroundLight || undefined}
                                />
                            </div>
                            <div className={'flex-shrink-0'}>
                                <FilePicker
                                    id={'background_light_file'}
                                    accept={'image/*'}
                                    onSelect={createFileHandler(
                                        'background_image_light',
                                        setSavingBackgroundLight,
                                        setBackgroundLight,
                                    )}
                                    hint={'SVG, PNG, JPG, or WEBP'}
                                />
                            </div>
                        </div>
                        {backgroundLight && (
                            <div className={'mt-2'}>
                                <img
                                    src={backgroundLight}
                                    alt={'Background (light) preview'}
                                    className={'max-h-28 mx-auto'}
                                />
                            </div>
                        )}
                        <div className={'mt-2'}>
                            <Button
                                onClick={() =>
                                    save(
                                        'background_image_light',
                                        backgroundLight,
                                        setSavingBackgroundLight,
                                        'Background (light) saved.',
                                    )
                                }
                                disabled={savingBackgroundLight}
                            >
                                Save
                            </Button>
                        </div>
                    </div>

                    <div>
                        <Label>Dark</Label>
                        <div className={'flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3'}>
                            <div className={'flex-1 min-w-0'}>
                                <Input
                                    value={backgroundDark}
                                    onChange={e => setBackgroundDark(e.target.value)}
                                    placeholder={'https://.../bg.png'}
                                    className={'w-full truncate font-mono text-xs'}
                                    title={backgroundDark || undefined}
                                />
                            </div>
                            <div className={'flex-shrink-0'}>
                                <FilePicker
                                    id={'background_dark_file'}
                                    accept={'image/*'}
                                    onSelect={createFileHandler(
                                        'background_image_dark',
                                        setSavingBackgroundDark,
                                        setBackgroundDark,
                                    )}
                                    hint={'SVG, PNG, JPG, or WEBP'}
                                />
                            </div>
                        </div>
                        {backgroundDark && (
                            <div className={'mt-2'}>
                                <img
                                    src={backgroundDark}
                                    alt={'Background (dark) preview'}
                                    className={'max-h-28 mx-auto'}
                                />
                            </div>
                        )}
                        <div className={'mt-2'}>
                            <Button
                                onClick={() =>
                                    save(
                                        'background_image_dark',
                                        backgroundDark,
                                        setSavingBackgroundDark,
                                        'Background (dark) saved.',
                                    )
                                }
                                disabled={savingBackgroundDark}
                            >
                                Save
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AdminBox>
    );
};
