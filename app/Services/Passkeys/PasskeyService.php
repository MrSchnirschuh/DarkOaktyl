<?php

namespace DarkOak\Services\Passkeys;

use DarkOak\Exceptions\DisplayException;
use DarkOak\Models\User;
use DarkOak\Models\UserPasskey;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSource;

class PasskeyService
{
    private const REGISTRATION_SESSION_KEY = 'passkeys.registration';
    private const LOGIN_SESSION_KEY = 'passkeys.login';

    public function __construct(
        private SerializerInterface $serializer,
        private PublicKeyCredentialLoader $credentialLoader,
        private AuthenticatorAttestationResponseValidator $attestationValidator,
        private AuthenticatorAssertionResponseValidator $assertionValidator,
        private Session $session,
        private ConfigRepository $config,
        private Request $request,
    ) {
    }

    public function createRegistrationOptions(User $user): array
    {
        $this->ensureCanRegister($user);

        $options = PublicKeyCredentialCreationOptions::create(
            $this->getRpEntity(),
            $this->getUserEntity($user),
            random_bytes(32),
            $this->getCredentialParameters(),
            $this->getAuthenticatorSelectionCriteria(),
            'none',
            $this->getExcludedCredentials($user),
            $this->config->get('modules.auth.passkeys.timeout', 60000)
        );

        $token = Str::uuid()->toString();
        $this->storeChallenge(self::REGISTRATION_SESSION_KEY, $token, [
            'user_id' => $user->id,
            'options' => $this->serializer->serialize($options, 'json'),
            'expires_at' => now()->addSeconds($this->config->get('modules.auth.passkeys.challenge_ttl', 300))->timestamp,
        ]);

        return [
            'token' => $token,
            'options' => $this->normalize($options),
        ];
    }

    public function finalizeRegistration(User $user, string $token, array $payload, string $name): UserPasskey
    {
        $challenge = $this->pullChallenge(self::REGISTRATION_SESSION_KEY, $token);
        $this->assertChallenge($challenge, $user);

        /** @var PublicKeyCredentialCreationOptions $options */
        $options = $this->serializer->deserialize($challenge['options'], PublicKeyCredentialCreationOptions::class, 'json');

        try {
            $publicKey = $this->credentialLoader->loadArray($payload);
            $response = $publicKey->response;

            if (!$response instanceof AuthenticatorAttestationResponse) {
                throw new DisplayException('Invalid attestation response received.');
            }

            $source = $this->attestationValidator->check($response, $options, $this->getRelyingPartyId());
        } catch (Throwable $exception) {
            Log::warning('Failed to finalize passkey registration', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            throw new DisplayException('Unable to register this passkey. Please try again.');
        }

        $passkey = $user->passkeys()->create([
            'name' => $name,
            'credential_id' => Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId),
            'public_key_credential' => $this->serializer->serialize($source, 'json'),
            'attestation_type' => $source->attestationType,
            'aaguid' => $source->aaguid->__toString(),
            'transports' => $source->transports,
            'counter' => $source->counter,
        ]);

        return $passkey;
    }

    public function createLoginOptions(User $user): array
    {
        $passkeys = $user->passkeys()->get();

        if ($passkeys->isEmpty()) {
            throw new DisplayException('No passkeys are registered on this account.');
        }

        $allowCredentials = $passkeys->map(function (UserPasskey $passkey) {
            $transports = $passkey->transports ?? [];

            return PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Base64UrlSafe::decodeNoPadding($passkey->credential_id),
                $transports
            );
        })->all();

        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $this->getRelyingPartyId(),
            $allowCredentials,
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            $this->config->get('modules.auth.passkeys.timeout', 60000)
        );

        $token = Str::uuid()->toString();
        $this->storeChallenge(self::LOGIN_SESSION_KEY, $token, [
            'user_id' => $user->id,
            'options' => $this->serializer->serialize($options, 'json'),
            'expires_at' => now()->addSeconds($this->config->get('modules.auth.passkeys.challenge_ttl', 300))->timestamp,
        ]);

        return [
            'token' => $token,
            'options' => $this->normalize($options),
        ];
    }

    public function verifyLogin(string $token, array $payload): UserPasskey
    {
        $challenge = $this->pullChallenge(self::LOGIN_SESSION_KEY, $token);
        $this->assertChallenge($challenge);

        /** @var PublicKeyCredentialRequestOptions $options */
        $options = $this->serializer->deserialize($challenge['options'], PublicKeyCredentialRequestOptions::class, 'json');

        try {
            $publicKey = $this->credentialLoader->loadArray($payload);
            $response = $publicKey->response;

            if (!$response instanceof AuthenticatorAssertionResponse) {
                throw new DisplayException('Invalid assertion response received.');
            }

            $credentialId = Base64UrlSafe::encodeUnpadded($publicKey->rawId);
            /** @var UserPasskey|null $passkey */
            $passkey = UserPasskey::query()->where('credential_id', $credentialId)->first();

            if (is_null($passkey)) {
                throw new DisplayException('This passkey is not registered.');
            }

            if ((int) $challenge['user_id'] !== $passkey->user_id) {
                throw new DisplayException('This passkey does not belong to the requested user.');
            }

            $source = $this->serializer->deserialize(
                $passkey->public_key_credential,
                PublicKeyCredentialSource::class,
                'json'
            );
        } catch (DisplayException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Failed to verify passkey login', [
                'token' => $token,
                'exception' => $exception->getMessage(),
            ]);

            throw new DisplayException('Unable to verify the provided passkey.');
        }

        try {
            $validatedSource = $this->assertionValidator->check(
                $source,
                $response,
                $options,
                $this->getRelyingPartyId(),
                $source->userHandle
            );
        } catch (Throwable $exception) {
            Log::warning('Passkey assertion validation failed', [
                'token' => $token,
                'exception' => $exception->getMessage(),
            ]);

            throw new DisplayException('Unable to verify the provided passkey.');
        }

        $passkey->update([
            'counter' => $validatedSource->counter,
            'last_used_at' => now(),
        ]);

        return $passkey->refresh();
    }

    private function ensureCanRegister(User $user): void
    {
        $limit = (int) $this->config->get('modules.auth.passkeys.max_per_user', 5);

        if ($user->passkeys()->count() >= $limit) {
            throw new DisplayException('You have reached the maximum number of passkeys allowed.');
        }
    }

    private function getRpEntity(): PublicKeyCredentialRpEntity
    {
        $config = $this->config->get('modules.auth.passkeys.relying_party');

        return new PublicKeyCredentialRpEntity(
            Arr::get($config, 'name', config('app.name', 'DarkOaktyl')),
            Arr::get($config, 'id', $this->request->getHost())
        );
    }

    private function getUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            $user->email,
            $user->uuid,
            $user->username
        );
    }

    /**
     * @return PublicKeyCredentialParameters[]
     */
    private function getCredentialParameters(): array
    {
        return [
            new PublicKeyCredentialParameters('public-key', -7),
            new PublicKeyCredentialParameters('public-key', -257),
        ];
    }

    private function getAuthenticatorSelectionCriteria(): AuthenticatorSelectionCriteria
    {
        return AuthenticatorSelectionCriteria::create(
            AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED,
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED
        );
    }

    /**
     * @return PublicKeyCredentialDescriptor[]
     */
    private function getExcludedCredentials(User $user): array
    {
        $passkeys = $user->relationLoaded('passkeys') ? $user->passkeys : $user->passkeys()->get();

        return $passkeys->map(function (UserPasskey $passkey) {
            return PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Base64UrlSafe::decodeNoPadding($passkey->credential_id),
                $passkey->transports ?? []
            );
        })->all();
    }

    private function storeChallenge(string $key, string $token, array $payload): void
    {
        $this->session->put(sprintf('%s.%s', $key, $token), $payload);
    }

    private function pullChallenge(string $key, string $token): ?array
    {
        $sessionKey = sprintf('%s.%s', $key, $token);
        $data = $this->session->get($sessionKey);
        $this->session->forget($sessionKey);

        return $data;
    }

    private function assertChallenge(?array $challenge, ?User $user = null): void
    {
        if (is_null($challenge)) {
            throw new DisplayException('The provided challenge has expired. Please start again.');
        }

        if ($user && (int) $challenge['user_id'] !== $user->id) {
            throw new DisplayException('This challenge does not match your account.');
        }

        if (Arr::get($challenge, 'expires_at') < now()->timestamp) {
            throw new DisplayException('The provided challenge has expired. Please start again.');
        }
    }

    private function normalize(object $object): array
    {
        return json_decode($this->serializer->serialize($object, 'json'), true);
    }

    private function getRelyingPartyId(): string
    {
        return Arr::get($this->config->get('modules.auth.passkeys.relying_party'), 'id', $this->request->getHost());
    }
}
