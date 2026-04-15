<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои деревни — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1200px; margin:20px auto; padding:0 15px; }

        .page-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:20px;
        }
        .page-title { font-size:24px; font-weight:bold; color:#d4a843; }

        /* Сводка */
        .summary-bar {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(160px,1fr));
            gap:12px; margin-bottom:25px;
        }
        .summary-item {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; padding:15px; text-align:center;
        }
        .summary-icon { font-size:28px; margin-bottom:6px; }
        .summary-value { font-size:22px; font-weight:bold; color:#d4a843; }
        .summary-label { font-size:11px; color:#888; margin-top:3px; }

        /* Сетка деревень */
        .villages-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(340px,1fr));
            gap:18px;
        }

        .village-card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:10px; overflow:hidden; transition:0.2s;
        }
        .village-card:hover {
            border-color:#d4a843;
            transform:translateY(-2px);
            box-shadow:0 8px 25px rgba(0,0,0,0.5);
        }

        .vc-header {
            background:#3a2c10; padding:12px 15px;
            display:flex; justify-content:space-between; align-items:center;
            border-bottom:1px solid #5a4a20;
        }
        .vc-name {
            font-weight:bold; color:#d4a843; font-size:16px;
        }
        .vc-coords { font-size:12px; color:#888; margin-top:2px; }
        .vc-points {
            font-size:13px; color:#d4a843; font-weight:bold;
            background:#1a1a0a; padding:4px 10px;
            border-radius:12px; border:1px solid #5a4a20;
        }

        .vc-body { padding:15px; }

        /* Прогресс баров ресурсов */
        .res-rows { margin-bottom:12px; }
        .res-row {
            display:flex; align-items:center; gap:8px;
            margin-bottom:6px; font-size:12px;
        }
        .res-row-icon { width:20px; text-align:center; font-size:16px; }
        .res-row-bar {
            flex:1; height:8px; background:#333;
            border-radius:4px; overflow:hidden;
        }
        .res-row-fill {
            height:100%; border-radius:4px; transition:0.3s;
        }
        .res-row-val { width:70px; text-align:right; color:#d4a843; font-weight:bold; }
        .res-row-rate { width:60px; text-align:right; color:#666; font-size:10px; }

        /* Здания мини */
        .buildings-mini {
            display:flex; flex-wrap:wrap; gap:5px;
            margin-bottom:12px;
        }
        .bld-tag {
            background:#1a1a0a; border:1px solid #444;
            border-radius:4px; padding:3px 8px;
            font-size:10px; color:#aaa;
            display:flex; align-items:center; gap:3px;
        }
        .bld-tag span { color:#d4a843; font-weight:bold; }

        /* Активные процессы */
        .active-processes { margin-bottom:12px; }
        .process-item {
            display:flex; justify-content:space-between;
            align-items:center; padding:6px 10px;
            border-radius:4px; font-size:12px; margin-bottom:4px;
        }
        .process-build {
            background:#1a2a1a; border:1px solid #4a6a4a; color:#4f4;
        }
        .process-train {
            background:#1a1a2a; border:1px solid #4a4a6a; color:#88f;
        }

        /* Население */
        .pop-bar-mini {
            display:flex; align-items:center; gap:8px;
            margin-bottom:12px; font-size:12px;
        }
        .pop-bar-mini-bar {
            flex:1; height:6px; background:#333;
            border-radius:3px; overflow:hidden;
        }
        .pop-bar-mini-fill {
            height:100%; border-radius:3px;
        }

        /* Кнопки */
        .vc-actions {
            display:grid; grid-template-columns:1fr 1fr;
            gap:8px;
        }
        .vc-btn {
            padding:8px; background:#3a2c10;
            border:1px solid #8b6914; border-radius:5px;
            color:#d4a843; text-decoration:none;
            text-align:center; font-size:12px; transition:0.2s;
        }
        .vc-btn:hover { background:#5a4a20; }
        .vc-btn-blue { background:#1a1a3a; border-color:#1a1a8b; color:#aaf; }
        .vc-btn-blue:hover { background:#2a2a5a; }
        .vc-btn-green { background:#1a3a1a; border-color:#1a8b1a; color:#4f4; }
        .vc-btn-green:hover { background:#2a5a2a; }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }

        @media(max-width:600px) {
            .villages-grid { grid-template-columns:1fr; }
            .summary-bar { grid-template-columns:repeat(2,1fr); }
        }
    </style>
</head>
<body>

<?php
require_once __DIR__ . '/navbar.php';
$resourceManager = new ResourceManager($db);

// Общая статистика
$total_points = 0;
$total_pop    = 0;
$total_max_pop = 0;
$total_wood   = 0;
$total_stone  = 0;
$total_iron   = 0;

foreach ($villages as $v) {
    $total_points  += (int)($v['points'] ?? 0);
    $total_pop     += (int)($v['population'] ?? 0);
    $total_max_pop += $resourceManager->getMaxPopulation($v);
    $total_wood    += (int)($v['r_wood'] ?? 0);
    $total_stone   += (int)($v['r_stone'] ?? 0);
    $total_iron    += (int)($v['r_iron'] ?? 0);
}
?>

<div class="container">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div class="page-title">🏘 Мои деревни</div>
        <span style="color:#888; font-size:13px;">
            Всего: <?= count($villages) ?>
        </span>
    </div>

    <!-- Сводная статистика -->
    <div class="summary-bar">
        <div class="summary-item">
            <div class="summary-icon">🏘</div>
            <div class="summary-value"><?= count($villages) ?></div>
            <div class="summary-label">Деревень</div>
        </div>
        <div class="summary-item">
            <div class="summary-icon">⭐</div>
            <div class="summary-value"><?= number_format($total_points) ?></div>
            <div class="summary-label">Очков всего</div>
        </div>
        <div class="summary-item">
            <div class="summary-icon">🪵</div>
            <div class="summary-value"><?= number_format($total_wood) ?></div>
            <div class="summary-label">Дерево (сумма)</div>
        </div>
        <div class="summary-item">
            <div class="summary-icon">🪨</div>
            <div class="summary-value"><?= number_format($total_stone) ?></div>
            <div class="summary-label">Камень (сумма)</div>
        </div>
        <div class="summary-item">
            <div class="summary-icon">⛏</div>
            <div class="summary-value"><?= number_format($total_iron) ?></div>
            <div class="summary-label">Железо (сумма)</div>
        </div>
        <div class="summary-item">
            <div class="summary-icon">👥</div>
            <div class="summary-value">
                <?= number_format($total_pop) ?>/<?= number_format($total_max_pop) ?>
            </div>
            <div class="summary-label">Население</div>
        </div>
    </div>

    <!-- Карточки деревень -->
    <?php if (empty($villages)): ?>
        <div style="text-align:center; padding:60px; color:#666;">
            <div style="font-size:48px; margin-bottom:10px;">🏚</div>
            У вас нет деревень
        </div>
    <?php else: ?>
    <div class="villages-grid">
        <?php foreach ($villages as $v):
            $resourceManager->updateResources($v['id']);

            // Обновлённые данные
            $stmt = $db->prepare("SELECT * FROM villages WHERE id = ?");
            $stmt->execute([$v['id']]);
            $v = $stmt->fetch();

            $prod      = $resourceManager->getProductionPerHour($v);
            $max_s     = $resourceManager->getMaxStorage($v);
            $max_p     = $resourceManager->getMaxPopulation($v);
            $curr_pop  = (int)($v['population'] ?? 0);
            $pop_pct   = $max_p > 0 ? min(100, round(($curr_pop / $max_p) * 100)) : 0;

            if ($pop_pct < 60)     $pop_color = '#4f4';
            elseif ($pop_pct < 85) $pop_color = '#fa4';
            else                   $pop_color = '#f44';

            $wood_pct  = $max_s > 0 ? min(100, round(($v['r_wood']  / $max_s) * 100)) : 0;
            $stone_pct = $max_s > 0 ? min(100, round(($v['r_stone'] / $max_s) * 100)) : 0;
            $iron_pct  = $max_s > 0 ? min(100, round(($v['r_iron']  / $max_s) * 100)) : 0;

            $is_building = !empty($v['build_queue']) && $v['queue_end_time'] > time();
            $is_training = !empty($v['train_queue']) && $v['train_end_time'] > time();

            $building_names = [
                'main'=>'Гл.зд','wood_level'=>'Лесопилка',
                'stone_level'=>'Каменоломня','iron_level'=>'Шахта',
                'farm'=>'Ферма','storage'=>'Склад','barracks'=>'Казармы',
                'stable'=>'Конюшня','smith'=>'Кузница',
                'wall'=>'Стена','hide'=>'Тайник'
            ];
            $unit_names = [
                'spear'=>'Копейщики','sword'=>'Мечники','axe'=>'Топорщики',
                'scout'=>'Разведчики','light'=>'Лёгкая кав.','heavy'=>'Тяжёлая кав.'
            ];
            $show_blds = [
                'main'=>'🏛','barracks'=>'⚔️','stable'=>'🐎',
                'wall'=>'🧱','farm'=>'🌾','storage'=>'🏚'
            ];
        ?>
        <div class="village-card">
            <div class="vc-header">
                <div>
                    <div class="vc-name">
                        <?= htmlspecialchars($v['name']) ?>
                    </div>
                    <div class="vc-coords">
                        📍 <?= $v['x'] ?>|<?= $v['y'] ?>
                        · K<?= $v['continent'] ?? '?' ?>
                    </div>
                </div>
                <div class="vc-points">
                    ⭐ <?= number_format($v['points']) ?>
                </div>
            </div>

            <div class="vc-body">

                <!-- Активные процессы -->
                <?php if ($is_building || $is_training): ?>
                <div class="active-processes">
                    <?php if ($is_building): ?>
                    <div class="process-item process-build">
                        <span>
                            🔨 <?= $building_names[$v['build_queue']] ?? $v['build_queue'] ?>
                        </span>
                        <span class="countdown" data-end="<?= $v['queue_end_time'] ?>">
                            <?= gmdate('H:i:s', max(0, $v['queue_end_time'] - time())) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($is_training):
                        list($ttype) = explode(':', $v['train_queue'] . ':');
                    ?>
                    <div class="process-item process-train">
                        <span>
                            ⚔ <?= $unit_names[$ttype] ?? $ttype ?>
                        </span>
                        <span class="countdown" data-end="<?= $v['train_end_time'] ?>">
                            <?= gmdate('H:i:s', max(0, $v['train_end_time'] - time())) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Ресурсы -->
                <div class="res-rows">
                    <div class="res-row">
                        <span class="res-row-icon">🪵</span>
                        <div class="res-row-bar">
                            <div class="res-row-fill"
                                 style="width:<?= $wood_pct ?>%;
                                        background:linear-gradient(90deg,#3a5a1a,#6a8a2a);">
                            </div>
                        </div>
                        <span class="res-row-val"><?= number_format($v['r_wood']) ?></span>
                        <span class="res-row-rate">+<?= number_format($prod['wood']) ?>/ч</span>
                    </div>
                    <div class="res-row">
                        <span class="res-row-icon">🪨</span>
                        <div class="res-row-bar">
                            <div class="res-row-fill"
                                 style="width:<?= $stone_pct ?>%;
                                        background:linear-gradient(90deg,#3a3a5a,#6a6a8a);">
                            </div>
                        </div>
                        <span class="res-row-val"><?= number_format($v['r_stone']) ?></span>
                        <span class="res-row-rate">+<?= number_format($prod['stone']) ?>/ч</span>
                    </div>
                    <div class="res-row">
                        <span class="res-row-icon">⛏</span>
                        <div class="res-row-bar">
                            <div class="res-row-fill"
                                 style="width:<?= $iron_pct ?>%;
                                        background:linear-gradient(90deg,#4a4a3a,#8a8a5a);">
                            </div>
                        </div>
                        <span class="res-row-val"><?= number_format($v['r_iron']) ?></span>
                        <span class="res-row-rate">+<?= number_format($prod['iron']) ?>/ч</span>
                    </div>
                </div>

                <!-- Население -->
                <div class="pop-bar-mini">
                    <span style="font-size:14px;">👥</span>
                    <div class="pop-bar-mini-bar">
                        <div class="pop-bar-mini-fill"
                             style="width:<?= $pop_pct ?>%;
                                    background:<?= $pop_color ?>;">
                        </div>
                    </div>
                    <span style="font-size:11px; color:<?= $pop_color ?>;">
                        <?= $curr_pop ?>/<?= $max_p ?>
                    </span>
                </div>

                <!-- Здания -->
                <div class="buildings-mini">
                    <?php foreach ($show_blds as $key => $icon):
                        $lvl = (int)($v[$key] ?? 0);
                        if ($lvl <= 0) continue;
                    ?>
                    <div class="bld-tag">
                        <?= $icon ?>
                        <?= $building_names[$key] ?? $key ?>
                        <span><?= $lvl ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Кнопки -->
                <div class="vc-actions">
                    <a href="?page=village&id=<?= $v['id'] ?>"
                       class="vc-btn">
                        🏘 Управлять
                    </a>
                    <a href="?page=map&x=<?= $v['x'] ?>&y=<?= $v['y'] ?>"
                       class="vc-btn vc-btn-blue">
                        🗺 На карте
                    </a>
                    <a href="?page=village&id=<?= $v['id'] ?>&screen=barracks"
                       class="vc-btn vc-btn-green">
                        ⚔ Казармы
                    </a>
                    <?php if ((int)($v['main'] ?? 0) >= 10): ?>
                    <a href="?page=settlers&village_id=<?= $v['id'] ?>"
                       class="vc-btn" style="grid-column:span 1;">
                        🏕 Поселенцы
                    </a>
                    <?php else: ?>
                    <a href="?page=village&id=<?= $v['id'] ?>&screen=storage"
                       class="vc-btn">
                        🏚 Склад
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script>
function updateCountdowns() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('.countdown[data-end]').forEach(el => {
        const end = parseInt(el.dataset.end);
        const rem = Math.max(0, end - now);
        const h   = Math.floor(rem / 3600);
        const m   = Math.floor((rem % 3600) / 60);
        const s   = rem % 60;
        el.textContent =
            `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    });
}
setInterval(updateCountdowns, 1000);
updateCountdowns();
</script>

</body>
</html>