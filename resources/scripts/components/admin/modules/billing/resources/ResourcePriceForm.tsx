import { Form, Formik, FieldArray } from 'formik';
import { useNavigate } from 'react-router-dom';
import AdminContentBlock from '@elements/AdminContentBlock';
import AdminBox from '@elements/AdminBox';
import Field, { FieldRow, TextareaField } from '@elements/Field';
import { Button } from '@elements/button';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { createResourcePrice, updateResourcePrice } from '@/api/admin/billing/resourcePrices';
import type { ResourcePrice } from '@/api/definitions/admin';
import { faCubes, faLayerGroup, faSlidersH } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { array, boolean, number, object, string } from 'yup';
import Select from '@elements/Select';
import ResourcePriceDeleteButton from './ResourcePriceDeleteButton';
import type { ResourcePriceValues } from '@/api/admin/billing/types';

interface RuleFormValue {
    id?: number;
    threshold: string;
    multiplier: string;
    mode: 'multiplier' | 'surcharge';
    label: string;
}

interface FormValues {
    resource: string;
    displayName: string;
    description: string;
    unit: string;
    baseQuantity: string;
    price: string;
    currency: string;
    minQuantity: string;
    maxQuantity: string;
    defaultQuantity: string;
    stepQuantity: string;
    isVisible: boolean;
    isMetered: boolean;
    sortOrder: string;
    scalingRules: RuleFormValue[];
}

const buildInitialValues = (resource?: ResourcePrice): FormValues => {
    if (!resource) {
        return {
            resource: '',
            displayName: '',
            description: '',
            unit: '',
            baseQuantity: '1',
            price: '0.0000',
            currency: '',
            minQuantity: '0',
            maxQuantity: '',
            defaultQuantity: '0',
            stepQuantity: '1',
            isVisible: true,
            isMetered: false,
            sortOrder: '0',
            scalingRules: [],
        };
    }

    return {
        resource: resource.resource,
        displayName: resource.displayName,
        description: resource.description ?? '',
        unit: resource.unit ?? '',
        baseQuantity: String(resource.baseQuantity),
        price: resource.price.toFixed(4),
        currency: resource.currency ?? '',
        minQuantity: String(resource.minQuantity),
        maxQuantity:
            resource.maxQuantity !== null && resource.maxQuantity !== undefined ? String(resource.maxQuantity) : '',
        defaultQuantity: String(resource.defaultQuantity),
        stepQuantity: String(resource.stepQuantity),
        isVisible: resource.isVisible,
        isMetered: resource.isMetered,
        sortOrder: String(resource.sortOrder ?? 0),
        scalingRules: resource.scalingRules.map(rule => ({
            id: rule.id,
            threshold: String(rule.threshold),
            multiplier: rule.multiplier.toString(),
            mode: rule.mode,
            label: rule.label ?? '',
        })),
    };
};

const toPayload = (values: FormValues): ResourcePriceValues => ({
    resource: values.resource.trim(),
    displayName: values.displayName.trim(),
    description: values.description.trim() !== '' ? values.description.trim() : null,
    unit: values.unit.trim() !== '' ? values.unit.trim() : null,
    baseQuantity: Number(values.baseQuantity || 0),
    price: Number(values.price || 0),
    currency: values.currency.trim() !== '' ? values.currency.trim().toUpperCase() : null,
    minQuantity: Number(values.minQuantity || 0),
    maxQuantity: values.maxQuantity.trim() !== '' ? Number(values.maxQuantity) : null,
    defaultQuantity: Number(values.defaultQuantity || 0),
    stepQuantity: Number(values.stepQuantity || 1),
    isVisible: values.isVisible,
    isMetered: values.isMetered,
    sortOrder: values.sortOrder.trim() !== '' ? Number(values.sortOrder) : null,
    metadata: null,
    scalingRules: values.scalingRules.map(rule => ({
        id: rule.id,
        threshold: Number(rule.threshold || 0),
        multiplier: Number(rule.multiplier || 0),
        mode: rule.mode,
        label: rule.label.trim() !== '' ? rule.label.trim() : null,
    })),
});

const validationSchema = object().shape({
    resource: string()
        .required('Resource key is required')
        .max(64)
        .matches(/^[a-z0-9_-]+$/, 'Use lowercase letters, numbers, dashes or underscores'),
    displayName: string().required('Display name is required').max(191),
    description: string().nullable(),
    unit: string().nullable().max(32),
    baseQuantity: number().typeError('Base quantity must be a number').min(1).required(),
    price: number().typeError('Price must be a number').min(0).required(),
    currency: string()
        .transform(value => (value === undefined ? value : value.trim()))
        .nullable()
        .test('currency-length', 'Currency must be a 3-letter code', value => !value || value.length === 3),
    minQuantity: number().typeError('Minimum quantity must be a number').min(0).required(),
    maxQuantity: string()
        .transform(value => (value === undefined ? value : value.trim()))
        .nullable(),
    defaultQuantity: number().typeError('Default quantity must be a number').min(0).required(),
    stepQuantity: number().typeError('Step quantity must be a number').min(1).required(),
    isVisible: boolean(),
    isMetered: boolean(),
    sortOrder: string()
        .transform(value => (value === undefined ? value : value.trim()))
        .nullable(),
    scalingRules: array().of(
        object().shape({
            threshold: number().typeError('Threshold must be a number').min(0).required(),
            multiplier: number().typeError('Multiplier must be a number').required(),
            mode: string().oneOf(['multiplier', 'surcharge'] as const),
            label: string().nullable().max(191),
        }),
    ),
});

export default ({ resource }: { resource?: ResourcePrice }) => {
    const navigate = useNavigate();
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const handleSubmit = (values: FormValues, { setSubmitting }: { setSubmitting: (value: boolean) => void }) => {
        clearFlashes('admin:billing:resources');

        const payload = toPayload(values);

        const action = resource
            ? updateResourcePrice(resource.uuid, payload).then(() => navigate('/admin/billing/pricing'))
            : createResourcePrice(payload).then(created => navigate(`/admin/billing/pricing/${created.uuid}`));

        action
            .catch(error => clearAndAddHttpError({ key: 'admin:billing:resources', error }))
            .finally(() => {
                setSubmitting(false);
            });
    };

    return (
        <AdminContentBlock title={resource ? 'Edit Resource Price' : 'Create Resource Price'}>
            <div className={'w-full flex flex-row items-center m-8'}>
                <FontAwesomeIcon icon={faCubes} className={'w-8 h-8 mr-4'} />
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                    <h2 className={'text-2xl text-neutral-50 font-header font-medium'}>
                        {resource ? resource.displayName : 'New Resource'}
                    </h2>
                    <p
                        className={
                            'hidden lg:block text-base text-neutral-400 whitespace-nowrap overflow-hidden overflow-ellipsis'
                        }
                    >
                        {resource ? resource.uuid : 'Define pricing for a reusable resource token.'}
                    </p>
                </div>
                {resource && (
                    <div className={'ml-auto'}>
                        <ResourcePriceDeleteButton resource={resource} />
                    </div>
                )}
            </div>
            <Formik
                initialValues={buildInitialValues(resource)}
                validationSchema={validationSchema}
                onSubmit={handleSubmit}
            >
                {({ values, isSubmitting }) => (
                    <Form>
                        <FlashMessageRender byKey={'admin:billing:resources'} className={'mb-4'} />
                        <div className={'grid grid-cols-1 lg:grid-cols-2 gap-6'}>
                            <div className={'w-full flex flex-col'}>
                                <AdminBox title={'Resource Details'} icon={faLayerGroup} isLoading={isSubmitting}>
                                    <FieldRow>
                                        <Field
                                            id={'resource'}
                                            name={'resource'}
                                            type={'text'}
                                            label={'Resource Key'}
                                            description={'Internal identifier, e.g. cpu, memory, disk.'}
                                            disabled={Boolean(resource)}
                                        />
                                        <Field
                                            id={'displayName'}
                                            name={'displayName'}
                                            type={'text'}
                                            label={'Display Name'}
                                            description={'Name shown in the admin and storefront.'}
                                        />
                                        <TextareaField
                                            id={'description'}
                                            name={'description'}
                                            label={'Description'}
                                            description={
                                                'Optional description to help other admins understand this resource.'
                                            }
                                            rows={3}
                                        />
                                        <Field
                                            id={'unit'}
                                            name={'unit'}
                                            type={'text'}
                                            label={'Unit'}
                                            description={'Optional unit label (e.g. MB, GB, vCPU).'}
                                        />
                                    </FieldRow>
                                </AdminBox>
                                <AdminBox
                                    title={'Pricing'}
                                    icon={faSlidersH}
                                    className={'mt-6'}
                                    isLoading={isSubmitting}
                                >
                                    <FieldRow>
                                        <Field
                                            id={'baseQuantity'}
                                            name={'baseQuantity'}
                                            type={'number'}
                                            label={'Base Quantity'}
                                            description={'Multiplier base used for pricing calculations.'}
                                        />
                                        <Field
                                            id={'price'}
                                            name={'price'}
                                            type={'number'}
                                            step={'0.0001'}
                                            label={'Base Price'}
                                            description={'Price charged per base quantity.'}
                                        />
                                        <Field
                                            id={'currency'}
                                            name={'currency'}
                                            type={'text'}
                                            label={'Currency'}
                                            description={
                                                '3-letter ISO currency code. Leave blank to use panel default.'
                                            }
                                        />
                                        <Field
                                            id={'minQuantity'}
                                            name={'minQuantity'}
                                            type={'number'}
                                            label={'Minimum Quantity'}
                                            description={'Smallest quantity allowed in the configurator.'}
                                        />
                                        <Field
                                            id={'maxQuantity'}
                                            name={'maxQuantity'}
                                            type={'number'}
                                            label={'Maximum Quantity'}
                                            description={'Maximum quantity allowed. Leave blank for unlimited.'}
                                        />
                                        <Field
                                            id={'defaultQuantity'}
                                            name={'defaultQuantity'}
                                            type={'number'}
                                            label={'Default Quantity'}
                                            description={'Pre-filled value for new configurations.'}
                                        />
                                        <Field
                                            id={'stepQuantity'}
                                            name={'stepQuantity'}
                                            type={'number'}
                                            label={'Step Increment'}
                                            description={'Quantity increments when adjusting sliders.'}
                                        />
                                        <Field
                                            id={'sortOrder'}
                                            name={'sortOrder'}
                                            type={'number'}
                                            label={'Sort Order'}
                                            description={'Optional ordering hint for lists.'}
                                        />
                                    </FieldRow>
                                    <div className={'mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4'}>
                                        <label className={'inline-flex items-center space-x-3'}>
                                            <Field id={'isVisible'} name={'isVisible'} type={'checkbox'} />
                                            <span className={'text-sm text-neutral-200'}>Visible in admin UI</span>
                                        </label>
                                        <label className={'inline-flex items-center space-x-3'}>
                                            <Field id={'isMetered'} name={'isMetered'} type={'checkbox'} />
                                            <span className={'text-sm text-neutral-200'}>
                                                Metered resource (bill on usage)
                                            </span>
                                        </label>
                                    </div>
                                </AdminBox>
                            </div>
                            <div className={'w-full flex flex-col'}>
                                <AdminBox title={'Scaling Rules'} icon={faSlidersH} isLoading={isSubmitting}>
                                    <p className={'text-xs text-neutral-400 mb-4'}>
                                        Define optional tiers that adjust pricing once a threshold is exceeded. Leave
                                        empty for flat pricing.
                                    </p>
                                    <FieldArray
                                        name={'scalingRules'}
                                        render={arrayHelpers => (
                                            <div className={'space-y-4'}>
                                                {values.scalingRules.length === 0 && (
                                                    <p className={'text-sm text-neutral-500'}>
                                                        No scaling rules configured.
                                                    </p>
                                                )}
                                                {values.scalingRules.map((rule, index) => (
                                                    <div
                                                        key={rule.id ?? index}
                                                        className={'rounded border border-neutral-700 p-4 space-y-3'}
                                                    >
                                                        <div className={'grid grid-cols-1 sm:grid-cols-2 gap-4'}>
                                                            <Field
                                                                id={`scalingRules.${index}.threshold`}
                                                                name={`scalingRules.${index}.threshold`}
                                                                type={'number'}
                                                                label={'Threshold'}
                                                                description={
                                                                    'Quantity at which this rule becomes active.'
                                                                }
                                                            />
                                                            <Field
                                                                id={`scalingRules.${index}.multiplier`}
                                                                name={`scalingRules.${index}.multiplier`}
                                                                type={'number'}
                                                                step={'0.0001'}
                                                                label={'Multiplier / Surcharge'}
                                                                description={'Applied value depending on mode.'}
                                                            />
                                                        </div>
                                                        <div className={'grid grid-cols-1 sm:grid-cols-2 gap-4'}>
                                                            <div>
                                                                <label className={'text-sm text-neutral-300'}>
                                                                    Mode
                                                                </label>
                                                                <Select
                                                                    value={rule.mode}
                                                                    onChange={event =>
                                                                        arrayHelpers.replace(index, {
                                                                            ...rule,
                                                                            mode: event.currentTarget
                                                                                .value as RuleFormValue['mode'],
                                                                        })
                                                                    }
                                                                    className={'mt-1'}
                                                                >
                                                                    <option value={'multiplier'}>Multiplier</option>
                                                                    <option value={'surcharge'}>Surcharge</option>
                                                                </Select>
                                                                <p className={'text-xs text-neutral-500 mt-2'}>
                                                                    Multiplier scales the price; surcharge adds a fixed
                                                                    amount.
                                                                </p>
                                                            </div>
                                                            <Field
                                                                id={`scalingRules.${index}.label`}
                                                                name={`scalingRules.${index}.label`}
                                                                type={'text'}
                                                                label={'Label'}
                                                                description={
                                                                    'Optional admin-facing label for this tier.'
                                                                }
                                                            />
                                                        </div>
                                                        <div className={'flex justify-end'}>
                                                            <Button.Text
                                                                type={'button'}
                                                                onClick={() => arrayHelpers.remove(index)}
                                                            >
                                                                Remove rule
                                                            </Button.Text>
                                                        </div>
                                                    </div>
                                                ))}
                                                <Button
                                                    type={'button'}
                                                    onClick={() =>
                                                        arrayHelpers.push({
                                                            threshold: '0',
                                                            multiplier: '1',
                                                            mode: 'multiplier',
                                                            label: '',
                                                        })
                                                    }
                                                >
                                                    Add scaling rule
                                                </Button>
                                            </div>
                                        )}
                                    />
                                </AdminBox>
                            </div>
                        </div>
                        <div className={'mt-6 flex justify-end'}>
                            <Button type={'submit'} disabled={isSubmitting}>
                                {resource ? 'Save changes' : 'Create resource'}
                            </Button>
                        </div>
                    </Form>
                )}
            </Formik>
        </AdminContentBlock>
    );
};
