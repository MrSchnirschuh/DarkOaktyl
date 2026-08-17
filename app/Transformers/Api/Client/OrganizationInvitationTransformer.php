<?php

namespace DarkOak\Transformers\Api\Client;

use DarkOak\Transformers\Api\Transformer;
use DarkOak\Models\OrganizationInvitation;

class OrganizationInvitationTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return OrganizationInvitation::RESOURCE_NAME;
    }

    public function transform(OrganizationInvitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'organization_id' => $invitation->organization_id,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'status' => $invitation->status,
            'invited_by' => $invitation->invited_by,
            'expires_at' => $invitation->expires_at?->toIso8601String(),
            'accepted_at' => $invitation->accepted_at?->toIso8601String(),
            'declined_at' => $invitation->declined_at?->toIso8601String(),
            'message' => $invitation->message,
            'created_at' => $invitation->created_at?->toIso8601String(),
            'updated_at' => $invitation->updated_at?->toIso8601String(),
        ];
    }
}
