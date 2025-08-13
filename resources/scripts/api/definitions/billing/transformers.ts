/* eslint-disable camelcase */
import { FractalResponseData } from '@/api/http';
import * as Models from '@definitions/billing/models';

export default class Transformers {
    static toOrder = ({ attributes: data }: FractalResponseData): Models.Order => ({
        id: data.id,
        name: data.name,
        user_id: data.user_id,
        description: data.description,
        total: data.total,
        status: data.status,
        product_id: data.product_id,
        type: data.type,
        created_at: new Date(data.created_at),
    });
}
