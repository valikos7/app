<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ошибка — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',Arial;
            background:#1a1a0e; color:#ddd;
            display:flex; align-items:center;
            justify-content:center; min-height:100vh;
            text-align:center;
        }
        .error-box {
            max-width:500px; padding:40px;
        }
        .error-code {
            font-size:80px; font-weight:bold;
            color:#d4a843; line-height:1;
            text-shadow:0 0 30px rgba(212,168,67,0.3);
        }
        .error-icon { font-size:60px; margin:15px 0; }
        .error-title {
            font-size:24px; color:#d4a843;
            margin-bottom:10px;
        }
        .error-msg {
            color:#888; font-size:14px;
            line-height:1.6; margin-bottom:25px;
        }
        .btn {
            display:inline-block; padding:12px 30px;
            background:#5a4a1a; color:#d4a843;
            text-decoration:none; border-radius:6px;
            border:1px solid #8b6914; transition:0.2s;
            margin:5px;
        }
        .btn:hover { background:#7a6a2a; }
    </style>
</head>
<body>
<div class="error-box">
    <div class="error-code"><?= $error_code ?? 404 ?></div>
    <div class="error-icon"><?= $error_icon ?? '🏚' ?></div>
    <div class="error-title"><?= $error_title ?? 'Страница не найдена' ?></div>
    <div class="error-msg"><?= $error_msg ?? 'Запрошенная страница не существует.' ?></div>
    <a href="?page=home" class="btn">🏠 На главную</a>
    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="?page=profile" class="btn">👤 Профиль</a>
    <?php endif; ?>
</div>
</body>
</html>