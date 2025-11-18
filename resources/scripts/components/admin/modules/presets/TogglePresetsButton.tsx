import { useState } from 'react';
import { Button } from '@elements/button';
import { updateGeneralSettings } from '@/api/admin/settings';
import { useStoreActions, useStoreState } from '@/state/hooks';
import useFlash from '@/plugins/useFlash';

const TogglePresetsButton = () => {
    // navigate not required here
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const settings = useStoreState(state => state.settings.data!);
    const enabled = settings.presets_module ?? false;
    const updateSettingsState = useStoreActions(actions => actions.settings.updateSettings);
    const [isSubmitting, setSubmitting] = useState(false);

    const handleToggle = () => {
        if (isSubmitting) return;

        const next = !enabled;
        setSubmitting(true);
        clearFlashes('admin:presets');

        updateGeneralSettings({ presets_module: next })
            .then(() => {
                updateSettingsState({ presets_module: next });
                addFlash({
                    key: 'admin:presets',
                    type: 'success',
                    message: next ? 'Presets module enabled.' : 'Presets module disabled.',
                });
            })
            .catch(error => clearAndAddHttpError({ key: 'admin:presets', error }))
            .finally(() => setSubmitting(false));
    };

    return !enabled ? (
        <Button className={'mr-4'} onClick={handleToggle} disabled={isSubmitting} aria-busy={isSubmitting}>
            {isSubmitting ? 'Enabling…' : 'Enable Presets'}
        </Button>
    ) : (
        <Button.Danger className={'mr-4'} onClick={handleToggle} disabled={isSubmitting} aria-busy={isSubmitting}>
            {isSubmitting ? 'Disabling…' : 'Disable Presets'}
        </Button.Danger>
    );
};

export default TogglePresetsButton;
