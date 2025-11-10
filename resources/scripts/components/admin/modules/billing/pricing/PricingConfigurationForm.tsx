import type { Actions } from 'easy-peasy';
import { useStoreActions } from 'easy-peasy';
import type { FormikHelpers } from 'formik';
import { Form, Formik, FieldArray } from 'formik';
import { useNavigate, Link, useParams } from 'react-router-dom';
import Field, { FieldRow } from '@elements/Field';
import tw from 'twin.macro';
import AdminContentBlock from '@elements/AdminContentBlock';
import { Button } from '@elements/button';
import type { ApplicationStore } from '@/state';
import AdminBox from '@elements/AdminBox';
import { object, string, number, boolean, array } from 'yup';
import { faCog, faArrowLeft, faDollarSign, faSliders, faClock } from '@fortawesome/free-solid-svg-icons';
import { useStoreState } from '@/state/hooks';
import { createPricingConfiguration, updatePricingConfiguration, getPricingConfiguration } from '@/api/admin/billing/pricing';
import { PricingConfiguration } from '@/api/definitions/admin';
import { PricingConfigurationValues } from '@/api/admin/billing/types';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useEffect, useState } from 'react';
import PricingConfigurationDeleteButton from './PricingConfigurationDeleteButton';
import Label from '@elements/Label';

interface Props {
    configuration?: PricingConfiguration;
}

export default ({ configuration }: Props) => {
    const navigate = useNavigate();
    const params = useParams<'id'>();
    const [enabled, setEnabled] = useState(configuration?.enabled ?? true);

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );
    const { secondary } = useStoreState(state => state.theme.data!.colors);

    const submit = (values: PricingConfigurationValues, { setSubmitting }: FormikHelpers<PricingConfigurationValues>) => {
        clearFlashes('admin:billing:pricing:create');

        if (!configuration) {
            createPricingConfiguration(values)
                .then(data => {
                    setSubmitting(false);
                    navigate(`/admin/billing/pricing/${data.id}`);
                })
                .catch(error => {
                    setSubmitting(false);
                    clearAndAddHttpError({ key: 'admin:billing:pricing:create', error });
                });
        } else {
            updatePricingConfiguration(configuration.id, values)
                .then(() => {
                    setSubmitting(false);
                    navigate(`/admin/billing/pricing`);
                })
                .catch(error => {
                    setSubmitting(false);
                    clearAndAddHttpError({ key: 'admin:billing:pricing:create', error });
                });
        }
    };

    return (
        <AdminContentBlock title={configuration ? 'Edit Pricing Configuration' : 'New Pricing Configuration'}>
            <div css={tw`w-full flex flex-row items-center m-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>
                        {configuration?.name ?? 'New Pricing Configuration'}
                    </h2>
                    <p css={tw`hidden lg:block text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        {configuration?.uuid ?? 'Configure resource-based pricing for dynamic product creation.'}
                    </p>
                </div>
                {configuration && (
                    <div className={'hidden md:flex ml-auto mr-12'}>
                        <Link to={`/admin/billing/pricing`}>
                            <Button>
                                <FontAwesomeIcon icon={faArrowLeft} className={'mr-2'} />
                                Return to Pricing List
                            </Button>
                        </Link>
                    </div>
                )}
            </div>
            <Formik
                onSubmit={submit}
                initialValues={{
                    name: configuration?.name ?? 'Default Pricing',
                    enabled: configuration?.enabled ?? true,
                    cpu_price: configuration?.cpuPrice ?? 0.001,
                    memory_price: configuration?.memoryPrice ?? 0.0001,
                    disk_price: configuration?.diskPrice ?? 0.00001,
                    backup_price: configuration?.backupPrice ?? 0.5,
                    database_price: configuration?.databasePrice ?? 0.25,
                    allocation_price: configuration?.allocationPrice ?? 0.1,
                    small_package_factor: configuration?.smallPackageFactor ?? 1.0,
                    medium_package_factor: configuration?.mediumPackageFactor ?? 1.0,
                    large_package_factor: configuration?.largePackageFactor ?? 0.95,
                    small_package_threshold: configuration?.smallPackageThreshold ?? 2048,
                    large_package_threshold: configuration?.largePackageThreshold ?? 8192,
                    durations: configuration?.relationships.durations ?? [
                        { duration_days: 30, price_factor: 1.0, enabled: true },
                    ],
                }}
                validationSchema={object().shape({
                    name: string().required().max(191).min(3),
                    enabled: boolean(),
                    cpu_price: number().required().min(0),
                    memory_price: number().required().min(0),
                    disk_price: number().required().min(0),
                    backup_price: number().required().min(0),
                    database_price: number().required().min(0),
                    allocation_price: number().required().min(0),
                    small_package_factor: number().required().min(0),
                    medium_package_factor: number().required().min(0),
                    large_package_factor: number().required().min(0),
                    small_package_threshold: number().required().min(0),
                    large_package_threshold: number().required().min(0),
                    durations: array().of(
                        object().shape({
                            duration_days: number().required().min(1),
                            price_factor: number().required().min(0),
                            enabled: boolean(),
                        }),
                    ),
                })}
            >
                {({ values, isSubmitting, isValid }) => (
                    <Form>
                        <div css={tw`grid grid-cols-1 lg:grid-cols-2 gap-4`}>
                            <div css={tw`w-full flex flex-col mr-0 lg:mr-2`}>
                                <AdminBox title={'General Settings'} icon={faCog}>
                                    <FieldRow>
                                        <Field
                                            id={'name'}
                                            name={'name'}
                                            type={'text'}
                                            label={'Configuration Name'}
                                            description={'A name to identify this pricing configuration.'}
                                        />
                                        <div className={'mt-1'}>
                                            <Label htmlFor={'enabled'}>Status</Label>
                                            <div className={'mt-1'}>
                                                <label css={tw`inline-flex items-center mr-2`}>
                                                    <Field
                                                        name={'enabled'}
                                                        type={'radio'}
                                                        value={'false'}
                                                        checked={!enabled}
                                                        onClick={() => setEnabled(false)}
                                                    />
                                                    <span css={tw`text-neutral-300 ml-2`}>Disabled</span>
                                                </label>
                                                <label css={tw`inline-flex items-center ml-2`}>
                                                    <Field
                                                        name={'enabled'}
                                                        type={'radio'}
                                                        value={'true'}
                                                        checked={enabled}
                                                        onClick={() => setEnabled(true)}
                                                    />
                                                    <span css={tw`text-neutral-300 ml-2`}>Enabled</span>
                                                </label>
                                            </div>
                                            <p css={tw`text-neutral-400 text-xs mt-1`}>
                                                When disabled, this pricing configuration cannot be used.
                                            </p>
                                        </div>
                                    </FieldRow>
                                </AdminBox>

                                <AdminBox title={'Resource Prices'} className={'lg:mt-4'} icon={faDollarSign}>
                                    <FieldRow>
                                        <Field
                                            id={'cpu_price'}
                                            name={'cpu_price'}
                                            type={'text'}
                                            label={'CPU Price (per %)'}
                                            description={'Price per 1% CPU allocation.'}
                                        />
                                        <Field
                                            id={'memory_price'}
                                            name={'memory_price'}
                                            type={'text'}
                                            label={'Memory Price (per MB)'}
                                            description={'Price per MB of memory.'}
                                        />
                                        <Field
                                            id={'disk_price'}
                                            name={'disk_price'}
                                            type={'text'}
                                            label={'Disk Price (per MB)'}
                                            description={'Price per MB of disk space.'}
                                        />
                                        <Field
                                            id={'backup_price'}
                                            name={'backup_price'}
                                            type={'text'}
                                            label={'Backup Price'}
                                            description={'Price per backup slot.'}
                                        />
                                        <Field
                                            id={'database_price'}
                                            name={'database_price'}
                                            type={'text'}
                                            label={'Database Price'}
                                            description={'Price per database.'}
                                        />
                                        <Field
                                            id={'allocation_price'}
                                            name={'allocation_price'}
                                            type={'text'}
                                            label={'Allocation Price'}
                                            description={'Price per port allocation.'}
                                        />
                                    </FieldRow>
                                </AdminBox>
                            </div>

                            <div css={tw`w-full flex flex-col mr-0 lg:mr-2`}>
                                <AdminBox title={'Package Size Factors'} icon={faSliders}>
                                    <FieldRow>
                                        <Field
                                            id={'small_package_threshold'}
                                            name={'small_package_threshold'}
                                            type={'text'}
                                            label={'Small Package Threshold (MB)'}
                                            description={'Memory threshold for small packages.'}
                                        />
                                        <Field
                                            id={'small_package_factor'}
                                            name={'small_package_factor'}
                                            type={'text'}
                                            label={'Small Package Factor'}
                                            description={'Price multiplier for small packages (< threshold).'}
                                        />
                                        <Field
                                            id={'medium_package_factor'}
                                            name={'medium_package_factor'}
                                            type={'text'}
                                            label={'Medium Package Factor'}
                                            description={'Price multiplier for medium packages.'}
                                        />
                                        <Field
                                            id={'large_package_threshold'}
                                            name={'large_package_threshold'}
                                            type={'text'}
                                            label={'Large Package Threshold (MB)'}
                                            description={'Memory threshold for large packages.'}
                                        />
                                        <Field
                                            id={'large_package_factor'}
                                            name={'large_package_factor'}
                                            type={'text'}
                                            label={'Large Package Factor'}
                                            description={'Price multiplier for large packages (> threshold).'}
                                        />
                                    </FieldRow>
                                </AdminBox>

                                <AdminBox title={'Duration Pricing'} className={'lg:mt-4'} icon={faClock}>
                                    <FieldArray name="durations">
                                        {({ push, remove }) => (
                                            <div>
                                                {values.durations && values.durations.length > 0 ? (
                                                    values.durations.map((duration, index) => (
                                                        <div key={index} css={tw`mb-4 p-4 bg-neutral-700 rounded`}>
                                                            <FieldRow>
                                                                <Field
                                                                    id={`durations.${index}.duration_days`}
                                                                    name={`durations.${index}.duration_days`}
                                                                    type={'text'}
                                                                    label={'Duration (Days)'}
                                                                />
                                                                <Field
                                                                    id={`durations.${index}.price_factor`}
                                                                    name={`durations.${index}.price_factor`}
                                                                    type={'text'}
                                                                    label={'Price Factor'}
                                                                />
                                                                <div className={'mt-1'}>
                                                                    <Label>Enabled</Label>
                                                                    <Field
                                                                        name={`durations.${index}.enabled`}
                                                                        type={'checkbox'}
                                                                        css={tw`ml-2`}
                                                                    />
                                                                </div>
                                                                {values.durations.length > 1 && (
                                                                    <Button
                                                                        variant={'danger'}
                                                                        type="button"
                                                                        onClick={() => remove(index)}
                                                                        css={tw`mt-2`}
                                                                    >
                                                                        Remove
                                                                    </Button>
                                                                )}
                                                            </FieldRow>
                                                        </div>
                                                    ))
                                                ) : null}
                                                <Button
                                                    type="button"
                                                    onClick={() =>
                                                        push({ duration_days: 30, price_factor: 1.0, enabled: true })
                                                    }
                                                    css={tw`mt-2`}
                                                >
                                                    Add Duration Option
                                                </Button>
                                            </div>
                                        )}
                                    </FieldArray>
                                </AdminBox>
                            </div>
                        </div>

                        <div css={tw`bg-neutral-700 rounded shadow-md py-2 px-4 mt-6`}>
                            <div css={tw`flex flex-row`}>
                                {configuration && <PricingConfigurationDeleteButton configId={configuration.id} />}
                                <Button type={'submit'} disabled={isSubmitting || !isValid} css={tw`ml-auto`}>
                                    {configuration ? 'Save Changes' : 'Create Configuration'}
                                </Button>
                            </div>
                        </div>
                    </Form>
                )}
            </Formik>
        </AdminContentBlock>
    );
};
