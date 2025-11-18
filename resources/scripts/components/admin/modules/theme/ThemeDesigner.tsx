import classNames from 'classnames';
import { useEffect, useState, type ChangeEvent } from 'react';
import { useStoreState } from '@/state/hooks';
import type { ThemeDesignerGroup, ThemeMode, ThemePaletteResponse } from '@/api/admin/theme/getPalette';
import { hexToHslCss, hexToRgbCss, normalizeColorHex } from '@/helpers/colorContrast';

type ColorFormat = 'hex' | 'rgb' | 'hsl';

const COLOR_FORMAT_OPTIONS: Array<{ label: string; value: ColorFormat }> = [
    { label: 'HEX', value: 'hex' },
    { label: 'RGB', value: 'rgb' },
    { label: 'HSL', value: 'hsl' },
];

const normalizeInputColor = (value: string): string | null => {
    const normalized = normalizeColorHex(value);
    return normalized ? normalized.toUpperCase() : null;
};

interface ColorEditorProps {
    token: ThemeDesignerGroup['keys'][number];
    modes: ThemePaletteResponse['modes'];
    defaults: ThemePaletteResponse['defaults'];
    overrides: ThemePaletteResponse['overrides'];
    onColorChange: (mode: ThemeMode, token: string, value: string) => void;
    onResetColor: (mode: ThemeMode, token: string) => void;
    format: ColorFormat;
}

const ColorEditor = ({ token, modes, defaults, overrides, onColorChange, onResetColor, format }: ColorEditorProps) => {
    const [drafts, setDrafts] = useState<Partial<Record<ThemeMode, string>>>(() => ({}));
    const [draftDirty, setDraftDirty] = useState<Record<ThemeMode, boolean>>({ dark: false, light: false });

    const formatHexForDisplay = (hex: string): string => {
        switch (format) {
            case 'rgb':
                return hexToRgbCss(hex) ?? hex.toUpperCase();
            case 'hsl':
                return hexToHslCss(hex) ?? hex.toUpperCase();
            default:
                return hex.toUpperCase();
        }
    };

    const theme = useStoreState(s => s.theme.data);

    const darkRaw =
        modes.dark?.[token.key] ?? theme?.colors?.[`${token.key}_dark`] ?? theme?.colors?.[token.key] ?? '#000000';
    const lightRaw =
        modes.light?.[token.key] ?? theme?.colors?.[`${token.key}_light`] ?? theme?.colors?.[token.key] ?? '#000000';

    const resolveHexValue = (mode: ThemeMode): string => {
        const source = mode === 'dark' ? darkRaw : lightRaw;
        return normalizeInputColor(source) ?? '#000000';
    };

    useEffect(() => {
        setDrafts(prev => {
            let changed = false;
            const next: Partial<Record<ThemeMode, string>> = { ...prev };
            (['dark', 'light'] as ThemeMode[]).forEach(currentMode => {
                if (draftDirty[currentMode]) {
                    return;
                }

                const formatted = formatHexForDisplay(resolveHexValue(currentMode));
                if (next[currentMode] !== formatted) {
                    next[currentMode] = formatted;
                    changed = true;
                }
            });

            return changed ? next : prev;
        });
    }, [format, darkRaw, lightRaw, draftDirty.dark, draftDirty.light]);

    const renderInput = (mode: ThemeMode) => {
        const currentHex = resolveHexValue(mode);
        const colorInputValue = currentHex.toLowerCase();
        const storedDraft = drafts[mode];
        const displayValue = storedDraft !== undefined ? storedDraft : formatHexForDisplay(currentHex);
        const isOverridden = overrides[mode]?.[token.key] ?? false;
        const infoDefaults: Record<string, string> = {
            danger: '#dc2626',
            info: '#f59e0b',
            warning: '#f97316',
            experimental: '#facc15',
            success: theme?.colors?.primary ?? '#16a34a',
        };

        const fallbackRaw =
            defaults[mode]?.[token.key] ?? theme?.colors?.[token.key] ?? infoDefaults[token.key] ?? '#000000';
        const fallbackHex = normalizeInputColor(fallbackRaw);
        const fallbackDisplay = fallbackHex ? formatHexForDisplay(fallbackHex) : fallbackRaw;

        const parseDraftToHex = (input: string | undefined): string | null => {
            if (!input) return null;
            const trimmed = input.trim();
            if (!trimmed) return null;

            if (format === 'hex' && !trimmed.startsWith('#')) {
                return normalizeInputColor(`#${trimmed}`);
            }

            return normalizeInputColor(trimmed);
        };

        const handleColorPickerChange = (event: ChangeEvent<HTMLInputElement>) => {
            const next = normalizeInputColor(event.target.value.trim());
            if (!next) {
                return;
            }

            onColorChange(mode, token.key, next);
            setDrafts(prev => ({ ...prev, [mode]: formatHexForDisplay(next) }));
            setDraftDirty(prev => ({ ...prev, [mode]: false }));
        };

        const handleTextChange = (event: ChangeEvent<HTMLInputElement>) => {
            const nextInput = event.target.value;
            setDrafts(prev => ({ ...prev, [mode]: nextInput }));
            setDraftDirty(prev => ({ ...prev, [mode]: true }));

            const normalized = parseDraftToHex(nextInput);
            if (normalized) {
                onColorChange(mode, token.key, normalized);
            }
        };

        const handleTextBlur = () => {
            const normalized = parseDraftToHex(drafts[mode]);

            if (normalized) {
                onColorChange(mode, token.key, normalized);
                setDrafts(prev => ({ ...prev, [mode]: formatHexForDisplay(normalized) }));
                setDraftDirty(prev => ({ ...prev, [mode]: false }));
                return;
            }

            setDraftDirty(prev => ({ ...prev, [mode]: false }));
            setDrafts(prev => ({ ...prev, [mode]: formatHexForDisplay(resolveHexValue(mode)) }));
        };

        return (
            <div className={'space-y-2'} key={`${token.key}-${mode}`}>
                <div
                    className={'flex items-center justify-between text-xs uppercase tracking-wide text-theme-secondary'}
                >
                    <span>{mode === 'dark' ? 'Dark' : 'Light'} mode</span>
                    <button
                        type={'button'}
                        onClick={() => onResetColor(mode, token.key)}
                        className={classNames(
                            'transition-colors underline',
                            isOverridden ? 'text-theme-accent' : 'text-theme-muted',
                        )}
                        disabled={!isOverridden}
                    >
                        Reset
                    </button>
                </div>

                <div
                    className={
                        'flex items-center space-x-3 rounded border border-theme-muted bg-theme-surface px-3 py-2'
                    }
                >
                    <input
                        type={'color'}
                        value={colorInputValue}
                        onChange={handleColorPickerChange}
                        className={'h-8 w-12 cursor-pointer rounded border-0 bg-transparent p-0'}
                    />
                    <input
                        type={'text'}
                        value={displayValue}
                        onChange={handleTextChange}
                        onBlur={handleTextBlur}
                        className={'w-full bg-transparent text-sm outline-none text-theme-primary'}
                        spellCheck={false}
                    />
                </div>

                <div className={'text-xs text-theme-muted'}>
                    Default: <span className={'font-mono'}>{fallbackDisplay}</span>
                </div>
            </div>
        );
    };

    return (
        <div
            className={'rounded-lg border border-theme-muted bg-theme-surface p-4 shadow-sm'}
            style={{ backgroundColor: 'rgb(var(--theme-surface-card-rgb, 30 41 59) / 0.6)' }}
        >
            <div className={'mb-3'}>
                <div className={'text-sm font-semibold text-theme-primary'}>{token.label}</div>
                <div className={'text-xs text-theme-muted'}>{token.description}</div>
            </div>

            <div className={'grid gap-4'}>{(['dark', 'light'] as ThemeMode[]).map(renderInput)}</div>
        </div>
    );
};

interface Props {
    groups: ThemeDesignerGroup[];
    modes: ThemePaletteResponse['modes'];
    defaults: ThemePaletteResponse['defaults'];
    overrides: ThemePaletteResponse['overrides'];
    activeGroup: string;
    onGroupChange: (group: string) => void;
    onColorChange: (mode: ThemeMode, token: string, value: string) => void;
    onResetColor: (mode: ThemeMode, token: string) => void;
}

const ThemeDesigner = ({
    groups,
    modes,
    defaults,
    overrides,
    activeGroup,
    onGroupChange,
    onColorChange,
    onResetColor,
}: Props) => {
    const [colorFormat, setColorFormat] = useState<ColorFormat>('hex');
    const effectiveGroups = (() => {
        const copy = [...groups];
        if (!copy.find(g => g.id === 'information')) {
            copy.push({
                id: 'information',
                label: 'Information',
                description: 'Alert and badge colours: danger, info, warning, experimental and success.',
                keys: [
                    { key: 'danger', label: 'Danger', description: 'Error / destructive actions' },
                    { key: 'info', label: 'Info', description: 'Informational / neutral notices' },
                    { key: 'warning', label: 'Warning', description: 'Warnings / cautions' },
                    { key: 'experimental', label: 'Experimental', description: 'Experimental / feature flags' },
                    { key: 'success', label: 'Success', description: 'Success / positive states' },
                ],
            } as any);
        }

        return copy;
    })();

    const active = effectiveGroups.find(group => group.id === activeGroup) ?? effectiveGroups[0] ?? null;

    if (!active) {
        return null;
    }

    return (
        <div className={'space-y-6'}>
            <div className={'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'}>
                <div className={'flex flex-wrap items-center gap-3'}>
                    {effectiveGroups.map(group => (
                        <button
                            key={group.id}
                            type={'button'}
                            onClick={() => onGroupChange(group.id)}
                            className={classNames(
                                'rounded px-3 py-1 text-sm border border-theme-muted transition-colors',
                                active.id === group.id
                                    ? 'bg-theme-surface text-theme-primary'
                                    : 'bg-transparent text-theme-muted',
                            )}
                            style={active.id === group.id ? undefined : { color: 'var(--theme-text-muted, #6b7280)' }}
                        >
                            {group.label}
                        </button>
                    ))}
                </div>

                <div className={'flex items-center gap-3'}>
                    <span className={'text-xs uppercase tracking-wide text-theme-secondary'}>Format</span>
                    <div className={'flex overflow-hidden rounded border border-theme-muted'}>
                        {COLOR_FORMAT_OPTIONS.map(option => (
                            <button
                                key={option.value}
                                type={'button'}
                                onClick={() => setColorFormat(option.value)}
                                className={classNames(
                                    'px-3 py-1 text-xs uppercase transition-colors',
                                    colorFormat === option.value
                                        ? 'bg-theme-surface text-theme-primary'
                                        : 'bg-transparent text-theme-muted hover:text-theme-secondary',
                                )}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            <div className={'space-y-4'}>
                <div>
                    <div className={'text-lg font-medium text-theme-primary'}>{active.label}</div>
                    <div className={'text-sm text-theme-secondary'}>{active.description}</div>
                </div>

                <div className={'grid gap-4 sm:grid-cols-2'}>
                    {active.keys.map(token => (
                        <ColorEditor
                            key={token.key}
                            token={token}
                            modes={modes}
                            defaults={defaults}
                            overrides={overrides}
                            onColorChange={onColorChange}
                            onResetColor={onResetColor}
                            format={colorFormat}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
};

export default ThemeDesigner;
