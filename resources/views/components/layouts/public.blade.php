@props(['active' => 'Home', 'title' => null])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Redline Komputer - Solusi IT dan Servis Komputer Terpercaya">
    <title>{{ $title ? $title.' · ' : '' }}Redline Komputer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-ui.public-nav :active="$active" />
    {{ $slot }}
    <x-ui.public-footer />
</body>
</html>
