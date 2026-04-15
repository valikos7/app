<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главное здание — <?= APP_NAME ?></title>
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

        .building-hero {
            display:flex; gap:25px; align-items:center;
            margin-bottom:25px; padding-bottom:20px;
            border-bottom:1px solid #444;
        }
        .building-icon-big { font-size:80px; }
        .building-desc { color:#aaa; font-size:14px; line-height:1.6; }
        .building-level-badge {
            display:inline-block; background:#3a2c10;
            border:2px solid #d4a843; border-radius:8px;
            padding:5px 15px; color:#d4a843;
            font-size:18px; font-weight:bold; margin-bottom:10px;
        }

        /* Таблица уровней */
        .levels-table { width:100%; border-collapse:collapse; }
        .levels-table th {
            background:#3a2c10; color:#d4a843;
            padding:10px 12px; text-align:left; font-size:13px;
        }
        .levels-table td {
            padding:10px 12px; border-bottom:1px solid #333; font-size:13px;
        }
        .levels-table tr.current td { background:#2a3a10; }
        .levels-table tr.next-level td { background:#1a2a1a; }
        .levels-table tr:hover td { background:#2a2010; }

        .btn-upgrade {
            display:inline-block; padding:12px 30px;
            background:#5a8a1a; color:#fff; border:none;
            border-radius:6px; font-size:15px; cursor:pointer;
            text-decoration:none; transition:0.2s;
        }
        .btn-upgrade:hover { background:#7aaa2a; }
        .btn-upgrade.disabled {
            background:#444; cursor:not-allowed; color:#888;
        }

        .cost-row {
            display:flex; gap:20px; flex-wrap:wrap;
            margin:15px 0; padding:15px;
            background:#1a1a0a; border-radius:6px;
            border:1px solid #444;
        }
        .cost-item { display:flex; align-items:center; gap:8px; font-size:14px; }
        .cost-item.ok { color:#4f4; }
        .cost-item.nok { color:#f44; }

        .bonus-info {
            background:#1a2a1a; border:1px solid #4a6a4a;
            border-radius:6px; padding:15px; margin:15px 0;
        }
        .bonus-info h4 { color:#4f4; margin-bottom:10px; }
        .bonus-row { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; }
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

<!-- Ресурсы -->
<?php
$db = $db ?? null;
$resourceManager = new ResourceManager($db ?? $GLOBALS['db']);
$prod = $resourceManager->getProductionPerHour($village);
$max_storage = $resourceManager->getMaxStorage($village);
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
    <a href="?page=village&id=<?= $village['id'] ?>&screen=main" class="active">🏛 Гл. здание</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=barracks">⚔ Казармы</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=stable">🐎 Конюшня</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=smith">🔨 Кузница</a>
    <a href="?page=village&id=<?= $village['id'] ?>&screen=wall">🧱 Стена</a>
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
        <div class="card-header">🏛 Главное здание — Уровень <?= $village['main'] ?></div>
        <div class="card-body">

            <div class="building-hero">
                <div class="building-icon-big">🏛</div>
                <div>
                    <div class="building-level-badge">Уровень <?= $village['main'] ?> / 30</div>
                    <div class="building-desc">
                        Главное здание — центр вашей деревни.<br>
                        Чем выше уровень, тем быстрее строятся остальные здания.<br>
                        Текущий бонус скорости строительства:
                        <strong style="color:#4f4;">
                            <?= min(50, $village['main'] * 5) ?>%
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Бонусы здания -->
            <div class="bonus-info">
                <h4>✅ Текущие бонусы (Уровень <?= $village['main'] ?>)</h4>
                <div class="bonus-row">
                    <span>Скорость строительства</span>
                    <span style="color:#4f4;">-<?= min(50, $village['main'] * 5) ?>%</span>
                </div>
                <div class="bonus-row">
                    <span>Доступные здания</span>
                    <span style="color:#4f4;">
                        <?php
                        $unlocked = ['Лесопилка', 'Каменоломня', 'Шахта', 'Ферма', 'Склад'];
                        if ($village['main'] >= 3) $unlocked[] = 'Казармы';
                        if ($village['main'] >= 5) $unlocked[] = 'Конюшня';
                        if ($village['main'] >= 5) $unlocked[] = 'Кузница';
                        if ($village['main'] >= 10) $unlocked[] = 'Мастерская';
                        echo implode(', ', $unlocked);
                        ?>
                    </span>
                </div>
            </div>

            <!-- Стоимость улучшения -->
            <?php
            $current = (int)$village['main'];
            $next = $current + 1;
            $wood_cost  = $next * 120;
            $stone_cost = $next * 100;
            $iron_cost  = $next * 80;

            $can_wood  = $village['r_wood']  >= $wood_cost;
            $can_stone = $village['r_stone'] >= $stone_cost;
            $can_iron  = $village['r_iron']  >= $iron_cost;
            $can_build = $can_wood && $can_stone && $can_iron && $current < 30;
            $is_building = !empty($village['build_queue']) && $village['queue_end_time'] > time();
            ?>

            <?php if ($current < 30): ?>
            <h3 style="color:#d4a843; margin-bottom:10px;">
                📦 Улучшение до уровня <?= $next ?>
            </h3>

            <div class="cost-row">
                <div class="cost-item <?= $can_wood ? 'ok' : 'nok' ?>">
                    🪵 <?= number_format($wood_cost) ?>
                    <?= $can_wood ? '✓' : '✗' ?>
                </div>
                <div class="cost-item <?= $can_stone ? 'ok' : 'nok' ?>">
                    🪨 <?= number_format($stone_cost) ?>
                    <?= $can_stone ? '✓' : '✗' ?>
                </div>
                <div class="cost-item <?= $can_iron ? 'ok' : 'nok' ?>">
                    ⛏ <?= number_format($iron_cost) ?>
                    <?= $can_iron ? '✓' : '✗' ?>
                </div>
                <div class="cost-item" style="color:#aaa;">
                    ⏱ <?= ($next * 60 + 30) ?>с
                </div>
            </div>

            <?php if ($is_building): ?>
                <div style="background:#1a2a1a;border:1px solid #4a4;border-radius:6px;
                            padding:15px;text-align:center;">
                    🔨 Идёт строительство...
                    <strong style="color:#0f0;" id="buildTimer">
                        <?= gmdate('H:i:s', max(0, $village['queue_end_time'] - time())) ?>
                    </strong>
                </div>
            <?php elseif ($can_build): ?>
                <a href="?page=village&id=<?= $village['id'] ?>&build=main"
                   class="btn-upgrade">
                    🏛 Улучшить до уровня <?= $next ?>
                </a>
            <?php else: ?>
                <span class="btn-upgrade disabled">
                    Недостаточно ресурсов
                </span>
            <?php endif; ?>
            <?php else: ?>
                <div style="color:#d4a843;text-align:center;font-size:18px;padding:20px;">
                    🏆 Максимальный уровень достигнут!
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Таблица всех уровней -->
    <div class="card">
        <div class="card-header">📊 Таблица уровней</div>
        <div class="card-body" style="padding:0;">
            <table class="levels-table">
                <tr>
                    <th>Уровень</th>
                    <th>🪵 Дерево</th>
                    <th>🪨 Камень</th>
                    <th>⛏ Железо</th>
                    <th>⏱ Время</th>
                    <th>Бонус</th>
                </tr>
                <?php for ($i = 1; $i <= 30; $i++):
                    $w = $i * 120;
                    $s = $i * 100;
                    $ir = $i * 80;
                    $t = $i * 60 + 30;
                    $b = min(50, $i * 5);
                    $cls = '';
                    if ($i == $current) $cls = 'current';
                    elseif ($i == $next && $current < 30) $cls = 'next-level';
                ?>
                <tr class="<?= $cls ?>">
                    <td>
                        <?php if ($i == $current): ?>
                            <strong style="color:#d4a843;">→ <?= $i ?> (текущий)</strong>
                        <?php else: ?>
                            <?= $i ?>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($w) ?></td>
                    <td><?= number_format($s) ?></td>
                    <td><?= number_format($ir) ?></td>
                    <td><?= $t ?>с</td>
                    <td style="color:#4f4;">-<?= $b ?>%</td>
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
    const rem = Math.max(0, buildEnd - Math.floor(Date.now()/1000));
    const h = Math.floor(rem/3600);
    const m = Math.floor((rem%3600)/60);
    const s = rem % 60;
    el.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    if (rem <= 0) location.reload();
}
setInterval(updateTimer, 1000);
</script>

</body>
</html>