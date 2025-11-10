# Email System Setup and Usage Guide

## Overview

This document explains the new email system that has been implemented for DarkOaktyl (Jexpanel). The system provides:

- Custom email templates with variable substitution
- Scheduled emails with cron triggers
- Admin UI for managing email templates and schedules
- Support for event-triggered emails
- Examples: Christmas coupons, resource gifts, welcome emails, etc.

## Installation Steps

### 1. Configure Email Settings

Update your `.env` file with your email provider settings:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="Your Panel Name"
```

### 2. Run Database Migrations

Run the migrations to create the necessary database tables:

```bash
php artisan migrate
```

This will create two tables:
- `email_templates` - Stores email template configurations
- `scheduled_emails` - Stores scheduled email configurations

### 3. Seed Default Email Templates

Run the seeder to create default email templates:

```bash
php artisan db:seed --class=EmailTemplateSeeder
```

This creates the following templates:
- **welcome** - Welcome email for new users
- **password_reset** - Password reset email
- **coupon_code** - Coupon code promotional email
- **resource_gift** - Resource gift redemption email
- **christmas_special** - Christmas special offer email

### 4. Set Up Cron Job

Add this to your crontab to process scheduled emails every 5 minutes:

```bash
* * * * * cd /path/to/your/panel && php artisan schedule:run >> /dev/null 2>&1
```

Or manually run:

```bash
php artisan p:email:process
```

## Using the Admin UI

### Accessing Email Management

1. Log into the admin panel
2. Navigate to **Admin → Emails**
3. You'll see two tabs:
   - **Templates** - Manage email templates
   - **Scheduled** - Manage scheduled emails

### Managing Email Templates

#### Creating a Template

1. Click **"Create Template"**
2. Fill in the form:
   - **Key**: Unique identifier (e.g., `welcome`, `coupon_2024`)
   - **Name**: Human-readable name
   - **Subject**: Email subject line (supports variables)
   - **Body HTML**: HTML email content (supports variables)
   - **Body Text**: Plain text version (optional, recommended)
   - **Variables**: Array of variable names available (e.g., `user_name`, `coupon_code`)
   - **Enabled**: Toggle to enable/disable the template

#### Using Variables in Templates

Variables are enclosed in double curly braces: `{{variable_name}}`

Example subject:
```
Welcome to {{app_name}}, {{user_name}}!
```

Example HTML body:
```html
<h1>Welcome {{user_name}}!</h1>
<p>Your account has been created successfully.</p>
<p>Your coupon code is: <strong>{{coupon_code}}</strong></p>
```

#### Testing Templates

1. Open a template
2. Click **"Test"**
3. Enter a test email address
4. Provide test data for variables (JSON format)
5. Click **"Send Test"**

### Managing Scheduled Emails

#### Creating a Scheduled Email

1. Click **"Create Scheduled Email"**
2. Fill in the form:
   - **Name**: Descriptive name
   - **Template**: Select an email template
   - **Trigger Type**:
     - `cron` - Run on a cron schedule (e.g., `0 0 25 12 *` for Christmas)
     - `date` - Run once at a specific date/time
     - `event` - Trigger on system events
   - **Trigger Value**: Cron expression or ISO date
   - **Recipients**: Configure who receives the email:
     - `all` - All users
     - `specific` - Specific email addresses
     - `role` - Users with a specific role (e.g., admins)
     - `servers` - Users with servers
   - **Template Data**: JSON object with variable values
   - **Enabled**: Toggle to enable/disable

#### Cron Expression Examples

- `0 0 25 12 *` - Every Christmas (Dec 25) at midnight
- `0 9 * * 1` - Every Monday at 9:00 AM
- `0 0 1 * *` - First day of every month at midnight
- `*/5 * * * *` - Every 5 minutes

## API Usage

### Sending an Email Programmatically

```php
use Everest\Services\Email\EmailService;

$emailService = app(EmailService::class);

// Send to a single user
$emailService->sendTemplatedEmail('welcome', 'user@example.com', [
    'user_name' => 'John Doe',
    'app_name' => 'DarkOaktyl',
    'app_url' => 'https://panel.example.com',
]);

// Send to multiple users
$emailService->sendBulkTemplatedEmail('coupon_code', [
    'user1@example.com',
    'user2@example.com',
], [
    'coupon_code' => 'XMAS2024',
    'discount' => '20',
    'expiry_date' => '2024-12-31',
]);
```

### API Endpoints

All endpoints require admin authentication.

#### Email Templates

- `GET /api/application/emails/templates` - List all templates
- `POST /api/application/emails/templates` - Create a template
- `GET /api/application/emails/templates/{id}` - Get a template
- `PATCH /api/application/emails/templates/{id}` - Update a template
- `DELETE /api/application/emails/templates/{id}` - Delete a template
- `POST /api/application/emails/templates/{id}/test` - Test a template

#### Scheduled Emails

- `GET /api/application/emails/scheduled` - List all scheduled emails
- `POST /api/application/emails/scheduled` - Create a scheduled email
- `GET /api/application/emails/scheduled/{id}` - Get a scheduled email
- `PATCH /api/application/emails/scheduled/{id}` - Update a scheduled email
- `DELETE /api/application/emails/scheduled/{id}` - Delete a scheduled email

## Use Cases

### Christmas Coupon Campaign

1. Create a template with key `christmas_coupon`
2. Add variables: `user_name`, `coupon_code`, `expiry_date`
3. Create a scheduled email:
   - Trigger: `0 0 24 12 *` (Dec 24 at midnight)
   - Recipients: All users
   - Template data: `{"coupon_code": "XMAS2024", "expiry_date": "2024-12-31"}`

### Resource Gift Email

1. Create a template with key `resource_gift`
2. Add variables: `user_name`, `resource_list`, `redemption_code`
3. Trigger via API when sending resources:
   ```php
   $emailService->sendTemplatedEmail('resource_gift', $user->email, [
       'user_name' => $user->name,
       'resource_list' => '<li>100 GB Storage</li><li>2 GB RAM</li>',
       'redemption_code' => 'GIFT-' . Str::random(8),
   ]);
   ```

### Monthly Newsletter

1. Create a template with key `newsletter`
2. Create a scheduled email:
   - Trigger: `0 9 1 * *` (First day of month at 9 AM)
   - Recipients: All users
   - Template data: Update monthly with new content

## Troubleshooting

### Emails Not Sending

1. Check your `.env` mail configuration
2. Test email configuration: `php artisan tinker`
   ```php
   Mail::raw('Test', function($msg) {
       $msg->to('test@example.com')->subject('Test');
   });
   ```
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify queue is running (if using queued emails)

### Scheduled Emails Not Running

1. Verify cron job is set up correctly
2. Check if scheduler is running: `php artisan schedule:list`
3. Manually trigger: `php artisan p:email:process`
4. Check `scheduled_emails` table for `enabled = 1`

### Variables Not Replacing

1. Ensure variable names match exactly (case-sensitive)
2. Use `{{variable_name}}` format (with or without spaces)
3. Check template data is passed correctly as array

## Advanced Configuration

### Custom Email Service Provider

If using a different email provider (Mailgun, Postmark, etc.), update `.env`:

```env
# For Mailgun
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.example.com
MAILGUN_SECRET=your-secret-key

# For Postmark
MAIL_MAILER=postmark
POSTMARK_TOKEN=your-token
```

### Queue Configuration

For better performance with bulk emails, configure a queue:

```env
QUEUE_CONNECTION=redis
```

Then emails will be processed asynchronously.

## Security Considerations

1. **Never expose email credentials** - Keep them in `.env` only
2. **Validate email addresses** - The system validates but be cautious with user input
3. **Rate limiting** - Consider rate limiting for bulk emails
4. **Template injection** - Admin-only access prevents malicious template injection
5. **Log monitoring** - Monitor logs for failed email deliveries

## Support

For issues or questions:
1. Check the logs: `storage/logs/laravel.log`
2. Review this documentation
3. Contact the development team
4. Open an issue on GitHub

## Future Enhancements

Potential future improvements:
- Email preview before sending
- Email statistics and tracking
- A/B testing for email templates
- Template version control
- Email bounce handling
- Unsubscribe management
