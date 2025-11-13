@extends('templates/public.layout')

@section('title', 'Installation Documentation')

@section('content')
    <div style="max-width: 900px; margin: 0 auto;">
        <h2 style="font-size: 36px; margin-bottom: 30px; color: #bc6e3c;">Installation Documentation</h2>
        
        <div style="background: rgba(255, 255, 255, 0.05); padding: 30px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 30px;">
            <h3 style="font-size: 24px; margin-bottom: 20px; color: #d4844f;">DarkOak Installation Guide</h3>
            
            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 20px; margin-bottom: 15px; color: #f5f5f5;">System Requirements</h4>
                <ul style="list-style: disc; margin-left: 30px; line-height: 1.8; color: #c0c0c0;">
                    <li>PHP 8.1 or 8.2</li>
                    <li>MySQL 5.7+ or MariaDB 10.2+</li>
                    <li>Redis (for cache and queue)</li>
                    <li>Composer</li>
                    <li>Node.js 16.13 or higher</li>
                    <li>pnpm 9.0.6</li>
                </ul>
            </div>

            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 20px; margin-bottom: 15px; color: #f5f5f5;">Installation Steps</h4>
                
                <div style="margin-bottom: 20px;">
                    <h5 style="font-size: 18px; margin-bottom: 10px; color: #bc6e3c;">1. Clone the Repository</h5>
                    <pre style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; color: #f5f5f5;"><code>git clone https://github.com/MrSchnirschuh/DarkOaktyl.git
cd DarkOaktyl
git checkout darkoak</code></pre>
                </div>

                <div style="margin-bottom: 20px;">
                    <h5 style="font-size: 18px; margin-bottom: 10px; color: #bc6e3c;">2. Install Dependencies</h5>
                    <pre style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; color: #f5f5f5;"><code>composer install --no-dev --optimize-autoloader
pnpm install</code></pre>
                </div>

                <div style="margin-bottom: 20px;">
                    <h5 style="font-size: 18px; margin-bottom: 10px; color: #bc6e3c;">3. Environment Configuration</h5>
                    <p style="margin-bottom: 10px; color: #c0c0c0;">Copy the example environment file and configure your settings:</p>
                    <pre style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; color: #f5f5f5;"><code>cp .env.example .env
php artisan key:generate</code></pre>
                    
                    <p style="margin-top: 15px; margin-bottom: 10px; color: #c0c0c0;">For DarkOak custom installation with domain separation, add these variables to your .env:</p>
                    <pre style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; color: #f5f5f5;"><code># Root domain for public website
APP_ROOT_DOMAIN=darkoak.eu

# Panel will be accessible at panel.darkoak.eu
PANEL_SUBDOMAIN=panel
APP_URL=http://panel.darkoak.eu</code></pre>
                </div>

                <div style="margin-bottom: 20px;">
                    <h5 style="font-size: 18px; margin-bottom: 10px; color: #bc6e3c;">4. Database Setup</h5>
                    <p style="margin-bottom: 10px; color: #c0c0c0;">Configure your database in .env, then run migrations:</p>
                    <pre style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; color: #f5f5f5;"><code>php artisan migrate --seed
php artisan db:seed --class=SettingSeeder</code></pre>
                </div>

                <div style="margin-bottom: 20px;">
                    <h5 style="font-size: 18px; margin-bottom: 10px; color: #bc6e3c;">5. Build Frontend Assets</h5>
                    <pre style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; color: #f5f5f5;"><code>pnpm run build</code></pre>
                </div>

                <div style="margin-bottom: 20px;">
                    <h5 style="font-size: 18px; margin-bottom: 10px; color: #bc6e3c;">6. Set Permissions</h5>
                    <pre style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; color: #f5f5f5;"><code>chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data /var/www/DarkOaktyl</code></pre>
                </div>

                <div style="margin-bottom: 20px;">
                    <h5 style="font-size: 18px; margin-bottom: 10px; color: #bc6e3c;">7. Web Server Configuration</h5>
                    <p style="margin-bottom: 10px; color: #c0c0c0;">For Nginx with domain separation:</p>
                    <pre style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; color: #f5f5f5; font-size: 12px;"><code># Public website (darkoak.eu)
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
}</code></pre>
                </div>

                <div style="margin-bottom: 20px;">
                    <h5 style="font-size: 18px; margin-bottom: 10px; color: #bc6e3c;">8. Queue Worker Setup</h5>
                    <p style="margin-bottom: 10px; color: #c0c0c0;">Set up the queue worker as a systemd service:</p>
                    <pre style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; color: #f5f5f5;"><code>php artisan queue:work --queue=high,standard,low --tries=3</code></pre>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 20px; margin-bottom: 15px; color: #f5f5f5;">Key Differences from Standard DarkOaktyl</h4>
                <ul style="list-style: disc; margin-left: 30px; line-height: 1.8; color: #c0c0c0;">
                    <li><strong>Domain Separation:</strong> Public website at root domain (darkoak.eu), panel at subdomain (panel.darkoak.eu)</li>
                    <li><strong>Public Website:</strong> No authentication required for the root domain landing page</li>
                    <li><strong>Renamed Folders:</strong> Folders have been renamed from the original Jexpanel structure to DarkOak naming conventions</li>
                    <li><strong>Custom Branch:</strong> This is the 'darkoak' branch, specifically for this custom installation</li>
                </ul>
            </div>

            <div style="margin-bottom: 30px;">
                <h4 style="font-size: 20px; margin-bottom: 15px; color: #f5f5f5;">Support & Resources</h4>
                <ul style="list-style: disc; margin-left: 30px; line-height: 1.8; color: #c0c0c0;">
                    <li><strong>Website:</strong> <a href="https://darkoak.eu" style="color: #bc6e3c;">https://darkoak.eu</a></li>
                    <li><strong>GitHub:</strong> <a href="https://github.com/MrSchnirschuh/DarkOaktyl" style="color: #bc6e3c;">github.com/MrSchnirschuh/DarkOaktyl</a></li>
                    <li><strong>Discord Support:</strong> <a href="https://discord.gg/ecCVKteUBE" style="color: #bc6e3c;">discord.gg/ecCVKteUBE</a> (DarkOaktyl)</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
