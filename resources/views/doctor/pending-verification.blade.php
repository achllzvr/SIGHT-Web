<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.ds-head')
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh;">
    <div class="card shadow p-4" style="max-width: 400px;">
        <div class="text-center mb-3">
            <i class="bi bi-hourglass-split" style="font-size: 2.5rem; color: #527267;"></i>
            <h2 class="mt-2">Pending Verification</h2>
        </div>
        <p class="text-center">Your account is pending email verification by the administrator. You will be notified once your account is verified and you can access the dashboard.</p>
        <div class="text-center mt-4">
            <a href="/logout" class="btn btn-outline-secondary">Logout</a>
        </div>
    </div>
    <script>
        setTimeout(() => { window.location.href = "/"; }, 5000);
    </script>
</body>
</html>
