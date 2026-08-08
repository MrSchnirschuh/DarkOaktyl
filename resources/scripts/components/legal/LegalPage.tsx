import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import tw from 'twin.macro';
import Spinner from '@elements/Spinner';
import { getPublishedLegalDocuments, type PublicLegalDocument } from '@/api/legal/documents';
import { ArrowLeftIcon } from '@heroicons/react/outline';

const LegalPage = () => {
    const { slug } = useParams<{ slug: string }>();
    const [document, setDocument] = useState<PublicLegalDocument | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);

    useEffect(() => {
        setLoading(true);
        setError(false);

        const currentSlug = slug;

        getPublishedLegalDocuments()
            .then(docs => {
                console.log(
                    '[LegalPage] slug:',
                    currentSlug,
                    'docs:',
                    docs.length,
                    docs.map(d => d.slug),
                );
                const found = docs.find(d => d.slug === currentSlug);
                if (found) {
                    setDocument(found);
                } else {
                    console.log('[LegalPage] No match for slug:', currentSlug);
                    setError(true);
                }
            })
            .catch(err => {
                console.error('[LegalPage] API error:', err);
                setError(true);
            })
            .finally(() => setLoading(false));
    }, [slug]);

    if (loading) {
        return (
            <div
                css={tw`min-h-screen flex items-center justify-center`}
                style={{ backgroundColor: 'var(--theme-background, #0f172a)' }}
            >
                <Spinner />
            </div>
        );
    }

    if (error || !document) {
        return (
            <div
                css={tw`min-h-screen flex items-center justify-center`}
                style={{ backgroundColor: 'var(--theme-background, #0f172a)' }}
            >
                <div css={tw`text-center p-8 max-w-md`}>
                    <h1 css={tw`text-4xl font-bold mb-4`} style={{ color: 'var(--theme-text-primary, #f1f5f9)' }}>
                        Not Found
                    </h1>
                    <p css={tw`mb-6`} style={{ color: 'var(--theme-text-secondary, #94a3b8)' }}>
                        This legal document could not be found or has not been published yet.
                    </p>
                    <Link
                        to={'/'}
                        css={tw`inline-flex items-center gap-2 px-4 py-2 rounded-lg transition-colors`}
                        style={{
                            backgroundColor: 'var(--theme-accent, #38bdf8)',
                            color: '#fff',
                        }}
                    >
                        <ArrowLeftIcon css={tw`w-4 h-4`} />
                        Back to Dashboard
                    </Link>
                </div>
            </div>
        );
    }

    const lines = document.content.split('\n');

    return (
        <div css={tw`min-h-screen`} style={{ backgroundColor: 'var(--theme-background, #0f172a)' }}>
            <div css={tw`w-full px-8 lg:px-16 py-12 sm:py-16`}>
                <Link
                    to={'/'}
                    css={tw`inline-flex items-center gap-1.5 text-sm mb-8 transition-colors`}
                    style={{ color: 'var(--theme-accent, #38bdf8)' }}
                >
                    <ArrowLeftIcon css={tw`w-4 h-4`} />
                    Back to Dashboard
                </Link>

                <div
                    css={tw`rounded-xl p-8 sm:p-12 xl:p-16 shadow-lg w-full`}
                    style={{ backgroundColor: 'var(--theme-secondary, #1e293b)' }}
                >
                    <h1
                        css={tw`text-3xl sm:text-4xl font-bold mb-6 leading-tight`}
                        style={{ color: 'var(--theme-text-primary, #f1f5f9)' }}
                    >
                        {document.title}
                    </h1>

                    {document.updatedAt && (
                        <p
                            css={tw`text-sm mb-8 pb-6 border-b`}
                            style={{
                                color: 'var(--theme-text-muted, #64748b)',
                                borderColor: 'var(--theme-surface-card, #334155)',
                            }}
                        >
                            Last updated:{' '}
                            {new Date(document.updatedAt).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                            })}
                        </p>
                    )}

                    <div
                        css={tw`space-y-3 leading-relaxed max-w-4xl`}
                        style={{ color: 'var(--theme-text-secondary, #cbd5e1)' }}
                    >
                        {lines.map((line, i) => {
                            if (!line.trim()) {
                                return <div key={i} css={tw`h-3`} />;
                            }

                            const isHeading = /^\d+\.\s/.test(line) && line.length < 80;
                            const isTitle = !isHeading && /^[A-Z][A-Za-z\s\-]{2,}$/.test(line) && line.length < 60;

                            if (isHeading) {
                                return (
                                    <h2
                                        key={i}
                                        css={tw`text-lg font-semibold mt-6 mb-2`}
                                        style={{ color: 'var(--theme-text-primary, #f1f5f9)' }}
                                    >
                                        {line}
                                    </h2>
                                );
                            }

                            if (isTitle && i < 5) {
                                return <div key={i} />;
                            }

                            return (
                                <p key={i} css={tw`text-base`}>
                                    {line}
                                </p>
                            );
                        })}
                    </div>
                </div>

                <div css={tw`text-center mt-8 text-xs`} style={{ color: 'var(--theme-text-muted, #64748b)' }}>
                    <Link
                        to={'/'}
                        css={tw`hover:underline transition-colors`}
                        style={{ color: 'var(--theme-accent, #38bdf8)' }}
                    >
                        &larr; Return to Dashboard
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default LegalPage;
