# DarkOak Branch - Summary of Changes

This document provides a comprehensive overview of the changes made to create the DarkOak custom installation branch.

## Purpose

The DarkOak branch is designed for a custom installation that separates the public-facing website from the panel interface using domain-based routing. This allows:
- A public website at the root domain (e.g., darkoak.eu) that doesn't require authentication
- The full DarkOaktyl panel at a subdomain (e.g., panel.darkoak.eu)

## Architecture Changes

### 1. Domain-Based Routing

**File Modified:** `app/Providers/RouteServiceProvider.php`

The RouteServiceProvider has been enhanced to support domain-based routing:

- When `APP_ROOT_DOMAIN` is configured, routes are separated by domain
- Public website routes are loaded on the root domain
- Panel routes (dashboard, admin, auth, API) are loaded on the subdomain
- When `APP_ROOT_DOMAIN` is not set, the application works as standard DarkOaktyl

**Implementation:**
```php
if ($rootDomain) {
    // Public website on root domain
    Route::domain($rootDomain)->middleware('web')
        ->group(base_path('routes/public.php'));
    
    // Panel on subdomain
    Route::domain($panelSubdomain . '.' . $rootDomain)
        ->group($panelRoutes);
} else {
    // Standard single-domain mode
    $panelRoutes();
}
```

### 2. New Public Website

**Files Created:**
- `app/Http/Controllers/Base/PublicWebsiteController.php` - Controller for public pages
- `routes/public.php` - Public website routes
- `resources/views/templates/public/layout.blade.php` - Base layout for public pages
- `resources/views/templates/public/home.blade.php` - Landing page
- `resources/views/templates/public/documentation.blade.php` - Documentation page

**Features:**
- Modern, responsive design with dark theme
- No authentication required
- Feature showcase on landing page
- Complete installation documentation
- Navigation between pages
- Direct links to panel login

### 3. Configuration

**File Modified:** `.env.example`

New environment variables:
```env
APP_ROOT_DOMAIN=         # Root domain for public website (leave empty to disable)
PANEL_SUBDOMAIN=panel    # Subdomain for the panel (default: panel)
```

### 4. Documentation

**Files Created:**
- `README_DARKOAK.md` - Branch-specific README
- `SETUP_GUIDE.md` - Step-by-step installation guide
- `nginx-darkoak.conf` - Complete nginx configuration

**Documentation includes:**
- Domain separation explanation
- Environment configuration
- Web server setup (nginx)
- SSL configuration
- Queue worker setup
- Cron configuration
- Troubleshooting tips
- Update procedures

### 5. Testing

**File Created:** `tests/Integration/Http/Controllers/Base/PublicWebsiteControllerTest.php`

Tests verify:
- Public home page is accessible without authentication
- Documentation page is accessible without authentication
- Panel routes are properly separated when domain separation is enabled

## File Structure

```
DarkOaktyl/
├── app/
│   ├── Http/Controllers/Base/
│   │   ├── IndexController.php (existing)
│   │   └── PublicWebsiteController.php (NEW)
│   └── Providers/
│       └── RouteServiceProvider.php (MODIFIED)
├── resources/views/templates/
│   ├── base/ (existing)
│   └── public/ (NEW)
│       ├── layout.blade.php
│       ├── home.blade.php
│       └── documentation.blade.php
├── routes/
│   ├── base.php (existing)
│   ├── admin.php (existing)
│   ├── auth.php (existing)
│   └── public.php (NEW)
├── tests/Integration/Http/Controllers/Base/
│   └── PublicWebsiteControllerTest.php (NEW)
├── .env.example (MODIFIED)
├── README_DARKOAK.md (NEW)
├── SETUP_GUIDE.md (NEW)
├── nginx-darkoak.conf (NEW)
└── CHANGES_SUMMARY.md (this file)
```

## Usage

### Standard Installation (No Domain Separation)

If you don't need domain separation, simply don't set `APP_ROOT_DOMAIN` in your `.env` file. The application will work as standard DarkOaktyl.

### Custom Installation (With Domain Separation)

1. Set environment variables:
   ```env
   APP_ROOT_DOMAIN=darkoak.eu
   PANEL_SUBDOMAIN=panel
   APP_URL=https://panel.darkoak.eu
   ```

2. Configure your web server to serve both domains from the same document root

3. Access:
   - Public website: https://darkoak.eu
   - Panel: https://panel.darkoak.eu

## Key Benefits

1. **Professional public presence**: Present your service with a proper landing page
2. **Clear separation**: Public content vs. authenticated panel
3. **Documentation accessibility**: Installation docs available without login
4. **Backward compatible**: Works as standard DarkOaktyl when domain separation is disabled
5. **Minimal changes**: Small, surgical modifications to existing codebase
6. **No breaking changes**: Existing functionality remains unchanged

## Technical Details

### Route Registration
Routes are registered conditionally based on the `APP_ROOT_DOMAIN` configuration. Laravel's `Route::domain()` method ensures routes are only accessible on their designated domain.

### Middleware
- Public routes: Only `web` middleware (sessions, CSRF protection)
- Panel routes: Existing middleware stack (auth, 2FA, admin auth, etc.)

### Views
Public website views are completely separate from the panel's React-based frontend, using simple Blade templates for better performance and SEO.

## Maintenance

### Updating from Upstream
Since this is a custom branch, updates from the main DarkOaktyl repository can be merged:

```bash
git fetch upstream
git merge upstream/main
# Resolve any conflicts in RouteServiceProvider.php if needed
```

### Adding New Public Pages
1. Add route in `routes/public.php`
2. Add method in `PublicWebsiteController.php`
3. Create Blade template in `resources/views/templates/public/`

## Security Considerations

1. Public routes have no authentication - this is intentional
2. Panel routes maintain all existing security measures
3. Domain separation provides additional isolation
4. CSRF protection applies to all web routes
5. SSL/TLS recommended for both domains

## Performance

- Public website uses lightweight Blade templates
- No React/JavaScript framework overhead for public pages
- Simple CSS without heavy dependencies
- Fast loading for better SEO and user experience

## Compatibility

- PHP 8.1+ (same as DarkOaktyl)
- Works with all existing DarkOaktyl features
- Compatible with existing themes and configurations
- No changes to database schema
- No changes to API endpoints

## Support

For issues specific to the DarkOak branch:
- Create an issue on GitHub
- Join Discord: https://discord.gg/ecCVKteUBE
- Check documentation: https://darkoak.eu/documentation

For general DarkOaktyl issues:
- Refer to main DarkOaktyl documentation
- Join Jexpanel Discord: https://discord.gg/qttGR4Z5Pk (for reference, but don't ask for support)
