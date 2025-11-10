import { Dispatch, SetStateAction, useRef, useState } from 'react';
import useFlash from '@/plugins/useFlash';
import Label from '@elements/Label';
import Input from '@elements/Input';
import AdminBox from '@elements/AdminBox';
import Spinner from '@elements/Spinner';
import updateColors from '@/api/admin/theme/updateColors';
import { CheckCircleIcon } from '@heroicons/react/outline';
import { useStoreActions, useStoreState } from '@/state/hooks';
import FlashMessageRender from '@/components/FlashMessageRender';
import { faPaintbrush } from '@fortawesome/free-solid-svg-icons';

interface Props {
    setReload: Dispatch<SetStateAction<boolean>>;
    mode: 'light' | 'dark';
    category?: 'text' | 'accent' | 'components' | 'all';
}

export default ({ setReload, mode, category = 'all' }: Props) => {
    const [loading, setLoading] = useState<boolean>(false);
    const [success, setSuccess] = useState<boolean>(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const colors = useStoreState(state => state.theme.data!.colors);
    const setTheme = useStoreActions(actions => actions.theme.setTheme);
    const modeSuffix = (m: 'light' | 'dark') => `_${m}`;

    // We'll debounce saves so changing colors in quick succession doesn't flood the backend
    const saveTimers = useRef<Record<string, number | null>>({});

    const commitUpdate = async (key: string, value: string) => {
        clearFlashes();
        setReload(true);
        setLoading(true);
        setSuccess(false);

        const backendKey = `${key}${modeSuffix(mode ?? 'dark')}`;

        const newColors: Record<string, string> = { ...(colors as Record<string, string>) };
        newColors[backendKey] = value;
        newColors[key] = value;

        setTheme({ colors: newColors } as any);

        try {
            await updateColors(backendKey, value);
            setReload(false);
            setSuccess(true);
            setLoading(false);
            setTimeout(() => setSuccess(false), 2000);
        } catch (error) {
            clearAndAddHttpError({ key: 'theme:colors', error });
            setLoading(false);
        }
    };

    const update = (key: string, value: string) => {
        // clear any existing timer and schedule a new commit in 600ms
        if (saveTimers.current[key]) {
            window.clearTimeout(saveTimers.current[key]!);
        }
        // optimistically update store immediately so preview is instant
        const optimistic: Record<string, string> = { ...(colors as Record<string, string>) };
        optimistic[`${key}_${mode}`] = value;
        optimistic[key] = value;
        setTheme({ colors: optimistic } as any);

        saveTimers.current[key] = window.setTimeout(() => commitUpdate(key, value), 600);
    };

    // Build a list of base keys from the current theme colors. This includes keys like "primary", and
    // any per-mode keys such as "background_light" / "background_dark". We render one input per base key.
    const baseKeys: string[] = [];
    Object.keys(colors).forEach(k => {
        // Exclude stored presets keys (they are saved under theme::colors:presets:name)
        if (k.startsWith('presets:')) return;
        const m = k.match(/(.+?)_(light|dark)$/);
        if (m && m[1]) {
            const base = m[1] as string;
            if (!baseKeys.includes(base)) baseKeys.push(base);
        } else {
            if (!baseKeys.includes(k)) baseKeys.push(k);
        }
    });

    // Filter keys by category prop.

    return (
        <AdminBox title={'Color Selection'} icon={faPaintbrush}>
            <FlashMessageRender byKey={'theme:colors'} className={'my-2'} />
            {loading && <Spinner className={'absolute top-0 right-0 m-3.5'} size={'small'} />}
            {success && <CheckCircleIcon className={'w-5 h-5 absolute top-0 right-0 m-3.5 text-green-500'} />}
            {baseKeys
                .filter(b => {
                    if (!category || category === 'all') return true;
                    const lower = b.toLowerCase();
                    if (category === 'text') return lower.includes('text');
                    if (category === 'accent') {
                        // Only allow exact base keys for accents so text_primary / text_secondary are not included here.
                        return ['primary', 'secondary', 'accent', 'accent_primary'].includes(lower);
                    }
                    // components should include background controls as well
                    if (category === 'components')
                        return ['sidebar', 'header', 'button', 'background'].some(x => lower.includes(x));
                    return true;
                })
                .map((base, idx) => (
                    <div className={idx === 0 ? '' : 'mt-6'} key={base}>
                        <Label>{base.charAt(0).toUpperCase() + base.slice(1).replace('_', ' ')}</Label>
                        <Input
                            id={base}
                            type={'color'}
                            name={base}
                            value={(colors as any)[`${base}_${mode}`] ?? (colors as any)[base] ?? '#ffffff'}
                            onChange={e => update(base, e.target.value)}
                        />
                        <p className={'text-xs text-gray-400 mt-1'}>
                            Configure the <strong>{base}</strong> color for the {mode} mode (falls back to canonical
                            value).
                        </p>
                    </div>
                ))}
        </AdminBox>
    );
};
