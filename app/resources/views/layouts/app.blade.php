<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(13, 110, 253, 0.14), transparent 30%),
                radial-gradient(circle at bottom right, rgba(25, 135, 84, 0.12), transparent 25%),
                #f8fafc;
        }

        .page-shell {
            padding: 48px 0;
        }

        .app-card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.08);
        }

        .upload-dropzone {
            border: 2px dashed rgba(13, 110, 253, 0.35);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.8);
            transition: border-color 0.2s ease, transform 0.2s ease;
        }

        .upload-dropzone:hover {
            border-color: rgba(13, 110, 253, 0.65);
            transform: translateY(-1px);
        }

        .table tbody tr {
            vertical-align: middle;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.875rem;
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
        }
    </style>
    @stack('styles')
</head>
<body>
    <main class="page-shell">
        @yield('content')
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
