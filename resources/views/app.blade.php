<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0D3F3E">
    <link rel="icon" type="image/svg+xml" href="/brand/taqwim-icon.svg">
    <link rel="apple-touch-icon" href="/brand/taqwim-icon.png">
    <title inertia>تقويم — إدارة عيادات الأسنان</title>
    <meta name="description" content="تقويم نظام إدارة عيادات الأسنان: ملفات مرضى وخطط علاج وفواتير ومدفوعات ومتابعات وتقارير، بالعربي والإنجليزي ومن الموبايل. Taqwim is dental clinic management software for Arabic-first clinics.">
    <meta property="og:site_name" content="تقويم | Taqwim">
    <meta property="og:title" content="تقويم — إدارة عيادات الأسنان">
    <meta property="og:description" content="من أول حجز لحد تحصيل الفاتورة، كل شغل العيادة في مكان واحد. Dental clinic management: patients, treatment plans, invoices, reminders and reports, in Arabic and English.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta property="og:image" content="{{ config('app.url') }}/landing/ar-dashboard.jpg">
    <meta property="og:locale" content="ar_AR">
    <meta property="og:locale:alternate" content="en_US">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="تقويم — إدارة عيادات الأسنان">
    <meta name="twitter:description" content="من أول حجز لحد تحصيل الفاتورة، كل شغل العيادة في مكان واحد.">
    <meta name="twitter:image" content="{{ config('app.url') }}/landing/ar-dashboard.jpg">
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'تقويم Taqwim',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'inLanguage' => ['ar', 'en'],
        'url' => config('app.url'),
        'description' => 'نظام إدارة عيادات الأسنان: ملفات مرضى وخطط علاج وفواتير ومتابعات وتقارير. Dental clinic management software: patients, treatment plans, invoices, recalls and reports.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-canvas text-ink-800">
    @inertia
</body>
</html>
