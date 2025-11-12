<?php

namespace Tests\Integration\Services\Emails;

use Everest\Models\EmailTemplate;
use Everest\Models\EmailTheme;
use Everest\Models\User;
use Everest\Services\Emails\EmailTemplateRenderer;
use Everest\Services\Themes\ThemePaletteService;
use Mockery;
use Everest\Tests\TestCase;

class EmailTemplateRendererTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRenderInterpolatesUserDataAndRetainsInlineMedia(): void
    {
        $palettes = [
            'dark' => [
                'primary_color' => '#101820',
                'secondary_color' => '#1f1f3a',
                'accent_color' => '#ff6b35',
                'background_color' => '#0b0f19',
                'body_color' => '#111827',
                'text_color' => '#f8fafc',
                'muted_text_color' => '#94a3b8',
                'button_color' => '#2563eb',
                'button_text_color' => '#ffffff',
            ],
            'light' => [
                'primary_color' => '#2563eb',
                'secondary_color' => '#1e40af',
                'accent_color' => '#fb923c',
                'background_color' => '#f8fafc',
                'body_color' => '#ffffff',
                'text_color' => '#0f172a',
                'muted_text_color' => '#475569',
                'button_color' => '#4338ca',
                'button_text_color' => '#111827',
            ],
        ];

        $paletteService = Mockery::mock(ThemePaletteService::class);
        $paletteService->shouldReceive('getEmailPalettes')->once()->andReturn($palettes);

        $renderer = new EmailTemplateRenderer($paletteService);

        $theme = EmailTheme::make([
            'name' => 'System Default',
            'primary_color' => '#000000',
            'secondary_color' => '#000000',
            'accent_color' => '#000000',
            'background_color' => '#000000',
            'body_color' => '#000000',
            'text_color' => '#000000',
            'muted_text_color' => '#000000',
            'button_color' => '#000000',
            'button_text_color' => '#000000',
            'is_default' => true,
        ]);

        $template = new EmailTemplate([
            'name' => 'Onboarding',
            'subject' => 'Welcome, {{ user.username }}',
            'content' => <<<'MD'
Hi **{{ user.username }}**,

![Logo](/assets/email/logo.png)

Your email is {{ user.email }}.
MD,
            'locale' => 'en',
            'metadata' => [],
            'is_enabled' => true,
        ]);
        $template->setRelation('theme', $theme);

        $user = User::factory()->make([
            'username' => 'alice',
            'email' => 'alice@example.com',
        ]);

        $rendered = $renderer->render($template, ['user' => $user]);

        $this->assertSame('Welcome, alice', $rendered['subject']);
        $this->assertStringContainsString('alice@example.com', $rendered['html']);
        $this->assertStringContainsString('<img src="/assets/email/logo.png"', $rendered['html']);
        $this->assertStringContainsString('alice@example.com', $rendered['text']);
        $this->assertSame('dual', $rendered['theme']->variant_mode);
        $this->assertSame('#101820', $rendered['theme']->primary_color);
        $this->assertSame('#f8fafc', $rendered['theme']->text_color);
        $this->assertSame('#f8fafc', $rendered['theme']->light_palette['background']);
    }

    public function testRenderSupportsDataKeysContainingSlashes(): void
    {
        $palettes = [
            'dark' => [
                'primary_color' => '#0f766e',
                'secondary_color' => '#134e4a',
                'accent_color' => '#10b981',
                'background_color' => '#022c22',
                'body_color' => '#064e3b',
                'text_color' => '#ecfdf5',
                'muted_text_color' => '#5eead4',
                'button_color' => '#0d9488',
                'button_text_color' => '#f0fdfa',
            ],
            'light' => [
                'primary_color' => '#34d399',
                'secondary_color' => '#10b981',
                'accent_color' => '#059669',
                'background_color' => '#f0fdfa',
                'body_color' => '#ecfdf5',
                'text_color' => '#064e3b',
                'muted_text_color' => '#047857',
                'button_color' => '#0d9488',
                'button_text_color' => '#f0fdfa',
            ],
        ];

        $paletteService = Mockery::mock(ThemePaletteService::class);
        $paletteService->shouldReceive('getEmailPalettes')->once()->andReturn($palettes);

        $renderer = new EmailTemplateRenderer($paletteService);

        $theme = EmailTheme::make([
            'name' => 'System Default',
            'primary_color' => '#000000',
            'secondary_color' => '#000000',
            'accent_color' => '#000000',
            'background_color' => '#000000',
            'body_color' => '#000000',
            'text_color' => '#000000',
            'muted_text_color' => '#000000',
            'button_color' => '#000000',
            'button_text_color' => '#000000',
            'is_default' => true,
        ]);

        $template = new EmailTemplate([
            'name' => 'Coupon Drop',
            'subject' => 'Coupon {{ data["coupon/code"] ?? "" }} ready',
            'content' => <<<'MD'
Redeem code **{{ data['coupon/code'] ?? '' }}** at checkout.
MD,
            'locale' => 'en',
            'metadata' => [],
            'is_enabled' => true,
        ]);
        $template->setRelation('theme', $theme);

        $context = [
            'data' => [
                'coupon/code' => 'SAVE/2025',
            ],
        ];

        $rendered = $renderer->render($template, $context);

        $this->assertSame('Coupon SAVE/2025 ready', $rendered['subject']);
        $this->assertStringContainsString('SAVE/2025', $rendered['html']);
        $this->assertStringContainsString('SAVE/2025', $rendered['text']);
    }
}
