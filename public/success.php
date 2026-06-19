<?php require_once '../config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - SendNaw</title>
    <style>
    body {
        margin: 0;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #6f42c1, #4c2aa8);
    }

    .card {
        background: white;
        border-radius: 30px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        width: 90%;
    }

    .icon {
        font-size: 80px;
        margin-bottom: 20px;
    }

    h1 {
        color: #28a745;
        margin-bottom: 10px;
    }

    p {
        color: #555;
        margin-bottom: 30px;
    }

    .redirect {
        color: #6f42c1;
        font-weight: bold;
    }

    .spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #6f42c1;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-left: 10px;
        vertical-align: middle;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
    </style>
</head>

<body>
    <div class="card">
        <div class="icon">✅</div>
        <h1>Payment Successful!</h1>
        <p>Your wallet has been credited.</p>
        <p>Redirecting to dashboard <span class="spinner"></span></p>
        <p class="redirect">(If not redirected, <a href="<?= FRONTEND_URL ?>/dashboard">click here</a>)</p>
    </div>
    <script>
    setTimeout(function() {
        window.location.href = "<?= FRONTEND_URL ?>/dashboard";
    }, 3000);
    </script>
</body>

</html>