@php
    $user = Auth::user();
    $appearanceMode = $user?->appearance_mode ?? 'system';
    $appearanceLastMode = $user?->appearance_last_mode ?? 'dark';
    $initialMode = match ($appearanceMode) {
        'light' => 'light',
        'dark' => 'dark',
        default => $appearanceLastMode === 'light' ? 'light' : 'dark',
    };
    $themeColors = $themeConfiguration['colors'] ?? [];
    $initialBackground = $initialMode === 'light'
    ? ($themeColors['background_light'] ?? $themeColors['background'] ?? '#f4f4f5')
    : ($themeColors['background'] ?? '#141414');
    $initialText = $initialMode === 'light'
        ? ($themeColors['text_primary_light'] ?? '#0f172a')
        : ($themeColors['text_primary'] ?? '#f5f5f5');
@endphp
<!DOCTYPE html>
<html lang="en" data-initial-theme="{{ $initialMode }}">
    <head>
    <title>{{ config('app.name', 'DarkOaktyl') }}</title>

        @section('meta')
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
            <meta name="csrf-token" content="{{ csrf_token() }}">
            <meta name="robots" content="noindex">
            <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
            <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
            <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
            <link rel="manifest" href="/favicons/manifest.json">
            <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#bc6e3c">
            <link rel="shortcut icon" href="/favicons/favicon.ico">
            <meta name="msapplication-config" content="/favicons/browserconfig.xml">
            <meta name="theme-color" content="#0e4688">
        @show

        @section('user-data')
            @if(!is_null(Auth::user()))
                <script>
                    window.DarkOaktylUser = {!! json_encode(Auth::user()->toReactObject()) !!};
                </script>
            @endif
            @if(!empty($siteConfiguration))
                <script>
                    window.SiteConfiguration = {!! json_encode($siteConfiguration) !!};
                </script>
            @endif
            @if(!empty($DarkOakConfiguration))
                <script>
                    window.DarkOakConfiguration = {!! json_encode($DarkOakConfiguration) !!};
                </script>
            @endif
            @if(!empty($themeConfiguration))
                <script>
                    window.ThemeConfiguration = {!! json_encode($themeConfiguration) !!};
                </script>
            @endif
        @show
        <style>
            @import url('//fonts.googleapis.com/css?family=Rubik:300,400,500&display=swap');
            @import url('//fonts.googleapis.com/css?family=IBM+Plex+Mono|IBM+Plex+Sans:500&display=swap');

            body {
                background-color: {{ $initialBackground }};
                color: {{ $initialText }};
            }
        </style>

        @yield('assets')

        @include('layouts.scripts')

        @viteReactRefresh
        @vite('resources/scripts/index.tsx')
    </head>
    <body>
        @section('content')
            @yield('above-container')
            @yield('container')
            @yield('below-container')
        @show
    </body>
</html>


