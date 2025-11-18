import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import type { FormikHelpers } from 'formik';
import { Form, Formik, useFormikContext } from 'formik';
import { useCallback } from 'react';
import { useStoreState } from '@/state/hooks';
import { getPresets as fetchPresets } from '@/api/admin/presets';
import PresetSaveModal from '@admin/management/servers/PresetSaveModal';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import tw from 'twin.macro';
import { object } from 'yup';

import type { Egg } from '@/api/admin/egg';
import type { CreateServerRequest } from '@/api/admin/servers/createServer';
import createServer from '@/api/admin/servers/createServer';
import type { Node } from '@/api/admin/node';
import AdminBox from '@elements/AdminBox';
import NodeSelect from '@admin/management/servers/NodeSelect';
import {
    ServerImageContainer,
    ServerServiceContainer,
    ServerVariableContainer,
} from '@admin/management/servers/ServerStartupContainer';
import BaseSettingsBox from '@admin/management/servers/settings/BaseSettingsBox';
import FeatureLimitsBox from '@admin/management/servers/settings/FeatureLimitsBox';
import ServerResourceBox from '@admin/management/servers/settings/ServerResourceBox';
import { Button } from '@elements/button';
import Field from '@elements/Field';
import FormikSwitch from '@elements/FormikSwitch';
import Label from '@elements/Label';
import SpinnerOverlay from '@elements/SpinnerOverlay';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import AdminContentBlock from '@elements/AdminContentBlock';
import { WithRelationships } from '@/api/admin';
import { AsyncSelectField } from '@/components/elements/SelectField';
import type { Option } from '@elements/SelectField';
import getAllocations from '@/api/admin/nodes/getAllocations';
import { Alert } from '@/components/elements/alert';

function InternalForm() {
    const { isSubmitting, isValid, setFieldValue, values } = useFormikContext<CreateServerRequest>();
    const { environment } = values as any;

    const [presets, setPresets] = useState<any[]>([]);
    const [saveModalOpen, setSaveModalOpen] = useState(false);
    const settings = useStoreState(s => s.settings.data!);

    const loadPresets = useCallback(async () => {
        try {
            const list = await fetchPresets();
            setPresets(list);
        } catch (e) {
            setPresets([]);
        }
    }, []);

    // load presets once (only if module enabled)
    useEffect(() => {
        if (settings.presets_module) loadPresets();
    }, [loadPresets, settings.presets_module]);

    const [egg, setEgg] = useState<WithRelationships<Egg, 'variables'> | undefined>(undefined);
    const [node, setNode] = useState<Node | undefined>(undefined);

    useEffect(() => {
        if (egg === undefined) {
            return;
        }

        setFieldValue('eggId', egg.id);
        setFieldValue('startup', '');
        setFieldValue('image', Object.values(egg.dockerImages)[0] ?? '');
    }, [egg]);

    const loadOptions = async (inputValue: string, callback: (options: Option[]) => void) => {
        if (!node) {
            callback([] as Option[]);
            return;
        }

        const allocations = await getAllocations(node.id, { search: inputValue, server_id: '0' });

        callback(
            allocations.map(a => {
                return { value: a.id.toString(), label: a.getDisplayText() };
            }),
        );
    };

    // helper removed: unused

    const applyPreset = (preset: any) => {
        if (!preset || !preset.settings) return;
        const s = preset.settings;
        // shallow merge for known keys
        if (s.name) setFieldValue('name', s.name);
        if (s.description) setFieldValue('description', s.description);
        if (s.node_id) setFieldValue('nodeId', s.node_id);

        if (s.limits) {
            Object.entries(s.limits).forEach(([k, v]) => setFieldValue(`limits.${k}` as any, v));
        }
        if (s.feature_limits) {
            Object.entries(s.feature_limits).forEach(([k, v]) => setFieldValue(`featureLimits.${k}` as any, v));
        }
        if (s.allocation) {
            if (s.allocation.default) setFieldValue('allocation.default' as any, s.allocation.default);
            if (s.allocation.additional) setFieldValue('allocation.additional' as any, s.allocation.additional);
        }
        if (s.startup) setFieldValue('startup', s.startup);
        if (s.environment) setFieldValue('environment', s.environment);
        if (s.egg_id) setFieldValue('eggId', s.egg_id);
        if (s.image) setFieldValue('image', s.image);
        if (s.skip_scripts !== undefined) setFieldValue('skipScripts', s.skip_scripts);
        if (s.start_on_completion !== undefined) setFieldValue('startOnCompletion', s.start_on_completion);
    };

    return (
        <Form>
            <div className="grid grid-cols-2 gap-y-6 gap-x-8 mb-16">
                {/* Preset selector row */}
                {settings.presets_module && (
                    <div className="col-span-2 mb-2 flex items-center space-x-3">
                        <div className="flex-1">
                            <Label>Preset</Label>
                            <select
                                className="w-full p-2 rounded bg-neutral-900 border border-neutral-800"
                                onChange={e => {
                                    const id = Number(e.target.value || 0);
                                    const p = presets.find(x => x.id === id);
                                    applyPreset(p);
                                }}
                            >
                                <option value="">-- Select preset --</option>
                                {presets.map(p => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <Button type="button" onClick={() => setSaveModalOpen(true)} className="h-10">
                                Save as preset
                            </Button>
                        </div>
                    </div>
                )}
                <PresetSaveModal
                    visible={saveModalOpen}
                    onDismissed={() => setSaveModalOpen(false)}
                    values={values as any}
                    onSaved={() => loadPresets()}
                />
                <div className="grid grid-cols-1 gap-y-6 col-span-2 md:col-span-1">
                    <BaseSettingsBox>
                        <NodeSelect node={node!} setNode={setNode} />
                        <div className="xl:col-span-2 bg-neutral-800 border border-neutral-900 shadow-inner p-4 rounded">
                            <FormikSwitch
                                name={'startOnCompletion'}
                                label={'Start after installation'}
                                description={'Should the server be automatically started after it has been installed?'}
                            />
                        </div>
                    </BaseSettingsBox>
                    <FeatureLimitsBox />
                    <ServerServiceContainer selectedEggId={egg?.id} setEgg={setEgg} nestId={0} />
                </div>
                <div className="grid grid-cols-1 gap-y-6 col-span-2 md:col-span-1">
                    <AdminBox icon={faNetworkWired} title="Networking" isLoading={isSubmitting}>
                        <div className="grid grid-cols-1 gap-4 lg:gap-6">
                            <div>
                                <Label htmlFor={'allocation.default'}>Primary Allocation</Label>
                                {!node ? (
                                    <Alert type={'info'}>Select a node to view allocations.</Alert>
                                ) : (
                                    <AsyncSelectField
                                        id={'allocation.default'}
                                        name={'allocation.default'}
                                        loadOptions={loadOptions}
                                    />
                                )}
                            </div>
                        </div>
                    </AdminBox>
                    <ServerResourceBox />
                    <ServerImageContainer />
                </div>

                <AdminBox title={'Startup Command'} className="relative w-full col-span-2">
                    <SpinnerOverlay visible={isSubmitting} />

                    <Field
                        id={'startup'}
                        name={'startup'}
                        label={'Startup Command'}
                        type={'text'}
                        description={
                            "Edit your server's startup command here. The following variables are available by default: {{SERVER_MEMORY}}, {{SERVER_IP}}, and {{SERVER_PORT}}."
                        }
                        placeholder={egg?.startup || ''}
                    />
                </AdminBox>

                <div className="col-span-2 grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                    {/* This ensures that no variables are rendered unless the environment has a value for the variable. */}
                    {egg?.relationships.variables
                        ?.filter(v => Object.keys(environment).find(e => e === v.environmentVariable) !== undefined)
                        .map((v, i) => (
                            <ServerVariableContainer key={i} variable={v} />
                        ))}
                </div>

                <div className="bg-neutral-700 rounded shadow-md px-4 py-3 col-span-2">
                    <div className="flex flex-row">
                        <Button type="submit" className="ml-auto" disabled={isSubmitting || !isValid}>
                            Create Server
                        </Button>
                    </div>
                </div>
            </div>
        </Form>
    );
}

export default () => {
    const navigate = useNavigate();

    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const submit = (r: CreateServerRequest, { setSubmitting }: FormikHelpers<CreateServerRequest>) => {
        clearFlashes('server:create');

        createServer(r)
            .then(s => navigate(`/admin/servers/${s.id}`))
            .catch(error => clearAndAddHttpError({ key: 'server:create', error }))
            .then(() => setSubmitting(false));
    };

    return (
        <AdminContentBlock title={'New Server'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-theme-primary font-header font-medium`}>New Server</h2>
                    <p
                        css={tw`hidden md:block text-base text-theme-muted whitespace-nowrap overflow-ellipsis overflow-hidden`}
                    >
                        Add a new server to the panel.
                    </p>
                </div>
            </div>

            <FlashMessageRender byKey={'server:create'} css={tw`mb-4`} />

            <Formik
                onSubmit={submit}
                initialValues={
                    {
                        externalId: '',
                        name: '',
                        description: '',
                        ownerId: 0,
                        nodeId: 0,
                        limits: {
                            memory: 1024,
                            swap: 0,
                            disk: 4096,
                            io: 500,
                            cpu: 0,
                            threads: '',
                            oomKiller: true,
                        },
                        featureLimits: {
                            allocations: 1,
                            backups: 0,
                            databases: 0,
                            subusers: 0,
                        },
                        allocation: {
                            default: 0,
                            additional: [] as number[],
                        },
                        startup: '',
                        environment: [],
                        eggId: 0,
                        image: '',
                        skipScripts: false,
                        startOnCompletion: true,
                    } as CreateServerRequest
                }
                validationSchema={object().shape({})}
            >
                <InternalForm />
            </Formik>
        </AdminContentBlock>
    );
};
