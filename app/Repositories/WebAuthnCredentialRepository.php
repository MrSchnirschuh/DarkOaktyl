<?php

namespace DarkOak\Repositories;

use DarkOak\Models\User;
use DarkOak\Models\UserPasskey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class WebAuthnCredentialRepository implements PublicKeyCredentialSourceRepository
{
	public function __construct(private SerializerInterface $serializer)
	{
	}

	public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
	{
		$encoded = Base64UrlSafe::encodeUnpadded($publicKeyCredentialId);

		$passkey = UserPasskey::query()->where('credential_id', $encoded)->first();

		return $passkey ? $this->deserialize($passkey->public_key_credential) : null;
	}

	public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
	{
		$user = User::query()->where('uuid', $publicKeyCredentialUserEntity->id)->first();

		if (is_null($user)) {
			return [];
		}

		return $user->passkeys
			->map(fn (UserPasskey $passkey) => $this->deserialize($passkey->public_key_credential))
			->filter()
			->values()
			->all();
	}

	public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
	{
		$user = User::query()->where('uuid', $publicKeyCredentialSource->userHandle)->first();

		if (is_null($user)) {
			return;
		}

		UserPasskey::query()->updateOrCreate(
			['credential_id' => Base64UrlSafe::encodeUnpadded($publicKeyCredentialSource->publicKeyCredentialId)],
			[
				'user_id' => $user->id,
				'name' => 'Passkey',
				'public_key_credential' => $this->serializer->serialize($publicKeyCredentialSource, 'json'),
				'attestation_type' => $publicKeyCredentialSource->attestationType,
				'aaguid' => $publicKeyCredentialSource->aaguid->__toString(),
				'transports' => $publicKeyCredentialSource->transports,
				'counter' => $publicKeyCredentialSource->counter,
			]
		);
	}

	private function deserialize(string $payload): ?PublicKeyCredentialSource
	{
		return $this->serializer->deserialize($payload, PublicKeyCredentialSource::class, 'json');
	}
}
