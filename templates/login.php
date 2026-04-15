<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',Arial;
            background:#1a1a0e;
            color:#ddd;
            min-height:100vh;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            background-image: radial-gradient(ellipse at center, #2a2010 0%, #1a1a0e 70%);
        }

        .logo {
            text-align:center;
            margin-bottom:30px;
        }
        .logo-icon { font-size:64px; }
        .logo-title {
            font-size:32px; font-weight:bold;
            color:#d4a843; margin-top:10px;
            text-shadow: 0 0 20px rgba(212,168,67,0.5);
        }
        .logo-sub { font-size:13px; color:#888; margin-top:5px; }

        .card {
            background:#2a2a1a;
            border:2px solid #5a4a20;
            border-radius:12px;
            padding:35px;
            width:100%;
            max-width:420px;
            box-shadow:0 20px 60px rgba(0,0,0,0.8);
        }

        .card-title {
            font-size:20px; font-weight:bold;
            color:#d4a843; margin-bottom:25px;
            text-align:center;
            padding-bottom:15px;
            border-bottom:1px solid #444;
        }

        .form-group { margin-bottom:18px; }
        label {
            display:block; font-size:13px;
            color:#888; margin-bottom:6px;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width:100%; padding:12px 15px;
            background:#1a1a0a; color:#ddd;
            border:2px solid #444; border-radius:6px;
            font-size:15px; transition:0.2s;
            font-family:'Segoe UI',Arial;
        }
        input:focus {
            border-color:#8b6914; outline:none;
            background:#1a1a0f;
            box-shadow:0 0 10px rgba(139,105,20,0.3);
        }

        .btn-login {
            width:100%; padding:14px;
            background:linear-gradient(135deg, #8b6914, #d4a843);
            color:#1a1a0e; border:none;
            border-radius:6px; font-size:16px;
            font-weight:bold; cursor:pointer;
            transition:0.3s; margin-top:5px;
        }
        .btn-login:hover {
            background:linear-gradient(135deg, #d4a843, #f0c050);
            transform:translateY(-1px);
            box-shadow:0 5px 20px rgba(212,168,67,0.4);
        }

        .divider {
            text-align:center; margin:20px 0;
            color:#555; font-size:13px;
            position:relative;
        }
        .divider::before, .divider::after {
            content:''; position:absolute;
            top:50%; width:40%; height:1px;
            background:#333;
        }
        .divider::before { left:0; }
        .divider::after { right:0; }

        .btn-register {
            display:block; width:100%; padding:12px;
            background:transparent; color:#d4a843;
            border:2px solid #5a4a20; border-radius:6px;
            font-size:14px; text-align:center;
            text-decoration:none; transition:0.2s;
        }
        .btn-register:hover {
            background:#3a2c10; border-color:#8b6914;
        }

        .alert-error {
            background:#3a1a1a; border:1px solid #8b1a1a;
            border-radius:6px; padding:12px 15px;
            color:#f66; font-size:13px; margin-bottom:20px;
            display:flex; align-items:center; gap:8px;
        }
        .alert-success {
            background:#1a3a1a; border:1px solid #1a8b1a;
            border-radius:6px; padding:12px 15px;
            color:#4f4; font-size:13px; margin-bottom:20px;
            display:flex; align-items:center; gap:8px;
        }

        .server-info {
            text-align:center; margin-top:20px;
            font-size:12px; color:#555;
        }
        .server-info span { color:#888; }

        .show-password {
            display:flex; align-items:center;
            gap:8px; margin-top:8px;
            font-size:12px; color:#666; cursor:pointer;
        }
        .show-password input { width:auto; }
    </style>
</head>
<body>

<!-- Логотип -->
<div class="logo">
    <div class="logo-icon">⚔️</div>
    <div class="logo-title"><?= APP_NAME ?></div>
    <div class="logo-sub">Браузерная стратегия</div>
</div>

<!-- Карточка входа -->
<div class="card">
    <div class="card-title">🔑 Вход в игру</div>

    <?php if (!empty($error)): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success">✅ <?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form method="POST" action="?page=login">

        <div class="form-group">
            <label>👤 Имя пользователя</label>
            <input type="text" name="username"
                   placeholder="Введите имя пользователя"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   required autofocus>
        </div>

        <div class="form-group">
            <label>🔒 Пароль</label>
            <input type="password" name="password"
                   id="password"
                   placeholder="Введите пароль"
                   required>
            <label class="show-password">
                <input type="checkbox" onchange="togglePassword()">
                Показать пароль
            </label>
        </div>

        <button type="submit" class="btn-login">
            Войти в игру →
        </button>
    </form>

    <div class="divider">или</div>

    <a href="?page=register" class="btn-register">
        🏰 Создать аккаунт
    </a>
</div>

<!-- Инфо о сервере -->
<div class="server-info">
    <span><?= APP_NAME ?></span> ·
    Сервер: <span><?= $config['game']['server_name'] ?? 'Мир 1' ?></span> ·
    Скорость: <span><?= $config['game']['speed'] ?? 1 ?>x</span>
</div>

<script>
function togglePassword() {
    const pwd = document.getElementById('password');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>