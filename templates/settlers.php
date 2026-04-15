<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Поселенцы — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:800px; margin:20px auto; padding:0 15px; }
        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
        }
        .card-body { padding:20px; }

        .settler-visual {
            text-align:center; padding:20px;
            background:#1a1a0a; border-radius:8px;
            margin-bottom:20px;
        }
        .settler-icon { font-size:64px; }
        .settler-title {
            font-size:22px; font-weight:bold;
            color:#d4a843; margin:10px 0;
        }

        .cost-grid {
            display:grid; grid-template-columns:repeat(3,1fr);
            gap:12px; margin:15px 0;
        }
        .cost-item {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:12px; text-align:center;
        }
        .cost-icon { font-size:28px; margin-bottom:6px; }
        .cost-label { font-size:11px; color:#888; }
        .cost-value { font-size:18px; font-weight:bold; }
        .cost-value.ok  { color:#4f4; }
        .cost-value.nok { color:#f44; }
        .cost-have { font-size:11px; color:#666; margin-top:3px; }

        .form-group { margin-bottom:15px; }
        label { display:block; color:#888; font-size:13px; margin-bottom:6px; }
        input[type="number"] {
            padding:10px; background:#1a1a0a;
            color:#ddd; border:2px solid #444;
            border-radius:6px; font-size:14px;
            width:120px; text-align:center;
        }
        input[type="number"]:focus {
            border-color:#8b6914; outline:none;
        }

        .coords-form {
            display:flex; gap:15px; align-items:flex-end;
            flex-wrap:wrap;
        }

        .btn-send {
            padding:12px 30px; background:#5a8a1a;
            color:#fff; border:none; border-radius:6px;
            font-size:15px; cursor:pointer; transition:0.2s;
        }
        .btn-send:hover { background:#7aaa2a; }
        .btn-send.disabled {
            background:#444; cursor:not-allowed; color:#888;
        }

        .map-preview {
            background:#1a1a0a; border:1px solid #444;
            border-radius:8px; padding:15px; margin-top:15px;
        }
        .map-preview h4 { color:#d4a843; margin-bottom:10px; font-size:13px; }

        .info-box {
            background:#1a2a1a; border:1px solid #4a6a4a;
            border-radius:6px; padding:15px; margin-bottom:15px;
        }
        .info-box h4 { color:#4f4; margin-bottom:8px; }
        .info-row {
            display:flex; justify-content:space-between;
            padding:5px 0; font-size:13px;
            border-bottom:1px solid #333;
        }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:#888; }
        .info-value { color:#d4a843; font-weight:bold; }

        .active-settler {
            background:#1a1a2a; border:1px solid #44a;
            border-radius:6px; padding:12px; margin-bottom:8px;
            display:flex; justify-content:space-between;
            align-items:center;
        }
        .settler-target { font-size:14px; color:#d4a843; }
        .settler-timer { font-size:14px; color:#0f0; font-weight:bold; }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        .requirement {
            display:flex; align-items:center; gap:8px;
            padding:8px; font-size:13px;
        }
        .req-ok  { color:#4f4; }
        .req-nok { color:#f44; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            🏕 Поселенцы — <?= htmlspecialchars($village['name']) ?>
        </div>
        <div class="card-body">

            <div class="settler-visual">
                <div class="settler-icon">🏕</div>
                <div class="settler-title">Основать новую деревню</div>
                <p style="color:#888; font-size:13px;">
                    Отправьте <?= $settlers_needed ?> поселенцев на свободные координаты
                    чтобы основать новую деревню
                </p>
            </div>

            <!-- Требования -->
            <div class="info-box">
                <h4>📋 Требования</h4>
                <?php
                $main_ok = (int)$village['main'] >= 10;
                $res_ok  = $village['r_wood']  >= $settler_cost['wood']  * $settlers_needed &&
                           $village['r_stone'] >= $settler_cost['stone'] * $settlers_needed &&
                           $village['r_iron']  >= $settler_cost['iron']  * $settlers_needed;
                $no_active = count($active_settlers) === 0;
                ?>
                <div class="requirement <?= $main_ok ? 'req-ok':'req-nok' ?>">
                    <?= $main_ok ? '✅':'❌' ?>
                    Главное здание уровня 10
                    (у вас: <?= $village['main'] ?>)
                </div>
                <div class="requirement <?= $res_ok ? 'req-ok':'req-nok' ?>">
                    <?= $res_ok ? '✅':'❌' ?>
                    Достаточно ресурсов
                </div>
                <div class="requirement <?= $no_active ? 'req-ok':'req-nok' ?>">
                    <?= $no_active ? '✅':'❌' ?>
                    Нет активных поселенцев из этой деревни
                </div>
            </div>

            <!-- Стоимость -->
            <h3 style="color:#d4a843; margin-bottom:12px;">
                💰 Стоимость (× <?= $settlers_needed ?> поселенца)
            </h3>
            <div class="cost-grid">
                <?php
                $costs = [
                    ['🪵', 'Дерево',  'wood'],
                    ['🪨', 'Камень',  'stone'],
                    ['⛏',  'Железо', 'iron']
                ];
                foreach ($costs as $c):
                    $need = $settler_cost[$c[2]] * $settlers_needed;
                    $have = (int)$village['r_' . $c[2]];
                    $ok   = $have >= $need;
                ?>
                <div class="cost-item">
                    <div class="cost-icon"><?= $c[0] ?></div>
                    <div class="cost-label"><?= $c[1] ?></div>
                    <div class="cost-value <?= $ok ? 'ok':'nok' ?>">
                        <?= number_format($need) ?>
                        <?= $ok ? '✓':'✗' ?>
                    </div>
                    <div class="cost-have">
                        Есть: <?= number_format($have) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Активные поселенцы -->
            <?php if (!empty($active_settlers)): ?>
            <div style="margin-bottom:20px;">
                <h3 style="color:#d4a843; margin-bottom:10px;">
                    🚶 Активные поселенцы
                </h3>
                <?php foreach ($active_settlers as $s):
                    $remaining = max(0, $s['arrival_time'] - time());
                    $mins = floor($remaining / 60);
                    $secs = $remaining % 60;
                ?>
                <div class="active-settler">
                    <div class="settler-target">
                        🏕 Цель: <?= $s['target_x'] ?>|<?= $s['target_y'] ?>
                    </div>
                    <div class="settler-timer"
                         data-end="<?= $s['arrival_time'] ?>">
                        ⏱ <?= $mins ?>м <?= $secs ?>с
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Форма отправки -->
            <?php if ($main_ok && $res_ok && $no_active): ?>
            <div style="margin-top:20px;">
                <h3 style="color:#d4a843; margin-bottom:15px;">
                    📍 Выбор координат
                </h3>

                <form method="POST" action="?page=settlers&action=send"
                      onsubmit="return confirmSend()">
                    <input type="hidden" name="village_id" value="<?= $village['id'] ?>">

                    <div class="coords-form">
                        <div class="form-group">
                            <label>X координата:</label>
                            <input type="number" name="target_x" id="targetX"
                                   min="-500" max="500" required
                                   placeholder="0"
                                   oninput="updatePreview()">
                        </div>
                        <div class="form-group">
                            <label>Y координата:</label>
                            <input type="number" name="target_y" id="targetY"
                                   min="-500" max="500" required
                                   placeholder="0"
                                   oninput="updatePreview()">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-send">
                                🏕 Отправить поселенцев
                            </button>
                        </div>
                    </div>

                    <!-- Превью похода -->
                    <div class="map-preview" id="routePreview" style="display:none;">
                        <h4>📍 Информация о походе</h4>
                        <div class="info-row">
                            <span class="info-label">Откуда</span>
                            <span class="info-value">
                                <?= $village['x'] ?>|<?= $village['y'] ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Куда</span>
                            <span class="info-value" id="previewTarget">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Расстояние</span>
                            <span class="info-value" id="previewDist">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Время в пути</span>
                            <span class="info-value" id="previewTime">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Прибытие</span>
                            <span class="info-value" id="previewArrival">—</span>
                        </div>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div style="text-align:center; padding:20px;">
                <span class="btn-send disabled">
                    ❌ Требования не выполнены
                </span>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Советы -->
    <div class="card">
        <div class="card-header">💡 Советы</div>
        <div class="card-body">
            <ul style="color:#888; font-size:13px; line-height:2; margin-left:20px;">
                <li>Убедитесь что выбранные координаты свободны на карте</li>
                <li>Поселенцы идут со скоростью <strong style="color:#d4a843;">30 мин/клетку</strong></li>
                <li>Если место окажется занятым — ресурсы вернутся</li>
                <li>Новая деревня основывается с начальными зданиями уровня 1</li>
                <li>Каждая деревня увеличивает ваши очки и влияние</li>
                <li>Координаты должны быть в диапазоне от -500 до 500</li>
            </ul>
        </div>
    </div>

</div>

<script>
const fromX = <?= $village['x'] ?>;
const fromY = <?= $village['y'] ?>;

function updatePreview() {
    const tx = parseInt(document.getElementById('targetX').value);
    const ty = parseInt(document.getElementById('targetY').value);

    if (isNaN(tx) || isNaN(ty)) {
        document.getElementById('routePreview').style.display = 'none';
        return;
    }

    const dist = Math.sqrt(Math.pow(tx - fromX, 2) + Math.pow(ty - fromY, 2));
    const travelSec = Math.max(300, Math.ceil(dist * 30 * 60));
    const mins = Math.floor(travelSec / 60);
    const secs = travelSec % 60;

    const arrival = new Date((Date.now() / 1000 + travelSec) * 1000);
    const arrStr  = arrival.toLocaleString('ru-RU');

    document.getElementById('previewTarget').textContent  = `${tx}|${ty}`;
    document.getElementById('previewDist').textContent    = dist.toFixed(1) + ' кл.';
    document.getElementById('previewTime').textContent    = `${mins} мин. ${secs} сек.`;
    document.getElementById('previewArrival').textContent = arrStr;
    document.getElementById('routePreview').style.display = 'block';
}

function confirmSend() {
    const tx = document.getElementById('targetX').value;
    const ty = document.getElementById('targetY').value;
    return confirm(
        `Отправить ${<?= $settlers_needed ?>} поселенцев на ${tx}|${ty}?\n` +
        `Стоимость: 🪵${<?= $settler_cost['wood'] * $settlers_needed ?>} ` +
        `🪨${<?= $settler_cost['stone'] * $settlers_needed ?>} ` +
        `⛏${<?= $settler_cost['iron'] * $settlers_needed ?>}`
    );
}

// Таймеры активных поселенцев
function updateSettlerTimers() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('[data-end]').forEach(el => {
        const end = parseInt(el.dataset.end);
        const rem = Math.max(0, end - now);
        const m   = Math.floor(rem / 60);
        const s   = rem % 60;
        el.textContent = `⏱ ${m}м ${String(s).padStart(2,'0')}с`;
        if (rem <= 0) location.reload();
    });
}
setInterval(updateSettlerTimers, 1000);
</script>

</body>
</html>