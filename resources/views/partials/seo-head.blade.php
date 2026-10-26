<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
@if(isset($authUser) || auth()->check())
<meta name="user-role" content="{{ auth()->user()?->role ?? ($authUser->role ?? '') }}">
@endif

<!-- SEO & Metadata -->
<title>{{ !empty($title) ? $title . ' - myITS Classroom' : 'myITS Classroom - Institut Teknologi Sepuluh Nopember' }}</title>
<meta name="description" content="{{ $description ?? 'Portal pembelajaran digital, manajemen perkuliahan, perwalian KRS, dan perpustakaan online Institut Teknologi Sepuluh Nopember (ITS).' }}">
<meta name="robots" content="{{ $robots ?? (auth()->check() ? 'noindex, nofollow, noarchive' : 'index, follow') }}">
<link rel="canonical" href="{{ url()->current() }}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="myITS Classroom">
<meta property="og:title" content="{{ !empty($title) ? $title . ' - myITS Classroom' : 'myITS Classroom' }}">
<meta property="og:description" content="{{ $description ?? 'Portal pembelajaran digital Institut Teknologi Sepuluh Nopember (ITS).' }}">
<meta property="og:image" content="{{ asset('image/Classroom.png') }}">
<meta property="og:locale" content="id_ID">

<!-- Twitter Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ !empty($title) ? $title . ' - myITS Classroom' : 'myITS Classroom' }}">
<meta name="twitter:description" content="{{ $description ?? 'Portal pembelajaran digital Institut Teknologi Sepuluh Nopember (ITS).' }}">
<meta name="twitter:image" content="{{ asset('image/Classroom.png') }}">

<!-- PWA & Mobile Capabilities -->
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#00337F">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="myITS Classroom">
<meta name="application-name" content="myITS Classroom">

<!-- Favicon & Touch Icons -->
<link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

<!-- Schema.org EducationalApplication JSON-LD -->
@php
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'WebApplication',
    'name' => 'myITS Classroom',
    'applicationCategory' => 'EducationalApplication',
    'operatingSystem' => 'All',
    'browserRequirements' => 'Requires JavaScript. Requires HTML5.',
    'url' => url('/'),
    'description' => 'Portal pembelajaran digital dan manajemen perkuliahan Institut Teknologi Sepuluh Nopember.',
    'provider' => [
        '@type' => 'CollegeOrUniversity',
        'name' => 'Institut Teknologi Sepuluh Nopember',
        'url' => 'https://www.its.ac.id',
    ],
];
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

<!-- Service Worker Registration for PWA -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function (err) {
                console.warn('[SW] Registration failed:', err);
            });
        });
    }
</script>

<!-- SSR Pre-Hydrated State -->
<script>
    @if(isset($authUser))
    window.__AUTH_USER__ = @json($authUser);
    @endif
    @if(isset($initialData))
    window.__INITIAL_DATA__ = @json($initialData);
    @endif
</script>
