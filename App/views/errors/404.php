<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - 404</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; line-height: 1.6; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 600px; width: 100%; padding: 40px 20px; text-align: center; }
        .code { font-size: 72px; font-weight: 700; margin-bottom: 12px; color: #333; }
        h1 { font-size: 24px; font-weight: 600; margin-bottom: 12px; }
        p { font-size: 16px; color: #666; margin-bottom: 30px; }
        .actions { display: flex; flex-direction: column; gap: 12px; max-width: 300px; margin: 0 auto; }
        .btn { display: block; padding: 12px 24px; background: #f48525; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; text-align: center; border: none; cursor: pointer; }
        .btn:hover { background: #d6731f; }
        .btn-secondary { background: white; color: #333; border: 1px solid #ddd; }
        .btn-secondary:hover { background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">404</div>
        <h1><?= htmlspecialchars($error_message ?? 'Page Not Found') ?></h1>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <div class="actions">
            <a href="/" class="btn">Go to Homepage</a>
            <button onclick="history.back()" class="btn btn-secondary">Go Back</button>
        </div>
    </div>
</body>
</html>
