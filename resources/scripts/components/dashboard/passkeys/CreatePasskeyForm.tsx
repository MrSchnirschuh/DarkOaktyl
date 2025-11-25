import { Field, Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import type { KeyedMutator } from 'swr';
import type { Passkey } from '@definitions/user';
import { useState } from 'react';
import { Button } from '@elements/button';
import Input from '@elements/Input';
import FormikFieldWrapper from '@elements/FormikFieldWrapper';
import SpinnerOverlay from '@elements/SpinnerOverlay';
import MessageBox from '@/components/MessageBox';
import tw from 'twin.macro';

import { fetchPasskeyRegistrationOptions, registerPasskey } from '@/api/account/passkeys';
import { createCredential, isWebAuthnSupported } from '@/webauthn';
import { useFlashKey } from '@/plugins/useFlash';

interface Values {
    name: string;
}

interface Props {
    mutate: KeyedMutator<Passkey[] | undefined>;
    limit: number;
    currentCount: number;
}

export default function CreatePasskeyForm({ mutate, limit, currentCount }: Props) {
    const { clearAndAddHttpError, addError } = useFlashKey('account:passkeys');
    const [supported] = useState(() => isWebAuthnSupported());
    const limitReached = currentCount >= limit;

    const submit = async ({ name }: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        if (limitReached || !supported) {
            return;
        }

        clearAndAddHttpError();

        try {
            const { token, options } = await fetchPasskeyRegistrationOptions();
            const credential = await createCredential(options);
            const passkey = await registerPasskey(name, token, credential);

            resetForm();
            await mutate(data => [passkey, ...(data || [])], false);
        } catch (error: any) {
            if (error?.name === 'AbortError') {
                addError('Passkey registration was canceled.');
            } else {
                clearAndAddHttpError(error);
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{ name: '' }}
            validationSchema={object().shape({
                name: string().required('Provide a label for this passkey.'),
            })}
        >
            {({ isSubmitting }) => (
                <Form>
                    <SpinnerOverlay visible={isSubmitting} />
                    {!supported && (
                        <MessageBox title="Browser Not Supported" type="warning" css={tw`mb-4`}>
                            This browser does not support WebAuthn. Please switch to a compatible browser to register a
                            passkey.
                        </MessageBox>
                    )}
                    {limitReached && (
                        <MessageBox title="Limit Reached" type="warning" css={tw`mb-4`}>
                            You have registered the maximum number of passkeys allowed ({limit}). Remove an existing
                            passkey before adding another.
                        </MessageBox>
                    )}
                    <FormikFieldWrapper label="Passkey Name" name="name">
                        <Field
                            name="name"
                            as={Input}
                            placeholder="e.g. MacBook Pro"
                            disabled={!supported || limitReached}
                        />
                    </FormikFieldWrapper>
                    <p css={tw`text-xs text-theme-muted mt-3`}>{`Passkeys registered: ${currentCount}/${limit}`}</p>
                    <div css={tw`flex justify-end mt-6`}>
                        <Button disabled={!supported || limitReached || isSubmitting}>Register Passkey</Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
}
