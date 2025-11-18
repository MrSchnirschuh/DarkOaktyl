import { Formik, Form } from 'formik';
import Field from '@elements/Field';
import { Button } from '@elements/button';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { useStoreState, useStoreActions } from '@/state/hooks';
import updateColor from '@/api/admin/theme/updateColors';
import { normalizeColorHex } from '@/helpers/colorContrast';
import tw from 'twin.macro';

const InfoColorsSettings = () => {
    const theme = useStoreState(s => s.theme.data!);
    const setTheme = useStoreActions(a => a.theme.setTheme);
    const { addFlash, clearFlashes } = useFlash();

    const initial = {
        danger: theme.colors?.danger ?? '#dc2626',
        info: theme.colors?.info ?? '#f59e0b',
        warning: theme.colors?.warning ?? '#f97316',
        experimental: theme.colors?.experimental ?? '#facc15',
        success: theme.colors?.success ?? theme.colors?.primary ?? '#16a34a',
    };

    const submit = async (values: any) => {
        clearFlashes('admin:theme:info');
        try {
            // Normalize and validate values before persisting
            const keys = ['danger', 'info', 'warning', 'experimental', 'success'];
            const normalized: Record<string, string> = {};

            for (const k of keys) {
                const raw = values[k];
                const n = normalizeColorHex(raw);
                if (!n) {
                    addFlash({ key: 'admin:theme:info', type: 'error', message: `Invalid color for ${k}: ${raw}` });
                    return;
                }
                normalized[k] = n.toUpperCase();
            }

            // persist each normalized color key via API
            await Promise.all(keys.map(k => updateColor(k, normalized[k])));

            const updated = {
                ...theme,
                colors: {
                    ...theme.colors,
                    ...normalized,
                },
            };
            setTheme(updated);
            addFlash({ key: 'admin:theme:info', type: 'success', message: 'Theme colors persisted and updated.' });
        } catch (e) {
            addFlash({ key: 'admin:theme:info', type: 'error', message: 'Could not persist theme colors.' });
        }
    };

    return (
        <div className="p-4">
            <h2 css={tw`text-2xl text-theme-primary font-header font-medium mb-4`}>Information</h2>
            <p className="text-theme-muted mb-3">
                Configure additional informational colors used for alerts, badges and messages.
            </p>
            <FlashMessageRender byKey={'admin:theme:info'} className={'mb-2'} />

            <div className="bg-neutral-900 p-4 rounded">
                <Formik onSubmit={submit} initialValues={initial}>
                    {() => (
                        <Form>
                            <div className="grid grid-cols-2 gap-4">
                                <Field id="danger" name="danger" label="Danger (red)" type="text" />
                                <Field id="info" name="info" label="Info (yellow)" type="text" />
                                <Field id="warning" name="warning" label="Warning (orange)" type="text" />
                                <Field
                                    id="experimental"
                                    name="experimental"
                                    label="Experimental (yellow)"
                                    type="text"
                                />
                                <Field id="success" name="success" label="Success (green)" type="text" />
                            </div>
                            <div className="mt-4">
                                <Button type="submit">Save Colors</Button>
                            </div>
                        </Form>
                    )}
                </Formik>
            </div>
        </div>
    );
};

export default InfoColorsSettings;
