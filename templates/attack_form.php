<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>
        <?php
        $mode_titles = [
            'attack'  => 'Атака',
            'spy'     => 'Разведка',
            'support' => 'Поддержка'
        ];
        echo ($mode_titles[$mode] ?? 'Атака') . ' — ' . APP_NAME;
        ?>
    </title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; margin:0; }

        .container { max-width:900px; margin:20px auto; padding:0 15px; }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .card-body { padding:20px; }

        /* Табы режимов */
        .mode-tabs {
            display:flex; gap:5px; margin-bottom:20px;
        }
        .mode-tab {
            flex:1; padding:12px; text-align:center;
            border-radius:6px; text-decoration:none;
            font-size:14px; font-weight:bold; transition:0.2s;
            border:2px solid transparent;
        }
        .mode-tab-attack {
            background:#2a1a1a; color:#f66; border-color:#6a1a1a;
        }
        .mode-tab-attack.active, .mode-tab-attack:hover {
            background:#4a1a1a; border-color:#c00;
        }
        .mode-tab-spy {
            background:#1a1a2a; color:#88f; border-color:#1a1a6a;
        }
        .mode-tab-spy.active, .mode-tab-spy:hover {
            background:#1a1a4a; border-color:#44f;
        }
        .mode-tab-support {
            background:#1a2a1a; color:#4f4; border-color:#1a6a1a;
        }
        .mode-tab-support.active, .mode-tab-support:hover {
            background:#1a4a1a; border-color:#0c0;
        }

        /* Информация о цели */
        .target-info {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:15px; margin-bottom:20px;
        }
        .target-name {
            font-size:18px; font-weight:bold; color:#d4a843;
            margin-bottom:8px;
        }
        .target-meta { color:#888; font-size:13px; }
        .target-meta span { margin-right:15px; }

        /* Выбор деревни */
        select {
            width:100%; padding:10px; background:#1a1a0a; color:#ddd;
            border:2px solid #444; border-radius:6px; font-size:14px;
            margin-bottom:15px; cursor:pointer;
        }
        select:focus { border-color:#8b6914; outline:none; }

        /* Таблица юнитов */
        table { width:100%; border-collapse:collapse; margin:15px 0; }
        th {
            background:#3a2c10; color:#d4a843;
            padding:10px; text-align:center; font-size:12px;
        }
        td {
            padding:8px 10px; border-bottom:1px solid #333;
            text-align:center; font-size:13px;
        }
        tr:hover td { background:#2a2010; }

        input[type="number"] {
            width:70px; padding:6px; background:#1a1a0a;
            color:#ddd; border:1px solid #555; border-radius:4px;
            text-align:center; font-size:13px;
        }
        input[type="number"]:focus { border-color:#8b6914; outline:none; }

        .unit-icon { font-size:20px; }
        .available { color:#0f0; font-size:12px; cursor:pointer; }
        .available:hover { text-decoration:underline; }

        /* Кнопки */
        .btn-attack {
            padding:14px 40px; background:#8b1a1a; color:#fff;
            border:none; font-size:16px; cursor:pointer;
            border-radius:6px; transition:0.2s; font-weight:bold;
        }
        .btn-attack:hover { background:#bb2a2a; }

        .btn-spy {
            padding:14px 40px; background:#1a1a8b; color:#fff;
            border:none; font-size:16px; cursor:pointer;
            border-radius:6px; transition:0.2s; font-weight:bold;
        }
        .btn-spy:hover { background:#2a2abb; }

        .btn-support {
            padding:14px 40px; background:#1a6a1a; color:#fff;
            border:none; font-size:16px; cursor:pointer;
            border-radius:6px; transition:0.2s; font-weight:bold;
        }
        .btn-support:hover { background:#2a8a2a; }

        .btn-all {
            padding:3px 8px; background:#3a2c10; color:#d4a843;
            border:1px solid #8b6914; border-radius:3px;
            cursor:pointer; font-size:10px;
        }
        .btn-all:hover { background:#5a4a20; }

        /* Предпросмотр */
        .travel-info {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:15px; margin:15px 0;
            text-align:center;
        }
        .travel-info strong { color:#d4a843; }

        /* Ряд кнопок */
        .action-row {
            display:flex; justify-content:center;
            gap:15px; margin-top:20px; flex-wrap:wrap;
        }

        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        @media(max-width:600px) {
            .mode-tabs { flex-direction:column; }
            input[type="number"] { width:55px; }
            th, td { padding:6px 4px; font-size:11px; }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Табы режимов -->
    <?php
    $is_own = ($target['userid'] == ($_SESSION['user_id'] ?? 0));
    $is_barb = ($target['userid'] == -1);
    ?>
    <div class="mode-tabs">
        <?php if (!$is_own): ?>
        <a href="?page=attack&target=<?= $target['id'] ?>&mode=attack"
           class="mode-tab mode-tab-attack <?= $mode==='attack' ? 'active':'' ?>">
            ⚔ Атака
        </a>
        <a href="?page=attack&target=<?= $target['id'] ?>&mode=spy"
           class="mode-tab mode-tab-spy <?= $mode==='spy' ? 'active':'' ?>">
            🔍 Разведка
        </a>
        <?php endif; ?>
        <?php if (!$is_barb): ?>
        <a href="?page=attack&target=<?= $target['id'] ?>&mode=support"
           class="mode-tab mode-tab-support <?= $mode==='support' ? 'active':'' ?>">
            🛡 Поддержка
        </a>
        <?php endif; ?>
    </div>

    <!-- Информация о цели -->
    <div class="target-info">
        <div class="target-name">
            <?php if ($mode === 'attack'): ?>⚔<?php elseif ($mode === 'spy'): ?>🔍<?php else: ?>🛡<?php endif; ?>
            <?= htmlspecialchars($target['name']) ?>
        </div>
        <div class="target-meta">
            <span>📍 <?= $target['x'] ?>|<?= $target['y'] ?></span>
            <span>👤 <?= htmlspecialchars($target['owner_name'] ?? 'Варвары') ?></span>
            <span>⭐ <?= number_format($target['points'] ?? 0) ?> очков</span>
        </div>
    </div>

    <!-- РЕЖИМ: РАЗВЕДКА -->
    <?php if ($mode === 'spy'): ?>
    <div class="card">
        <div class="card-header" style="background:#1a1a3a; border-bottom:2px solid #44f;">
            🔍 Отправить разведчиков
        </div>
        <div class="card-body">

            <p style="color:#888; font-size:13px; margin-bottom:15px;">
                Разведчики соберут информацию о ресурсах, зданиях и войсках цели.
                Чем больше разведчиков отправите — тем выше шанс успеха.
                Если у противника есть свои разведчики, они могут перехватить ваших.
            </p>

            <form method="POST" action="?page=attack&action=spy">
                <input type="hidden" name="target_village" value="<?= $target['id'] ?>">

                <label style="color:#888; font-size:13px;">Атаковать из:</label>
                <select name="from_village" id="fromVillageSpy"
                        onchange="updateSpyInfo()">
                    <?php foreach ($my_villages as $v): ?>
                        <option value="<?= $v['id'] ?>"
                                data-x="<?= $v['x'] ?>" data-y="<?= $v['y'] ?>"
                                data-scouts="<?= (int)($village_troops[$v['id']]['scout'] ?? 0) ?>">
                            <?= htmlspecialchars($v['name']) ?>
                            (<?= $v['x'] ?>|<?= $v['y'] ?>)
                            — 🔍 <?= (int)($village_troops[$v['id']]['scout'] ?? 0) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div style="display:flex; gap:15px; align-items:center;
                            flex-wrap:wrap; margin:15px 0;">
                    <div>
                        <label style="color:#888; font-size:13px;">Разведчиков:</label>
                        <input type="number" name="scout" id="scoutInput"
                               value="1" min="1" max="100"
                               oninput="updateSpyInfo()">
                    </div>
                    <div>
                        <span style="color:#888; font-size:13px;">Доступно:</span>
                        <span class="available" id="scoutAvail"
                              onclick="sendAllScouts()">
                            0
                        </span>
                    </div>
                </div>

                <!-- Шанс успеха -->
                <div style="background:#1a1a2a; border:1px solid #44a;
                            border-radius:6px; padding:15px; margin:15px 0;">
                    <div style="display:flex; justify-content:space-between;
                                margin-bottom:8px;">
                        <span style="color:#88f;">Шанс успеха:</span>
                        <strong id="successChance" style="color:#88f;">~70%</strong>
                    </div>
                    <div style="height:8px; background:#333; border-radius:4px;
                                overflow:hidden;">
                        <div id="successBar"
                             style="height:100%; width:70%;
                                    background:linear-gradient(90deg,#44a,#88f);
                                    border-radius:4px; transition:0.3s;">
                        </div>
                    </div>
                    <div style="margin-top:8px; font-size:11px; color:#666;">
                        Больше разведчиков = выше шанс. Противник может иметь своих.
                    </div>
                </div>

                <div class="travel-info" id="spyTravel">
                    <strong>Выберите деревню</strong>
                </div>

                <div class="action-row">
                    <button type="submit" class="btn-spy">
                        🔍 Отправить разведчиков
                    </button>
                    <a href="?page=map" style="padding:14px 30px; background:#3a2c10;
                       color:#d4a843; text-decoration:none; border-radius:6px;
                       border:1px solid #8b6914;">
                        ← Карта
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- РЕЖИМ: ПОДДЕРЖКА -->
    <?php elseif ($mode === 'support'): ?>
    <div class="card">
        <div class="card-header" style="background:#1a3a1a; border-bottom:2px solid #0c0;">
            🛡 Отправить поддержку
        </div>
        <div class="card-body">

            <p style="color:#888; font-size:13px; margin-bottom:15px;">
                Войска поддержки будут защищать эту деревню от атак.
                Они останутся там до отзыва или гибели в бою.
            </p>

            <form method="POST" action="?page=attack&action=support">
                <input type="hidden" name="target_village" value="<?= $target['id'] ?>">

                <label style="color:#888; font-size:13px;">Из деревни:</label>
                <select name="from_village" id="fromVillage"
                        onchange="updateTroops()">
                    <?php foreach ($my_villages as $v): ?>
                        <option value="<?= $v['id'] ?>"
                                data-x="<?= $v['x'] ?>" data-y="<?= $v['y'] ?>"
                                data-troops='<?= json_encode($village_troops[$v['id']]) ?>'>
                            <?= htmlspecialchars($v['name']) ?>
                            (<?= $v['x'] ?>|<?= $v['y'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php $this->renderTroopTable($unit_stats, 'support'); ?>

                <div class="travel-info" id="travelInfo">
                    <strong>Выберите войска</strong>
                </div>

                <div class="action-row">
                    <button type="submit" class="btn-support">
                        🛡 Отправить поддержку
                    </button>
                    <a href="?page=map" style="padding:14px 30px; background:#3a2c10;
                       color:#d4a843; text-decoration:none; border-radius:6px;
                       border:1px solid #8b6914;">
                        ← Карта
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- РЕЖИМ: АТАКА -->
    <?php else: ?>
    <div class="card">
        <div class="card-header" style="background:#2a1a1a; border-bottom:2px solid #c00;">
            ⚔ Планирование атаки
        </div>
        <div class="card-body">

            <form method="POST" action="?page=attack&action=launch">
                <input type="hidden" name="target_village" value="<?= $target['id'] ?>">

                <label style="color:#888; font-size:13px;">Атаковать из:</label>
                <select name="from_village" id="fromVillage"
                        onchange="updateTroops()">
                    <?php foreach ($my_villages as $v): ?>
                        <option value="<?= $v['id'] ?>"
                                data-x="<?= $v['x'] ?>" data-y="<?= $v['y'] ?>"
                                data-troops='<?= json_encode($village_troops[$v['id']]) ?>'>
                            <?= htmlspecialchars($v['name']) ?>
                            (<?= $v['x'] ?>|<?= $v['y'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php $this->renderTroopTable($unit_stats, 'attack'); ?>

                <div class="travel-info" id="travelInfo">
                    <strong>Выберите войска</strong>
                </div>

                <div class="action-row">
                    <button type="submit" class="btn-attack">
                        ⚔ Отправить атаку
                    </button>
                    <a href="?page=map" style="padding:14px 30px; background:#3a2c10;
                       color:#d4a843; text-decoration:none; border-radius:6px;
                       border:1px solid #8b6914;">
                        ← Карта
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
const targetX = <?= (int)$target['x'] ?>;
const targetY = <?= (int)$target['y'] ?>;
const currentMode = '<?= $mode ?>';

<?php
$speeds = [];
$carries = [];
foreach ($unit_stats as $t => $s) {
    $speeds[$t]  = $s['speed'];
    $carries[$t] = $s['carry'];
}
?>
const unitSpeeds  = <?= json_encode($speeds) ?>;
const unitCarries = <?= json_encode($carries) ?>;

let currentTroops = {};

// === ОБЩИЕ ФУНКЦИИ ===

function updateTroops() {
    const sel = document.getElementById('fromVillage');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    currentTroops = JSON.parse(opt.dataset.troops || '{}');

    const types = Object.keys(unitSpeeds);
    types.forEach(type => {
        const avail = currentTroops[type] || 0;
        const el = document.getElementById('avail_' + type);
        if (el) el.textContent = avail;
        const inp = document.getElementById('input_' + type);
        if (inp) {
            inp.max = avail;
            if (parseInt(inp.value) > avail) inp.value = avail;
        }
    });

    calculateTravel();
}

function sendAll(type) {
    const avail = currentTroops[type] || 0;
    const inp = document.getElementById('input_' + type);
    if (inp) inp.value = avail;
    calculateTravel();
}

function calculateTravel() {
    const sel = document.getElementById('fromVillage');
    if (!sel) return;

    const opt = sel.options[sel.selectedIndex];
    const fromX = parseFloat(opt.dataset.x);
    const fromY = parseFloat(opt.dataset.y);
    const dist = Math.sqrt(Math.pow(targetX - fromX, 2) + Math.pow(targetY - fromY, 2));

    let slowest = 0;
    let totalCarry = 0;
    let totalUnits = 0;

    Object.keys(unitSpeeds).forEach(type => {
        const count = parseInt(document.getElementById('input_' + type)?.value || 0);
        if (count > 0) {
            totalUnits += count;
            if (unitSpeeds[type] > slowest) slowest = unitSpeeds[type];
            totalCarry += (unitCarries[type] || 0) * count;
        }
    });

    const info = document.getElementById('travelInfo');
    if (!info) return;

    if (totalUnits === 0) {
        info.innerHTML = '<strong>Выберите войска</strong>';
        return;
    }

    const travelSec = Math.ceil(dist * slowest * 60);
    const mins = Math.floor(travelSec / 60);
    const secs = travelSec % 60;

    let html = `
        📏 Расстояние: <strong>${dist.toFixed(1)}</strong> кл. &nbsp;|&nbsp;
        ⏱ Время: <strong>${mins}м ${secs}с</strong> &nbsp;|&nbsp;
        👥 Войск: <strong>${totalUnits}</strong>
    `;

    if (currentMode === 'attack') {
        html += ` &nbsp;|&nbsp; 📦 Грузоподъёмность: <strong>${totalCarry}</strong>`;
    }

    info.innerHTML = html;
}

// === ФУНКЦИИ ДЛЯ ШПИОНАЖА ===

function updateSpyInfo() {
    const sel = document.getElementById('fromVillageSpy');
    if (!sel) return;

    const opt = sel.options[sel.selectedIndex];
    const avail = parseInt(opt.dataset.scouts || 0);
    document.getElementById('scoutAvail').textContent = avail;

    const inp = document.getElementById('scoutInput');
    inp.max = avail;
    if (parseInt(inp.value) > avail) inp.value = avail;

    const count = parseInt(inp.value) || 1;

    // Расчёт шанса
    let chance = 70;
    if (count >= 10) chance = 95;
    else if (count >= 5) chance = 85;
    else if (count >= 3) chance = 75;
    else if (count >= 2) chance = 65;
    else chance = 50;

    document.getElementById('successChance').textContent = '~' + chance + '%';
    document.getElementById('successBar').style.width = chance + '%';

    // Расчёт времени
    const fromX = parseFloat(opt.dataset.x);
    const fromY = parseFloat(opt.dataset.y);
    const dist = Math.sqrt(Math.pow(targetX - fromX, 2) + Math.pow(targetY - fromY, 2));
    const scoutSpeed = unitSpeeds['scout'] || 9;
    const travelSec = Math.max(60, Math.ceil(dist * scoutSpeed * 60));
    const mins = Math.floor(travelSec / 60);
    const secs = travelSec % 60;

    document.getElementById('spyTravel').innerHTML = `
        📏 Расстояние: <strong>${dist.toFixed(1)}</strong> кл. &nbsp;|&nbsp;
        ⏱ Время: <strong>${mins}м ${secs}с</strong> &nbsp;|&nbsp;
        🔍 Разведчиков: <strong>${count}</strong>
    `;
}

function sendAllScouts() {
    const sel = document.getElementById('fromVillageSpy');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    const avail = parseInt(opt.dataset.scouts || 0);
    document.getElementById('scoutInput').value = avail;
    updateSpyInfo();
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    if (currentMode === 'spy') {
        updateSpyInfo();
    } else {
        updateTroops();
    }

    // Обработчики изменений
    document.querySelectorAll('input[type="number"]').forEach(inp => {
        inp.addEventListener('input', function() {
            if (currentMode === 'spy') updateSpyInfo();
            else calculateTravel();
        });
    });
});
</script>

</body>
</html>

<?php
// Вспомогательный метод в контексте шаблона
// (вызывается как $this->renderTroopTable() из контроллера)
?>