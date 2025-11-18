import { Formik, Form } from 'formik';
import Field from '@elements/Field';
import { Button } from '@elements/button';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { useStoreState, useStoreActions } from '@/state/hooks';
import { updateGeneralSettings } from '@/api/admin/settings';
import TogglePresetsButton from './TogglePresetsButton';
import tw from 'twin.macro';

const PresetsSettings = () => {
    const settings = useStoreState(s => s.settings.data!);
    const updateSettingsState = useStoreActions(a => a.settings.updateSettings);
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();

    const submitSettings = async (values: any) => {
        clearFlashes('admin:presets:settings');
        try {
            await updateGeneralSettings({
                presets_global_port_start: values.presets_global_port_start || null,
                presets_global_port_end: values.presets_global_port_end || null,
            });
            updateSettingsState({
                presets_global_port_start: values.presets_global_port_start || null,
                presets_global_port_end: values.presets_global_port_end || null,
            });
            addFlash({ key: 'admin:presets:settings', type: 'success', message: 'Settings updated.' });
        } catch (e) {
            clearAndAddHttpError({ key: 'admin:presets:settings', error: e });
        }
    };

    return (
        <div className="p-4">
            <h2 css={tw`text-2xl text-theme-primary font-header font-medium mb-4`}>Presets Settings</h2>
            <p className="text-theme-muted mb-3">
                Diese Werte sind die Fallback-Standardports, die verwendet werden, wenn ein Preset keine
                Start-/End-Port‑Angaben enthält.
            </p>
            <FlashMessageRender byKey={'admin:presets:settings'} className={'mb-2'} />

            <div className="bg-neutral-900 p-4 rounded">
                <Formik
                    onSubmit={submitSettings}
                    initialValues={{
                        presets_global_port_start: settings.presets_global_port_start ?? null,
                        presets_global_port_end: settings.presets_global_port_end ?? null,
                    }}
                >
                    {() => (
                        <Form>
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <Field
                                        id="presets_global_port_start"
                                        name="presets_global_port_start"
                                        label="Port Start"
                                        type="number"
                                    />
                                </div>
                                <div>
                                    <Field
                                        id="presets_global_port_end"
                                        name="presets_global_port_end"
                                        label="Port End"
                                        type="number"
                                    />
                                </div>
                            </div>
                            <div className="mt-3">
                                <Button type="submit">Save Settings</Button>
                            </div>
                        </Form>
                    )}
                </Formik>
            </div>

            <div className="mt-6 flex justify-center">
                <TogglePresetsButton />
            </div>
        </div>
    );
};

export default PresetsSettings;
