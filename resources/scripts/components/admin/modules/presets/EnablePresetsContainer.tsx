import { useStoreState } from '@/state/hooks';
import FeatureContainer from '@elements/FeatureContainer';
import SuccessSvg from '@/assets/images/themed/SuccessSvg';
import { faThLarge } from '@fortawesome/free-solid-svg-icons';
import FlashMessageRender from '@/components/FlashMessageRender';
import TogglePresetsButton from './TogglePresetsButton';

const EnablePresetsContainer = () => {
    const primary = useStoreState(state => state.theme.data!.colors.primary);

    return (
        <FeatureContainer image={<SuccessSvg color={primary} />} icon={faThLarge} title={'Presets Module'}>
            <FlashMessageRender byKey={'admin:presets'} className={'mb-4'} />
            <p>
                Manage reusable server presets for quick deployments. Create presets from the New Server flow and manage
                them here (edit, delete, global visibility and default port ranges).
            </p>
            <p className={'text-right mt-2'}>
                <TogglePresetsButton />
            </p>
        </FeatureContainer>
    );
};

export default EnablePresetsContainer;
