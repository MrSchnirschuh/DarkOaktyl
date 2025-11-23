<?php

namespace Database\Factories;

use Carbon\Carbon;
use DarkOak\Models\User;
use DarkOak\Models\UserPasskey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class UserPasskeyFactory extends Factory
{
    protected $model = UserPasskey::class;

    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'user_id' => User::factory(),
            'name' => 'Passkey ' . $this->faker->word(),
            'credential_id' => $this->faker->regexify('[A-Za-z0-9_-]{32}'),
            'public_key_credential' => '{}',
            'attestation_type' => 'none',
            'aaguid' => Uuid::uuid4()->toString(),
            'transports' => ['internal'],
            'counter' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
