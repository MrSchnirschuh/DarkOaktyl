import { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { faEnvelope, faPlus, faTrash, faPencilAlt, faPaperPlane } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

import AdminBox from '@elements/AdminBox';
import { Button } from '@elements/button';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import { getEmailTemplates, deleteEmailTemplate } from '@/api/admin/emails';
import { EmailTemplate } from '@/api/admin/emails/types';

export default () => {
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const [templates, setTemplates] = useState<EmailTemplate[]>([]);
    const [loading, setLoading] = useState(true);

    const loadTemplates = () => {
        setLoading(true);
        getEmailTemplates()
            .then(data => {
                setTemplates(data);
                setLoading(false);
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'emails:templates', error });
                setLoading(false);
            });
    };

    useEffect(() => {
        clearFlashes();
        loadTemplates();
    }, []);

    const handleDelete = (id: number, name: string) => {
        if (!confirm(`Are you sure you want to delete the template "${name}"?`)) {
            return;
        }

        deleteEmailTemplate(id)
            .then(() => {
                addFlash({
                    type: 'success',
                    key: 'emails:templates',
                    message: `Template "${name}" has been deleted.`,
                });
                loadTemplates();
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'emails:templates', error });
            });
    };

    return (
        <>
            <FlashMessageRender byKey={'emails:templates'} css={tw`mb-4`} />

            <div css={tw`flex justify-between items-center mb-4`}>
                <h3 css={tw`text-xl font-semibold text-neutral-50`}>Email Templates</h3>
                <Button.Success>
                    <FontAwesomeIcon icon={faPlus} css={tw`mr-2`} />
                    Create Template
                </Button.Success>
            </div>

            {loading ? (
                <AdminBox css={tw`text-center py-8`}>
                    <p css={tw`text-neutral-400`}>Loading templates...</p>
                </AdminBox>
            ) : templates.length === 0 ? (
                <AdminBox css={tw`text-center py-8`}>
                    <p css={tw`text-neutral-400`}>No email templates found.</p>
                </AdminBox>
            ) : (
                <div css={tw`grid grid-cols-1 gap-4`}>
                    {templates.map(template => (
                        <AdminBox key={template.id} css={tw`flex justify-between items-start`}>
                            <div css={tw`flex-1`}>
                                <div css={tw`flex items-center mb-2`}>
                                    <FontAwesomeIcon icon={faEnvelope} css={tw`text-neutral-400 mr-2`} />
                                    <h4 css={tw`text-lg font-semibold text-neutral-50`}>{template.name}</h4>
                                    {!template.enabled && (
                                        <span css={tw`ml-2 px-2 py-1 text-xs bg-red-500 text-white rounded`}>
                                            Disabled
                                        </span>
                                    )}
                                </div>
                                <p css={tw`text-sm text-neutral-400 mb-2`}>
                                    <strong>Key:</strong> {template.key}
                                </p>
                                <p css={tw`text-sm text-neutral-400 mb-2`}>
                                    <strong>Subject:</strong> {template.subject}
                                </p>
                                {template.variables && template.variables.length > 0 && (
                                    <p css={tw`text-sm text-neutral-500`}>
                                        <strong>Variables:</strong> {template.variables.join(', ')}
                                    </p>
                                )}
                            </div>
                            <div css={tw`flex gap-2 ml-4`}>
                                <Button.Text size={Button.Sizes.Small} css={tw`text-blue-400 hover:text-blue-300`}>
                                    <FontAwesomeIcon icon={faPaperPlane} css={tw`mr-1`} />
                                    Test
                                </Button.Text>
                                <Button.Text size={Button.Sizes.Small} css={tw`text-yellow-400 hover:text-yellow-300`}>
                                    <FontAwesomeIcon icon={faPencilAlt} css={tw`mr-1`} />
                                    Edit
                                </Button.Text>
                                <Button.Danger
                                    size={Button.Sizes.Small}
                                    onClick={() => handleDelete(template.id, template.name)}
                                >
                                    <FontAwesomeIcon icon={faTrash} css={tw`mr-1`} />
                                    Delete
                                </Button.Danger>
                            </div>
                        </AdminBox>
                    ))}
                </div>
            )}
        </>
    );
};
