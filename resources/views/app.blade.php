<!DOCTYPE html>
<html lang="{{ $config['default_locale'] ?? 'en' }}" dir="{{ in_array($config['default_locale'] ?? 'en', $config['rtl_locales'] ?? []) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $config['brand_name'] ?? 'ERP Integration Hub' }}</title>

    {{-- Preconnect for performance --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

    {{-- Package CSS --}}
    @if(file_exists(public_path('vendor/erp-integration-hub/app.css')))
        <link rel="stylesheet" href="{{ asset('vendor/erp-integration-hub/app.css') }}">
    @endif

    {{-- Inline critical vars for Vue --}}
    <script>
        window.__BRIDGE_CONFIG__ = @json($config);
    </script>
</head>
<body class="antialiased">
    <div id="erp-integration-hub-app">
        {{-- Vue SPA mounts here --}}
        <div class="loading-skeleton" style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f172a;">
            <div style="text-align:center;color:#94a3b8;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" style="margin:0 auto 16px;display:block;animation:spin 1.5s linear infinite;">
                    <circle cx="12" cy="12" r="10" stroke="#334155" stroke-width="2"/>
                    <path d="M12 2a10 10 0 0 1 10 10" stroke="#3b82f6" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <div style="font-family:Inter,sans-serif;font-size:14px;font-weight:500;letter-spacing:.05em;">
                    ERP Integration Hub
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    {{-- Vue SPA bundle --}}
    @if(file_exists(public_path('vendor/erp-integration-hub/app.js')))
        <script type="module" src="{{ asset('vendor/erp-integration-hub/app.js') }}"></script>
    @endif
</body>
</html>
