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
import { CheckCircleIcon, SaveIcon, DocumentTextIcon, EyeIcon } from '@heroicons/react/outline';
import { Link } from 'react-router-dom';

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
            <div css={tw`flex items-center justify-center py-24`}>
                <Spinner />
            </div>
        );
    }

    return (
        <div css={tw`space-y-8`}>
            <FlashMessageRender byKey={'admin:legal'} className={'mb-2'} />
            <div css={tw`grid gap-8 xl:grid-cols-2`}>
                {documents.map(document => (
                    <AdminBox key={document.slug} title={document.title}>
                        <div css={tw`space-y-6`}>
                            {/* Header with status and preview link */}
                            <div css={tw`flex items-center justify-between`}>
                                <div css={tw`flex items-center gap-2`}>
                                    <DocumentTextIcon
                                        css={tw`w-5 h-5`}
                                        style={{ color: 'var(--theme-accent, #22c55e)' }}
                                    />
                                    <span
                                        css={tw`text-sm font-medium`}
                                        style={{ color: 'var(--theme-text-primary, #f1f5f9)' }}
                                    >
                                        {document.title}
                                    </span>
                                </div>
                                {document.slug && (
                                    <Link
                                        to={`/legal/${document.slug}`}
                                        target={'_blank'}
                                        rel={'noreferrer'}
                                        css={tw`inline-flex items-center gap-1 text-xs px-2 py-1 rounded transition-colors`}
                                        style={{
                                            color: 'var(--theme-text-muted, #64748b)',
                                            backgroundColor: 'var(--theme-surface-card, #1e293b)',
                                        }}
                                    >
                                        <EyeIcon css={tw`w-3 h-3`} />
                                        Preview
                                    </Link>
                                )}
                            </div>

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
                                    rows={14}
                                    onChange={event =>
                                        updateLocalDocument(document.slug, { content: event.target.value })
                                    }
                                    placeholder={'Paste the legal text here...'}
                                    css={tw`font-mono text-sm leading-relaxed`}
                                />
                                <p css={tw`text-xs mt-2`} style={{ color: 'var(--theme-text-muted, #64748b)' }}>
                                    Line breaks are automatically preserved. Format with numbered sections.
                                </p>
                            </div>

                            {/* Publish toggle + last updated */}
                            <div
                                css={tw`flex items-center justify-between pt-2 border-t`}
                                style={{ borderColor: 'var(--theme-surface-card, #334155)' }}
                            >
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
                                    <span css={tw`text-xs`} style={{ color: 'var(--theme-text-muted, #64748b)' }}>
                                        Updated:{' '}
                                        {new Date(document.updatedAt).toLocaleDateString('en-GB', {
                                            day: 'numeric',
                                            month: 'short',
                                            year: 'numeric',
                                        })}
                                    </span>
                                )}
                            </div>

                            {/* Save button */}
                            <div css={tw`text-right`}>
                                <Button onClick={() => handleSave(document)} disabled={saving === document.slug}>
                                    <SaveIcon css={tw`w-4 h-4 mr-1.5`} />
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
