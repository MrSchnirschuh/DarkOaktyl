<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ config('app.name', 'DarkOak') }} - @yield('title', 'Welcome')</title>

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
        <link rel="manifest" href="/favicons/manifest.json">
        <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#bc6e3c">
        <link rel="shortcut icon" href="/favicons/favicon.ico">
        <meta name="msapplication-config" content="/favicons/browserconfig.xml">
        <meta name="theme-color" content="#0e4688">

        <style>
            @import url('//fonts.googleapis.com/css?family=Rubik:300,400,500&display=swap');
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Rubik', sans-serif;
                background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                color: #f5f5f5;
                min-height: 100vh;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            
            nav {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px 0;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                margin-bottom: 40px;
            }
            
            nav h1 {
                font-size: 28px;
                font-weight: 500;
                color: #f5f5f5;
            }
            
            nav ul {
                display: flex;
                list-style: none;
                gap: 30px;
            }
            
            nav a {
                color: #f5f5f5;
                text-decoration: none;
                font-weight: 400;
                transition: color 0.3s ease;
            }
            
            nav a:hover,
            nav a.active {
                color: #bc6e3c;
            }
            
            .hero {
                text-align: center;
                padding: 60px 20px;
            }
            
            .hero h2 {
                font-size: 48px;
                margin-bottom: 20px;
                background: linear-gradient(45deg, #bc6e3c, #d4844f);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .hero p {
                font-size: 20px;
                color: #c0c0c0;
                margin-bottom: 40px;
                line-height: 1.6;
            }
            
            .button {
                display: inline-block;
                padding: 15px 40px;
                background: linear-gradient(45deg, #bc6e3c, #d4844f);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 500;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            .button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(188, 110, 60, 0.3);
            }
            
            .features {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 30px;
                margin: 60px 0;
            }
            
            .feature-card {
                background: rgba(255, 255, 255, 0.05);
                padding: 30px;
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                transition: transform 0.3s ease, border-color 0.3s ease;
            }
            
            .feature-card:hover {
                transform: translateY(-5px);
                border-color: #bc6e3c;
            }
            
            .feature-card h3 {
                font-size: 24px;
                margin-bottom: 15px;
                color: #bc6e3c;
            }
            
            .feature-card p {
                color: #c0c0c0;
                line-height: 1.6;
            }
            
            footer {
                text-align: center;
                padding: 40px 20px;
                margin-top: 80px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                color: #888;
            }
            
            @media (max-width: 768px) {
                nav {
                    flex-direction: column;
                    gap: 20px;
                }
                
                .hero h2 {
                    font-size: 36px;
                }
                
                .hero p {
                    font-size: 18px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <nav>
                <h1>{{ config('app.name', 'DarkOak') }}</h1>
                <ul>
                    <li><a href="/" class="{{ Request::is('/') ? 'active' : '' }}">Home</a></li>
                    <li><a href="/documentation" class="{{ Request::is('documentation') ? 'active' : '' }}">Documentation</a></li>
                    <li><a href="{{ env('APP_URL', 'http://panel.darkoak.eu') }}/auth/login">Login to Panel</a></li>
                </ul>
            </nav>

            @yield('content')

            <footer>
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'DarkOak') }}. All rights reserved.</p>
            </footer>
        </div>
    </body>
</html>
