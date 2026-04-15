<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Конюшня — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .top-bar {
            background:#2c1f0e; border-bottom:3px solid #8b6914;
            padding:8px 15px; display:flex;
            justify-content:space-between; align-items:center;
        }
        .top-bar a { color:#d4a843; text-decoration:none; margin:0 8px; font-size:13px; }
        .res-bar {
            background:#2a2010; border-bottom:2px solid #5a4a20;
            padding:8px 15px; display:flex;
            justify-content:center; gap:25px; flex-wrap:wrap;
        }
        .res-item { display:flex; align-items:center; gap:6px; font-size:14px; }
        .res-value { font-weight:bold; color:#e8c870; }
        .village-nav {
            background:#241c0e; border-bottom:2px solid #5a4a20;
            display:flex; justify-content:center; flex-wrap:wrap;
        }
        .village-nav a {
            padding:10px 20px; color:#aaa; text-decoration:none;
            font-size:13px; border-right:1px solid #333; transition:0.2s;
        }
        .village-nav a:hover, .village-nav a.active {
            background:#3a2c10; color:#d4a843;
        }
        .container { max-width:950px; margin:20px auto; padding:0 15px; }
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
        .units-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(280px,1fr));
            gap:15px;
        }
        .unit-card {
            background:#1a1a0a; border:2px solid #444;
            border-radius:8px; padding:15px; transition:0.2s;
        }
        .unit-card:hover { border-color:#8b6914; }
        .unit-card-header {
            display:flex; align-items:center; gap:12px; margin-bottom:12px;
        }
        .unit-icon-big { font-size:40px; }
        .unit-name { font-size:16px; font-weight:bold; color:#d4a843; }
        .unit-type { font-size:11px; color:#888; }
        .unit-stats-grid {
            display:grid; grid-template-columns:1fr 1fr;
            gap:6px; margin-bottom:12px;
        }
        .unit-stat {
            background:#2a2a1a; padding:6px 10px;
            border-radius:4px; font-size:12px;
            display:flex; justify-content:space-between;
        }
        .unit-stat-label { color:#888; }
        .unit-stat-value { color:#d4a843; font-weight:bold; }
        .unit-cost { display:flex; gap:10px; margin-bottom:12px; font-size:12px; }
        .unit-cost-item.ok { color:#4f4; }
        .unit-cost-item.nok { color:#f44; }
        .train-form { display:flex; gap:8px; align-items:center; }
        .train-input {
            width:70px; padding:8px; background:#2a2a1a;
            color:#ddd; border:1px solid #666; border-radius:4px;
            text-align:center; font-size:14px;
        }
        .btn-train {
            padding:8px 18px; background:#1a3a5a;
            color:#ddd; border:1px solid #1a6a9a;
            border-radius:4px; cursor:pointer; font-size:13px; transition:0.2s;
        }
        .btn-train:hover { background:#2a5a8a; }
        .troops-overview { display:flex; flex-wrap:wrap; gap:10px; }
        .troop-badge {
            background:#1a2a3a; border:1px solid #44a;
            border-radius:6px; padding:8px 12px; text-align:center; min-width:80px;
        }
        .troop-badge-icon { font-size:24px; }
        .troop-badge-name { font-size:10px; color:#888; margin-top:2px; }
        .troop-badge-count { font-size:16px; font-weight:bold; color:#d4a843; }
        .train-queue {
            background:#1a2a3a; border:1px solid #4a4a8a;
            border-radius:6px; padding:15px; text-align:center;
        }
        .queue-timer-big { font-size:28px; color:#88f; font-weight:bold; margin:8px 0; }
        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div>
        <strong style="color:#d4a843;">⚔ <?= APP_NAME ?></strong>
        <a href="?page=village&id=<?= $village['id'] ?>">← Деревня</a>
        <a href="?page=map">Карта</a>
    </div>
</div>

<?php
$db = $db ?? $GLOBALS['db'];
$resourceManager = new ResourceManager($db);
$prod = $resourceManager->getProductionPerHour($village);
$max_storage = $resourceManager->getMaxStorage($village);
$max_pop = $resourceManager->getMaxPopulation($village);
?>

<div class="res-bar">
    <div class="res-item">
        🪵 <span class="res-value"><?= number_format($village['r_wood']) ?></span>
        /<?= number_format($max_storage) ?>
    </div>
    <div class="res-item">
        🪨 <span class="res-value"><?= number_format($village['r_stone']) ?></span>
        /<?= number_format($max_storage) ?>
    </div>
    <div class="res-item">
        ⛏ <span class="res-value"><?= number_format($village['r_iron']) ?></span>
        /<?= number_format($max_storage) ?>
    </div>
    <div class="res-item">
        👥 <span class="res-value"><?= $village['population'] ?? 0 ?></span>
        /<?= $max_pop ?>
    </div>
</div>

<div class="village-nav">
    <a href="?page=village&id=<?= $village['id'] ?>">🏘 Обзор</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=main">🏛 Гл. здание</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=barracks">⚔ Казармы</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=stable" class="active">🐎 Конюшня</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=smith">🔨 Кузница</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=wall">🧱 Стена</a>
</div>

<div class="container">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Очередь -->
    <?php if (!empty($village['train_queue']) && $village['train_end_time'] > time()):
        $remaining = $village['train_end_time'] - time();
        list($ttype, $tamount) = explode(':', $village['train_queue'] . ':1');
        $tnames = ['scout'=>'Разведчики','light'=>'Лёгкая кав.','heavy'=>'Тяжёлая кав.'];
    ?>
    <div class="card">
        <div class="card-header">🐎 Идёт тренировка</div>
        <div class="card-body">
            <div class="train-queue">
                <div style="font-size:14px;color:#d4a843;font-weight:bold;">
                    🐎 <?= $tnames[$ttype] ?? $ttype ?> × <?= $tamount ?>
                </div>
                <div class="queue-timer-big" id="trainTimer">
                    <?= gmdate('H:i:s', $remaining) ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Кавалерия в деревне -->
    <div class="card">
        <div class="card-header">
            🐎 Кавалерия в деревне
            <span style="font-size:12px;color:#aaa;">
                Уровень конюшни: <?= $village['stable'] ?>
            </span>
        </div>
        <div class="card-body">
            <?php
            $units = $GLOBALS['units'] ?? [];
            $cavalry = [
                'scout' => ['name'=>'Разведчик',    'icon'=>'🔍'],
                'light' => ['name'=>'Лёгкая кав.',  'icon'=>'🐎'],
                'heavy' => ['name'=>'Тяжёлая кав.', 'icon'=>'🦄'],
            ];
            ?>
            <div class="troops-overview">
                <?php foreach ($cavalry as $type => $info):
                    $count = (int)($units[$type] ?? 0);
                ?>
                <div class="troop-badge">
                    <div class="troop-badge-icon"><?= $info['icon'] ?></div>
                    <div class="troop-badge-name"><?= $info['name'] ?></div>
                    <div class="troop-badge-count"><?= number_format($count) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Тренировка кавалерии -->
    <div class="card">
        <div class="card-header">🐎 Тренировать кавалерию</div>
        <div class="card-body">

            <?php
            $cavalry_units = [
                'scout' => [
                    'name'    => 'Разведчик',
                    'icon'    => '🔍',
                    'attack'  => 0,
                    'def_inf' => 2,
                    'def_cav' => 1,
                    'carry'   => 0,
                    'speed'   => 9,
                    'pop'     => 2,
                    'wood'    => 80,
                    'stone'   => 40,
                    'iron'    => 30,
                    'time'    => 50,
                    'desc'    => 'Быстрый разведчик. Используется для шпионажа.'
                ],
                'light' => [
                    'name'    => 'Лёгкая кавалерия',
                    'icon'    => '🐎',
                    'attack'  => 130,
                    'def_inf' => 30,
                    'def_cav' => 40,
                    'carry'   => 80,
                    'speed'   => 10,
                    'pop'     => 4,
                    'wood'    => 100,
                    'stone'   => 130,
                    'iron'    => 160,
                    'time'    => 120,
                    'desc'    => 'Быстрая и мощная атакующая кавалерия.'
                ],
                'heavy' => [
                    'name'    => 'Тяжёлая кавалерия',
                    'icon'    => '🦄',
                    'attack'  => 150,
                    'def_inf' => 200,
                    'def_cav' => 80,
                    'carry'   => 50,
                    'speed'   => 11,
                    'pop'     => 6,
                    'wood'    => 150,
                    'stone'   => 200,
                    'iron'    => 250,
                    'time'    => 180,
                    'desc'    => 'Мощная защитная кавалерия. Элита армии.'
                ],
            ];

            $is_training = !empty($village['train_queue']) && $village['train_end_time'] > time();
            $pop_left = $max_pop - ($village['population'] ?? 0);
            ?>

            <div class="units-grid">
                <?php foreach ($cavalry_units as $type => $u):
                    $can_w = $village['r_wood']  >= $u['wood'];
                    $can_s = $village['r_stone'] >= $u['stone'];
                    $can_i = $village['r_iron']  >= $u['iron'];
                    $can_afford = $can_w && $can_s && $can_i;
                    $max_train = min(
                        floor($village['r_wood'] / $u['wood']),
                        floor($village['r_stone'] / $u['stone']),
                        floor($village['r_iron'] / $u['iron']),
                        floor($pop_left / $u['pop'])
                    );
                ?>
                <div class="unit-card">
                    <div class="unit-card-header">
                        <div class="unit-icon-big"><?= $u['icon'] ?></div>
                        <div>
                            <div class="unit-name"><?= $u['name'] ?></div>
                            <div class="unit-type">Кавалерия · Скорость: <?= $u['speed'] ?> мин/кл</div>
                        </div>
                    </div>

                    <p style="font-size:12px;color:#888;margin-bottom:10px;">
                        <?= $u['desc'] ?>
                    </p>

                    <div class="unit-stats-grid">
                        <div class="unit-stat">
                            <span class="unit-stat-label">⚔ Атака</span>
                            <span class="unit-stat-value"><?= $u['attack'] ?></span>
                        </div>
                        <div class="unit-stat">
                            <span class="unit-stat-label">🛡 Пех.</span>
                            <span class="unit-stat-value"><?= $u['def_inf'] ?></span>
                        </div>
                        <div class="unit-stat">
                            <span class="unit-stat-label">🛡 Кав.</span>
                            <span class="unit-stat-value"><?= $u['def_cav'] ?></span>
                        </div>
                        <div class="unit-stat">
                            <span class="unit-stat-label">📦 Груз.</span>
                            <span class="unit-stat-value"><?= $u['carry'] ?></span>
                        </div>
                        <div class="unit-stat">
                            <span class="unit-stat-label">👥 Попул.</span>
                            <span class="unit-stat-value"><?= $u['pop'] ?></span>
                        </div>
                        <div class="unit-stat">
                            <span class="unit-stat-label">⏱ Время</span>
                            <span class="unit-stat-value"><?= $u['time'] ?>с</span>
                        </div>
                    </div>

                    <div class="unit-cost">
                        <div class="unit-cost-item <?= $can_w ? 'ok':'nok' ?>">
                            🪵 <?= $u['wood'] ?> <?= $can_w ? '✓':'✗' ?>
                        </div>
                        <div class="unit-cost-item <?= $can_s ? 'ok':'nok' ?>">
                            🪨 <?= $u['stone'] ?> <?= $can_s ? '✓':'✗' ?>
                        </div>
                        <div class="unit-cost-item <?= $can_i ? 'ok':'nok' ?>">
                            ⛏ <?= $u['iron'] ?> <?= $can_i ? '✓':'✗' ?>
                        </div>
                    </div>

                    <?php if (!$is_training && $can_afford && $pop_left >= $u['pop']): ?>
                    <form method="POST"
                          action="?page=village&id=<?= $village['id'] ?>&screen=stable">
                        <input type="hidden" name="train" value="1">
                        <input type="hidden" name="unit_type" value="<?= $type ?>">
                        <input type="hidden" name="from_stable" value="1">
                        <div class="train-form">
                            <input type="number" name="amount" class="train-input"
                                   value="1" min="1"
                                   max="<?= max(1, $max_train) ?>">
                            <button type="submit" class="btn-train">
                                Тренировать
                            </button>
                            <span style="font-size:11px;color:#aaa;">
                                Макс: <?= max(0, $max_train) ?>
                            </span>
                        </div>
                    </form>
                    <?php elseif ($is_training): ?>
                        <div style="color:#888;font-size:12px;">⏳ Идёт тренировка...</div>
                    <?php elseif ($pop_left < $u['pop']): ?>
                        <div style="color:#f44;font-size:12px;">
                            👥 Нет места! Улучшите ферму.
                        </div>
                    <?php else: ?>
                        <div style="color:#f44;font-size:12px;">Недостаточно ресурсов</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<script>
let trainEnd = <?= !empty($village['train_end_time']) ? $village['train_end_time'] : 0 ?>;
function updateTimer() {
    const el = document.getElementById('trainTimer');
    if (!el || trainEnd === 0) return;
    const rem = Math.max(0, trainEnd - Math.floor(Date.now()/1000));
    const h = Math.floor(rem/3600);
    const m = Math.floor((rem%3600)/60);
    const s = rem % 60;
    el.textContent =
        `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    if (rem <= 0) location.reload();
}
setInterval(updateTimer, 1000);
</script>

</body>
</html>