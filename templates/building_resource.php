<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>
        <?php
        $building_titles = [
            'wood_level'  => 'Лесопилка',
            'stone_level' => 'Каменоломня',
            'iron_level'  => 'Железная шахта'
        ];
        echo ($building_titles[$screen] ?? 'Здание') . ' — ' . APP_NAME;
        ?>
    </title>
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
        .village-nav a:hover { background:#3a2c10; color:#d4a843; }
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

        .building-hero {
            display:flex; gap:25px; align-items:center;
            padding:20px; background:#1a1a0a;
            border-radius:8px; margin-bottom:20px;
        }
        .building-icon-big { font-size:72px; }
        .building-level-badge {
            display:inline-block; background:#3a2c10;
            border:2px solid #d4a843; border-radius:8px;
            padding:5px 15px; color:#d4a843;
            font-size:20px; font-weight:bold; margin-bottom:8px;
        }

        /* График производства */
        .prod-chart {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(120px,1fr));
            gap:10px; margin:15px 0;
        }
        .prod-item {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:12px; text-align:center;
        }
        .prod-icon { font-size:24px; margin-bottom:5px; }
        .prod-label { font-size:11px; color:#888; margin-bottom:4px; }
        .prod-value { font-size:16px; font-weight:bold; color:#d4a843; }
        .prod-current { border-color:#8b6914; background:#2a2010; }
        .prod-next    { border-color:#4a6a4a; background:#1a2a1a; }

        /* Прогресс бар */
        .level-progress {
            height:12px; background:#333; border-radius:6px;
            overflow:hidden; margin:12px 0; position:relative;
        }
        .level-progress-fill {
            height:100%;
            background:linear-gradient(90deg, #5a4a1a, #d4a843);
            border-radius:6px; transition:0.5s;
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
        .btn-upgrade:hover { background:#7aaa2a; transform:translateY(-1px); }
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
        .levels-table tr.next-lvl td { background:#1a2a1a; }
        .levels-table tr:hover td { background:#2a2010; }

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
$prod_per_hour   = $resourceManager->getProductionPerHour($village);
$max_storage     = $resourceManager->getMaxStorage($village);

// Определяем тип здания
$building_configs = [
    'wood_level' => [
        'icon'       => '🌲',
        'name'       => 'Лесопилка',
        'resource'   => 'wood',
        'res_icon'   => '🪵',
        'res_name'   => 'дерева',
        'color'      => '#6a8a2a',
        'desc'       => 'Производит дерево для строительства зданий и тренировки войск.'
    ],
    'stone_level' => [
        'icon'       => '⛏',
        'name'       => 'Каменоломня',
        'resource'   => 'stone',
        'res_icon'   => '🪨',
        'res_name'   => 'камня',
        'color'      => '#6a6a8a',
        'desc'       => 'Добывает камень — основной строительный материал.'
    ],
    'iron_level' => [
        'icon'       => '🔩',
        'name'       => 'Железная шахта',
        'resource'   => 'iron',
        'res_icon'   => '⛏',
        'res_name'   => 'железа',
        'color'      => '#8a7a6a',
        'desc'       => 'Добывает железо для производства оружия и доспехов.'
    ]
];

$cfg          = $building_configs[$screen];
$current_level = (int)($village[$screen] ?? 0);
$next_level    = $current_level + 1;

// Производство на уровень
function getProductionAtLevel($level) {
    return round(40 * (1 + $level * 0.20));
}

$current_prod = getProductionAtLevel($current_level);
$next_prod    = getProductionAtLevel($next_level);
$prod_increase = $next_prod - $current_prod;

// Стоимость
$wood_cost  = $next_level * 120;
$stone_cost = $next_level * 100;
$iron_cost  = $next_level * 80;

$can_w = $village['r_wood']  >= $wood_cost;
$can_s = $village['r_stone'] >= $stone_cost;
$can_i = $village['r_iron']  >= $iron_cost;
$can_build = $can_w && $can_s && $can_i && $current_level < 30;

$is_building = !empty($village['build_queue'])
            && $village['build_queue'] === $screen
            && $village['queue_end_time'] > time();

$main_level  = (int)($village['main'] ?? 1);
$speed_bonus = 1 - ($main_level * 0.05);
if ($speed_bonus < 0.5) $speed_bonus = 0.5;
$base_time   = ($next_level * 60) + 30;
$build_time  = round($base_time * $speed_bonus);
?>

<div class="res-bar">
    <div class="res-item">
        🪵 <span class="res-value"><?= number_format($village['r_wood']) ?></span>
        /<?= number_format($max_storage) ?>
        <span style="color:#666; font-size:11px;">
            +<?= number_format($prod_per_hour['wood']) ?>/ч
        </span>
    </div>
    <div class="res-item">
        🪨 <span class="res-value"><?= number_format($village['r_stone']) ?></span>
        /<?= number_format($max_storage) ?>
        <span style="color:#666; font-size:11px;">
            +<?= number_format($prod_per_hour['stone']) ?>/ч
        </span>
    </div>
    <div class="res-item">
        ⛏ <span class="res-value"><?= number_format($village['r_iron']) ?></span>
        /<?= number_format($max_storage) ?>
        <span style="color:#666; font-size:11px;">
            +<?= number_format($prod_per_hour['iron']) ?>/ч
        </span>
    </div>
</div>

<div class="village-nav">
    <a href="?page=village&id=<?= $village['id'] ?>">🏘 Обзор</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=main">🏛 Гл. здание</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=wood_level"
       <?= $screen==='wood_level' ? 'style="background:#3a2c10;color:#d4a843;"' : '' ?>>
       🌲 Лесопилка
    </a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=stone_level"
       <?= $screen==='stone_level' ? 'style="background:#3a2c10;color:#d4a843;"' : '' ?>>
       ⛏ Каменоломня
    </a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=iron_level"
       <?= $screen==='iron_level' ? 'style="background:#3a2c10;color:#d4a843;"' : '' ?>>
       🔩 Шахта
    </a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=farm">🌾 Ферма</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=storage">🏚 Склад</a>
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

    <!-- Основная карточка -->
    <div class="card">
        <div class="card-header">
            <?= $cfg['icon'] ?> <?= $cfg['name'] ?> — Уровень <?= $current_level ?>
        </div>
        <div class="card-body">

            <!-- Герой здания -->
            <div class="building-hero">
                <div class="building-icon-big"><?= $cfg['icon'] ?></div>
                <div style="flex:1;">
                    <div class="building-level-badge">
                        Уровень <?= $current_level ?> / 30
                    </div>
                    <p style="color:#aaa; font-size:13px; margin:8px 0;">
                        <?= $cfg['desc'] ?>
                    </p>

                    <!-- Прогресс бар уровня -->
                    <div class="level-progress">
                        <div class="level-progress-fill"
                             style="width:<?= ($current_level/30)*100 ?>%;
                                    background:linear-gradient(90deg, #5a4a1a, <?= $cfg['color'] ?>);">
                        </div>
                    </div>
                    <div style="font-size:11px; color:#666;">
                        Прогресс: <?= $current_level ?>/30 уровней
                    </div>
                </div>
            </div>

            <!-- Текущее и следующее производство -->
            <h3 style="color:#d4a843; margin-bottom:12px;">
                <?= $cfg['res_icon'] ?> Производство <?= $cfg['res_name'] ?>
            </h3>
            <div class="prod-chart">
                <div class="prod-item prod-current">
                    <div class="prod-icon"><?= $cfg['res_icon'] ?></div>
                    <div class="prod-label">Сейчас (ур.<?= $current_level ?>)</div>
                    <div class="prod-value"><?= number_format($current_prod) ?>/ч</div>
                </div>
                <?php if ($current_level < 30): ?>
                <div class="prod-item prod-next">
                    <div class="prod-icon">⬆</div>
                    <div class="prod-label">После (ур.<?= $next_level ?>)</div>
                    <div class="prod-value" style="color:#4f4;">
                        <?= number_format($next_prod) ?>/ч
                    </div>
                </div>
                <div class="prod-item">
                    <div class="prod-icon">📈</div>
                    <div class="prod-label">Прирост</div>
                    <div class="prod-value" style="color:#4f4;">
                        +<?= number_format($prod_increase) ?>/ч
                    </div>
                </div>
                <?php endif; ?>
                <div class="prod-item">
                    <div class="prod-icon">📦</div>
                    <div class="prod-label">Сейчас в складе</div>
                    <div class="prod-value">
                        <?= number_format($village['r_' . $cfg['resource']]) ?>
                    </div>
                </div>
                <div class="prod-item">
                    <div class="prod-icon">🏚</div>
                    <div class="prod-label">Макс. склад</div>
                    <div class="prod-value"><?= number_format($max_storage) ?></div>
                </div>
            </div>

            <!-- Стоимость улучшения -->
            <?php if ($current_level < 30): ?>
            <h3 style="color:#d4a843; margin:20px 0 10px;">
                🔨 Улучшение до уровня <?= $next_level ?>
                <span style="font-size:13px; color:#888;">
                    · Время: <?= $build_time ?>с
                    (бонус ГЗ: <?= round((1-$speed_bonus)*100) ?>%)
                </span>
            </h3>

            <div class="cost-row">
                <div class="cost-item <?= $can_w ? 'ok':'nok' ?>">
                    🪵 <?= number_format($wood_cost) ?>
                    <?= $can_w ? '✓':'✗' ?>
                    <span style="font-size:11px; color:#666;">
                        / <?= number_format($village['r_wood']) ?>
                    </span>
                </div>
                <div class="cost-item <?= $can_s ? 'ok':'nok' ?>">
                    🪨 <?= number_format($stone_cost) ?>
                    <?= $can_s ? '✓':'✗' ?>
                    <span style="font-size:11px; color:#666;">
                        / <?= number_format($village['r_stone']) ?>
                    </span>
                </div>
                <div class="cost-item <?= $can_i ? 'ok':'nok' ?>">
                    ⛏ <?= number_format($iron_cost) ?>
                    <?= $can_i ? '✓':'✗' ?>
                    <span style="font-size:11px; color:#666;">
                        / <?= number_format($village['r_iron']) ?>
                    </span>
                </div>
                <div class="cost-item" style="color:#aaa;">
                    ⏱ <?= $build_time ?>с
                    (<?= floor($build_time/60) ?>м <?= $build_time%60 ?>с)
                </div>
            </div>

            <!-- Кнопка улучшения -->
            <?php if ($is_building): ?>
                <div style="background:#1a2a1a; border:1px solid #4a4;
                            border-radius:6px; padding:15px; text-align:center;">
                    🔨 Идёт улучшение...
                    <strong style="color:#0f0;" id="buildTimer">
                        <?= gmdate('H:i:s', max(0, $village['queue_end_time'] - time())) ?>
                    </strong>
                </div>
            <?php elseif (!empty($village['build_queue']) && $village['queue_end_time'] > time()): ?>
                <div style="background:#2a1a0a; border:1px solid #8b4a1a;
                            border-radius:6px; padding:15px; text-align:center;
                            color:#d4a843; font-size:13px;">
                    ⏳ Идёт строительство другого здания (<?= $village['build_queue'] ?>)
                </div>
            <?php elseif ($can_build): ?>
                <a href="?page=village&id=<?= $village['id'] ?>&build=<?= $screen ?>"
                   class="btn-upgrade">
                    <?= $cfg['icon'] ?> Улучшить до уровня <?= $next_level ?>
                </a>
            <?php else: ?>
                <span class="btn-upgrade disabled">
                    ❌ Недостаточно ресурсов
                </span>
            <?php endif; ?>

            <?php else: ?>
                <div style="text-align:center; color:#d4a843;
                            font-size:18px; padding:20px;">
                    🏆 Максимальный уровень достигнут!
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Таблица всех уровней -->
    <div class="card">
        <div class="card-header">
            📊 Таблица уровней — <?= $cfg['name'] ?>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="levels-table">
                <tr>
                    <th>Уровень</th>
                    <th>🪵 Дерево</th>
                    <th>🪨 Камень</th>
                    <th>⛏ Железо</th>
                    <th>⏱ Время</th>
                    <th><?= $cfg['res_icon'] ?> Произв./ч</th>
                    <th>Прирост</th>
                </tr>
                <?php for ($i = 1; $i <= 30; $i++):
                    $w    = $i * 120;
                    $s    = $i * 100;
                    $ir   = $i * 80;
                    $t    = round(($i * 60 + 30) * $speed_bonus);
                    $prod = getProductionAtLevel($i);
                    $prev = getProductionAtLevel($i - 1);
                    $inc  = $prod - $prev;
                    $cls  = '';
                    if ($i === $current_level)     $cls = 'current';
                    elseif ($i === $next_level)    $cls = 'next-lvl';
                ?>
                <tr class="<?= $cls ?>">
                    <td>
                        <?php if ($i === $current_level): ?>
                            <strong style="color:#d4a843;">→ <?= $i ?> ✓</strong>
                        <?php elseif ($i === $next_level && $current_level < 30): ?>
                            <strong style="color:#4f4;"><?= $i ?> ←</strong>
                        <?php else: ?>
                            <?= $i ?>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($w) ?></td>
                    <td><?= number_format($s) ?></td>
                    <td><?= number_format($ir) ?></td>
                    <td><?= floor($t/60) ?>м <?= $t%60 ?>с</td>
                    <td style="color:#d4a843; font-weight:bold;">
                        <?= number_format($prod) ?>
                    </td>
                    <td style="color:#4f4; font-size:11px;">+<?= $inc ?></td>
                </tr>
                <?php endfor; ?>
            </table>
        </div>
    </div>

</div>

<script>
let buildEnd = <?= !empty($village['queue_end_time']) ? $village['queue_end_time'] : 0 ?>;

function updateTimer() {
    const el = document.getElementById('buildTimer');
    if (!el || buildEnd === 0) return;
    const rem = Math.max(0, buildEnd - Math.floor(Date.now() / 1000));
    const h   = Math.floor(rem / 3600);
    const m   = Math.floor((rem % 3600) / 60);
    const s   = rem % 60;
    el.textContent =
        `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    if (rem <= 0) location.reload();
}
setInterval(updateTimer, 1000);
</script>

</body>
</html>