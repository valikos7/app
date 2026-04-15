<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ферма — <?= APP_NAME ?></title>
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

        .farm-visual {
            text-align:center; padding:25px;
            background:#1a1a0a; border-radius:8px; margin-bottom:20px;
        }
        .farm-icon { font-size:72px; }
        .farm-level {
            font-size:42px; font-weight:bold;
            color:#d4a843; margin:10px 0;
        }

        .pop-grid {
            display:grid; grid-template-columns:repeat(3,1fr);
            gap:12px; margin:15px 0;
        }
        .pop-item {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:12px; text-align:center;
        }
        .pop-icon { font-size:28px; margin-bottom:5px; }
        .pop-label { font-size:11px; color:#888; margin-bottom:4px; }
        .pop-value { font-size:20px; font-weight:bold; }
        .pop-value.ok  { color:#4f4; }
        .pop-value.warn { color:#fa4; }
        .pop-value.bad  { color:#f44; }

        /* Население визуализация */
        .pop-bar-container {
            margin:15px 0;
        }
        .pop-bar-label {
            display:flex; justify-content:space-between;
            font-size:12px; color:#888; margin-bottom:5px;
        }
        .pop-bar {
            height:20px; background:#333; border-radius:10px;
            overflow:hidden; position:relative;
        }
        .pop-bar-fill {
            height:100%; border-radius:10px;
            transition:0.3s;
        }
        .pop-bar-text {
            position:absolute; top:50%; left:50%;
            transform:translate(-50%,-50%);
            font-size:11px; font-weight:bold; color:#fff;
        }

        .cost-row {
            display:flex; gap:20px; flex-wrap:wrap;
            margin:15px 0; padding:15px;
            background:#1a1a0a; border-radius:6px; border:1px solid #444;
        }
        .cost-item { display:flex; align-items:center; gap:8px; font-size:14px; }
        .cost-item.ok  { color:#4f4; }
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
$max_pop     = $resourceManager->getMaxPopulation($village);
$current_pop = (int)($village['population'] ?? 0);
$farm_level  = (int)($village['farm'] ?? 0);

$pop_pct = $max_pop > 0 ? min(100, round(($current_pop / $max_pop) * 100)) : 0;

if ($pop_pct < 60)      $pop_color = '#4f4';
elseif ($pop_pct < 85)  $pop_color = '#fa4';
else                    $pop_color = '#f44';
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
    <div class="res-item">
        👥 <span class="res-value"><?= $current_pop ?></span>
        /<?= $max_pop ?>
    </div>
</div>

<div class="village-nav">
    <a href="?page=village&id=<?= $village['id'] ?>">🏘 Обзор</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=main">🏛 Гл. здание</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=farm" class="active">🌾 Ферма</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=storage">🏚 Склад</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=barracks">⚔ Казармы</a>
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
        <div class="card-header">🌾 Ферма — Уровень <?= $farm_level ?></div>
        <div class="card-body">

            <div class="farm-visual">
                <div class="farm-icon">🌾</div>
                <div class="farm-level"><?= $farm_level ?></div>
                <div style="color:#888; font-size:13px;">
                    из 30 максимальных уровней
                </div>
            </div>

            <!-- Население -->
            <h3 style="color:#d4a843; margin-bottom:12px;">👥 Население деревни</h3>

            <div class="pop-bar-container">
                <div class="pop-bar-label">
                    <span>Занято: <?= $current_pop ?></span>
                    <span>Максимум: <?= $max_pop ?></span>
                </div>
                <div class="pop-bar">
                    <div class="pop-bar-fill"
                         style="width:<?= $pop_pct ?>%;
                                background:linear-gradient(90deg, <?= $pop_color ?>, <?= $pop_color ?>aa);">
                    </div>
                    <div class="pop-bar-text">
                        <?= $current_pop ?> / <?= $max_pop ?> (<?= $pop_pct ?>%)
                    </div>
                </div>
            </div>

            <div class="pop-grid">
                <div class="pop-item">
                    <div class="pop-icon">👥</div>
                    <div class="pop-label">Текущее население</div>
                    <div class="pop-value <?= $pop_pct < 60 ? 'ok' : ($pop_pct < 85 ? 'warn' : 'bad') ?>">
                        <?= number_format($current_pop) ?>
                    </div>
                </div>
                <div class="pop-item">
                    <div class="pop-icon">🏠</div>
                    <div class="pop-label">Максимум (ур.<?= $farm_level ?>)</div>
                    <div class="pop-value ok"><?= number_format($max_pop) ?></div>
                </div>
                <div class="pop-item">
                    <div class="pop-icon">✨</div>
                    <div class="pop-label">Свободно мест</div>
                    <div class="pop-value <?= ($max_pop-$current_pop) > 10 ? 'ok' : 'bad' ?>">
                        <?= number_format(max(0, $max_pop - $current_pop)) ?>
                    </div>
                </div>
                <div class="pop-item">
                    <div class="pop-icon">⬆</div>
                    <div class="pop-label">После улучшения</div>
                    <div class="pop-value" style="color:#4f4;">
                        <?= number_format(100 + ($farm_level + 1) * 80) ?>
                    </div>
                </div>
                <div class="pop-item">
                    <div class="pop-icon">📈</div>
                    <div class="pop-label">Прирост мест</div>
                    <div class="pop-value" style="color:#4f4;">+80</div>
                </div>
                <div class="pop-item">
                    <div class="pop-icon">🌾</div>
                    <div class="pop-label">Уровень фермы</div>
                    <div class="pop-value" style="color:#d4a843;">
                        <?= $farm_level ?>
                    </div>
                </div>
            </div>

            <!-- Предупреждение -->
            <?php if ($pop_pct >= 85): ?>
            <div style="background:#3a1a1a; border:1px solid #a44;
                        border-radius:6px; padding:12px; margin:15px 0;
                        color:#f44; font-size:13px;">
                ⚠ Население почти заполнено! Улучшите ферму для продолжения тренировки войск.
            </div>
            <?php elseif ($pop_pct >= 60): ?>
            <div style="background:#3a2a0a; border:1px solid #8b6a1a;
                        border-radius:6px; padding:12px; margin:15px 0;
                        color:#fa4; font-size:13px;">
                ⚠ Население заполнено более чем на 60%. Рекомендуем улучшить ферму.
            </div>
            <?php endif; ?>

            <!-- Стоимость -->
            <?php
            $next = $farm_level + 1;
            $wood_cost  = $next * 120;
            $stone_cost = $next * 100;
            $iron_cost  = $next * 80;
            $can_w = $village['r_wood']  >= $wood_cost;
            $can_s = $village['r_stone'] >= $stone_cost;
            $can_i = $village['r_iron']  >= $iron_cost;
            $can_build = $can_w && $can_s && $can_i && $farm_level < 30;
            $is_building = !empty($village['build_queue'])
                        && $village['build_queue'] === 'farm'
                        && $village['queue_end_time'] > time();
            ?>

            <?php if ($farm_level < 30): ?>
            <h3 style="color:#d4a843; margin:20px 0 10px;">
                Улучшение до уровня <?= $next ?>
                <span style="font-size:13px; color:#4f4;">
                    → +80 мест населения
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
                <a href="?page=village&id=<?= $village['id'] ?>&build=farm"
                   class="btn-upgrade">
                    🌾 Улучшить ферму до уровня <?= $next ?>
                </a>
            <?php else: ?>
                <span class="btn-upgrade disabled">Недостаточно ресурсов</span>
            <?php endif; ?>
            <?php else: ?>
                <div style="text-align:center; color:#d4a843; font-size:18px; padding:20px;">
                    🏆 Ферма максимального уровня!
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Таблица -->
    <div class="card">
        <div class="card-header">📊 Таблица уровней фермы</div>
        <div class="card-body" style="padding:0;">
            <table class="levels-table">
                <tr>
                    <th>Уровень</th>
                    <th>🪵 Дерево</th>
                    <th>🪨 Камень</th>
                    <th>⛏ Железо</th>
                    <th>👥 Макс. население</th>
                    <th>Прирост</th>
                </tr>
                <?php for ($i = 1; $i <= 30; $i++):
                    $w   = $i * 120;
                    $s   = $i * 100;
                    $ir  = $i * 80;
                    $pop = 100 + $i * 80;
                ?>
                <tr class="<?= $i == $farm_level ? 'current' : '' ?>">
                    <td>
                        <?= $i == $farm_level
                            ? "<strong style='color:#d4a843;'>→ {$i} (текущий)</strong>"
                            : $i ?>
                    </td>
                    <td><?= number_format($w) ?></td>
                    <td><?= number_format($s) ?></td>
                    <td><?= number_format($ir) ?></td>
                    <td style="color:#4f4; font-weight:bold;">
                        <?= number_format($pop) ?>
                    </td>
                    <td style="color:#888;">+80</td>
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