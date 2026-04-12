import Label from '@elements/Label';
import Select from '@elements/Select';
import AdminBox from '@elements/AdminBox';
import { faLock } from '@fortawesome/free-solid-svg-icons';
import Input from '@elements/Input';
import useFlash from '@/plugins/useFlash';
import { useStoreState } from '@/state/hooks';
import useStatus from '@/plugins/useStatus';
import { updateModule } from '@/api/admin/auth/module';

type TwoFactorEnforcement = 'NONE' | 'ADMIN' | 'ALL';

export default () => {
    const { status, setStatus } = useStatus();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const settings = useStoreState(state => state.DarkOak.data!.auth.security);

    // Get current 2FA enforcement level
    // Supports new '2fa.enforcement' with fallback to legacy 'force2fa'
    const getEnforcementLevel = (): TwoFactorEnforcement => {
        // New format: 2fa.enforcement
        if (settings['2fa']?.enforcement) {
            const level = settings['2fa'].enforcement.toUpperCase();
            if (['NONE', 'ADMIN', 'ALL'].includes(level)) {
                return level as TwoFactorEnforcement;
            }
        }
        // Legacy fallback: force2fa boolean
        if (settings.force2fa) {
            return 'ALL';
        }
        return 'NONE';
    };

    const currentLevel = getEnforcementLevel();

    const update = async (key: string, value: any) => {
        clearFlashes();
        setStatus('loading');

        updateModule('security', key, value)
            .then(() => setStatus('success'))
            .catch(error => {
                setStatus('error');
                clearAndAddHttpError({ key: 'auth:security', error });
            });
    };

    return (
        <AdminBox title={'Security Module'} icon={faLock} byKey={'auth:security'} status={status}>
            <div>
                <Label>Two-Factor Authentication Enforcement</Label>
                <Select
                    id={'2fa.enforcement'}
                    name={'2fa.enforcement'}
                    onChange={e => update('2fa.enforcement', e.target.value)}
                >
                    <option value={'NONE'} selected={currentLevel === 'NONE'}>
                        None - Optional 2FA
                    </option>
                    <option value={'ADMIN'} selected={currentLevel === 'ADMIN'}>
                        Admin Only - Required for admins
                    </option>
                    <option value={'ALL'} selected={currentLevel === 'ALL'}>
                        All Users - Required for everyone
                    </option>
                </Select>
                <p className={'text-xs text-theme-muted mt-1'}>
                    Control who is required to use two-factor authentication. &quot;None&quot; allows optional 2FA,
                    &quot;Admin&quot; requires it for admin users, &quot;All&quot; requires it for everyone.
                </p>
            </div>
            <div className={'mt-6'}>
                <Label>Login Attempt Limit</Label>
                <Input
                    placeholder={`${settings.attempts ?? 3}`}
                    id={'attempts'}
                    type={'number'}
                    name={'attempts'}
                    onChange={e => update('attempts', e.target.value)}
                />
                <p className={'text-xs text-theme-muted mt-1'}>
                    Set the maximum amount of attempts a user can make to login before being throttled.
                </p>
            </div>
        </AdminBox>
    );
};

