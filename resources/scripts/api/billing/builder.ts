import http, { FractalResponseData } from '@/api/http';
import { rawDataToNode, Node } from '@/api/billing/getNodes';
import { PaymentIntent } from '@/api/billing/intent';
import { Server } from '@definitions/server';

export interface BuilderResourceScalingRule {
    id: number;
    threshold: number;
    mode: string;
    multiplier: number;
    label?: string;
    metadata?: Record<string, unknown> | null;
}

export interface BuilderResource {
    uuid: string;
    resource: string;
    display_name: string;
    description?: string;
    unit: string;
    base_quantity: number;
    price: number;
    currency: string;
    min_quantity: number;
    max_quantity?: number | null;
    default_quantity?: number | null;
    step_quantity?: number | null;
    is_metered: boolean;
    sort_order: number;
    scaling_rules: BuilderResourceScalingRule[];
}

export interface BuilderTerm {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    duration_days: number;
    multiplier: number;
    is_default: boolean;
}

export interface BuilderQuotePayload {
    resources: { resource: string; quantity: number }[];
    term?: string | null;
    coupons?: string[];
    options?: Record<string, unknown>;
    node_id?: number;
}

export interface BuilderIntentPayload extends BuilderQuotePayload {
    category_id: number;
    node_id: number;
    variables?: { key: string; value: string }[];
    renewal?: boolean;
    server_id?: number;
}

export interface BuilderIntentUpdatePayload {
    intent: string;
    node_id: number;
    variables?: { key: string; value: string }[];
    renewal?: boolean;
    server_id?: number;
}

export interface BuilderQuoteResult {
    quote: {
        subtotal: number;
        total: number;
        total_after_discount?: number;
        discount?: number;
        term_multiplier: number;
        term?: Record<string, unknown> | null;
        resources: Record<string, { resource: string; display_name: string; quantity: number; unit: string; total: number }>;
    };
    coupons: {
        uuid: string;
        code: string;
        name: string;
        type: string;
        value?: number | null;
        percentage?: number | null;
        metadata?: Record<string, unknown> | null;
    }[];
}

export const getBuilderResources = (): Promise<BuilderResource[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/billing/builder/resources')
            .then(({ data }) => resolve((data.data || []) as BuilderResource[]))
            .catch(reject);
    });
};

export const getBuilderTerms = (): Promise<BuilderTerm[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/billing/builder/terms')
            .then(({ data }) => resolve((data.data || []) as BuilderTerm[]))
            .catch(reject);
    });
};

export const getBuilderQuote = (payload: BuilderQuotePayload): Promise<BuilderQuoteResult> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/billing/builder/quote', payload)
            .then(({ data }) => resolve(data as BuilderQuoteResult))
            .catch(reject);
    });
};

export const getBuilderNodes = (type: 'paid' | 'free' = 'paid'): Promise<Node[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/billing/builder/nodes', { params: { type } })
            .then(({ data }) => resolve((data.data || []).map((datum: FractalResponseData) => rawDataToNode(datum))))
            .catch(reject);
    });
};

export const getBuilderKey = (): Promise<{ key: string }> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/billing/builder/key')
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};

export const createBuilderIntent = (payload: BuilderIntentPayload): Promise<PaymentIntent> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/billing/builder/intent', payload)
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};

export const updateBuilderIntent = (payload: BuilderIntentUpdatePayload): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/billing/builder/intent', {
            intent: payload.intent,
            node_id: payload.node_id,
            variables: payload.variables,
            renewal: payload.renewal,
            server_id: payload.server_id,
        })
            .then(() => resolve())
            .catch(reject);
    });
};

export const processBuilderFree = (payload: BuilderIntentPayload): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/billing/builder/free', payload)
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
