<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUMI Mobile App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    @include('partials.ds-head')
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef9f1 0%, #dcfce7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .card {
            max-width: 520px;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }
        .icon-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #527267;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.25rem;
        }
    </style>
</head>
<body>
    <div class="container px-3">
        <div class="card p-4 p-md-5 text-center mx-auto">
            <div class="icon-circle">
                <i class="bi bi-phone"></i>
            </div>
            <h1 class="h3 fw-bold mb-3">Use the LUMI mobile app</h1>
            <p class="text-muted mb-4">
                Guardian monitoring is available in the <strong>LUMI</strong> mobile app.
                The web portal for parents has been retired — please sign in on your phone to manage children, view metrics, and share clinician access.
            </p>
            <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                <a href="{{ route('welcome') }}" class="btn btn-outline-secondary">Back to home</a>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary w-100">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
