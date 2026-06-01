import { Dispatch, SetStateAction, useMemo, useState } from 'react';
import { Dialog } from '@elements/dialog';
import Label from '@elements/Label';
import InputField from '@elements/inputs/InputField';
import Select from '@elements/Select';
import Switch from '@elements/Switch';
import { VisibleDialog } from './DomainRootsContainer';
import {
    createDomainRoot,
    DomainRoot,
    DomainRootValues,
    mutateDomainRoots,
    updateDomainRoot,
} from '@/api/admin/domainRoots';
import { useFlashKey } from '@/plugins/useFlash';

interface Props {
    root?: DomainRoot;
    setOpen: Dispatch<SetStateAction<VisibleDialog>>;
}

const defaultConfig = (provider: string, existing: Record<string, any> = {}) => {
    if (provider === 'cloudflare') {
        return {
            zone_id: existing.zone_id ?? '',
            api_token: existing.api_token ?? '',
            record_type: (existing.record_type || 'A').toUpperCase(),
            origin_ipv4: existing.origin_ipv4 ?? '',
            origin_ipv6: existing.origin_ipv6 ?? '',
            ttl: existing.ttl ?? 120,
            proxied: existing.proxied ?? true,
        };
    }

    return {};
};

const DomainRootDialog = ({ root, setOpen }: Props) => {
    const [values, setValues] = useState<DomainRootValues>({
        name: root?.name ?? '',
        root_domain: root?.rootDomain ?? '',
        provider: root?.provider ?? 'manual',
        is_active: root?.isActive ?? true,
        provider_config: defaultConfig(root?.provider ?? 'manual', root?.providerConfig ?? {}),
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlashKey('admin:domainRoots');

    const providerConfig = useMemo(() => values.provider_config || {}, [values.provider_config]);

    const close = () => setOpen('none');

    const sanitizeValues = (): DomainRootValues => {
        if (values.provider !== 'cloudflare') {
            return { ...values, provider_config: {} };
        }

        const config = {
            ...providerConfig,
            record_type: (providerConfig.record_type || 'A').toUpperCase(),
            ttl: Number(providerConfig.ttl || 120) || 120,
            proxied: Boolean(providerConfig.proxied ?? true),
        };

        return {
            ...values,
            provider_config: Object.entries(config).reduce((acc, [key, value]) => {
                if (value === '' || value === null || value === undefined) {
                    return acc;
                }

                return { ...acc, [key]: value };
            }, {} as Record<string, any>),
        };
    };

    const submit = async () => {
        setIsSubmitting(true);
        clearFlashes();

        const payload = sanitizeValues();

        try {
            if (root) {
                await updateDomainRoot(root.id, payload);
            } else {
                await createDomainRoot(payload);
            }

            mutateDomainRoots();
            close();
        } catch (error) {
            clearAndAddHttpError(error);
        } finally {
            setIsSubmitting(false);
        }
    };

    const setField = (field: keyof DomainRootValues, value: any) => {
        setValues(prev => ({ ...prev, [field]: value }));
    };

    const updateProvider = (provider: string) => {
        setValues(prev => ({
            ...prev,
            provider,
            provider_config: defaultConfig(provider, prev.provider_config),
        }));
    };

    const updateProviderConfig = (field: string, value: any) => {
        setValues(prev => ({
            ...prev,
            provider_config: {
                ...(prev.provider_config || {}),
                [field]: value,
            },
        }));
    };

    return (
        <Dialog.Confirm
            confirm={root ? 'Save' : 'Create'}
            open
            onClose={close}
            onConfirmed={submit}
            title={root ? 'Edit Domain Root' : 'Create Domain Root'}
            disableConfirm={isSubmitting}
        >
            <div className={'mt-4 grid grid-cols-1 gap-4'}>
                <div>
                    <Label>Name</Label>
                    <InputField
                        name={'name'}
                        value={values.name}
                        onChange={e => setField('name', e.currentTarget.value)}
                    />
                    <p className={'text-theme-muted text-sm mt-1'}>Friendly label shown throughout the admin UI.</p>
                </div>
                <div>
                    <Label>Root Domain</Label>
                    <InputField
                        name={'root_domain'}
                        value={values.root_domain}
                        onChange={e => setField('root_domain', e.currentTarget.value)}
                    />
                    <p className={'text-theme-muted text-sm mt-1'}>Example: example.com</p>
                </div>
                <div>
                    <Label>Provider</Label>
                    <Select value={values.provider} onChange={e => updateProvider(e.currentTarget.value)}>
                        <option value={'manual'}>Manual (no automation)</option>
                        <option value={'cloudflare'}>Cloudflare</option>
                    </Select>
                </div>
                <div className={'xl:col-span-2 bg-black/50 border border-black shadow-inner p-4 rounded'}>
                    <Switch
                        name={'is_active'}
                        defaultChecked={values.is_active}
                        onChange={() => setField('is_active', !values.is_active)}
                        label={'Domain Availability'}
                        description={'Toggle availability for customers selecting this root domain during checkout.'}
                    />
                </div>
                {values.provider === 'cloudflare' && (
                    <div className={'grid grid-cols-1 lg:grid-cols-2 gap-4'}>
                        <div>
                            <Label>Zone ID</Label>
                            <InputField
                                name={'zone_id'}
                                value={providerConfig.zone_id ?? ''}
                                onChange={e => updateProviderConfig('zone_id', e.currentTarget.value)}
                            />
                        </div>
                        <div>
                            <Label>API Token</Label>
                            <InputField
                                type={'password'}
                                name={'api_token'}
                                value={providerConfig.api_token ?? ''}
                                onChange={e => updateProviderConfig('api_token', e.currentTarget.value)}
                            />
                        </div>
                        <div>
                            <Label>Record Type</Label>
                            <Select
                                value={(providerConfig.record_type || 'A').toUpperCase()}
                                onChange={e => updateProviderConfig('record_type', e.currentTarget.value)}
                            >
                                <option value={'A'}>A (IPv4)</option>
                                <option value={'AAAA'}>AAAA (IPv6)</option>
                            </Select>
                        </div>
                        <div>
                            <Label>Origin IPv4</Label>
                            <InputField
                                name={'origin_ipv4'}
                                value={providerConfig.origin_ipv4 ?? ''}
                                onChange={e => updateProviderConfig('origin_ipv4', e.currentTarget.value)}
                            />
                        </div>
                        <div>
                            <Label>Origin IPv6</Label>
                            <InputField
                                name={'origin_ipv6'}
                                value={providerConfig.origin_ipv6 ?? ''}
                                onChange={e => updateProviderConfig('origin_ipv6', e.currentTarget.value)}
                            />
                        </div>
                        <div>
                            <Label>TTL (seconds)</Label>
                            <InputField
                                name={'ttl'}
                                type={'number'}
                                value={providerConfig.ttl ?? 120}
                                onChange={e => updateProviderConfig('ttl', e.currentTarget.value)}
                            />
                        </div>
                        <div className={'xl:col-span-2 bg-black/50 border border-black shadow-inner p-4 rounded'}>
                            <Switch
                                name={'proxied'}
                                defaultChecked={providerConfig.proxied ?? true}
                                onChange={() => updateProviderConfig('proxied', !(providerConfig.proxied ?? true))}
                                label={'Cloudflare Proxy'}
                                description={'Enable Cloudflare proxy (orange cloud). Disable to expose the origin IP.'}
                            />
                        </div>
                    </div>
                )}
            </div>
        </Dialog.Confirm>
    );
};

export default DomainRootDialog;
