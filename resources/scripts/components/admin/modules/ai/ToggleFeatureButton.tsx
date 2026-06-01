import { Button } from '@elements/button';
import { useStoreState } from '@/state/hooks';
import { updateSettings } from '@/api/admin/ai/settings';

export default () => {
    const ai = useStoreState(state => state.DarkOak.data!.ai);

    const submit = () => {
        updateSettings({ ...ai, enabled: !ai.enabled }).then(() => {
            // @ts-expect-error this is fine
            window.location = '/admin/ai';
        });
    };

    return (
        <div className={'mr-4'} onClick={submit}>
            {!ai.enabled ? <Button>Enable DarkOaktyl AI</Button> : <Button.Danger>Disable DarkOaktyl AI</Button.Danger>}
        </div>
    );
};
