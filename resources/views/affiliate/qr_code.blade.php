<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('affiliate_view.scan_qr')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="flex flex-col items-center justify-center min-h-screen bg-gray-50 text-gray-800">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-sm w-full text-center border border-gray-100">
        <h1 class="text-2xl font-bold mb-4 text-gray-900">@lang('affiliate_view.scan_qr')</h1>
        <p class="mb-6 text-gray-600 text-sm">@lang('affiliate_view.scan_instruction')</p>

        <div class="mb-6 flex justify-center">
            <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-inner">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($url) }}"
                    alt="QR Code" class="w-full h-auto rounded-lg">
            </div>
        </div>

        <div class="text-xs text-gray-400 break-all bg-gray-50 p-3 rounded-lg border border-gray-200">
            <span class="font-semibold text-gray-500">@lang('affiliate_view.link'):</span>
            <div class="mt-1">{{ $url }}</div>
        </div>
    </div>
</body>

</html>
