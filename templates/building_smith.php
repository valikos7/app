<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Кузница — <?= APP_NAME ?></title>
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

        .research-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(260px,1fr));
            gap:15px;
        }
        .research-card {
            background:#1a1a0a; border:2px solid #444;
            border-radius:8px; padding:15px; transition:0.2s;
        }
        .research-card:hover { border-color:#8b6914; }
        .research-card.researched { border-color:#4a8a4a; }
        .research-card.max { border-color:#d4a843; }

        .research-header {
            display:flex; align-items:center; gap:12px; margin-bottom:10px;
        }
        .research-icon { font-size:36px; }
        .research-name { font-size:15px; font-weight:bold; color:#d4a843; }
        .research-level { font-size:12px; color:#888; }

        .research-desc { font-size:12px; color:#888; margin-bottom:12px; }

        .research-bonus {
            background:#1a2a1a; border:1px solid #4a6a4a;
            border-radius:4px; padding:8px; margin-bottom:12px;
            font-size:12px;
        }
        .bonus-line { display:flex; justify-content:space-between; }
        .bonus-label { color:#888; }
        .bonus-val { color:#4f4; font-weight:bold; }

        .research-cost {
            display:flex; gap:10px; flex-wrap:wrap;
            margin-bottom:10px; font-size:12px;
        }
        .cost-item.ok { color:#4f4; }
        .cost-item.nok { color:#f44; }

        .btn-research {
            width:100%; padding:8px; background:#3a2c10;
            color:#d4a843; border:1px solid #8b6914;
            border-radius:4px; cursor:pointer; font-size:13px;
            transition:0.2s; text-align:center;
        }
        .btn-research:hover { background:#5a4a20; }
        .btn-research.done { background:#1a3a1a; color:#4f4; border-color:#4a8a4a; cursor:default; }
        .btn-research.disabled { background:#2a2a2a; color:#666; border-color:#444; cursor:not-allowed; }

        .progress-bar {
            height:6px; background:#333; border-radius:3px;
            margin:8px 0; overflow:hidden;
        }
        .progress-fill {
            height:100%;
            background:linear-gradient(90deg, #5a4a1a, #d4a843);
            border-radius:3px; transition:0.3s;
        }

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
    <a href="?page=village&id=<?= $village['id'] ?>&screen=smith" class="active">🔨 Кузница</a>
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
            🔨 Кузница — Уровень <?= $village['smith'] ?>
            <span style="float:right; font-size:12px; color:#aaa;">
                Исследования улучшают характеристики войск
            </span>
        </div>
        <div class="card-body">

            <p style="color:#888; font-size:13px; margin-bottom:20px;">
                В кузнице вы можете исследовать улучшения для ваших войск.
                Каждый уровень исследования увеличивает атаку или защиту на <strong style="color:#d4a843;">5%</strong>.
                Максимальный уровень исследования: <strong style="color:#d4a843;">20</strong>.
            </p>

            <?php
            // Получаем текущие исследования
            $stmt = $db->prepare("SELECT * FROM smith_research WHERE village_id = ?");
            $stmt->execute([$village['id']]);
            $research_raw = $stmt->fetch() ?: [];

            $researches = [
                'spear_att' => [
                    'name'  => 'Копья: Атака',
                    'icon'  => '🔱',
                    'desc'  => 'Улучшает атаку копейщиков',
                    'bonus' => 'Атака +5% за уровень',
                    'wood'  => 100, 'stone' => 50, 'iron' => 150
                ],
                'sword_def' => [
                    'name'  => 'Мечи: Защита',
                    'icon'  => '⚔️',
                    'desc'  => 'Улучшает защиту мечников',
                    'bonus' => 'Защита +5% за уровень',
                    'wood'  => 80, 'stone' => 120, 'iron' => 100
                ],
                'axe_att' => [
                    'name'  => 'Топоры: Атака',
                    'icon'  => '🪓',
                    'desc'  => 'Улучшает атаку топорщиков',
                    'bonus' => 'Атака +5% за уровень',
                    'wood'  => 120, 'stone' => 60, 'iron' => 200
                ],
                'scout_speed' => [
                    'name'  => 'Разведка: Скорость',
                    'icon'  => '🔍',
                    'desc'  => 'Увеличивает скорость разведчиков',
                    'bonus' => 'Скорость +5% за уровень',
                    'wood'  => 90, 'stone' => 90, 'iron' => 90
                ],
                'light_att' => [
                    'name'  => 'Лёгкая кав.: Атака',
                    'icon'  => '🐎',
                    'desc'  => 'Улучшает атаку лёгкой кавалерии',
                    'bonus' => 'Атака +5% за уровень',
                    'wood'  => 200, 'stone' => 100, 'iron' => 300
                ],
                'heavy_def' => [
                    'name'  => 'Тяжёлая кав.: Защита',
                    'icon'  => '🦄',
                    'desc'  => 'Улучшает защиту тяжёлой кавалерии',
                    'bonus' => 'Защита +5% за уровень',
                    'wood'  => 300, 'stone' => 200, 'iron' => 400
                ],
            ];
            ?>

            <div class="research-grid">
                <?php foreach ($researches as $key => $r):
                    $current_level = (int)($research_raw[$key] ?? 0);
                    $next_level = $current_level + 1;
                    $max_level = 20;
                    $is_max = ($current_level >= $max_level);

                    $wood_cost  = $r['wood']  * $next_level;
                    $stone_cost = $r['stone'] * $next_level;
                    $iron_cost  = $r['iron']  * $next_level;

                    $can_w = $village['r_wood']  >= $wood_cost;
                    $can_s = $village['r_stone'] >= $stone_cost;
                    $can_i = $village['r_iron']  >= $iron_cost;
                    $can_afford = $can_w && $can_s && $can_i;

                    $pct = ($current_level / $max_level) * 100;
                    $card_class = $is_max ? 'max' : ($current_level > 0 ? 'researched' : '');
                ?>
                <div class="research-card <?= $card_class ?>">
                    <div class="research-header">
                        <div class="research-icon"><?= $r['icon'] ?></div>
                        <div>
                            <div class="research-name"><?= $r['name'] ?></div>
                            <div class="research-level">
                                Уровень <?= $current_level ?>/<?= $max_level ?>
                            </div>
                        </div>
                    </div>

                    <div class="research-desc"><?= $r['desc'] ?></div>

                    <div class="progress-bar">
                        <div class="progress-fill" style="width:<?= $pct ?>%;"></div>
                    </div>

                    <div class="research-bonus">
                        <div class="bonus-line">
                            <span class="bonus-label">Текущий бонус:</span>
                            <span class="bonus-val">+<?= $current_level * 5 ?>%</span>
                        </div>
                        <?php if (!$is_max): ?>
                        <div class="bonus-line">
                            <span class="bonus-label">После улучшения:</span>
                            <span class="bonus-val">+<?= $next_level * 5 ?>%</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$is_max): ?>
                    <div class="research-cost">
                        <span class="cost-item <?= $can_w ? 'ok':'nok' ?>">
                            🪵<?= number_format($wood_cost) ?>
                        </span>
                        <span class="cost-item <?= $can_s ? 'ok':'nok' ?>">
                            🪨<?= number_format($stone_cost) ?>
                        </span>
                        <span class="cost-item <?= $can_i ? 'ok':'nok' ?>">
                            ⛏<?= number_format($iron_cost) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ($is_max): ?>
                        <div class="btn-research done">✅ Максимальный уровень</div>
                    <?php elseif ($can_afford): ?>
                        <a href="?page=village&id=<?= $village['id'] ?>&screen=smith&research=<?= $key ?>"
                           class="btn-research">
                            🔨 Исследовать (ур. <?= $next_level ?>)
                        </a>
                    <?php else: ?>
                        <div class="btn-research disabled">Недостаточно ресурсов</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

</body>
</html>