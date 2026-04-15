<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Стена — <?= APP_NAME ?></title>
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
        .container { max-width:900px; margin:20px auto; padding:0 15px; }
        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
        }
        .card-body { padding:20px; }

        .wall-visual {
            text-align:center; padding:30px;
            background:#1a1a0a; border-radius:8px; margin-bottom:20px;
        }
        .wall-emoji { font-size:60px; }
        .wall-level-big {
            font-size:48px; font-weight:bold; color:#d4a843; margin:10px 0;
        }

        .wall-progress {
            height:20px; background:#333; border-radius:10px;
            overflow:hidden; margin:15px 0; position:relative;
        }
        .wall-progress-fill {
            height:100%; border-radius:10px;
            background:linear-gradient(90deg,#5a3a1a,#d4a843);
            transition:width 0.5s;
        }
        .wall-progress-text {
            position:absolute; top:50%; left:50%;
            transform:translate(-50%,-50%);
            font-size:12px; font-weight:bold; color:#fff;
        }

        .bonus-grid {
            display:grid; grid-template-columns:1fr 1fr;
            gap:10px; margin:15px 0;
        }
        .bonus-card {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:12px; text-align:center;
        }
        .bonus-label { font-size:11px; color:#888; margin-bottom:5px; }
        .bonus-value { font-size:20px; font-weight:bold; color:#d4a843; }

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
$wall_level = (int)($village['wall'] ?? 0);
?>

<div class="res-bar">
    <div class="res-item">
        🪵 <span class="res-value"><?= number_format($village['r_wood']) ?></span>
    </div>
    <div class="res-item">
        🪨 <span class="res-value"><?= number_format($village['r_stone']) ?></span>
    </div>
    <div class="res-item">
        ⛏ <span class="res-value"><?= number_format($village['r_iron']) ?></span>
    </div>
</div>

<div class="village-nav">
    <a href="?page=village&id=<?= $village['id'] ?>">🏘 Обзор</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=main">🏛 Гл. здание</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=barracks">⚔ Казармы</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=stable">🐎 Конюшня</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=smith">🔨 Кузница</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=wall" class="active">🧱 Стена</a>
</div>

<div class="container">

    <?php if (isset($_SESSION['success'])): ?>
        <div style="background:#1a3a1a;border:1px solid #4a4;border-radius:6px;
                    padding:12px;margin-bottom:15px;color:#0f0;">
            <?= $_SESSION['success'] ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background:#3a1a1a;border:1px solid #a44;border-radius:6px;
                    padding:12px;margin-bottom:15px;color:#f66;">
            <?= $_SESSION['error'] ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">🧱 Стена — Уровень <?= $wall_level ?></div>
        <div class="card-body">

            <div class="wall-visual">
                <div class="wall-emoji">🧱</div>
                <div class="wall-level-big"><?= $wall_level ?></div>
                <div style="color:#888; font-size:13px;">из 20 максимальных уровней</div>
                <div class="wall-progress">
                    <div class="wall-progress-fill"
                         style="width:<?= ($wall_level/20)*100 ?>%;"></div>
                    <div class="wall-progress-text">
                        <?= ($wall_level/20*100) ?>%
                    </div>
                </div>
            </div>

            <!-- Бонусы стены -->
            <div class="bonus-grid">
                <div class="bonus-card">
                    <div class="bonus-label">🛡 Бонус защиты</div>
                    <div class="bonus-value">+<?= $wall_level * 5 ?>%</div>
                </div>
                <div class="bonus-card">
                    <div class="bonus-label">⚔ Базовая защита</div>
                    <div class="bonus-value"><?= 20 + $wall_level * 10 ?></div>
                </div>
                <div class="bonus-card">
                    <div class="bonus-label">🪵 Защита от тарана</div>
                    <div class="bonus-value"><?= $wall_level * 2 ?> ур.</div>
                </div>
                <div class="bonus-card">
                    <div class="bonus-label">📊 Следующий уровень</div>
                    <div class="bonus-value" style="font-size:14px;">
                        +<?= ($wall_level+1) * 5 ?>% защиты
                    </div>
                </div>
            </div>

            <!-- Улучшение -->
            <?php
            $next = $wall_level + 1;
            $wood_cost  = $next * 150;
            $stone_cost = $next * 200;
            $iron_cost  = $next * 100;

            $can_w = $village['r_wood']  >= $wood_cost;
            $can_s = $village['r_stone'] >= $stone_cost;
            $can_i = $village['r_iron']  >= $iron_cost;
            $can_build = $can_w && $can_s && $can_i && $wall_level < 20;
            $is_building = !empty($village['build_queue'])
                        && $village['build_queue'] === 'wall'
                        && $village['queue_end_time'] > time();
            ?>

            <?php if ($wall_level < 20): ?>
            <h3 style="color:#d4a843;margin:20px 0 10px;">
                Улучшение до уровня <?= $next ?>
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
                        <?= gmdate('H:i:s', max(0, $village['queue_end_time'] - time())) ?>
                    </strong>
                </div>
            <?php elseif ($can_build): ?>
                <a href="?page=village&id=<?= $village['id'] ?>&build=wall"
                   class="btn-upgrade">
                    🧱 Улучшить стену до уровня <?= $next ?>
                </a>
            <?php else: ?>
                <span class="btn-upgrade disabled">Недостаточно ресурсов</span>
            <?php endif; ?>
            <?php else: ?>
                <div style="text-align:center;color:#d4a843;font-size:18px;padding:20px;">
                    🏆 Стена максимального уровня!
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Таблица уровней -->
    <div class="card">
        <div class="card-header">📊 Таблица уровней стены</div>
        <div class="card-body" style="padding:0;">
            <table class="levels-table">
                <tr>
                    <th>Уровень</th>
                    <th>🪵 Дерево</th>
                    <th>🪨 Камень</th>
                    <th>⛏ Железо</th>
                    <th>🛡 Бонус защиты</th>
                    <th>⚔ Базовая защита</th>
                </tr>
                <?php for ($i = 1; $i <= 20; $i++):
                    $w = $i * 150; $s = $i * 200; $ir = $i * 100;
                    $bonus = $i * 5;
                    $base = 20 + $i * 10;
                ?>
                <tr class="<?= $i == $wall_level ? 'current' : '' ?>">
                    <td>
                        <?= $i == $wall_level
                            ? "<strong style='color:#d4a843;'>→ $i (текущий)</strong>"
                            : $i ?>
                    </td>
                    <td><?= number_format($w) ?></td>
                    <td><?= number_format($s) ?></td>
                    <td><?= number_format($ir) ?></td>
                    <td style="color:#4f4;">+<?= $bonus ?>%</td>
                    <td><?= $base ?></td>
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