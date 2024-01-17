<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title></title>
    <link rel="stylesheet" href="{{ asset('css/app.css')}}">
    <link rel="stylesheet" href="https://kit-pro.fontawesome.com/releases/v5.11.2/css/pro.min.css">
</head>
<body class="front_loading">
    <div id="app"></div>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBDvWZb_jyc268AAk_uxHnwH3mcev34R8o&libraries=places"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>