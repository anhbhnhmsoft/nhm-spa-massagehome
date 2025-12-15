<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('affiliate_view.download_app')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="flex flex-col items-center justify-center min-h-screen bg-gray-50 text-gray-800 p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl max-w-sm w-full text-center border border-gray-100">
        <h1 class="text-2xl font-bold mb-4 text-gray-900">@lang('affiliate_view.download_app')</h1>

        <div class="mb-6">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600 font-medium">@lang('affiliate_view.redirecting')</p>
        </div>

        <div class="space-y-4 pt-4 border-t border-gray-100">
            <p class="text-sm text-gray-500">@lang('affiliate_view.redirect_manual')</p>
            <a href="{{ $storeUrl }}"
                class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0">
                @lang('affiliate_view.download_now')
            </a>
        </div>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = "{{ $storeUrl }}";
        }, 1500);
    </script>
</body>

</html>
