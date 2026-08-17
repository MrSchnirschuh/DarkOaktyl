<?php

namespace DarkOak\Tests\Integration\Http\Middleware;

use DarkOak\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use DarkOak\Http\Middleware\RequireTwoFactorAuthentication;
use DarkOak\Exceptions\Http\TwoFactorAuthRequiredException;

class RequireTwoFactorAuthenticationTest extends \DarkOak\Tests\TestCase
{
    /**
     * @var RequireTwoFactorAuthentication
     */
    private $middleware;

    public function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireTwoFactorAuthentication();
    }

    /**
     * Test that users without 2FA can access when enforcement is NONE.
     */
    public function testUserCanAccessWhenEnforcementIsNone()
    {
        Config::set('modules.auth.security.2fa.enforcement', 'NONE');

        /** @var User $user */
        $user = User::factory()->create([
            'use_totp' => false,
            'root_admin' => false,
        ]);

        $request = Request::create('/dashboard');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that non-admin users can access when enforcement is ADMIN.
     */
    public function testNonAdminCanAccessWhenEnforcementIsAdmin()
    {
        Config::set('modules.auth.security.2fa.enforcement', 'ADMIN');

        /** @var User $user */
        $user = User::factory()->create([
            'use_totp' => false,
            'root_admin' => false,
            'admin_role_id' => null,
        ]);

        $request = Request::create('/dashboard');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that admin users are redirected when enforcement is ADMIN and 2FA is not enabled.
     */
    public function testAdminIsRedirectedWhenEnforcementIsAdminAnd2faNotEnabled()
    {
        Config::set('modules.auth.security.2fa.enforcement', 'ADMIN');

        /** @var User $user */
        $user = User::factory()->create([
            'use_totp' => false,
            'root_admin' => true,
        ]);

        $request = Request::create('/dashboard');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/account/security', $response->headers->get('Location'));
    }

    /**
     * Test that admin users with admin_role_id are redirected when enforcement is ADMIN.
     */
    public function testUserWithAdminRoleIsRedirectedWhenEnforcementIsAdmin()
    {
        Config::set('modules.auth.security.2fa.enforcement', 'ADMIN');

        /** @var User $user */
        $role = \DarkOak\Models\AdminRole::factory()->create();
        $user = User::factory()->create([
            'use_totp' => false,
            'root_admin' => false,
            'admin_role_id' => $role->id,
        ]);

        $request = Request::create('/dashboard');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/account/security', $response->headers->get('Location'));
    }

    /**
     * Test that all users are redirected when enforcement is ALL and 2FA is not enabled.
     */
    public function testUserIsRedirectedWhenEnforcementIsAllAnd2faNotEnabled()
    {
        Config::set('modules.auth.security.2fa.enforcement', 'ALL');

        /** @var User $user */
        $user = User::factory()->create([
            'use_totp' => false,
            'root_admin' => false,
        ]);

        $request = Request::create('/dashboard');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/account/security', $response->headers->get('Location'));
    }

    /**
     * Test that users with 2FA enabled can access regardless of enforcement level.
     */
    public function testUserWith2faCanAccessRegardlessOfEnforcement()
    {
        Config::set('modules.auth.security.2fa.enforcement', 'ALL');

        /** @var User $user */
        $user = User::factory()->create([
            'use_totp' => true,
            'root_admin' => false,
        ]);

        $request = Request::create('/dashboard');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that API requests throw exception when 2FA is required.
     */
    public function testApiRequestThrowsExceptionWhen2faRequired()
    {
        Config::set('modules.auth.security.2fa.enforcement', 'ALL');

        /** @var User $user */
        $user = User::factory()->create([
            'use_totp' => false,
        ]);

        $request = Request::create('/api/client/servers');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $this->expectException(TwoFactorAuthRequiredException::class);

        $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
    }

    /**
     * Test that legacy force2fa config is supported (maps to ALL).
     */
    public function testLegacyForce2faConfigIsSupported()
    {
        Config::set('modules.auth.security.2fa.enforcement', null);
        Config::set('modules.auth.security.force2fa', true);

        /** @var User $user */
        $user = User::factory()->create([
            'use_totp' => false,
        ]);

        $request = Request::create('/dashboard');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * Test that auth routes are exempt from 2FA check.
     */
    public function testAuthRoutesAreExemptFrom2faCheck()
    {
        Config::set('modules.auth.security.2fa.enforcement', 'ALL');

        /** @var User $user */
        $user = User::factory()->create([
            'use_totp' => false,
        ]);

        $request = Request::create('/auth/login');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test that account routes are exempt from 2FA check.
     */
    public function testAccountRoutesAreExemptFrom2faCheck()
    {
        Config::set('modules.auth.security.2fa.enforcement', 'ALL');

        /** @var User $user */
        $user = User::factory()->create([
            'use_totp' => false,
        ]);

        $request = Request::create('/account/security');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
