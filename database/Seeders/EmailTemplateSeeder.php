<?php

namespace Database\Seeders;

use Everest\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'key' => 'welcome',
                'name' => 'Welcome Email',
                'subject' => 'Welcome to {{app_name}}!',
                'body_html' => '<h1>Welcome to {{app_name}}!</h1><p>Dear {{user_name}},</p><p>Thank you for joining us. We are excited to have you on board!</p><p>Get started by visiting your <a href="{{app_url}}">dashboard</a>.</p><p>Best regards,<br>The {{app_name}} Team</p>',
                'body_text' => 'Welcome to {{app_name}}! Dear {{user_name}}, Thank you for joining us. We are excited to have you on board! Get started by visiting your dashboard at {{app_url}}. Best regards, The {{app_name}} Team',
                'variables' => ['app_name', 'user_name', 'app_url'],
                'enabled' => true,
            ],
            [
                'key' => 'password_reset',
                'name' => 'Password Reset',
                'subject' => 'Reset Your Password',
                'body_html' => '<h1>Password Reset Request</h1><p>Dear {{user_name}},</p><p>You have requested to reset your password. Click the link below to reset it:</p><p><a href="{{reset_url}}">Reset Password</a></p><p>If you did not request this, please ignore this email.</p><p>Best regards,<br>The {{app_name}} Team</p>',
                'body_text' => 'Password Reset Request. Dear {{user_name}}, You have requested to reset your password. Visit this link to reset it: {{reset_url}}. If you did not request this, please ignore this email. Best regards, The {{app_name}} Team',
                'variables' => ['user_name', 'reset_url', 'app_name'],
                'enabled' => true,
            ],
            [
                'key' => 'coupon_code',
                'name' => 'Coupon Code Email',
                'subject' => 'Your Exclusive Coupon Code!',
                'body_html' => '<h1>Special Offer for You!</h1><p>Dear {{user_name}},</p><p>We have a special coupon code just for you: <strong>{{coupon_code}}</strong></p><p>Use it before {{expiry_date}} to get {{discount}}% off!</p><p>Visit <a href="{{app_url}}">our website</a> to redeem your code.</p><p>Best regards,<br>The {{app_name}} Team</p>',
                'body_text' => 'Special Offer for You! Dear {{user_name}}, We have a special coupon code just for you: {{coupon_code}}. Use it before {{expiry_date}} to get {{discount}}% off! Visit {{app_url}} to redeem your code. Best regards, The {{app_name}} Team',
                'variables' => ['user_name', 'coupon_code', 'expiry_date', 'discount', 'app_url', 'app_name'],
                'enabled' => true,
            ],
            [
                'key' => 'resource_gift',
                'name' => 'Resource Gift Email',
                'subject' => 'You\'ve Received Resources!',
                'body_html' => '<h1>Resources Gifted!</h1><p>Dear {{user_name}},</p><p>Good news! You have received the following resources:</p><ul>{{resource_list}}</ul><p>Use this redemption code: <strong>{{redemption_code}}</strong></p><p>Visit your <a href="{{dashboard_url}}">dashboard</a> to redeem your resources.</p><p>Best regards,<br>The {{app_name}} Team</p>',
                'body_text' => 'Resources Gifted! Dear {{user_name}}, Good news! You have received resources: {{resource_list}}. Use this redemption code: {{redemption_code}}. Visit your dashboard at {{dashboard_url}} to redeem your resources. Best regards, The {{app_name}} Team',
                'variables' => ['user_name', 'resource_list', 'redemption_code', 'dashboard_url', 'app_name'],
                'enabled' => true,
            ],
            [
                'key' => 'christmas_special',
                'name' => 'Christmas Special',
                'subject' => '🎄 Merry Christmas - Special Holiday Offer!',
                'body_html' => '<h1>🎄 Merry Christmas!</h1><p>Dear {{user_name}},</p><p>Happy Holidays from all of us at {{app_name}}!</p><p>As a special Christmas gift, use this coupon code: <strong>{{coupon_code}}</strong></p><p>Valid until {{expiry_date}}</p><p>Best wishes,<br>The {{app_name}} Team</p>',
                'body_text' => 'Merry Christmas! Dear {{user_name}}, Happy Holidays from all of us at {{app_name}}! As a special Christmas gift, use this coupon code: {{coupon_code}}. Valid until {{expiry_date}}. Best wishes, The {{app_name}} Team',
                'variables' => ['user_name', 'coupon_code', 'expiry_date', 'app_name'],
                'enabled' => true,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
