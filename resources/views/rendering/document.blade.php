<!doctype html>
<html lang="{{ $locale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    {!! $scripts['head'] !!}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $options->title }}</title>
    @if ($options->description !== null)
        <meta name="description" content="{{ $options->description }}">
    @endif
    @if ($options->canonical !== null)
        <link rel="canonical" href="{{ $options->canonical }}">
    @endif
    @foreach ($options->stylesheets as $stylesheet)
        <link rel="stylesheet" href="{{ $stylesheet }}">
    @endforeach
</head>
<body>
    {!! $scripts['bodyStart'] !!}
    {{ $content }}
    {!! $scripts['bodyEnd'] !!}
</body>
</html>
