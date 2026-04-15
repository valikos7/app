<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Склад — <?= APP_NAME ?></title>
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

        .storage-visual {
            display:grid; grid-template-columns:repeat(3,1fr);
            gap:15px; margin-bottom:25px;
        }
        .storage-item {
            background:#1a1a0a; border:1px solid #444;
            border-radius:8px; padding:15px; text-align:center;
        }
        .storage-icon { font-size:36px; margin-bottom:8px; }
        .storage-label { font-size:12px; color:#888; margin-bottom:5px; }
        .storage-current { font-size:20px; font-weight:bold; color:#d4a843; }
        .storage-max { font-size:12px; color:#666; margin-top:3px; }

        .res-progress {
            height:10px; background:#333; border-radius:5px;
            overflow:hidden; margin:8px 0;
        }
        .res-progress-fill {
            height:100%; border-radius:5px; transition:0.3s;
        }
        .fill-wood  { background:linear-gradient(90deg,#3a5a1a,#6a8a2a); }
        .fill-stone { background:linear-gradient(90deg,#3a3a5a,#6a6a8a); }
        .fill-iron  { background:linear-gradient(90deg,#4a4a4a,#8a8a8a); }

        .upgrade-section { margin-top:20px; }
        .cost-row {
            display:flex; gap:20px; flex-wrap:wrap;
            margin:15px 0; padding:15px;
            background:#1a1a0a; border-radius:6px; border:1px solid #444;
        }
        .cost-item { display:flex; align-items:center; gap:8px; font-size:14px; }
        .cost-item.ok { color:#4f4; }
        .cost-item.nok { color:#f44; }

        .btn-upgrade {
            display:inline-block; padding:12px 30px;
            background:#5a8a1a; color:#fff; border:none;
            border-radius:6px; font-size:15px; cursor:pointer;
            text-decoration:none; transition:0.2s;
        }
        .btn-upgrade:hover { background:#7aaa2a; }
        .btn-upgrade.disabled { background:#444; cursor:not-allowed; color:#888; }

        .levels-table { width:100%; border-collapse:collapse; }
        .levels-table th {
            background:#3a2c10; color:#d4a843;
            padding:10px 12px; text-align:left; font-size:13px;
        }
        .levels-table td {
            padding:10px 12px; border-bottom:1px solid #333; font-size:13px;
        }
        .levels-table tr.current td { background:#2a3a10; }

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
$max_storage = $resourceManager->getMaxStorage($village);
$prod = $resourceManager->getProductionPerHour($village);

$wood_pct  = min(100, round(($village['r_wood']  / $max_storage) * 100));
$stone_pct = min(100, round(($village['r_stone'] / $max_storage) * 100));
$iron_pct  = min(100, round(($village['r_iron']  / $max_storage) * 100));
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
</div>

<div class="village-nav">
    <a href="?page=village&id=<?= $village['id'] ?>">🏘 Обзор</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=main">🏛 Гл. здание</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=barracks">⚔ Казармы</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=stable">🐎 Конюшня</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=smith">🔨 Кузница</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=storage" class="active">🏚 Склад</a>
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

    <div class="card">
        <div class="card-header">
            🏚 Склад — Уровень <?= $village['storage'] ?>
            <span style="float:right; font-size:12px; color:#aaa;">
                Макс. хранилище: <?= number_format($max_storage) ?> ед.
            </span>
        </div>
        <div class="card-body">

            <!-- Визуализация ресурсов -->
            <div class="storage-visual">
                <div class="storage-item">
                    <div class="storage-icon">🪵</div>
                    <div class="storage-label">Дерево</div>
                    <div class="storage-current">
                        <?= number_format($village['r_wood']) ?>
                    </div>
                    <div class="res-progress">
                        <div class="res-progress-fill fill-wood"
                             style="width:<?= $wood_pct ?>%;"></div>
                    </div>
                    <div class="storage-max"><?= $wood_pct ?>% заполнено</div>
                    <div style="font-size:11px; color:#888; margin-top:4px;">
                        +<?= number_format($prod['wood']) ?>/час
                    </div>
                </div>
                <div class="storage-item">
                    <div class="storage-icon">🪨</div>
                    <div class="storage-label">Камень</div>
                    <div class="storage-current">
                        <?= number_format($village['r_stone']) ?>
                    </div>
                    <div class="res-progress">
                        <div class="res-progress-fill fill-stone"
                             style="width:<?= $stone_pct ?>%;"></div>
                    </div>
                    <div class="storage-max"><?= $stone_pct ?>% заполнено</div>
                    <div style="font-size:11px; color:#888; margin-top:4px;">
                        +<?= number_format($prod['stone']) ?>/час
                    </div>
                </div>
                <div class="storage-item">
                    <div class="storage-icon">⛏</div>
                    <div class="storage-label">Железо</div>
                    <div class="storage-current">
                        <?= number_format($village['r_iron']) ?>
                    </div>
                    <div class="res-progress">
                        <div class="res-progress-fill fill-iron"
                             style="width:<?= $iron_pct ?>%;"></div>
                    </div>
                    <div class="storage-max"><?= $iron_pct ?>% заполнено</div>
                    <div style="font-size:11px; color:#888; margin-top:4px;">
                        +<?= number_format($prod['iron']) ?>/час
                    </div>
                </div>
            </div>

            <!-- Время до заполнения -->
            <?php
            $times = [];
            if ($prod['wood'] > 0) {
                $left_wood = $max_storage - $village['r_wood'];
                $mins = round(($left_wood / $prod['wood']) * 60);
                $times[] = "🪵 заполнится через " . floor($mins/60) . "ч " . ($mins%60) . "м";
            }
            if ($prod['stone'] > 0) {
                $left_stone = $max_storage - $village['r_stone'];
                $mins = round(($left_stone / $prod['stone']) * 60);
                $times[] = "🪨 заполнится через " . floor($mins/60) . "ч " . ($mins%60) . "м";
            }
            if ($prod['iron'] > 0) {
                $left_iron = $max_storage - $village['r_iron'];
                $mins = round(($left_iron / $prod['iron']) * 60);
                $times[] = "⛏ заполнится через " . floor($mins/60) . "ч " . ($mins%60) . "м";
            }
            ?>
            <?php if (!empty($times)): ?>
            <div style="background:#1a1a0a; border:1px solid #444; border-radius:6px;
                        padding:12px; margin-bottom:20px; font-size:12px; color:#888;">
                <?= implode(' &nbsp;|&nbsp; ', $times) ?>
            </div>
            <?php endif; ?>

            <!-- Улучшение -->
            <?php
            $current = (int)$village['storage'];
            $next = $current + 1;
            $wood_cost  = $next * 120;
            $stone_cost = $next * 100;
            $iron_cost  = $next * 80;
            $new_cap = 800 + ($next * 400);

            $can_w = $village['r_wood']  >= $wood_cost;
            $can_s = $village['r_stone'] >= $stone_cost;
            $can_i = $village['r_iron']  >= $iron_cost;
            $can_build = $can_w && $can_s && $can_i && $current < 30;
            $is_building = !empty($village['build_queue'])
                        && $village['build_queue'] === 'storage'
                        && $village['queue_end_time'] > time();
            ?>

            <?php if ($current < 30): ?>
            <div class="upgrade-section">
                <h3 style="color:#d4a843; margin-bottom:10px;">
                    📦 Улучшение до уровня <?= $next ?>
                    <span style="font-size:13px; color:#4f4;">
                        → вместимость: <?= number_format($new_cap) ?> ед.
                    </span>
                </h3>
                <div class="cost-row">
                    <div class="cost-item <?= $can_w ? 'ok':'nok' ?>">
                        🪵 <?= number_format($wood_cost) ?> <?= $can_w ? '✓':'✗' ?>
                    </div>
                    <div class="cost-item <?= $can_s ? 'ok':'nok' ?>">
                        🪨 <?= number_format($stone_cost) ?> <?= $can_s ? '✓':'✗' ?>
                    </div>
                    <div class="cost-item <?= $can_i ? 'ok':'nok' ?>">
                        ⛏ <?= number_format($iron_cost) ?> <?= $can_i ? '✓':'✗' ?>
                    </div>
                </div>

                <?php if ($is_building): ?>
                    <div style="background:#1a2a1a;border:1px solid #4a4;
                                border-radius:6px;padding:15px;text-align:center;">
                        🔨 Идёт строительство...
                        <strong style="color:#0f0;" id="buildTimer">
                            <?= gmdate('H:i:s', max(0,$village['queue_end_time']-time())) ?>
                        </strong>
                    </div>
                <?php elseif ($can_build): ?>
                    <a href="?page=village&id=<?= $village['id'] ?>&build=storage"
                       class="btn-upgrade">
                        🏚 Улучшить склад до уровня <?= $next ?>
                    </a>
                <?php else: ?>
                    <span class="btn-upgrade disabled">Недостаточно ресурсов</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Таблица уровней -->
    <div class="card">
        <div class="card-header">📊 Таблица уровней склада</div>
        <div class="card-body" style="padding:0;">
            <table class="levels-table">
                <tr>
                    <th>Уровень</th>
                    <th>🪵 Дерево</th>
                    <th>🪨 Камень</th>
                    <th>⛏ Железо</th>
                    <th>📦 Вместимость</th>
                </tr>
                <?php for ($i = 1; $i <= 30; $i++):
                    $w = $i*120; $s = $i*100; $ir = $i*80;
                    $cap = 800 + ($i * 400);
                ?>
                <tr class="<?= $i == $current ? 'current' : '' ?>">
                    <td>
                        <?= $i == $current
                            ? "<strong style='color:#d4a843;'>→ $i (текущий)</strong>"
                            : $i ?>
                    </td>
                    <td><?= number_format($w) ?></td>
                    <td><?= number_format($s) ?></td>
                    <td><?= number_format($ir) ?></td>
                    <td style="color:#4f4;"><?= number_format($cap) ?></td>
                </tr>
                <?php endfor; ?>
            </table>
        </div>
    </div>

</div>

<script>
let buildEnd = <?= !empty($village['queue_end_time']) ? $village['queue_end_time'] : 0 ?>;
function tick() {
    const el = document.getElementById('buildTimer');
    if (!el) return;
    const rem = Math.max(0, buildEnd - Math.floor(Date.now()/1000));
    const h = Math.floor(rem/3600);
    const m = Math.floor((rem%3600)/60);
    const s = rem%60;
    el.textContent =
        `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    if (rem <= 0) location.reload();
}
setInterval(tick, 1000);
</script>

</body>
</html>