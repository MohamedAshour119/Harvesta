{{-- resources/views/welcome.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Cairo 3A</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    @viteReactRefresh
    @vite('resources/frontend/src/main.tsx')
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">

</head>

<body>
    <div id="root" class="h-full text-text_color w-full overflow-x-hidden"></div>
</body>

{{--
<script src="https://tap-sdks.b-cdn.net/card/1.0.2/index.js"></script> --}}

</html>