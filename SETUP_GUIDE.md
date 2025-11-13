# Quick Setup Guide for DarkOak Branch

This guide will help you set up the DarkOak custom installation with domain separation.

## Prerequisites

- Server with Ubuntu 20.04+ or Debian 11+
- PHP 8.1 or 8.2
- MySQL 5.7+ or MariaDB 10.2+
- Redis
- Nginx or Apache
- Node.js 16.13+ and pnpm

## Quick Installation

### 1. Clone and Checkout Branch

```bash
cd /var/www
git clone https://github.com/MrSchnirschuh/DarkOaktyl.git
cd DarkOaktyl
git checkout darkoak
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
pnpm install
```

### 3. Configure Environment

```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file
nano .env
```

**Important .env settings for DarkOak:**

```env
# Application settings
APP_URL=https://panel.darkoak.eu
APP_ENV=production
APP_DEBUG=false

# Domain separation (DarkOak specific)
APP_ROOT_DOMAIN=darkoak.eu
PANEL_SUBDOMAIN=panel

# Database configuration
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=darkoaktyl
DB_USERNAME=darkoaktyl
DB_PASSWORD=your_secure_password

# Redis configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### 4. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE darkoaktyl;
CREATE USER 'darkoaktyl'@'127.0.0.1' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON darkoaktyl.* TO 'darkoaktyl'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
php artisan migrate --seed
```

### 5. Build Frontend

```bash
pnpm run build
```

### 6. Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data /var/www/DarkOaktyl
```

### 7. Configure Web Server

```bash
# Copy nginx configuration
cp nginx-darkoak.conf /etc/nginx/sites-available/darkoak
ln -s /etc/nginx/sites-available/darkoak /etc/nginx/sites-enabled/

# Test and reload nginx
nginx -t
systemctl reload nginx
```

### 8. Configure Queue Worker

Create systemd service file: `/etc/systemd/system/darkoaktyl-worker.service`

```ini
[Unit]
Description=DarkOaktyl Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/DarkOaktyl
ExecStart=/usr/bin/php /var/www/DarkOaktyl/artisan queue:work --queue=high,standard,low --tries=3 --sleep=3
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Then enable and start:

```bash
systemctl enable darkoaktyl-worker
systemctl start darkoaktyl-worker
```

### 9. Setup Cron (Optional but Recommended)

Add to crontab:

```bash
crontab -e
```

Add this line:

```
* * * * * php /var/www/DarkOaktyl/artisan schedule:run >> /dev/null 2>&1
```

### 10. Configure SSL (Recommended)

```bash
# Install certbot
apt install certbot python3-certbot-nginx

# Obtain certificates for both domains
certbot --nginx -d darkoak.eu -d panel.darkoak.eu

# Certbot will automatically update nginx configuration
```

## Accessing Your Installation

- **Public Website**: https://darkoak.eu
- **Panel**: https://panel.darkoak.eu
- **Documentation**: https://darkoak.eu/documentation

## First Login

1. Create your first admin user:

```bash
php artisan p:user:make
```

Follow the prompts to create an admin account.

## Troubleshooting

### Routes not working

```bash
# Clear and cache routes
php artisan route:clear
php artisan route:cache
php artisan config:clear
php artisan config:cache
```

### Public website not showing

- Check that `APP_ROOT_DOMAIN` is set in .env
- Verify DNS points both domains to your server
- Check nginx configuration is correct

### Permission errors

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data /var/www/DarkOaktyl
```

### Queue not processing

```bash
# Check worker status
systemctl status darkoaktyl-worker

# View logs
journalctl -u darkoaktyl-worker -f
```

## Updating

```bash
cd /var/www/DarkOaktyl

# Pull latest changes
git pull origin darkoak

# Update dependencies
composer install --no-dev --optimize-autoloader
pnpm install

# Build frontend
pnpm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
systemctl restart darkoaktyl-worker
```

## Support

- Discord: https://discord.gg/ecCVKteUBE
- GitHub: https://github.com/MrSchnirschuh/DarkOaktyl
- Website: https://darkoak.eu
