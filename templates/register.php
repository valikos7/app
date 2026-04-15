<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',Arial;
            background:#1a1a0e; color:#ddd;
            min-height:100vh;
            display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            padding:20px;
            background-image:radial-gradient(ellipse at center, #2a2010 0%, #1a1a0e 70%);
        }

        .logo {
            text-align:center; margin-bottom:25px;
        }
        .logo-icon { font-size:56px; }
        .logo-title {
            font-size:28px; font-weight:bold;
            color:#d4a843; margin-top:8px;
            text-shadow:0 0 20px rgba(212,168,67,0.5);
        }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:12px; padding:35px;
            width:100%; max-width:480px;
            box-shadow:0 20px 60px rgba(0,0,0,0.8);
        }

        .card-title {
            font-size:20px; font-weight:bold;
            color:#d4a843; margin-bottom:25px;
            text-align:center; padding-bottom:15px;
            border-bottom:1px solid #444;
        }

        .form-group { margin-bottom:16px; }
        label {
            display:block; font-size:13px;
            color:#888; margin-bottom:6px;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width:100%; padding:11px 15px;
            background:#1a1a0a; color:#ddd;
            border:2px solid #444; border-radius:6px;
            font-size:14px; transition:0.2s;
            font-family:'Segoe UI',Arial;
        }
        input:focus {
            border-color:#8b6914; outline:none;
            box-shadow:0 0 10px rgba(139,105,20,0.3);
        }
        input.valid   { border-color:#4a8a4a; }
        input.invalid { border-color:#8a1a1a; }

        .hint { font-size:11px; color:#666; margin-top:4px; }
        .hint.ok  { color:#4f4; }
        .hint.err { color:#f44; }

        .strength-bar {
            height:4px; background:#333;
            border-radius:2px; margin-top:6px;
            overflow:hidden;
        }
        .strength-fill {
            height:100%; border-radius:2px;
            transition:0.3s; width:0%;
        }

        .btn-register {
            width:100%; padding:14px;
            background:linear-gradient(135deg, #2a5a1a, #4a8a2a);
            color:#fff; border:none; border-radius:6px;
            font-size:16px; font-weight:bold;
            cursor:pointer; transition:0.3s; margin-top:5px;
        }
        .btn-register:hover {
            background:linear-gradient(135deg, #4a8a2a, #6aaa3a);
            transform:translateY(-1px);
            box-shadow:0 5px 20px rgba(74,138,42,0.4);
        }

        .divider {
            text-align:center; margin:20px 0;
            color:#555; font-size:13px; position:relative;
        }
        .divider::before, .divider::after {
            content:''; position:absolute; top:50%;
            width:40%; height:1px; background:#333;
        }
        .divider::before { left:0; }
        .divider::after { right:0; }

        .btn-login {
            display:block; width:100%; padding:12px;
            background:transparent; color:#d4a843;
            border:2px solid #5a4a20; border-radius:6px;
            font-size:14px; text-align:center;
            text-decoration:none; transition:0.2s;
        }
        .btn-login:hover { background:#3a2c10; border-color:#8b6914; }

        .alert-error {
            background:#3a1a1a; border:1px solid #8b1a1a;
            border-radius:6px; padding:12px 15px;
            color:#f66; font-size:13px; margin-bottom:20px;
        }

        .bonuses {
            background:#1a2a1a; border:1px solid #4a6a4a;
            border-radius:8px; padding:15px; margin-bottom:20px;
        }
        .bonuses h4 { color:#4f4; font-size:13px; margin-bottom:8px; }
        .bonus-item {
            display:flex; align-items:center; gap:8px;
            font-size:12px; color:#888; margin:5px 0;
        }
        .bonus-icon { font-size:16px; }
    </style>
</head>
<body>

<div class="logo">
    <div class="logo-icon">🏰</div>
    <div class="logo-title"><?= APP_NAME ?></div>
</div>

<div class="card">
    <div class="card-title">🏰 Регистрация</div>

    <?php if (!empty($error)): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Бонусы для новых игроков -->
    <div class="bonuses">
        <h4>🎁 Бонусы для новых игроков:</h4>
        <div class="bonus-item">
            <span class="bonus-icon">🏘</span>
            Первая деревня со стартовыми зданиями
        </div>
        <div class="bonus-item">
            <span class="bonus-icon">🪵</span>
            1200 единиц каждого ресурса
        </div>
        <div class="bonus-item">
            <span class="bonus-icon">⚔</span>
            Доступ ко всем игровым функциям
        </div>
    </div>

    <form method="POST" action="?page=register" id="regForm">

        <div class="form-group">
            <label>👤 Имя пользователя</label>
            <input type="text" name="username" id="username"
                   placeholder="От 3 до 32 символов"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   required autofocus
                   oninput="checkUsername(this)">
            <div class="hint" id="usernameHint">От 3 до 32 символов</div>
        </div>

        <div class="form-group">
            <label>📧 Email</label>
            <input type="email" name="email" id="email"
                   placeholder="your@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required
                   oninput="checkEmail(this)">
            <div class="hint" id="emailHint">Используется для восстановления</div>
        </div>

        <div class="form-group">
            <label>🔒 Пароль</label>
            <input type="password" name="password" id="password"
                   placeholder="Минимум 6 символов"
                   required
                   oninput="checkPassword(this)">
            <div class="strength-bar">
                <div class="strength-fill" id="strengthFill"></div>
            </div>
            <div class="hint" id="passwordHint">Введите пароль</div>
        </div>

        <div class="form-group">
            <label>🔒 Повторите пароль</label>
            <input type="password" name="password2" id="password2"
                   placeholder="Повторите пароль"
                   required
                   oninput="checkPassword2(this)">
            <div class="hint" id="password2Hint"></div>
        </div>

        <button type="submit" class="btn-register" id="submitBtn">
            🏰 Начать играть
        </button>
    </form>

    <div class="divider">уже есть аккаунт?</div>

    <a href="?page=login" class="btn-login">
        🔑 Войти в игру
    </a>
</div>

<script>
function checkUsername(el) {
    const val = el.value.trim();
    const hint = document.getElementById('usernameHint');
    if (val.length < 3) {
        el.className = 'invalid';
        hint.className = 'hint err';
        hint.textContent = '❌ Слишком короткое (минимум 3 символа)';
    } else if (val.length > 32) {
        el.className = 'invalid';
        hint.className = 'hint err';
        hint.textContent = '❌ Слишком длинное (максимум 32 символа)';
    } else {
        el.className = 'valid';
        hint.className = 'hint ok';
        hint.textContent = '✓ Отлично!';
    }
}

function checkEmail(el) {
    const val = el.value.trim();
    const hint = document.getElementById('emailHint');
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (re.test(val)) {
        el.className = 'valid';
        hint.className = 'hint ok';
        hint.textContent = '✓ Email корректный';
    } else {
        el.className = 'invalid';
        hint.className = 'hint err';
        hint.textContent = '❌ Некорректный email';
    }
}

function checkPassword(el) {
    const val = el.value;
    const hint = document.getElementById('passwordHint');
    const fill = document.getElementById('strengthFill');

    let strength = 0;
    let color = '#f44';
    let label = 'Очень слабый';

    if (val.length >= 6)  strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const pct = (strength / 5) * 100;

    if (strength <= 1) { color = '#f44'; label = 'Очень слабый'; }
    else if (strength === 2) { color = '#f84'; label = 'Слабый'; }
    else if (strength === 3) { color = '#fa4'; label = 'Средний'; }
    else if (strength === 4) { color = '#af4'; label = 'Сильный'; }
    else { color = '#4f4'; label = 'Отличный!'; }

    fill.style.width = pct + '%';
    fill.style.background = color;

    hint.className = 'hint';
    hint.style.color = color;
    hint.textContent = label;

    if (val.length < 6) {
        hint.className = 'hint err';
        hint.textContent = '❌ Минимум 6 символов';
    }

    checkPassword2(document.getElementById('password2'));
}

function checkPassword2(el) {
    const val = el.value;
    const pwd = document.getElementById('password').value;
    const hint = document.getElementById('password2Hint');
    if (!val) return;
    if (val === pwd) {
        el.className = 'valid';
        hint.className = 'hint ok';
        hint.textContent = '✓ Пароли совпадают';
    } else {
        el.className = 'invalid';
        hint.className = 'hint err';
        hint.textContent = '❌ Пароли не совпадают';
    }
}

// Валидация перед отправкой
document.getElementById('regForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const password2 = document.getElementById('password2').value;

    if (username.length < 3 || username.length > 32) {
        e.preventDefault();
        alert('Имя пользователя: от 3 до 32 символов!');
        return;
    }
    if (password.length < 6) {
        e.preventDefault();
        alert('Пароль должен быть минимум 6 символов!');
        return;
    }
    if (password !== password2) {
        e.preventDefault();
        alert('Пароли не совпадают!');
        return;
    }
});
</script>

</body>
</html>