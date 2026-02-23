<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>EasyNCC</title>
    <meta name="description" content="EasyNCC - Sistema Gestionale per Noleggio Con Conducente">
    <meta name="author" content="EasyNCC">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('easy_small_green.png') }}">
    <link rel="icon" type="image/png" href="{{ URL::asset('easy_small_green.png') }}">

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body>
    @inertia
</body>

</html>
