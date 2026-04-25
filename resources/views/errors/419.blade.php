<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Expired - 419</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .error-container { text-align: center; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 500px; width: 90%; }
        .error-code { font-size: 80px; font-weight: 800; color: #dc3545; margin-bottom: 10px; }
        .error-message { font-size: 20px; color: #495057; margin-bottom: 25px; }
        .btn-home { background-color: #3b82f6; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; transition: all 0.3s; }
        .btn-home:hover { background-color: #2563eb; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(37,99,235,0.3); color: white; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">419</div>
        <div class="error-message">Oops! Your session has expired due to inactivity.</div>
        <p class="text-muted mb-4">You can simply go back or refresh the page to continue your work.</p>
        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
            <button onclick="window.history.back()" class="btn btn-outline-secondary px-4 me-md-2">Go Back</button>
            <a href="{{ url('/') }}" class="btn btn-home text-white px-4">Home</a>
        </div>
    </div>
</body>
</html>
