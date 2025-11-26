<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>PraticaVoice</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <nav class="bg-white shadow p-4 mb-6">
        <div class="max-w-7xl mx-auto flex justify-between">
            <h1 class="text-xl font-bold">📁 PraticaVoice</h1>
            <a href="/voice.html" class="text-blue-600 underline">🎤 Ricerca vocale</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto">
        @yield('content')
    </div>

</body>
</html>
