@extends('templates/public.layout')

@section('title', 'Home')

@section('content')
    <div class="hero">
        <h2>Welcome to {{ config('app.name', 'DarkOak') }}</h2>
        <p>
            A powerful game server management panel with integrated billing, email system,
            and highly customizable themes. Built on modern technology for security and performance.
        </p>
        <a href="{{ env('APP_URL', 'http://panel.darkoak.eu') }}/auth/login" class="button">Access Panel</a>
    </div>

    <div class="features">
        <div class="feature-card">
            <h3>🎮 Game Server Management</h3>
            <p>
                Manage your game servers with ease using our intuitive control panel. 
                Support for multiple games and instant deployment.
            </p>
        </div>

        <div class="feature-card">
            <h3>💳 Integrated Billing</h3>
            <p>
                Built-in billing system with support for Stripe and PayPal. 
                Manage subscriptions, coupons, and invoices effortlessly.
            </p>
        </div>

        <div class="feature-card">
            <h3>🎨 Customizable Themes</h3>
            <p>
                Highly customizable themes with light, dark, and system modes. 
                Automatic theme activation based on date and time.
            </p>
        </div>

        <div class="feature-card">
            <h3>📧 Email Integration</h3>
            <p>
                Integrated email system for sending coupons, notifications, and updates. 
                Support for timed sending and templates.
            </p>
        </div>

        <div class="feature-card">
            <h3>🔒 Advanced Security</h3>
            <p>
                Two-factor authentication, role-based access control, 
                and comprehensive security features to protect your infrastructure.
            </p>
        </div>

        <div class="feature-card">
            <h3>⚡ Modern Technology</h3>
            <p>
                Built with PHP, Laravel, TypeScript, React, and Docker. 
                Fast, reliable, and continuously updated.
            </p>
        </div>
    </div>
@endsection
