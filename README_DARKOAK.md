# DarkOak Custom Installation Branch

This branch (`darkoak`) is a customized version of DarkOaktyl designed for a specific installation with domain separation.

## Key Features of This Branch

### Domain Separation
- **Root Domain** (e.g., `darkoak.eu`): Public-facing website without authentication
- **Panel Subdomain** (e.g., `panel.darkoak.eu`): Full DarkOaktyl panel with authentication

### Public Website
The root domain hosts a public website that includes:
- Landing page with features and information
- Installation documentation page
- No authentication required
- Links to the panel login

### Renamed Structure
Folders and namespaces have been renamed from the original Jexpanel structure to DarkOak naming conventions.

## Installation

### Environment Configuration

Add these variables to your `.env` file to enable domain separation:

```bash
# Root domain for public website (leave empty to disable domain separation)
APP_ROOT_DOMAIN=darkoak.eu

# Panel subdomain (default: panel)
PANEL_SUBDOMAIN=panel

# Panel URL
APP_URL=http://panel.darkoak.eu
```

If `APP_ROOT_DOMAIN` is left empty, the panel will work on all domains (standard installation).

### Web Server Configuration

#### Nginx Example

```nginx
# Public website (darkoak.eu)
server {
    listen 80;
    server_name darkoak.eu;
    root /var/www/DarkOaktyl/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}

# Panel (panel.darkoak.eu)
server {
    listen 80;
    server_name panel.darkoak.eu;
    root /var/www/DarkOaktyl/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Routes

### Public Website Routes
- `/` - Public landing page
- `/documentation` - Installation documentation

### Panel Routes (on subdomain when domain separation is enabled)
- `/` - Dashboard (requires authentication)
- `/auth/*` - Authentication pages
- `/admin/*` - Admin panel
- `/server/:id/*` - Server management
- `/api/*` - API endpoints

## Development

Follow the standard DarkOaktyl development process:

```bash
# Install dependencies
composer install
pnpm install

# Build assets
pnpm run build

# For development with hot reload
pnpm run dev
```

## Differences from Main DarkOaktyl

1. **Domain-based routing**: Routes are separated based on domain (public vs. panel)
2. **Public website**: New public-facing website at root domain
3. **No authentication on root**: Root domain pages are accessible without login
4. **Documentation page**: Built-in installation guide accessible from public site
5. **Branch-specific**: This is a custom branch, not intended for upstream merging

## Support

- Discord (DarkOaktyl Support): https://discord.gg/ecCVKteUBE
- GitHub: https://github.com/MrSchnirschuh/DarkOaktyl
- Website: https://darkoak.eu

## License

MIT License - Same as DarkOaktyl
