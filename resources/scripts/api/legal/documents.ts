import http from '@/api/http';

export interface PublicLegalDocument {
    slug: string;
    title: string;
    content: string;
    updatedAt: string | null;
}

interface PublicLegalDocumentResponse {
    slug: string;
    title: string;
    content: string;
    updated_at: string | null;
}

const mapDocument = (doc: PublicLegalDocumentResponse): PublicLegalDocument => ({
    slug: doc.slug,
    title: doc.title,
    content: doc.content ?? '',
    updatedAt: doc.updated_at,
});

export const getPublishedLegalDocuments = async (): Promise<PublicLegalDocument[]> => {
    const { data: response } = await http.get('/api/legal/published');

    return Array.isArray(response?.data) ? response.data.map(mapDocument) : [];
};
