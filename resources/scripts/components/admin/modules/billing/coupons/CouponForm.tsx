import { Form, Formik } from 'formik';
import { useNavigate } from 'react-router-dom';
import AdminContentBlock from '@elements/AdminContentBlock';
import AdminBox from '@elements/AdminBox';
import Field, { FieldRow, TextareaField } from '@elements/Field';
import { Button } from '@elements/button';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import { createCoupon, updateCoupon } from '@/api/admin/billing/coupons';
import type { Coupon } from '@/api/definitions/admin';
import { faTicketAlt, faInfoCircle, faClock, faGift } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { boolean, object, string } from 'yup';
import Select from '@elements/Select';
import CouponDeleteButton from './CouponDeleteButton';
import type { CouponValues } from '@/api/admin/billing/types';

interface FormValues {
    code: string;
    name: string;
    description: string;
    type: 'amount' | 'percentage' | 'resource' | 'duration';
    value: string;
    percentage: string;
    maxUsages: string;
    perUserLimit: string;
    appliesToTermId: string;
    isActive: boolean;
    startsAt: string;
    expiresAt: string;
    metadata: string;
}

const buildInitialValues = (coupon?: Coupon): FormValues => {
    if (!coupon) {
        return {
            code: '',
            name: '',
            description: '',
            type: 'percentage',
            value: '',
            percentage: '10',
            maxUsages: '',
            perUserLimit: '',
            appliesToTermId: '',
            isActive: true,
            startsAt: '',
            expiresAt: '',
            metadata: '',
        };
    }

    return {
        code: coupon.code,
        name: coupon.name,
        description: coupon.description ?? '',
        type: coupon.type,
        value: coupon.value ? coupon.value.toString() : '',
        percentage: coupon.percentage ? coupon.percentage.toString() : '',
        maxUsages: coupon.maxUsages ? coupon.maxUsages.toString() : '',
        perUserLimit: coupon.perUserLimit ? coupon.perUserLimit.toString() : '',
        appliesToTermId: coupon.appliesToTermId ? coupon.appliesToTermId.toString() : '',
        isActive: coupon.isActive,
        startsAt: coupon.startsAt ? coupon.startsAt.toISOString().slice(0, 16) : '',
        expiresAt: coupon.expiresAt ? coupon.expiresAt.toISOString().slice(0, 16) : '',
        metadata: coupon.metadata ? JSON.stringify(coupon.metadata, null, 2) : '',
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

const toPayload = (values: FormValues): CouponValues => {
    const base: CouponValues = {
        code: values.code.trim(),
        name: values.name.trim(),
        description: values.description.trim() !== '' ? values.description.trim() : null,
        type: values.type,
        value: undefined,
        percentage: undefined,
        maxUsages: values.maxUsages.trim() !== '' ? Number(values.maxUsages) : null,
        perUserLimit: values.perUserLimit.trim() !== '' ? Number(values.perUserLimit) : null,
        appliesToTermId: values.appliesToTermId.trim() !== '' ? Number(values.appliesToTermId) : null,
        isActive: values.isActive,
        startsAt: values.startsAt.trim() !== '' ? values.startsAt : null,
        expiresAt: values.expiresAt.trim() !== '' ? values.expiresAt : null,
        metadata: undefined,
    };

    // Set value or percentage based on type
    if (values.type === 'amount' || values.type === 'resource') {
        base.value = values.value.trim() !== '' ? Number(values.value) : null;
    } else if (values.type === 'percentage') {
        base.percentage = values.percentage.trim() !== '' ? Number(values.percentage) : null;
    } else if (values.type === 'duration') {
        base.value = values.value.trim() !== '' ? Number(values.value) : null;
    }

    if (values.metadata.trim() !== '') {
        base.metadata = normalizeMetadata(values.metadata) ?? null;
    } else {
        base.metadata = null;
    }

    return base;
};

const validationSchema = object().shape({
    code: string()
        .required('Code is required')
        .max(32)
        .matches(/^[A-Z0-9_-]+$/, 'Use uppercase letters, numbers, dashes or underscores'),
    name: string().required('Name is required').max(191),
    description: string().nullable(),
    type: string().oneOf(['amount', 'percentage', 'resource', 'duration']),
    value: string()
        .when('type', {
            is: (val: string) => val === 'amount' || val === 'resource' || val === 'duration',
            then: () => string().required('Value is required for this coupon type'),
            otherwise: () => string(),
        })
        .test('valid-number', 'Value must be a valid number', function (value) {
            const { type } = this.parent;
            if ((type === 'amount' || type === 'resource' || type === 'duration') && value) {
                return !isNaN(Number(value)) && Number(value) >= 0;
            }
            return true;
        }),
    percentage: string()
        .when('type', {
            is: 'percentage',
            then: () => string().required('Percentage is required'),
            otherwise: () => string(),
        })
        .test('valid-percentage', 'Percentage must be between 0 and 100', function (value) {
            const { type } = this.parent;
            if (type === 'percentage' && value) {
                const num = Number(value);
                return !isNaN(num) && num >= 0 && num <= 100;
            }
            return true;
        }),
    maxUsages: string()
        .transform(value => (value === undefined ? value : value.trim()))
        .nullable()
        .test('valid-number', 'Max usages must be a positive number', value => {
            if (!value || value.trim() === '') return true;
            return !isNaN(Number(value)) && Number(value) > 0;
        }),
    perUserLimit: string()
        .transform(value => (value === undefined ? value : value.trim()))
        .nullable()
        .test('valid-number', 'Per user limit must be a positive number', value => {
            if (!value || value.trim() === '') return true;
            return !isNaN(Number(value)) && Number(value) > 0;
        }),
    appliesToTermId: string()
        .transform(value => (value === undefined ? value : value.trim()))
        .nullable(),
    isActive: boolean(),
    startsAt: string().nullable(),
    expiresAt: string().nullable(),
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

export default ({ coupon }: { coupon?: Coupon }) => {
    const navigate = useNavigate();
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const handleSubmit = (values: FormValues, { setSubmitting }: { setSubmitting: (value: boolean) => void }) => {
        clearFlashes('admin:billing:coupons');

        let payload: CouponValues;

        try {
            payload = toPayload(values);
        } catch (error: any) {
            clearAndAddHttpError({
                key: 'admin:billing:coupons',
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

        const action = coupon
            ? updateCoupon(coupon.uuid, payload).then(() => navigate('/admin/billing/coupons'))
            : createCoupon(payload).then(created => navigate(`/admin/billing/coupons/${created.uuid}`));

        action
            .catch(error => clearAndAddHttpError({ key: 'admin:billing:coupons', error }))
            .finally(() => setSubmitting(false));
    };

    return (
        <AdminContentBlock title={coupon ? 'Edit Coupon' : 'Create Coupon'}>
            <div className={'w-full flex flex-row items-center m-8'}>
                <FontAwesomeIcon icon={faTicketAlt} className={'w-8 h-8 mr-4'} />
                <div className={'flex flex-col flex-shrink'} style={{ minWidth: '0' }}>
                    <h2 className={'text-2xl text-theme-primary font-header font-medium'}>
                        {coupon ? coupon.name : 'New Coupon'}
                    </h2>
                    <p
                        className={
                            'hidden lg:block text-base text-theme-muted whitespace-nowrap overflow-hidden overflow-ellipsis'
                        }
                    >
                        {coupon ? coupon.uuid : 'Create discount codes and promotional offers for customers.'}
                    </p>
                </div>
                {coupon && (
                    <div className={'ml-auto'}>
                        <CouponDeleteButton coupon={coupon} />
                    </div>
                )}
            </div>
            <Formik
                initialValues={buildInitialValues(coupon)}
                validationSchema={validationSchema}
                onSubmit={handleSubmit}
            >
                {({ values, isSubmitting }) => (
                    <Form>
                        <FlashMessageRender byKey={'admin:billing:coupons'} className={'mb-4'} />
                        <div className={'grid grid-cols-1 lg:grid-cols-2 gap-6'}>
                            <div className={'w-full flex flex-col'}>
                                <AdminBox title={'Basic Information'} icon={faInfoCircle} isLoading={isSubmitting}>
                                    <FieldRow>
                                        <Field
                                            id={'code'}
                                            name={'code'}
                                            type={'text'}
                                            label={'Code'}
                                            description={'Unique coupon code customers will enter.'}
                                        />
                                        <Field
                                            id={'name'}
                                            name={'name'}
                                            type={'text'}
                                            label={'Name'}
                                            description={'Admin-facing display name for this coupon.'}
                                        />
                                        <TextareaField
                                            id={'description'}
                                            name={'description'}
                                            label={'Description'}
                                            description={'Optional description shown to customers.'}
                                            rows={3}
                                        />
                                    </FieldRow>
                                </AdminBox>

                                <AdminBox
                                    title={'Discount Settings'}
                                    icon={faGift}
                                    className={'mt-6'}
                                    isLoading={isSubmitting}
                                >
                                    <FieldRow>
                                        <div>
                                            <label className={'text-sm text-theme-secondary mb-2 block'}>Type</label>
                                            <Field id={'type'} name={'type'} as={Select}>
                                                <option value={'percentage'}>Percentage Discount</option>
                                                <option value={'amount'}>Fixed Amount Discount</option>
                                                <option value={'resource'}>Resource Credit</option>
                                                <option value={'duration'}>Free Time Period</option>
                                            </Field>
                                            <p className={'text-xs text-theme-muted mt-2'}>
                                                Choose the type of discount this coupon provides.
                                            </p>
                                        </div>

                                        {(values.type === 'amount' ||
                                            values.type === 'resource' ||
                                            values.type === 'duration') && (
                                            <Field
                                                id={'value'}
                                                name={'value'}
                                                type={'number'}
                                                step={'0.01'}
                                                label={
                                                    values.type === 'amount'
                                                        ? 'Amount'
                                                        : values.type === 'resource'
                                                        ? 'Resource Value'
                                                        : 'Duration (days)'
                                                }
                                                description={
                                                    values.type === 'amount'
                                                        ? 'Fixed discount amount in currency.'
                                                        : values.type === 'resource'
                                                        ? 'Value of free resources to grant.'
                                                        : 'Number of free days to grant.'
                                                }
                                            />
                                        )}

                                        {values.type === 'percentage' && (
                                            <Field
                                                id={'percentage'}
                                                name={'percentage'}
                                                type={'number'}
                                                min={0}
                                                max={100}
                                                label={'Percentage'}
                                                description={'Percentage discount (0-100%).'}
                                            />
                                        )}

                                        <Field
                                            id={'maxUsages'}
                                            name={'maxUsages'}
                                            type={'number'}
                                            label={'Maximum Uses'}
                                            description={
                                                'Total number of times this coupon can be used. Leave blank for unlimited.'
                                            }
                                        />
                                        <Field
                                            id={'perUserLimit'}
                                            name={'perUserLimit'}
                                            type={'number'}
                                            label={'Per User Limit'}
                                            description={'Maximum uses per customer. Leave blank for unlimited.'}
                                        />
                                        <Field
                                            id={'appliesToTermId'}
                                            name={'appliesToTermId'}
                                            type={'number'}
                                            label={'Applies to Term ID'}
                                            description={'Restrict to specific billing term. Leave blank for any term.'}
                                        />
                                    </FieldRow>
                                    <div className={'mt-4'}>
                                        <label className={'inline-flex items-center space-x-3'}>
                                            <Field id={'isActive'} name={'isActive'} type={'checkbox'} />
                                            <span className={'text-sm text-theme-secondary'}>Active</span>
                                        </label>
                                    </div>
                                </AdminBox>
                            </div>

                            <div className={'w-full flex flex-col'}>
                                <AdminBox title={'Validity Period'} icon={faClock} isLoading={isSubmitting}>
                                    <FieldRow>
                                        <Field
                                            id={'startsAt'}
                                            name={'startsAt'}
                                            type={'datetime-local'}
                                            label={'Start Date'}
                                            description={
                                                'When this coupon becomes valid. Leave blank for immediate activation.'
                                            }
                                        />
                                        <Field
                                            id={'expiresAt'}
                                            name={'expiresAt'}
                                            type={'datetime-local'}
                                            label={'Expiration Date'}
                                            description={'When this coupon expires. Leave blank for no expiration.'}
                                        />
                                    </FieldRow>
                                </AdminBox>

                                <AdminBox
                                    title={'Metadata'}
                                    icon={faInfoCircle}
                                    className={'mt-6'}
                                    isLoading={isSubmitting}
                                >
                                    <p className={'text-xs text-theme-muted mb-4'}>
                                        Optional JSON metadata for custom integrations or tracking information.
                                    </p>
                                    <TextareaField
                                        id={'metadata'}
                                        name={'metadata'}
                                        label={'Metadata JSON'}
                                        rows={8}
                                        description={'Custom data in JSON format.'}
                                    />
                                </AdminBox>
                            </div>
                        </div>
                        <div className={'mt-6 flex justify-end'}>
                            <Button type={'submit'} disabled={isSubmitting}>
                                {coupon ? 'Save changes' : 'Create coupon'}
                            </Button>
                        </div>
                    </Form>
                )}
            </Formik>
        </AdminContentBlock>
    );
};
