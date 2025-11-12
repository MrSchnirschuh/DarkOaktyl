import { useStoreState } from '@/state/hooks';
import FeatureContainer from '@elements/FeatureContainer';
import SuccessSvg from '@/assets/images/themed/SuccessSvg';
import { faEnvelope } from '@fortawesome/free-solid-svg-icons';
import FlashMessageRender from '@/components/FlashMessageRender';
import ToggleEmailsButton from './ToggleEmailsButton';

const EnableEmailsContainer = () => {
    const primary = useStoreState(state => state.theme.data!.colors.primary);

    return (
        <FeatureContainer image={<SuccessSvg color={primary} />} icon={faEnvelope} title={'Email Module'}>
            <FlashMessageRender byKey={'admin:emails'} className={'mb-4'} />
            <p>
                Manage transactional and automated messages directly from the panel. Configure branded themes, reusable
                templates, and scheduled or event-driven triggers to keep your users informed automatically.
            </p>
            <p className={'text-right mt-2'}>
                <ToggleEmailsButton />
            </p>
        </FeatureContainer>
    );
};

export default EnableEmailsContainer;
