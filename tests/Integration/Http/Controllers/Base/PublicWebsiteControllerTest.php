<?php

namespace DarkOak\Tests\Integration\Http\Controllers\Base;

use DarkOak\Tests\TestCase;

class PublicWebsiteControllerTest extends TestCase
{
    /**
     * Test that the public home page is accessible without authentication.
     */
    public function testPublicHomePageIsAccessible(): void
    {
        // Temporarily set the APP_ROOT_DOMAIN to test domain separation
        config(['app.root_domain' => 'darkoak.test']);
        
        $response = $this->get('/', [
            'Host' => 'darkoak.test',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('templates/public.home');
    }

    /**
     * Test that the documentation page is accessible without authentication.
     */
    public function testDocumentationPageIsAccessible(): void
    {
        config(['app.root_domain' => 'darkoak.test']);
        
        $response = $this->get('/documentation', [
            'Host' => 'darkoak.test',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('templates/public.documentation');
    }

    /**
     * Test that panel routes are not accessible on the root domain when domain separation is enabled.
     */
    public function testPanelRoutesNotAccessibleOnRootDomain(): void
    {
        config(['app.root_domain' => 'darkoak.test']);
        
        // Try to access auth route on root domain (should not work with domain separation)
        $response = $this->get('/auth/login', [
            'Host' => 'darkoak.test',
        ]);

        // This should either redirect or return 404 when domain separation is enabled
        $this->assertTrue(in_array($response->status(), [302, 404]));
    }
}
