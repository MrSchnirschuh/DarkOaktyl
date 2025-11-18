import { Form, Formik } from 'formik';
import { useNavigate } from 'react-router-dom';
import AdminContentBlock from '@elements/AdminContentBlock';
import AdminBox from '@elements/AdminBox';
import Field, { FieldRow, TextareaField } from '@elements/Field';
import { Button } from '@elements/button';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { createBillingTerm, updateBillingTerm } from '@/api/admin/billing/billingTerms';
import type { BillingTerm } from '@/api/definitions/admin';
import { faClock, faInfoCircle } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { boolean, number, object, string } from 'yup';
import BillingTermDeleteButton from './BillingTermDeleteButton';
import type { BillingTermValues } from '@/api/admin/billing/types';

interface FormValues {
    name: string;
    slug: string;
    durationDays: string;
    multiplier: string;
    isActive: boolean;
    isDefault: boolean;
    sortOrder: string;
    metadata: string;
}

const buildInitialValues = (term?: BillingTerm): FormValues => {
    if (!term) {
        return {
            name: '',
            slug: '',
            durationDays: '30',
            multiplier: '1.0000',
            isActive: true,
            isDefault: false,
            sortOrder: '0',
            metadata: '',
        };
    }

    return {
        name: term.name,
        slug: term.slug ?? '',
        durationDays: String(term.durationDays),
        multiplier: term.multiplier.toFixed(4),
        isActive: term.isActive,
        isDefault: term.isDefault,
        sortOrder: String(term.sortOrder ?? 0),
        metadata: term.metadata ? JSON.stringify(term.metadata, null, 2) : '',
    };
};

const normalizeMetadata = (input: string): Record<string, unknown> | null => {
    if (!input || input.trim().length === 0) {
        return null;
    }

    try {
        return JSON.parse(input);
    } catch (error) {
        throw new Error('Metadata must be valid JSON.');
    }
};

const toPayload = (values: FormValues): BillingTermValues => {
    const base: BillingTermValues = {
        name: values.name.trim(),
        slug: values.slug.trim() !== '' ? values.slug.trim().toLowerCase() : null,
        durationDays: Number(values.durationDays || 0),
        multiplier: Number(values.multiplier || 0),
        isActive: values.isActive,
        isDefault: values.isDefault,
        sortOrder: values.sortOrder.trim() !== '' ? Number(values.sortOrder) : null,
        metadata: undefined,
    };

    if (values.metadata.trim() !== '') {
        base.metadata = normalizeMetadata(values.metadata) ?? null;
    } else {
        base.metadata = null;
    }

    return base;
};

const validationSchema = object().shape({
    name: string().required('Name is required').max(191),
    slug: string()
        .nullable()
        .transform(value => (value === undefined ? value : value.trim()))
        .matches(/^[a-z0-9_-]*$/, 'Only lowercase letters, numbers, dashes, and underscores allowed')
        .max(191),
    durationDays: number().typeError('Duration must be a number').min(1, 'Minimum duration is 1 day').required(),
    multiplier: number().typeError('Multiplier must be a number').min(0, 'Multiplier must be positive').required(),
    isActive: boolean(),
    isDefault: boolean(),
    sortOrder: string()
        .transform(value => (value === undefined ? value : value.trim()))
        .nullable(),
    metadata: string()
        .test('valid-json', 'Metadata must be valid JSON', value => {
            if (!value || value.trim().length === 0) {
                return true;
            }

            try {
                JSON.parse(value);
                return true;
            } catch (error) {
                return false;
            }
        })
        .nullable(),
});

export default ({ term }: { term?: BillingTerm }) => {
    const navigate = useNavigate();
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const handleSubmit = (values: FormValues, { setSubmitting }: { setSubmitting: (value: boolean) => void }) => {
        clearFlashes('admin:billing:terms');

        let payload: BillingTermValues;

        try {
            payload = toPayload(values);
        } catch (error: any) {
            clearAndAddHttpError({
                key: 'admin:billing:terms',
                error: {
                    response: {
                        data: {
                            errors: {
                                metadata: [error.message],
                            },
                        },
                    },
                },
            });
            setSubmitting(false);
            return;
        }

        const action = term
            ? updateBillingTerm(term.uuid, payload).then(() => navigate('/admin/billing/terms'))
            : createBillingTerm(payload).then(created => navigate(`/admin/billing/terms/${created.uuid}`));

        action
            .catch(error => clearAndAddHttpError({ key: 'admin:billing:terms', error }))
            .finally(() => setSubmitting(false));
    };

    return (
        <AdminContentBlock title={term ? 'Edit Billing Term' : 'Create Billing Term'}>
            <div className={'w-full flex flex-row items-center m-8'}>
                <FontAwesomeIcon icon={faClock} className={'w-8 h-8 mr-4'} />
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                    <h2 className={'text-2xl text-theme-primary font-header font-medium'}>
                        {term ? term.name : 'New Billing Term'}
                    </h2>
                    <p
                        className={
                            'hidden lg:block text-base text-theme-muted whitespace-nowrap overflow-hidden overflow-ellipsis'
                        }
                    >
                        {term ? term.uuid : 'Define runtime tiers and multiplier factors for billing.'}
                    </p>
                </div>
                {term && (
                    <div className={'ml-auto'}>
                        <BillingTermDeleteButton term={term} />
                    </div>
                )}
            </div>
            <Formik
                initialValues={buildInitialValues(term)}
                validationSchema={validationSchema}
                onSubmit={handleSubmit}
            >
                {({ isSubmitting }) => (
                    <Form>
                        <FlashMessageRender byKey={'admin:billing:terms'} className={'mb-4'} />
                        <div className={'grid grid-cols-1 lg:grid-cols-2 gap-6'}>
                            <div className={'w-full flex flex-col'}>
                                <AdminBox title={'Details'} icon={faInfoCircle} isLoading={isSubmitting}>
                                    <FieldRow>
                                        <Field
                                            id={'name'}
                                            name={'name'}
                                            type={'text'}
                                            label={'Name'}
                                            description={'Admin-facing label for this term.'}
                                        />
                                        <Field
                                            id={'slug'}
                                            name={'slug'}
                                            type={'text'}
                                            label={'Slug'}
                                            description={
                                                'Optional identifier used in APIs. Leave blank to auto-generate.'
                                            }
                                        />
                                        <Field
                                            id={'durationDays'}
                                            name={'durationDays'}
                                            type={'number'}
                                            label={'Duration (days)'}
                                            description={'Number of days covered by this term.'}
                                        />
                                        <Field
                                            id={'multiplier'}
                                            name={'multiplier'}
                                            type={'number'}
                                            step={'0.0001'}
                                            label={'Multiplier'}
                                            description={'Factor applied to the base price for this term.'}
                                        />
                                        <Field
                                            id={'sortOrder'}
                                            name={'sortOrder'}
                                            type={'number'}
                                            label={'Sort Order'}
                                            description={'Optional ordering hint for matrix views.'}
                                        />
                                    </FieldRow>
                                    <div className={'mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4'}>
                                        <label className={'inline-flex items-center space-x-3'}>
                                            <Field id={'isActive'} name={'isActive'} type={'checkbox'} />
                                            <span className={'text-sm text-theme-secondary'}>Active</span>
                                        </label>
                                        <label className={'inline-flex items-center space-x-3'}>
                                            <Field id={'isDefault'} name={'isDefault'} type={'checkbox'} />
                                            <span className={'text-sm text-theme-secondary'}>Default term</span>
                                        </label>
                                    </div>
                                </AdminBox>
                            </div>
                            <div className={'w-full flex flex-col'}>
                                <AdminBox title={'Metadata'} icon={faInfoCircle} isLoading={isSubmitting}>
                                    <p className={'text-xs text-theme-muted mb-4'}>
                                        Optional JSON metadata used for extensions or automation. Leave blank to ignore.
                                    </p>
                                    <TextareaField
                                        id={'metadata'}
                                        name={'metadata'}
                                        label={'Metadata JSON'}
                                        rows={8}
                                        description={'Paste a JSON object with additional term configuration.'}
                                    />
                                </AdminBox>
                            </div>
                        </div>
                        <div className={'mt-6 flex justify-end'}>
                            <Button type={'submit'} disabled={isSubmitting}>
                                {term ? 'Save changes' : 'Create term'}
                            </Button>
                        </div>
                    </Form>
                )}
            </Formik>
        </AdminContentBlock>
    );
};
