import { useEffect, useState } from 'react';
import tw from 'twin.macro';
import Spinner from '@elements/Spinner';
import AdminBox from '@elements/AdminBox';
import FlashMessageRender from '@/components/FlashMessageRender';
import Input, { Textarea } from '@elements/Input';
import Label from '@elements/Label';
import Switch from '@elements/Switch';
import { Button } from '@elements/button';
import useFlash from '@/plugins/useFlash';
import { getLegalDocuments, updateLegalDocument, type LegalDocument } from '@/api/admin/legal/documents';

const LegalContainer = () => {
    const [documents, setDocuments] = useState<LegalDocument[]>([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState<string | null>(null);
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();

    const fetchDocuments = () => {
        setLoading(true);
        getLegalDocuments()
            .then(setDocuments)
            .catch(error => clearAndAddHttpError({ key: 'admin:legal', error }))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        fetchDocuments();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const updateLocalDocument = (slug: string, data: Partial<LegalDocument>) => {
        setDocuments(prev => prev.map(doc => (doc.slug === slug ? { ...doc, ...data } : doc)));
    };

    const handleSave = (document: LegalDocument) => {
        setSaving(document.slug);
        clearFlashes('admin:legal');

        updateLegalDocument(document.slug, {
            title: document.title,
            content: document.content,
            isPublished: document.isPublished,
        })
            .then(() => {
                addFlash({
                    key: 'admin:legal',
                    type: 'success',
                    message: `${document.title} saved.`,
                });
            })
            .catch(error => {
                clearAndAddHttpError({ key: 'admin:legal', error });
                fetchDocuments();
            })
            .finally(() => setSaving(null));
    };

    if (loading) {
        return (
            <div css={tw`flex items-center justify-center py-12`}>
                <Spinner />
            </div>
        );
    }

    return (
        <div css={tw`space-y-6`}>
            <FlashMessageRender byKey={'admin:legal'} className={'mb-2'} />
            <div css={tw`grid gap-6 xl:grid-cols-2`}>
                {documents.map(document => (
                    <AdminBox key={document.slug} title={document.title}>
                        <div css={tw`space-y-4`}>
                            <div>
                                <Label>Title</Label>
                                <Input
                                    value={document.title}
                                    onChange={event =>
                                        updateLocalDocument(document.slug, { title: event.target.value })
                                    }
                                    placeholder={'Enter title'}
                                />
                            </div>
                            <div>
                                <Label>Content</Label>
                                <Textarea
                                    value={document.content}
                                    rows={10}
                                    onChange={event =>
                                        updateLocalDocument(document.slug, { content: event.target.value })
                                    }
                                    placeholder={'Paste the legal text here...'}
                                />
                                <p css={tw`text-xs text-theme-muted mt-1`}>
                                    Basic formatting such as line breaks is applied automatically.
                                </p>
                            </div>
                            <Switch
                                name={`publish-${document.slug}`}
                                label={'Publish page'}
                                description={'Only published pages are visible to visitors.'}
                                defaultChecked={document.isPublished}
                                onChange={() =>
                                    updateLocalDocument(document.slug, { isPublished: !document.isPublished })
                                }
                            />
                            {document.updatedAt && (
                                <p css={tw`text-xs text-theme-muted`}>
                                    Last updated: {new Date(document.updatedAt).toLocaleString('en-GB')}
                                </p>
                            )}
                            <div css={tw`text-right`}>
                                <Button onClick={() => handleSave(document)} disabled={saving === document.slug}>
                                    {saving === document.slug ? 'Saving...' : 'Save changes'}
                                </Button>
                            </div>
                        </div>
                    </AdminBox>
                ))}
            </div>
        </div>
    );
};

export default LegalContainer;
