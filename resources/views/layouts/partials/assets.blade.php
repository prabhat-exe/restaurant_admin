@php
    $manifestPath = public_path('build/manifest.json');
    $entry = null;
    $cssEntry = null;
    $cssFiles = [];
    $jsFile = null;

    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $entry = $manifest['resources/js/app.js'] ?? null;
        $cssEntry = $manifest['resources/css/app.css'] ?? null;

        if ($entry) {
            $jsFile = $entry['file'] ?? null;
            $cssFiles = $entry['css'] ?? [];
        }

        // Tailwind CSS can be emitted as a standalone entry in the manifest.
        if ($cssEntry && isset($cssEntry['file'])) {
            $cssFiles[] = $cssEntry['file'];
        }

        $cssFiles = array_values(array_unique($cssFiles));
    }
@endphp

@if ($entry || !empty($cssFiles))
    @foreach ($cssFiles as $css)
        <link rel="stylesheet" href="{{ asset('build/' . $css) }}">
    @endforeach

    @if ($jsFile)
        <script type="module" src="{{ asset('build/' . $jsFile) }}"></script>
    @endif
@else
    <style>
        body::before {
            content: 'Build assets missing. Run npm run build once.';
            display: block;
            padding: 0.75rem;
            font: 14px/1.4 sans-serif;
            background: #fff7ed;
            color: #9a3412;
            border-bottom: 1px solid #fed7aa;
        }
    </style>
@endif
