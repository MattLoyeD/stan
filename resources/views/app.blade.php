<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stan</title>
    <script>
    (function() {
        var p = new URLSearchParams(window.location.search);
        var t = p.get('_stan_token');
        if (t) {
            localStorage.setItem('stan_token', t);
            history.replaceState(null, '', window.location.pathname);
        }
    })();
    </script>
    @vite(['resources/js/app.ts', 'resources/css/app.css'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
