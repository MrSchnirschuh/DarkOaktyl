import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@elements/button';
import { updateEmailSetting } from '@/api/admin/emails';
import { useStoreActions, useStoreState } from '@/state/hooks';
import useFlash from '@/plugins/useFlash';

const ToggleEmailsButton = () => {
    const navigate = useNavigate();
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const emails = useStoreState(state => state.everest.data?.emails ?? null);
    const enabled = emails?.enabled ?? false;
    const updateEverest = useStoreActions(actions => actions.everest.updateEverest);
    const [isSubmitting, setSubmitting] = useState(false);

    const handleToggle = () => {
        if (isSubmitting) return;

        const nextEnabled = !enabled;
        setSubmitting(true);
        clearFlashes('admin:emails');

        updateEmailSetting('enabled', nextEnabled)
            .then(() => {
                if (emails) {
                    updateEverest({ emails: { ...emails, enabled: nextEnabled } });
                } else {
                    navigate(0);
                }

                addFlash({
                    key: 'admin:emails',
                    type: 'success',
                    message: nextEnabled ? 'Emails have been enabled.' : 'Emails have been disabled.',
                });
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'admin:emails', error });
            })
            .finally(() => setSubmitting(false));
    };

    return !enabled ? (
        <Button className={'mr-4'} onClick={handleToggle} disabled={isSubmitting} aria-busy={isSubmitting}>
            {isSubmitting ? 'Enabling…' : 'Enable Emails'}
        </Button>
    ) : (
        <Button.Danger className={'mr-4'} onClick={handleToggle} disabled={isSubmitting} aria-busy={isSubmitting}>
            {isSubmitting ? 'Disabling…' : 'Disable Emails'}
        </Button.Danger>
    );
};

export default ToggleEmailsButton;
