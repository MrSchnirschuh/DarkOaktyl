<?php

namespace DarkOak\Transformers\Api\Client;

use DarkOak\Models\UserPasskey;
use DarkOak\Transformers\Api\Transformer;

class UserPasskeyTransformer extends Transformer
{
    public function transform(UserPasskey $passkey): array
    {
        return [
            'uuid' => $passkey->uuid,
            'name' => $passkey->name,
            'attestation_type' => $passkey->attestation_type,
            'aaguid' => $passkey->aaguid,
            'transports' => $passkey->transports ?? [],
            'last_used_at' => optional($passkey->last_used_at)->toAtomString(),
            'created_at' => optional($passkey->created_at)->toAtomString(),
        ];
    }
}
