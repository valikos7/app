<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создать альянс — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .top-bar {
            background:#2c1f0e; border-bottom:3px solid #8b6914;
            padding:8px 20px; display:flex;
            justify-content:space-between; align-items:center;
        }
        .top-bar a { color:#d4a843; text-decoration:none; margin:0 10px; font-size:14px; }
        .top-bar a:hover { color:#fff; }

        .container { max-width:600px; margin:30px auto; padding:0 15px; }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
        }
        .card-body { padding:25px; }

        .form-group { margin-bottom:20px; }
        label {
            display:block; color:#888; font-size:13px;
            margin-bottom:6px; font-weight:bold;
        }
        input[type="text"], textarea {
            width:100%; padding:10px; background:#1a1a0a;
            color:#ddd; border:1px solid #555; border-radius:6px;
            font-size:14px; font-family:'Segoe UI',Arial;
            transition:0.2s;
        }
        input[type="text"]:focus, textarea:focus {
            border-color:#8b6914; outline:none;
        }
        textarea { height:120px; resize:vertical; }

        .hint {
            font-size:11px; color:#666; margin-top:4px;
        }

        .btn {
            display:inline-block; padding:10px 25px;
            background:#5a4a1a; color:#ddd;
            border:1px solid #8b6914; border-radius:6px;
            cursor:pointer; font-size:14px;
            text-decoration:none; transition:0.2s;
        }
        .btn:hover { background:#7a6a2a; }
        .btn-green {
            background:#1a5a1a; border-color:#4a8a4a;
        }
        .btn-green:hover { background:#2a7a2a; }

        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px;
            margin-bottom:15px; color:#f66; font-size:13px;
        }

        .preview-box {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:15px; margin-top:20px;
        }
        .preview-tag {
            display:inline-block; background:#3a2c10;
            border:1px solid #d4a843; border-radius:4px;
            padding:3px 10px; color:#d4a843;
            font-weight:bold; font-size:16px;
        }
        .preview-name {
            font-size:20px; font-weight:bold;
            color:#d4a843; margin-left:10px;
        }

        .rules {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:15px; margin-bottom:20px;
            font-size:13px; color:#888;
        }
        .rules h4 { color:#d4a843; margin-bottom:8px; }
        .rules li { margin:5px 0 5px 15px; }
    </style>
</head>
<body>

<div class="top-bar">
    <div>
        <strong style="color:#d4a843;">⚔ <?= APP_NAME ?></strong>
        <a href="?page=home">Главная</a>
        <a href="?page=profile">Профиль</a>
        <a href="?page=alliances">← Альянсы</a>
    </div>
    <div><a href="?page=logout" style="color:#c00;">Выход</a></div>
</div>

<div class="container">

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Правила -->
    <div class="rules">
        <h4>📋 Правила создания альянса</h4>
        <ul>
            <li>Название: от 3 до 64 символов</li>
            <li>Тег: от 2 до 8 символов (только буквы и цифры)</li>
            <li>Название и тег должны быть уникальными</li>
            <li>Вы автоматически станете лидером альянса</li>
        </ul>
    </div>

    <div class="card">
        <div class="card-header">🏰 Создать новый альянс</div>
        <div class="card-body">

            <form method="POST" action="?page=alliance&action=create" id="createForm">

                <div class="form-group">
                    <label>Название альянса:</label>
                    <input type="text" name="name" id="allianceName"
                           placeholder="Например: Великие Воины"
                           maxlength="64" required
                           oninput="updatePreview()">
                    <div class="hint">От 3 до 64 символов</div>
                </div>

                <div class="form-group">
                    <label>Тег альянса:</label>
                    <input type="text" name="tag" id="allianceTag"
                           placeholder="Например: GW"
                           maxlength="8" required
                           oninput="updatePreview()"
                           style="text-transform:uppercase;">
                    <div class="hint">От 2 до 8 символов. Будет отображаться как [ТЕГ]</div>
                </div>

                <div class="form-group">
                    <label>Описание (необязательно):</label>
                    <textarea name="description"
                              placeholder="Расскажите об альянсе..."></textarea>
                </div>

                <!-- Предпросмотр -->
                <div class="preview-box">
                    <div style="font-size:12px; color:#666; margin-bottom:8px;">
                        Предпросмотр:
                    </div>
                    <span class="preview-tag" id="previewTag">[??]</span>
                    <span class="preview-name" id="previewName">Название альянса</span>
                </div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" class="btn btn-green">
                        🏰 Создать альянс
                    </button>
                    <a href="?page=alliances" class="btn">Отмена</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function updatePreview() {
    const name = document.getElementById('allianceName').value || 'Название альянса';
    const tag  = document.getElementById('allianceTag').value.toUpperCase() || '??';
    document.getElementById('previewName').textContent = name;
    document.getElementById('previewTag').textContent  = '[' + tag + ']';
}

// Тег всегда в верхнем регистре
document.getElementById('allianceTag').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>

</body>
</html>