<?php

namespace DarkOak\Tests\Integration\Api\Application\Billing;

use DarkOak\Mail\TemplatedMail;
use DarkOak\Models\Billing\Coupon;
use DarkOak\Models\EmailTemplate;
use DarkOak\Models\EmailTheme;
use DarkOak\Models\User;
use DarkOak\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class CouponSendControllerTest extends ApplicationApiIntegrationTestCase
{
    public function testPersonalizedCouponsAreGeneratedAndDelivered(): void
    {
        Mail::fake();

        $admin = $this->getApiUser();
        $recipient = User::factory()->create();

        $theme = EmailTheme::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Theme',
            'description' => null,
            'primary_color' => '#2563eb',
            'secondary_color' => '#1e40af',
            'accent_color' => '#f97316',
            'background_color' => '#0f172a',
            'body_color' => '#ffffff',
            'text_color' => '#0f172a',
            'muted_text_color' => '#475569',
            'button_color' => '#2563eb',
            'button_text_color' => '#ffffff',
            'logo_url' => null,
            'footer_text' => 'Testing',
            'is_default' => false,
            'meta' => ['appearance' => 'system'],
            'variant_mode' => 'single',
            'light_palette' => null,
        ]);

        $template = EmailTemplate::query()->create([
            'uuid' => (string) Str::uuid(),
            'key' => 'testing.personalized-coupon-' . Str::random(4),
            'name' => 'Testing Personalized Coupon',
            'description' => 'Used for integration testing of personalized coupon delivery.',
            'subject' => 'Exclusive code {{ $couponCode ?? $code ?? "missing" }}',
            'content' => <<<'BLADE'
# Your offer

Your personalized code is **{{ $couponCode ?? $code ?? 'missing' }}**.
BLADE,
            'locale' => 'en',
            'is_enabled' => true,
            'theme_id' => $theme->id,
            'metadata' => [],
        ]);

        $coupon = Coupon::query()->create([
            'code' => 'BASE-TEST',
            'name' => 'Base Coupon',
            'description' => 'Base coupon for personalization tests.',
            'type' => 'percentage',
            'percentage' => 10,
            'max_usages' => null,
            'per_user_limit' => null,
            'applies_to_term_id' => null,
            'is_active' => true,
            'created_by_id' => $admin->id,
            'updated_by_id' => $admin->id,
            'metadata' => ['segment' => 'test'],
        ]);

        $response = $this->postJson("/api/application/billing/coupons/{$coupon->uuid}/send", [
            'template_uuid' => $template->uuid,
            'user_ids' => [$recipient->id],
            'personalize' => true,
            'personalized_code_prefix' => 'VIP-',
            'personalized_code_length' => 6,
            'personalized_max_usages' => 1,
            'personalized_per_user_limit' => 1,
        ]);

        $response->assertNoContent();

        $personalized = Coupon::query()->where('parent_coupon_id', $coupon->id)->first();
        $this->assertNotNull($personalized, 'Expected a personalized coupon to be created.');
        $this->assertSame($recipient->id, $personalized->personalized_for_id);
        $this->assertSame($coupon->id, $personalized->parent_coupon_id);
        $this->assertSame(1, $personalized->max_usages);
        $this->assertSame(1, $personalized->per_user_limit);
        $this->assertTrue(Str::startsWith($personalized->code, 'VIP-'));
        $this->assertTrue($personalized->metadata['personalized'] ?? false);
        $this->assertSame($coupon->uuid, $personalized->metadata['parent_coupon_uuid'] ?? null);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($recipient, $personalized) {
            $rendered = $mail->render();

            return $mail->hasTo($recipient->email)
                && str_contains($rendered, $personalized->code);
        });
    }
}

