import http from '@/api/http';

export interface LegalDocument {
    slug: string;
    title: string;
    content: string;
    isPublished: boolean;
    updatedAt: string | null;
}

interface LegalDocumentResponse {
    slug: string;
    title: string;
    content: string;
    is_published: boolean;
    updated_at: string | null;
}

const mapDocument = (doc: LegalDocumentResponse): LegalDocument => ({
    slug: doc.slug,
    title: doc.title,
    content: doc.content ?? '',
    isPublished: Boolean(doc.is_published),
    updatedAt: doc.updated_at,
});

export const getLegalDocuments = async (): Promise<LegalDocument[]> => {
    const { data } = await http.get('/api/application/legal/documents');

    return Array.isArray(data?.data) ? data.data.map(mapDocument) : [];
};

interface UpdatePayload {
    title: string;
    content: string;
    isPublished: boolean;
}

export const updateLegalDocument = async (slug: string, payload: UpdatePayload): Promise<void> => {
    await http.put(`/api/application/legal/documents/${slug}`, {
        title: payload.title,
        content: payload.content,
        is_published: payload.isPublished,
    });
};
