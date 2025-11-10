import { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { faClock, faPlus, faTrash, faPencilAlt, faToggleOn, faToggleOff } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

import AdminBox from '@elements/AdminBox';
import { Button } from '@elements/button';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import { getScheduledEmails, deleteScheduledEmail } from '@/api/admin/emails';
import { ScheduledEmail } from '@/api/admin/emails/types';

export default () => {
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const [scheduledEmails, setScheduledEmails] = useState<ScheduledEmail[]>([]);
    const [loading, setLoading] = useState(true);

    const loadScheduledEmails = () => {
        setLoading(true);
        getScheduledEmails()
            .then(data => {
                setScheduledEmails(data);
                setLoading(false);
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'emails:scheduled', error });
                setLoading(false);
            });
    };

    useEffect(() => {
        clearFlashes();
        loadScheduledEmails();
    }, []);

    const handleDelete = (id: number, name: string) => {
        if (!confirm(`Are you sure you want to delete the scheduled email "${name}"?`)) {
            return;
        }

        deleteScheduledEmail(id)
            .then(() => {
                addFlash({
                    type: 'success',
                    key: 'emails:scheduled',
                    message: `Scheduled email "${name}" has been deleted.`,
                });
                loadScheduledEmails();
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'emails:scheduled', error });
            });
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'Never';
        return new Date(dateString).toLocaleString();
    };

    return (
        <>
            <FlashMessageRender byKey={'emails:scheduled'} css={tw`mb-4`} />

            <div css={tw`flex justify-between items-center mb-4`}>
                <h3 css={tw`text-xl font-semibold text-neutral-50`}>Scheduled Emails</h3>
                <Button.Success>
                    <FontAwesomeIcon icon={faPlus} css={tw`mr-2`} />
                    Create Scheduled Email
                </Button.Success>
            </div>

            {loading ? (
                <AdminBox css={tw`text-center py-8`}>
                    <p css={tw`text-neutral-400`}>Loading scheduled emails...</p>
                </AdminBox>
            ) : scheduledEmails.length === 0 ? (
                <AdminBox css={tw`text-center py-8`}>
                    <p css={tw`text-neutral-400`}>No scheduled emails found.</p>
                </AdminBox>
            ) : (
                <div css={tw`grid grid-cols-1 gap-4`}>
                    {scheduledEmails.map(scheduled => (
                        <AdminBox key={scheduled.id} css={tw`flex justify-between items-start`}>
                            <div css={tw`flex-1`}>
                                <div css={tw`flex items-center mb-2`}>
                                    <FontAwesomeIcon icon={faClock} css={tw`text-neutral-400 mr-2`} />
                                    <h4 css={tw`text-lg font-semibold text-neutral-50`}>{scheduled.name}</h4>
                                    {scheduled.enabled ? (
                                        <span css={tw`ml-2 px-2 py-1 text-xs bg-green-500 text-white rounded`}>
                                            <FontAwesomeIcon icon={faToggleOn} css={tw`mr-1`} />
                                            Enabled
                                        </span>
                                    ) : (
                                        <span css={tw`ml-2 px-2 py-1 text-xs bg-gray-500 text-white rounded`}>
                                            <FontAwesomeIcon icon={faToggleOff} css={tw`mr-1`} />
                                            Disabled
                                        </span>
                                    )}
                                </div>
                                <p css={tw`text-sm text-neutral-400 mb-1`}>
                                    <strong>Template:</strong> {scheduled.template?.name || scheduled.template_key}
                                </p>
                                <p css={tw`text-sm text-neutral-400 mb-1`}>
                                    <strong>Trigger:</strong> {scheduled.trigger_type}
                                    {scheduled.trigger_value && ` - ${scheduled.trigger_value}`}
                                </p>
                                <p css={tw`text-sm text-neutral-500 mb-1`}>
                                    <strong>Last Run:</strong> {formatDate(scheduled.last_run_at)}
                                </p>
                                {scheduled.next_run_at && (
                                    <p css={tw`text-sm text-neutral-500`}>
                                        <strong>Next Run:</strong> {formatDate(scheduled.next_run_at)}
                                    </p>
                                )}
                            </div>
                            <div css={tw`flex gap-2 ml-4`}>
                                <Button.Text size={Button.Sizes.Small} css={tw`text-yellow-400 hover:text-yellow-300`}>
                                    <FontAwesomeIcon icon={faPencilAlt} css={tw`mr-1`} />
                                    Edit
                                </Button.Text>
                                <Button.Danger
                                    size={Button.Sizes.Small}
                                    onClick={() => handleDelete(scheduled.id, scheduled.name)}
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
