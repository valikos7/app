<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($village['name']) ?> — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }

        .res-bar {
            background:#2a2010; border-bottom:2px solid #5a4a20;
            padding:8px 15px; display:flex;
            justify-content:center; align-items:center;
            gap:20px; flex-wrap:wrap;
        }
        .res-item { display:flex; align-items:center; gap:5px; font-size:13px; }
        .res-icon { font-size:18px; }
        .res-value { font-weight:bold; color:#e8c870; }
        .res-rate { font-size:10px; color:#888; }
        .res-bar-fill {
            height:3px; background:#5a4a20; border-radius:2px;
            margin-top:2px; width:70px; overflow:hidden;
        }
        .res-bar-fill-inner {
            height:100%; background:#d4a843; border-radius:2px;
        }

        .village-nav {
            background:#241c0e; border-bottom:2px solid #5a4a20;
            display:flex; justify-content:center; flex-wrap:wrap;
            overflow-x:auto;
        }
        .village-nav a {
            padding:9px 13px; color:#aaa; text-decoration:none;
            font-size:12px; border-right:1px solid #333;
            transition:0.2s; white-space:nowrap;
        }
        .village-nav a:hover { background:#3a2c10; color:#d4a843; }
        .village-nav a.active {
            background:#3a2c10; color:#d4a843;
            border-bottom:2px solid #d4a843;
        }

        .main-content {
            max-width:1200px; margin:0 auto; padding:12px;
            display:grid; grid-template-columns:1fr 320px; gap:12px;
        }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:12px;
        }
        .card-header {
            background:#3a2c10; padding:9px 14px;
            font-weight:bold; color:#d4a843; font-size:13px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .card-body { padding:12px; }

        .village-map-title {
            background:#3a2c10; padding:10px 14px;
            font-weight:bold; color:#d4a843;
            display:flex; justify-content:space-between; align-items:center;
        }

        .buildings-grid {
            display:grid; grid-template-columns:repeat(4,1fr);
            gap:6px; padding:12px;
        }
        .building-cell {
            background:#1a1a0a; border:2px solid #444;
            border-radius:6px; padding:8px 4px; text-align:center;
            cursor:pointer; transition:0.2s;
            text-decoration:none; color:#ddd; display:block;
        }
        .building-cell:hover { border-color:#d4a843; background:#2a2a10; }
        .building-cell.built { border-color:#5a4a20; }
        .building-cell.max-level { border-color:#d4a843; background:#2a2010; }
        .building-icon { font-size:24px; margin-bottom:3px; }
        .building-name { font-size:9px; color:#aaa; }
        .building-level { font-size:16px; font-weight:bold; color:#d4a843; }
        .building-level.zero { color:#555; }

        .build-queue {
            background:#1a2a1a; border:1px solid #4a6a4a;
            border-radius:6px; padding:10px; text-align:center;
        }
        .queue-building { font-size:13px; color:#d4a843; font-weight:bold; }
        .queue-timer { font-size:22px; color:#0f0; font-weight:bold; margin:4px 0; }
        .queue-bar { height:5px; background:#333; border-radius:3px; overflow:hidden; margin-top:6px; }
        .queue-bar-fill { height:100%; background:linear-gradient(90deg,#4a8a4a,#0f0); border-radius:3px; }

        .troops-table { width:100%; border-collapse:collapse; }
        .troops-table td { padding:5px 8px; border-bottom:1px solid #333; font-size:12px; }
        .troops-table .unit-icon { font-size:15px; }
        .troops-table .unit-count { font-weight:bold; color:#d4a843; text-align:right; }

        .movement-item {
            border-radius:4px; padding:7px; margin-bottom:5px; font-size:12px;
        }
        .movement-item.attack  { border:1px solid #a44; background:#2a1a1a; }
        .movement-item.return  { border:1px solid #4a4; background:#1a2a1a; }
        .movement-item.support { border:1px solid #44a; background:#1a1a2a; }
        .movement-item.scout   { border:1px solid #aa4; background:#2a2a1a; }

        .support-item { padding:7px 0; border-bottom:1px solid #333; font-size:12px; }
        .support-item:last-child { border-bottom:none; }
        .troop-chip-mini {
            display:inline-flex; align-items:center; gap:3px;
            background:#1a2a1a; border:1px solid #4a6a4a;
            border-radius:3px; padding:2px 5px; font-size:10px; margin:2px;
        }

        .village-info td { padding:5px 8px; font-size:12px; border-bottom:1px solid #333; }
        .village-info td:first-child { color:#888; }
        .village-info td:last-child { color:#ddd; font-weight:bold; }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4; border-radius:6px;
            padding:12px; margin-bottom:12px; color:#0f0; font-size:13px;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44; border-radius:6px;
            padding:12px; margin-bottom:12px; color:#f66; font-size:13px;
        }

        @media(max-width:900px) {
            .main-content { grid-template-columns:1fr; }
            .buildings-grid { grid-template-columns:repeat(3,1fr); }
        }
        @media(max-width:500px) {
            .buildings-grid { grid-template-columns:repeat(2,1fr); }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<?php
$resourceManager = new ResourceManager($db);
$prod        = $resourceManager->getProductionPerHour($village);
$max_storage = $resourceManager->getMaxStorage($village);
$wood_pct    = $max_storage > 0 ? min(100, round(($village['r_wood']  / $max_storage)*100)) : 0;
$stone_pct   = $max_storage > 0 ? min(100, round(($village['r_stone'] / $max_storage)*100)) : 0;
$iron_pct    = $max_storage > 0 ? min(100, round(($village['r_iron']  / $max_storage)*100)) : 0;
?>

<!-- Ресурсы -->
<div class="res-bar">
    <div class="res-item">
        <span class="res-icon">🪵</span>
        <div>
            <span class="res-value" id="res_wood"><?= number_format($village['r_wood']) ?></span>
            <span style="color:#555;">/<?= number_format($max_storage) ?></span>
            <div class="res-bar-fill">
                <div class="res-bar-fill-inner" style="width:<?= $wood_pct ?>%;"></div>
            </div>
            <div class="res-rate">+<?= number_format($prod['wood']) ?>/ч</div>
        </div>
    </div>
    <div class="res-item">
        <span class="res-icon">🪨</span>
        <div>
            <span class="res-value" id="res_stone"><?= number_format($village['r_stone']) ?></span>
            <span style="color:#555;">/<?= number_format($max_storage) ?></span>
            <div class="res-bar-fill">
                <div class="res-bar-fill-inner" style="width:<?= $stone_pct ?>%;"></div>
            </div>
            <div class="res-rate">+<?= number_format($prod['stone']) ?>/ч</div>
        </div>
    </div>
    <div class="res-item">
        <span class="res-icon">⛏</span>
        <div>
            <span class="res-value" id="res_iron"><?= number_format($village['r_iron']) ?></span>
            <span style="color:#555;">/<?= number_format($max_storage) ?></span>
            <div class="res-bar-fill">
                <div class="res-bar-fill-inner" style="width:<?= $iron_pct ?>%;"></div>
            </div>
            <div class="res-rate">+<?= number_format($prod['iron']) ?>/ч</div>
        </div>
    </div>
    <div class="res-item">
        <span class="res-icon">🌾</span>
        <div>
            <span class="res-value"><?= number_format($village['food'] ?? 0) ?></span>
            <div class="res-rate">Ферма ур.<?= $village['farm'] ?></div>
        </div>
    </div>
    <div class="res-item">
        <span class="res-icon">👥</span>
        <div>
            <span class="res-value"><?= $village['population'] ?? 0 ?></span>
            <span style="color:#555;">
                /<?= $resourceManager->getMaxPopulation($village) ?>
            </span>
            <div class="res-rate">Население</div>
        </div>
    </div>
</div>

<!-- Навигация деревни -->
<div class="village-nav">
    <a href="?page=village&id=<?= $village['id'] ?>" class="active">🏘 Обзор</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=main">🏛 ГЗ</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=wood_level">🌲 Лес</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=stone_level">⛏ Камень</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=iron_level">🔩 Шахта</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=farm">🌾 Ферма</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=storage">🏚 Склад</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=barracks">⚔ Казармы</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=stable">🐎 Конюшня</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=garage">⚙ Мастерская</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=smith">🔨 Кузница</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=wall">🧱 Стена</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=hide">🏴 Тайник</a>
    <?php if ((int)($village['main'] ?? 0) >= 10): ?>
    <a href="?page=settlers&village_id=<?= $village['id'] ?>">🏕 Поселенцы</a>
    <?php endif; ?>
    <a href="?page=market&tab=internal">💰 Рынок</a>
    <a href="?page=map">🗺 Карта</a>
</div>

<!-- Основной контент -->
<div class="main-content">

    <!-- Левая: здания -->
    <div>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="village-map-title">
                <span>🏘 <?= htmlspecialchars($village['name']) ?></span>
                <span style="font-size:11px; color:#aaa;">
                    <?= $village['x'] ?>|<?= $village['y'] ?>
                    · K<?= $village['continent'] ?? '?' ?>
                    · <?= number_format($village['points']) ?> оч.
                </span>
            </div>

            <?php
            $buildings = [
                'main'        => ['icon'=>'🏛',  'name'=>'Гл. з��ание'],
                'wood_level'  => ['icon'=>'🌲',  'name'=>'Лесопилка'],
                'stone_level' => ['icon'=>'⛏',  'name'=>'Каменоломня'],
                'iron_level'  => ['icon'=>'🔩',  'name'=>'Шахта'],
                'farm'        => ['icon'=>'🌾',  'name'=>'Ферма'],
                'storage'     => ['icon'=>'🏚',  'name'=>'Склад'],
                'barracks'    => ['icon'=>'⚔️',  'name'=>'Казармы'],
                'stable'      => ['icon'=>'🐎',  'name'=>'Конюшня'],
                'smith'       => ['icon'=>'🔨',  'name'=>'Кузница'],
                'garage'      => ['icon'=>'⚙️',  'name'=>'Мастерская'],
                'wall'        => ['icon'=>'🧱',  'name'=>'Стена'],
                'hide'        => ['icon'=>'🏴',  'name'=>'Тайник'],
            ];
            ?>
            <div class="buildings-grid">
                <?php foreach ($buildings as $key => $info):
                    $level      = (int)($village[$key] ?? 0);
                    $max_levels = ['wall'=>20,'hide'=>10];
                    $max_level  = $max_levels[$key] ?? 30;
                    $class      = 'building-cell';
                    if ($level > 0)           $class .= ' built';
                    if ($level >= $max_level) $class .= ' max-level';

                    $next       = $level + 1;
                    $wood_cost  = $next * 120;
                    $stone_cost = $next * 100;
                    $iron_cost  = $next * 80;
                    $can_afford = (
                        ($village['r_wood']  ?? 0) >= $wood_cost &&
                        ($village['r_stone'] ?? 0) >= $stone_cost &&
                        ($village['r_iron']  ?? 0) >= $iron_cost
                    );
                    $is_building = (
                        !empty($village['build_queue']) &&
                        $village['build_queue'] === $key &&
                        $village['queue_end_time'] > time()
                    );
                ?>
                <a href="?page=village&id=<?= $village['id'] ?>&build=<?= $key ?>"
                   class="<?= $class ?>"
                   title="<?= $info['name'] ?> (Ур.<?= $level ?>)
Стоимость: 🪵<?= $wood_cost ?> 🪨<?= $stone_cost ?> ⛏<?= $iron_cost ?>">
                    <div class="building-icon"><?= $info['icon'] ?></div>
                    <div class="building-name"><?= $info['name'] ?></div>
                    <div class="building-level <?= $level == 0 ? 'zero' : '' ?>">
                        <?= $level > 0 ? $level : '—' ?>
                    </div>
                    <?php if ($is_building): ?>
                        <div style="font-size:8px; color:#0f0;">🔨 строится</div>
                    <?php elseif ($level >= $max_level): ?>
                        <div style="font-size:8px; color:#d4a843;">МАКС</div>
                    <?php elseif ($can_afford): ?>
                        <div style="font-size:8px; color:#4f4;">↑</div>
                    <?php else: ?>
                        <div style="font-size:8px; color:#a44;">
                            🪵<?= $wood_cost ?>
                        </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Правая: сайдбар -->
    <div>

        <!-- Очередь строительства -->
        <div class="card">
            <div class="card-header">🔨 Строительство</div>
            <div class="card-body">
                <?php if (!empty($village['build_queue']) && $village['queue_end_time'] > time()):
                    $remaining = $village['queue_end_time'] - time();
                    $bnames    = [
                        'main'=>'Гл. здание','wood_level'=>'Лесопилка',
                        'stone_level'=>'Каменоломня','iron_level'=>'Шахта',
                        'farm'=>'Ферма','storage'=>'Склад','barracks'=>'Казармы',
                        'stable'=>'Конюшня','smith'=>'Кузница',
                        'garage'=>'Мастерская','wall'=>'Стена','hide'=>'Тайник'
                    ];
                    $bname = $bnames[$village['build_queue']] ?? $village['build_queue'];
                ?>
                    <div class="build-queue">
                        <div class="queue-building">🔨 <?= $bname ?></div>
                        <div class="queue-timer" id="buildTimer">
                            <?= gmdate('H:i:s', $remaining) ?>
                        </div>
                        <div class="queue-bar">
                            <div class="queue-bar-fill" style="width:60%;"></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="color:#666; text-align:center; font-size:12px;">
                        Очередь пуста
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Тренировка -->
        <?php if (!empty($village['train_queue']) && $village['train_end_time'] > time()):
            $train_remaining = $village['train_end_time'] - time();
            list($train_type, $train_amount) = explode(':', $village['train_queue'] . ':1');
            $tnames = [
                'spear'=>'Копейщики','sword'=>'Мечники','axe'=>'Топорщики',
                'scout'=>'Разведчики','light'=>'Лёгкая кав.','heavy'=>'Тяжёлая кав.',
                'ram'=>'Тараны','catapult'=>'Катапульты'
            ];
        ?>
        <div class="card">
            <div class="card-header">⚔ Тренировка</div>
            <div class="card-body">
                <div class="build-queue" style="border-color:#44a;">
                    <div class="queue-building">
                        ⚔ <?= $tnames[$train_type] ?? $train_type ?> × <?= $train_amount ?>
                    </div>
                    <div class="queue-timer" id="trainTimer" style="color:#88f;">
                        <?= gmdate('H:i:s', $train_remaining) ?>
                    </div>
                    <div class="queue-bar">
                        <div class="queue-bar-fill"
                             style="background:linear-gradient(90deg,#44a,#88f);">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Войска -->
        <div class="card">
            <div class="card-header">⚔ Войска</div>
            <div class="card-body">
                <?php
                $stmt = $db->prepare("SELECT * FROM unit_place WHERE villages_to_id = ?");
                $stmt->execute([$village['id']]);
                $units    = $stmt->fetch() ?: [];
                $unit_info = [
                    'spear'    => ['name'=>'Копейщик',    'icon'=>'🔱'],
                    'sword'    => ['name'=>'Мечник',       'icon'=>'⚔️'],
                    'axe'      => ['name'=>'Топорщик',     'icon'=>'🪓'],
                    'scout'    => ['name'=>'Разведчик',    'icon'=>'🔍'],
                    'light'    => ['name'=>'Лёгкая кав.', 'icon'=>'🐎'],
                    'heavy'    => ['name'=>'Тяжёлая кав.','icon'=>'🦄'],
                    'ram'      => ['name'=>'Таран',        'icon'=>'🪵'],
                    'catapult' => ['name'=>'Катапульта',   'icon'=>'💣'],
                ];
                $has_units = false;
                ?>
                <table class="troops-table">
                    <?php foreach ($unit_info as $type => $info):
                        $count = (int)($units[$type] ?? 0);
                        if ($count <= 0) continue;
                        $has_units = true;
                    ?>
                    <tr>
                        <td><span class="unit-icon"><?= $info['icon'] ?></span></td>
                        <td><?= $info['name'] ?></td>
                        <td class="unit-count"><?= number_format($count) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$has_units): ?>
                    <tr>
                        <td colspan="3" style="color:#666; text-align:center; padding:8px;">
                            Войск нет
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php if ($village['barracks'] > 0): ?>
                <div style="text-align:center; margin-top:6px;">
                    <a href="?page=village&id=<?= $village['id'] ?>&screen=barracks"
                       style="color:#d4a843; font-size:11px;">
                        ⚔ Тренировать →
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Поддержка в деревне -->
        <?php
        try {
            require_once __DIR__ . '/../controllers/SupportController.php';
            $supportCtrl         = new SupportController($db);
            $supports_in_village = $supportCtrl->inVillage($village['id']);
        } catch (Exception $e) {
            $supports_in_village = [];
        }

        $unit_icons_sup = [
            'spear'=>'🔱','sword'=>'⚔️','axe'=>'🪓',
            'scout'=>'🔍','light'=>'🐎','heavy'=>'🦄',
            'ram'=>'🪵','catapult'=>'💣'
        ];

        if (!empty($supports_in_village)):
        ?>
        <div class="card">
            <div class="card-header">
                🛡 Поддержка
                <span style="font-size:11px; color:#888;">
                    <?= count($supports_in_village) ?> отряд(ов)
                </span>
            </div>
            <div class="card-body">
                <?php foreach ($supports_in_village as $sup):
                    $sup_troops = json_decode($sup['troops'], true);
                    if (array_sum($sup_troops) <= 0) continue;
                ?>
                <div class="support-item">
                    <div style="color:#4f4; font-weight:bold; font-size:12px; margin-bottom:3px;">
                        🤝 <?= htmlspecialchars($sup['from_user'] ?? '?') ?>
                    </div>
                    <div>
                        <?php foreach ($sup_troops as $type => $count):
                            if ($count <= 0) continue;
                        ?>
                        <span class="troop-chip-mini">
                            <?= $unit_icons_sup[$type] ?? '' ?>
                            <span style="color:#d4a843;"><?= $count ?></span>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="text-align:center; margin-top:6px;">
                    <a href="?page=support"
                       style="color:#888; font-size:11px; text-decoration:none;">
                        Управление →
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Движение войск -->
        <?php
        $stmt = $db->prepare("
            SELECT tm.*
            FROM troop_movements tm
            WHERE (
                (tm.attacker_id = ? AND tm.from_village_id = ?)
                OR tm.to_village_id = ?
            )
            AND tm.status = 'moving'
            ORDER BY tm.arrival_time ASC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id'], $village['id'], $village['id']]);
        $movements = $stmt->fetchAll();

        if (!empty($movements)):
        $type_labels = [
            'attack'  => ['⚔ Атака',     'attack'],
            'return'  => ['🔙 Возврат',   'return'],
            'support' => ['🛡 Поддержка', 'support'],
            'scout'   => ['🔍 Разведка',  'scout'],
        ];
        ?>
        <div class="card">
            <div class="card-header">
                🚶 Движение
                <span style="font-size:11px; color:#888;"><?= count($movements) ?></span>
            </div>
            <div class="card-body">
                <?php foreach ($movements as $mov):
                    $remaining = max(0, $mov['arrival_time'] - time());
                    $mins = floor($remaining / 60);
                    $secs = $remaining % 60;
                    $lbl  = $type_labels[$mov['type']] ?? ['?', ''];
                ?>
                <div class="movement-item <?= $lbl[1] ?>">
                    <?= $lbl[0] ?> → #<?= $mov['to_village_id'] ?><br>
                    <span style="color:#0f0; font-weight:bold;">
                        ⏱ <?= $mins ?>м <?= str_pad($secs,2,'0',STR_PAD_LEFT) ?>с
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Информация -->
        <div class="card">
            <div class="card-header">📊 Информация</div>
            <div class="card-body">
                <table class="village-info" style="width:100%;">
                    <tr><td>Координаты</td><td><?= $village['x'] ?>|<?= $village['y'] ?></td></tr>
                    <tr><td>Континент</td><td>K<?= $village['continent'] ?? '?' ?></td></tr>
                    <tr><td>Очки</td><td><?= number_format($village['points']) ?></td></tr>
                    <tr><td>Склад</td><td><?= number_format($max_storage) ?></td></tr>
                    <tr><td>Стена</td><td>Ур. <?= $village['wall'] ?? 0 ?></td></tr>
                    <tr>
                        <td>Население</td>
                        <td>
                            <?= $village['population'] ?? 0 ?>
                            / <?= $resourceManager->getMaxPopulation($village) ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Производство</td>
                        <td style="font-size:11px;">
                            🪵<?= number_format($prod['wood']) ?>
                            🪨<?= number_format($prod['stone']) ?>
                            ⛏<?= number_format($prod['iron']) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
let buildEnd = <?= !empty($village['queue_end_time']) ? $village['queue_end_time'] : 0 ?>;
let trainEnd = <?= !empty($village['train_end_time']) ? $village['train_end_time'] : 0 ?>;

function formatTime(sec) {
    sec = Math.max(0, sec);
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

function updateTimers() {
    const now = Math.floor(Date.now() / 1000);
    const bEl = document.getElementById('buildTimer');
    if (bEl && buildEnd > 0) {
        bEl.textContent = formatTime(buildEnd - now);
        if (buildEnd - now <= 0) location.reload();
    }
    const tEl = document.getElementById('trainTimer');
    if (tEl && trainEnd > 0) {
        tEl.textContent = formatTime(trainEnd - now);
        if (trainEnd - now <= 0) location.reload();
    }
}
setInterval(updateTimers, 1000);

// Плавные ресурсы
const prodPerSec = {
    wood:  <?= round($prod['wood']  / 3600, 6) ?>,
    stone: <?= round($prod['stone'] / 3600, 6) ?>,
    iron:  <?= round($prod['iron']  / 3600, 6) ?>
};
let resources = {
    wood:  <?= (int)$village['r_wood'] ?>,
    stone: <?= (int)$village['r_stone'] ?>,
    iron:  <?= (int)$village['r_iron'] ?>
};
const maxStorage = <?= $max_storage ?>;

setInterval(() => {
    resources.wood  = Math.min(maxStorage, resources.wood  + prodPerSec.wood);
    resources.stone = Math.min(maxStorage, resources.stone + prodPerSec.stone);
    resources.iron  = Math.min(maxStorage, resources.iron  + prodPerSec.iron);

    const wEl = document.getElementById('res_wood');
    const sEl = document.getElementById('res_stone');
    const iEl = document.getElementById('res_iron');
    if (wEl) wEl.textContent = Math.floor(resources.wood).toLocaleString('ru');
    if (sEl) sEl.textContent = Math.floor(resources.stone).toLocaleString('ru');
    if (iEl) iEl.textContent = Math.floor(resources.iron).toLocaleString('ru');
}, 1000);
</script>

</body>
</html>