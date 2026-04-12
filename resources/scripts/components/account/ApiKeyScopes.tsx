import { useState } from 'react';
import tw from 'twin.macro';
import { Form, Formik, FieldArray } from 'formik';
import { Button } from '@/elements/button';
import { Checkbox } from '@/elements/Checkbox';
import asModal from '@/hoc/asModal';
import ModalContext from '@/elements/ModalContext';
import { useContext } from 'react';
import SpinnerOverlay from '@/elements/SpinnerOverlay';
import { Actions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { useStoreActions } from 'easy-peasy';
import updateApiKeyScopes from '@/api/routes/account/updateApiKeyScopes';
import getApiKeyScopes from '@/api/routes/account/getApiKeyScopes';
import { useQuery } from '@tanstack/react-query';

interface Props {
    apiKeyId: string;
    currentScopes: string[];
    onSuccess: () => void;
}

interface ScopeGroup {
    [key: string]: {
        label: string;
        scopes: Record<string, string>;
    };
}

const ApiKeyScopes = ({ apiKeyId, currentScopes, onSuccess }: Props) => {
    const { dismiss } = useContext(ModalContext);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const { data: scopeGroups, isLoading: isLoadingScopes } = useQuery<ScopeGroup>({
        queryKey: ['apiKeyScopes'],
        queryFn: getApiKeyScopes,
        staleTime: 1000 * 60 * 5,
    });

    const handleSubmit = async (values: { scopes: string[] }) => {
        setIsSubmitting(true);
        clearFlashes('account:api-key-scopes');

        try {
            await updateApiKeyScopes(apiKeyId, values.scopes);
            onSuccess();
            dismiss();
        } catch (error) {
            clearAndAddHttpError({ key: 'account:api-key-scopes', error });
        } finally {
            setIsSubmitting(false);
        }
    };

    if (isLoadingScopes) {
        return (
            <div css={tw`relative min-h-[200px]`}>
                <SpinnerOverlay visible={true} />
            </div>
        );
    }

    if (!scopeGroups) {
        return (
            <div css={tw`text-center py-4 text-theme-secondary`}>
                Fehler beim Laden der Scopes.
            </div>
        );
    }

    return (
        <Formik initialValues={{ scopes: currentScopes }} onSubmit={handleSubmit}>
            {({ values }) => (
                <Form css={tw`relative`}>
                    <SpinnerOverlay visible={isSubmitting} />

                    <h3 css={tw`text-xl font-medium mb-4`}>API Key Berechtigungen</h3>
                    <p css={tw`text-sm text-theme-secondary mb-4`}>
                        Wähle die Scopes aus, die dieser API-Key haben soll. Wenn keine Scopes ausgewählt sind, hat der Key volle Berechtigungen (legacy-Verhalten).
                    </p>

                    <FieldArray name="scopes">
                        {({ push, remove }) => (
                            <div css={tw`space-y-4 max-h-[400px] overflow-y-auto pr-2`}>
                                {Object.entries(scopeGroups).map(([groupKey, group]) => (
                                    <div key={groupKey} css={tw`border border-theme-border rounded-lg p-3`}>
                                        <h4 css={tw`font-medium mb-3 text-theme-primary`}>{group.label}</h4>
                                        <div css={tw`grid grid-cols-1 sm:grid-cols-2 gap-2`}>
                                            {Object.entries(group.scopes).map(([scopeKey, scopeLabel]) => {
                                                const isChecked = values.scopes.includes(scopeKey);
                                                return (
                                                    <label
                                                        key={scopeKey}
                                                        css={tw`flex items-center space-x-2 p-2 rounded hover:bg-theme-bg-secondary cursor-pointer`}
                                                    >
                                                        <Checkbox
                                                            checked={isChecked}
                                                            onChange={() => {
                                                                if (isChecked) {
                                                                    const index = values.scopes.indexOf(scopeKey);
                                                                    if (index > -1) remove(index);
                                                                } else {
                                                                    push(scopeKey);
                                                                }
                                                            }}
                                                        />
                                                        <span css={tw`text-sm`}>
                                                            <code css={tw`text-xs text-theme-secondary mr-1`}>{scopeKey}</code>
                                                            {scopeLabel}
                                                        </span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </FieldArray>

                    <div css={tw`flex justify-end space-x-3 mt-6 pt-4 border-t border-theme-border`}>
                        <Button type="button" variant="secondary" onClick={() => dismiss()}>
                            Abbrechen
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            Speichern
                        </Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

ApiKeyScopes.displayName = 'ApiKeyScopes';

export default asModal<Props>({
    closeOnEscape: !false,
    closeOnBackground: !false,
})(ApiKeyScopes);
