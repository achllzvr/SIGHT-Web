@php
    $logoPath = public_path('assets/email/lumi_logo.png');
    if (!is_file($logoPath)) {
        $logoPath = public_path('assets/lumi_app_icon.png');
    }
    $logoSrc = isset($message) && is_file($logoPath)
        ? $message->embed($logoPath)
        : (is_file($logoPath)
            ? rtrim((string) config('app.url'), '/') . '/assets/email/lumi_logo.png'
            : rtrim((string) config('app.url'), '/') . '/assets/lumi_app_icon.png');
@endphp
<img src="{{ $logoSrc }}" alt="LUMI" width="64" height="64" style="display:block;margin:0 auto 14px;border-radius:16px;border:3px solid #8168ab;">
