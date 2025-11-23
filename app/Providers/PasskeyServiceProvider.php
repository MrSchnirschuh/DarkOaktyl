<?php

namespace DarkOak\Providers;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSourceRepository;
use DarkOak\Repositories\WebAuthnCredentialRepository;

class PasskeyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AttestationStatementSupportManager::class, static fn () => new AttestationStatementSupportManager());

        $this->app->singleton(SerializerInterface::class, function ($app) {
            $manager = $app->make(AttestationStatementSupportManager::class);

            return (new WebauthnSerializerFactory($manager))->create();
        });

        $this->app->singleton(AttestationObjectLoader::class, function ($app) {
            $loader = AttestationObjectLoader::create($app->make(AttestationStatementSupportManager::class));

            return $loader;
        });

        $this->app->singleton(PublicKeyCredentialLoader::class, function ($app) {
            return PublicKeyCredentialLoader::create(
                $app->make(AttestationObjectLoader::class),
                $app->make(SerializerInterface::class)
            );
        });

        $this->app->singleton(AuthenticatorAttestationResponseValidator::class, function ($app) {
            return AuthenticatorAttestationResponseValidator::create(
                $app->make(AttestationStatementSupportManager::class)
            );
        });

        $this->app->singleton(AuthenticatorAssertionResponseValidator::class, static fn () => AuthenticatorAssertionResponseValidator::create());

        $this->app->singleton(PublicKeyCredentialSourceRepository::class, WebAuthnCredentialRepository::class);
    }
}
