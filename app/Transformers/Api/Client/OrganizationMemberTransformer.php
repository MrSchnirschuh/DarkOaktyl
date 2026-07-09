<?php

namespace DarkOak\Transformers\Api\Client;

use DarkOak\Models\OrganizationMember;
use DarkOak\Transformers\Api\Transformer;

class OrganizationMemberTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return OrganizationMember::RESOURCE_NAME;
    }

    public function transform(OrganizationMember $member): array
    {
        return [
            'id' => $member->id,
            'organization_id' => $member->organization_id,
            'user_id' => $member->user_id,
            'role' => $member->role,
            'joined_at' => $member->joined_at?->toIso8601String(),
            'monthly_share_amount' => $member->monthly_share_amount,
            'payment_method' => $member->payment_method,
            'billing_email' => $member->billing_email,
            'last_payment_at' => $member->last_payment_at?->toIso8601String(),
            'created_at' => $member->created_at?->toIso8601String(),
            'updated_at' => $member->updated_at?->toIso8601String(),
        ];
    }
}
