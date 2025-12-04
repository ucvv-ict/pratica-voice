<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>PraticaVoice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.10.111/pdf.min.js"></script>
    <style>
    .modal-bg {
        background: rgba(0,0,0,0.6);
    }
    .thumb-wrapper {
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .thumb-wrapper:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .thumb-selected {
        outline: 3px solid #2563eb;
        border-radius: 4px;
    }
    </style>

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
