@php
    $hasViteManifest = file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json'));
@endphp
@if ($hasViteManifest)
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@elseif (app()->environment('production'))
    @php
        throw new RuntimeException('Frontend assets are missing. Run npm ci && npm run build before deploying.');
    @endphp
@endif

