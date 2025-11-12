import classNames from 'classnames';
import type { ChangeEvent } from 'react';
import type { ThemeDesignerGroup, ThemeMode, ThemePaletteResponse } from '@/api/admin/theme/getPalette';

const normalizeHex = (value: string): string => {
    const hex = value.trim();
    if (/^#([0-9a-f]{6})$/i.test(hex)) {
        return `#${hex.slice(1).toUpperCase()}`;
    }

    if (/^#([0-9a-f]{3})$/i.test(hex)) {
        const [, short] = /^#([0-9a-f]{3})$/i.exec(hex) ?? [];
        if (short) {
            const expanded = `${short[0]}${short[0]}${short[1]}${short[1]}${short[2]}${short[2]}`.toUpperCase();
            return `#${expanded}`;
        }
    }

    return '#000000';
};

const isValidHex = (value: string): boolean => /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value.trim());

interface ColorEditorProps {
    token: ThemeDesignerGroup['keys'][number];
    modes: ThemePaletteResponse['modes'];
    defaults: ThemePaletteResponse['defaults'];
    overrides: ThemePaletteResponse['overrides'];
    onColorChange: (mode: ThemeMode, token: string, value: string) => void;
    onResetColor: (mode: ThemeMode, token: string) => void;
}

const ColorEditor = ({ token, modes, defaults, overrides, onColorChange, onResetColor }: ColorEditorProps) => {
    const renderInput = (mode: ThemeMode) => {
        const value = modes[mode][token.key] ?? '#000000';
        const isOverridden = overrides[mode]?.[token.key] ?? false;
        const fallback = defaults[mode]?.[token.key] ?? '#000000';

        const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
            const next = event.target.value.trim();
            if (!isValidHex(next)) {
                return;
            }

            onColorChange(mode, token.key, normalizeHex(next));
        };

        return (
            <div className={'space-y-2'} key={`${token.key}-${mode}`}>
                <div className={'flex items-center justify-between text-xs uppercase tracking-wide text-neutral-400'}>
                    <span>{mode === 'dark' ? 'Dark' : 'Light'} mode</span>
                    <button
                        type={'button'}
                        onClick={() => onResetColor(mode, token.key)}
                        className={classNames(
                            'transition-colors underline',
                            isOverridden ? 'text-accent-400' : 'text-neutral-600',
                        )}
                        disabled={!isOverridden}
                    >
                        Reset
                    </button>
                </div>

                <div
                    className={'flex items-center space-x-3 rounded border border-neutral-700 bg-neutral-800 px-3 py-2'}
                >
                    <input
                        type={'color'}
                        value={value}
                        onChange={handleChange}
                        className={'h-8 w-12 cursor-pointer rounded border-0 bg-transparent p-0'}
                    />
                    <input
                        type={'text'}
                        value={value}
                        onChange={handleChange}
                        className={'w-full bg-transparent text-sm text-neutral-100 outline-none'}
                        spellCheck={false}
                    />
                </div>

                <div className={'text-xs text-neutral-500'}>
                    Default: <span className={'font-mono'}>{fallback}</span>
                </div>
            </div>
        );
    };

    return (
        <div className={'rounded-lg border border-neutral-700 bg-neutral-900/60 p-4 shadow-sm'}>
            <div className={'mb-3'}>
                <div className={'text-sm font-semibold text-neutral-100'}>{token.label}</div>
                <div className={'text-xs text-neutral-500'}>{token.description}</div>
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
    const active = groups.find(group => group.id === activeGroup) ?? groups[0] ?? null;

    if (!active) {
        return null;
    }

    return (
        <div className={'space-y-6'}>
            <div className={'flex flex-wrap items-center gap-3'}>
                {groups.map(group => (
                    <button
                        key={group.id}
                        type={'button'}
                        onClick={() => onGroupChange(group.id)}
                        className={classNames(
                            'rounded px-3 py-1 text-sm border border-neutral-700 transition-colors',
                            active.id === group.id ? 'bg-neutral-700 text-white' : 'bg-transparent text-neutral-300',
                        )}
                    >
                        {group.label}
                    </button>
                ))}
            </div>

            <div className={'space-y-4'}>
                <div>
                    <div className={'text-lg font-medium text-neutral-100'}>{active.label}</div>
                    <div className={'text-sm text-neutral-400'}>{active.description}</div>
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
                        />
                    ))}
                </div>
            </div>
        </div>
    );
};

export default ThemeDesigner;
