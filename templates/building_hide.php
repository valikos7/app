<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Тайник — <?= APP_NAME ?></title>
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

        .hide-visual {
            text-align:center; padding:25px;
            background:#1a1a0a; border-radius:8px; margin-bottom:20px;
        }
        .hide-icon { font-size:64px; }
        .hide-level {
            font-size:42px; font-weight:bold;
            color:#d4a843; margin:10px 0;
        }

        .protect-grid {
            display:grid; grid-template-columns:repeat(3,1fr);
            gap:12px; margin:15px 0;
        }
        .protect-item {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:12px; text-align:center;
        }
        .protect-icon { font-size:28px; margin-bottom:6px; }
        .protect-label { font-size:11px; color:#888; }
        .protect-value { font-size:18px; font-weight:bold; color:#4f4; }

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
    </div>
</div>

<?php
$db = $db ?? $GLOBALS['db'];
$resourceManager = new ResourceManager($db);
$max_storage = $resourceManager->getMaxStorage($village);
$hide_level = (int)($village['hide'] ?? 0);
$protected = $hide_level * 200;
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
    <a href="?page=village&id=<?= $village['id'] ?>&screen=smith">🔨 Кузница</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=wall">🧱 Стена</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=hide" class="active">🏴 Тайник</a>
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
        <div class="card-header">🏴 Тайник — Уровень <?= $hide_level ?></div>
        <div class="card-body">

            <div class="hide-visual">
                <div class="hide-icon">🏴</div>
                <div class="hide-level"><?= $hide_level ?></div>
                <div style="color:#888; font-size:13px;">
                    из 10 максимальных уровней
                </div>
            </div>

            <p style="color:#888; font-size:13px; margin-bottom:20px;">
                Тайник прячет ресурсы от грабителей. При атаке враг не сможет
                похитить защищённые ресурсы. Каждый уровень защищает
                <strong style="color:#d4a843;">200 единиц</strong> каждого ресурса.
            </p>

            <!-- Защищённые ресурсы -->
            <div class="protect-grid">
                <div class="protect-item">
                    <div class="protect-icon">🪵</div>
                    <div class="protect-label">Защищено дерева</div>
                    <div class="protect-value"><?= number_format($protected) ?></div>
                </div>
                <div class="protect-item">
                    <div class="protect-icon">🪨</div>
                    <div class="protect-label">Защищено камня</div>
                    <div class="protect-value"><?= number_format($protected) ?></div>
                </div>
                <div class="protect-item">
                    <div class="protect-icon">⛏</div>
                    <div class="protect-label">Защищено железа</div>
                    <div class="protect-value"><?= number_format($protected) ?></div>
                </div>
            </div>

            <!-- Статус защиты -->
            <div style="background:#1a2a1a; border:1px solid #4a6a4a;
                        border-radius:6px; padding:15px; margin:15px 0;">
                <h4 style="color:#4f4; margin-bottom:10px;">
                    🛡 Текущая защита ресурсов
                </h4>
                <?php foreach (['r_wood'=>['🪵','Дерево'], 'r_stone'=>['🪨','Камень'], 'r_iron'=>['⛏','Железо']] as $key => $info):
                    $current_res = (int)$village[$key];
                    $safe = min($current_res, $protected);
                    $unsafe = max(0, $current_res - $protected);
                    $safe_pct = $current_res > 0 ? round(($safe/$current_res)*100) : 100;
                ?>
                <div style="margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                        <span><?= $info[0] ?> <?= $info[1] ?>: <?= number_format($current_res) ?></span>
                        <span>
                            <span style="color:#4f4;">Защищено: <?= number_format($safe) ?></span>
                            <?php if ($unsafe > 0): ?>
                            · <span style="color:#f44;">Уязвимо: <?= number_format($unsafe) ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div style="height:8px; background:#333; border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:<?= $safe_pct ?>%;
                                    background:linear-gradient(90deg,#2a5a2a,#4f4);
                                    border-radius:4px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Улучшение -->
            <?php
            $current = $hide_level;
            $next = $current + 1;
            $wood_cost  = $next * 80;
            $stone_cost = $next * 60;
            $iron_cost  = $next * 40;

            $can_w = $village['r_wood']  >= $wood_cost;
            $can_s = $village['r_stone'] >= $stone_cost;
            $can_i = $village['r_iron']  >= $iron_cost;
            $can_build = $can_w && $can_s && $can_i && $current < 10;
            $is_building = !empty($village['build_queue'])
                        && $village['build_queue'] === 'hide'
                        && $village['queue_end_time'] > time();
            ?>

            <?php if ($current < 10): ?>
            <h3 style="color:#d4a843; margin-bottom:10px;">
                Улучшение до уровня <?= $next ?>
                <span style="font-size:13px; color:#4f4;">
                    → защита: <?= number_format($next * 200) ?> ед. каждого
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
                <div style="background:#1a2a1a; border:1px solid #4a4;
                            border-radius:6px; padding:15px; text-align:center;">
                    🔨 Идёт строительство...
                    <strong style="color:#0f0;" id="buildTimer">
                        <?= gmdate('H:i:s', max(0, $village['queue_end_time'] - time())) ?>
                    </strong>
                </div>
            <?php elseif ($can_build): ?>
                <a href="?page=village&id=<?= $village['id'] ?>&build=hide"
                   class="btn-upgrade">
                    🏴 Улучшить тайник до уровня <?= $next ?>
                </a>
            <?php else: ?>
                <span class="btn-upgrade disabled">Недостаточно ресурсов</span>
            <?php endif; ?>
            <?php else: ?>
                <div style="text-align:center; color:#d4a843; font-size:18px; padding:20px;">
                    🏆 Тайник максимального уровня!
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Таблица уровней -->
    <div class="card">
        <div class="card-header">📊 Уровни тайника</div>
        <div class="card-body" style="padding:0;">
            <table class="levels-table">
                <tr>
                    <th>Уровень</th>
                    <th>🪵 Дерево</th>
                    <th>🪨 Камень</th>
                    <th>⛏ Железо</th>
                    <th>🛡 Защита каждого</th>
                </tr>
                <?php for ($i = 1; $i <= 10; $i++):
                    $w = $i*80; $s = $i*60; $ir = $i*40;
                    $prot = $i * 200;
                ?>
                <tr class="<?= $i == $hide_level ? 'current' : '' ?>">
                    <td>
                        <?= $i == $hide_level
                            ? "<strong style='color:#d4a843;'>→ $i (текущий)</strong>"
                            : $i ?>
                    </td>
                    <td><?= number_format($w) ?></td>
                    <td><?= number_format($s) ?></td>
                    <td><?= number_format($ir) ?></td>
                    <td style="color:#4f4;"><?= number_format($prot) ?> ед.</td>
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