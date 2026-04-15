<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дворянин — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1000px; margin:20px auto; padding:0 15px; }

        .page-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:20px;
        }
        .page-title { font-size:24px; font-weight:bold; color:#d4a843; }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:10px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 16px;
            font-weight:bold; color:#d4a843; font-size:15px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .card-body { padding:20px; }

        /* Инфо-блок */
        .nobleman-info {
            background:#2a1a0a; border:2px solid #d4a843;
            border-radius:10px; padding:20px; margin-bottom:20px;
            display:flex; gap:20px; align-items:center; flex-wrap:wrap;
        }
        .nobleman-icon { font-size:64px; flex-shrink:0; }
        .nobleman-desc { flex:1; }
        .nobleman-desc h2 { color:#d4a843; margin-bottom:10px; }
        .nobleman-desc p  { color:#aaa; font-size:13px; line-height:1.7; }

        .mechanic-steps {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
            gap:10px; margin-top:15px;
        }
        .mechanic-step {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:12px; text-align:center;
        }
        .step-num  { font-size:24px; font-weight:bold; color:#d4a843; margin-bottom:5px; }
        .step-text { font-size:12px; color:#888; }

        /* Мои дворяне */
        .village-row {
            display:flex; align-items:center; gap:15px;
            padding:12px 0; border-bottom:1px solid #333; flex-wrap:wrap;
        }
        .village-row:last-child { border-bottom:none; }
        .village-name { flex:1; font-weight:bold; color:#d4a843; }
        .village-coords { color:#888; font-size:12px; }
        .nobleman-count {
            font-size:20px; font-weight:bold; color:#d4a843;
            background:#3a2c10; border:1px solid #8b6914;
            border-radius:6px; padding:5px 14px;
        }
        .nobleman-zero { color:#555; background:#1a1a0a; border-color:#333; }

        /* Цели захвата */
        .target-card {
            background:#1a1a0a; border:2px solid #333;
            border-radius:8px; padding:14px; margin-bottom:10px;
            display:flex; align-items:center; gap:14px; flex-wrap:wrap;
        }
        .target-card:hover { border-color:#8b6914; }

        .loyalty-bar-container { flex:1; min-width:150px; }
        .loyalty-label {
            display:flex; justify-content:space-between;
            font-size:12px; color:#888; margin-bottom:4px;
        }
        .loyalty-bar {
            height:12px; background:#333; border-radius:6px; overflow:hidden;
        }
        .loyalty-bar-fill {
            height:100%; border-radius:6px; transition:0.5s;
        }

        .loyalty-high   { background:linear-gradient(90deg,#2a8a2a,#4f4); }
        .loyalty-medium { background:linear-gradient(90deg,#8a6a1a,#fa4); }
        .loyalty-low    { background:linear-gradient(90deg,#8a2a2a,#f44); }
        .loyalty-critical { background:linear-gradient(90deg,#aa1a1a,#f00); animation:blink 0.8s infinite; }
        @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.5;} }

        /* История */
        .capture-item {
            display:flex; align-items:center; gap:12px;
            padding:10px 0; border-bottom:1px solid #333; font-size:13px;
        }
        .capture-item:last-child { border-bottom:none; }
        .capture-icon { font-size:24px; min-width:30px; text-align:center; }
        .capture-info { flex:1; }
        .capture-village { font-weight:bold; color:#d4a843; }
        .capture-meta    { font-size:11px; color:#666; margin-top:3px; }

        /* Стоимость */
        .cost-box {
            background:#1a1a0a; border:2px solid #8b6914;
            border-radius:8px; padding:15px; text-align:center;
        }
        .cost-icon  { font-size:36px; margin-bottom:8px; }
        .cost-name  { font-size:15px; font-weight:bold; color:#d4a843; margin-bottom:10px; }
        .cost-stats { display:flex; gap:15px; justify-content:center; flex-wrap:wrap; font-size:13px; margin-bottom:10px; }
        .cost-stat  { display:flex; align-items:center; gap:5px; }
        .cost-prices{ display:flex; gap:10px; justify-content:center; flex-wrap:wrap; font-size:12px; color:#d4a843; }

        .btn {
            display:inline-flex; align-items:center; gap:5px;
            padding:7px 16px; border-radius:4px; font-size:12px;
            border:1px solid #8b6914; background:#5a4a1a;
            color:#d4a843; cursor:pointer; transition:0.2s; text-decoration:none;
        }
        .btn:hover { background:#7a6a2a; }
        .btn-red { background:#5a1a1a; border-color:#8a1a1a; color:#f66; }
        .btn-red:hover { background:#7a2a2a; }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        /* Предупреждение */
        .warning-box {
            background:#2a2a0a; border:2px solid #8a8a1a;
            border-radius:8px; padding:15px; margin-bottom:15px;
            font-size:13px; color:#fa4;
        }

        @media(max-width:600px) {
            .nobleman-info { flex-direction:column; text-align:center; }
            .mechanic-steps { grid-template-columns:repeat(2,1fr); }
            .village-row { flex-direction:column; align-items:flex-start; }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div class="page-title">👑 Дворянин — Захват деревень</div>
    </div>

    <!-- Информация о механике -->
    <div class="nobleman-info">
        <div class="nobleman-icon">👑</div>
        <div class="nobleman-desc">
            <h2>Как захватить деревню?</h2>
            <p>
                Дворянин — элитный юнит который снижает лояльность вражеской деревни при победной атаке.
                Когда лояльность достигает 0, деревня переходит под ваш контроль!
            </p>
            <div class="mechanic-steps">
                <div class="mechanic-step">
                    <div class="step-num">1</div>
                    <div class="step-text">Обучите Дворянина в Мастерской (нужно ГЗ ур.20)</div>
                </div>
                <div class="mechanic-step">
                    <div class="step-num">2</div>
                    <div class="step-text">Атакуйте вражескую деревню с Дворянином</div>
                </div>
                <div class="mechanic-step">
                    <div class="step-num">3</div>
                    <div class="step-text">При победе — лояльность снизится на 20-35 пунктов</div>
                </div>
                <div class="mechanic-step">
                    <div class="step-num">4</div>
                    <div class="step-text">Когда лояльность = 0, деревня ваша!</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Предупреждение -->
    <div class="warning-box">
        ⚠ <strong>Важно:</strong> Лояльность восстанавливается на 1 пункт в час!
        Действуйте быстро — проводите атаки одну за другой.
        Для 3-4 атак нужно несколько Дворян.
    </div>

    <!-- Стоимость Дворянина -->
    <?php if ($nobleman_config): ?>
    <div class="card">
        <div class="card-header">👑 Дворянин — характеристики</div>
        <div class="card-body">
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <div class="cost-box" style="flex:1; min-width:200px;">
                    <div class="cost-icon">👑</div>
                    <div class="cost-name"><?= htmlspecialchars($nobleman_config['name']) ?></div>
                    <div class="cost-stats">
                        <div class="cost-stat">⚔ <?= $nobleman_config['attack'] ?></div>
                        <div class="cost-stat">🛡 <?= $nobleman_config['def_inf'] ?></div>
                        <div class="cost-stat">🚶 <?= $nobleman_config['speed'] ?> мин/кл</div>
                        <div class="cost-stat">👥 <?= $nobleman_config['pop'] ?> населения</div>
                        <div class="cost-stat">⏱ <?= round($nobleman_config['train_time']/3600) ?> ч.</div>
                    </div>
                    <div class="cost-prices">
                        <span>🪵<?= number_format($nobleman_config['wood']) ?></span>
                        <span>🪨<?= number_format($nobleman_config['stone']) ?></span>
                        <span>⛏<?= number_format($nobleman_config['iron']) ?></span>
                    </div>
                    <div style="font-size:11px;color:#888;margin-top:8px;">
                        Обучается в Мастерской · Нужно ГЗ ур.20
                    </div>
                </div>
                <div style="flex:1; min-width:200px; font-size:13px; color:#888; line-height:1.8;">
                    <div style="color:#d4a843; font-weight:bold; margin-bottom:8px;">
                        Эффект при атаке:
                    </div>
                    <div>⚔ При победе: -20..35 лояльности</div>
                    <div>⏱ Восстановление: +1/час</div>
                    <div>🏰 Нужно атак: ~3-4 раза</div>
                    <div>👑 Дворянин должен выжить в бою</div>
                    <div style="color:#f44;margin-top:8px;">
                        ⚠ Нельзя захватить деревни варваров!
                    </div>
                    <div style="color:#f44;">
                        ⚠ Нельзя захватить свои деревни!
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Мои дворяне -->
    <div class="card">
        <div class="card-header">
            👑 Мои дворяне
            <span style="font-size:12px;color:#888;">
                В деревнях
            </span>
        </div>
        <div class="card-body">
            <?php
            $total_noblemen = 0;
            foreach ($my_villages as $v) {
                $total_noblemen += (int)($v['nobleman'] ?? 0);
            }
            if ($total_noblemen > 0): ?>
            <div style="font-size:13px;color:#888;margin-bottom:15px;">
                Всего дворян: <strong style="color:#d4a843;"><?= $total_noblemen ?></strong>
            </div>
            <?php endif; ?>

            <?php if (empty($my_villages)): ?>
                <div style="color:#666;text-align:center;padding:20px;">Деревень нет</div>
            <?php else: ?>
            <?php foreach ($my_villages as $v):
                $noblemen = (int)($v['nobleman'] ?? 0);
                $loyalty  = (int)($v['loyalty'] ?? 100);

                // Terrain бонус
                $terrain_icons = ['plain'=>'🌾','forest'=>'🌲','mountain'=>'⛰','river'=>'🌊','hill'=>'🏔'];
                $terrain_icon  = $terrain_icons[$v['terrain']??'plain']??'🌾';
            ?>
            <div class="village-row">
                <div>
                    <div class="village-name">
                        <?= $terrain_icon ?>
                        <?= htmlspecialchars($v['name']) ?>
                    </div>
                    <div class="village-coords">
                        📍 <?= $v['x'] ?>|<?= $v['y'] ?>
                        · <?= ucfirst($v['terrain']??'plain') ?>
                    </div>
                </div>
                <div class="nobleman-count <?= $noblemen===0?'nobleman-zero':'' ?>">
                    👑 <?= $noblemen ?> дворян
                </div>
                <?php if ($noblemen > 0): ?>
                <a href="?page=map&x=<?= $v['x'] ?>&y=<?= $v['y'] ?>" class="btn">
                    Атаковать с карты
                </a>
                <?php else: ?>
                <a href="?page=village&id=<?= $v['id'] ?>&screen=garage" class="btn">
                    + Обучить дворянина
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Деревни с низкой лояльностью -->
    <?php if (!empty($capturable_villages)): ?>
    <div class="card">
        <div class="card-header">
            🎯 Деревни под угрозой захвата
            <span style="font-size:12px;color:#888;">
                Лояльность < 100
            </span>
        </div>
        <div class="card-body">
            <?php foreach ($capturable_villages as $v):
                $loyalty  = (int)($v['loyalty'] ?? 100);
                $loyalty_pct = $loyalty;

                if ($loyalty_pct >= 70)      $loyalty_class = 'loyalty-high';
                elseif ($loyalty_pct >= 40)  $loyalty_class = 'loyalty-medium';
                elseif ($loyalty_pct >= 10)  $loyalty_class = 'loyalty-low';
                else                         $loyalty_class = 'loyalty-critical';

                $terrain_icons = ['plain'=>'🌾','forest'=>'🌲','mountain'=>'⛰','river'=>'🌊','hill'=>'🏔'];
                $terrain_icon  = $terrain_icons[$v['terrain']??'plain']??'🌾';
            ?>
            <div class="target-card">
                <div>
                    <div style="font-size:14px;font-weight:bold;color:#d4a843;">
                        <?= $terrain_icon ?> <?= htmlspecialchars($v['name']) ?>
                    </div>
                    <div style="font-size:12px;color:#888;">
                        👤 <?= htmlspecialchars($v['owner_name']??'?') ?>
                        · 📍 <?= $v['x'] ?>|<?= $v['y'] ?>
                        · ⭐ <?= number_format($v['points']) ?>
                    </div>
                </div>

                <div class="loyalty-bar-container">
                    <div class="loyalty-label">
                        <span>❤ Лояльность</span>
                        <span style="color:<?= $loyalty<=10?'#f44':($loyalty<=40?'#fa4':'#4f4') ?>;">
                            <?= $loyalty ?>/100
                        </span>
                    </div>
                    <div class="loyalty-bar">
                        <div class="loyalty-bar-fill <?= $loyalty_class ?>"
                             style="width:<?= $loyalty_pct ?>%;"></div>
                    </div>
                    <div style="font-size:10px;color:#555;margin-top:3px;">
                        <?php if ($loyalty <= 0): ?>
                            🏆 Готова к захвату!
                        <?php else: ?>
                            Ещё ~<?= ceil($loyalty/30) ?> атак с дворянином
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:5px;">
                    <a href="?page=attack&target=<?= $v['id'] ?>"
                       class="btn" style="background:#5a1a1a;border-color:#8a1a1a;color:#f66;">
                        ⚔ Атаковать
                    </a>
                    <a href="?page=map&x=<?= $v['x'] ?>&y=<?= $v['y'] ?>"
                       class="btn">
                        🗺 Карта
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- История захватов -->
    <?php if (!empty($capture_history)): ?>
    <div class="card">
        <div class="card-header">
            📋 История захватов
            <span style="font-size:12px;color:#888;">
                <?= count($capture_history) ?> записей
            </span>
        </div>
        <div class="card-body">
            <?php foreach ($capture_history as $c):
                $is_capture = ($c['to_user_id'] == $_SESSION['user_id']);
                $time_diff  = time() - $c['captured_at'];
                if ($time_diff < 3600) $ts = floor($time_diff/60)." мин. назад";
                elseif ($time_diff < 86400) $ts = floor($time_diff/3600)." ч. назад";
                else $ts = date('d.m.Y', $c['captured_at']);
            ?>
            <div class="capture-item">
                <div class="capture-icon">
                    <?= $is_capture ? '🏆' : '💀' ?>
                </div>
                <div class="capture-info">
                    <div class="capture-village">
                        <?= $is_capture ? '✅ Захвачена:' : '❌ Потеряна:' ?>
                        <?= htmlspecialchars($c['village_name']??$c['old_name']??'?') ?>
                        <span style="color:#888;font-size:11px;">
                            (<?= $c['x']??'?' ?>|<?= $c['y']??'?' ?>)
                        </span>
                    </div>
                    <div class="capture-meta">
                        <?php if ($is_capture): ?>
                            У игрока: <?= htmlspecialchars($c['from_name']??'?') ?>
                        <?php else: ?>
                            Захватил: <?= htmlspecialchars($c['to_name']??'?') ?>
                        <?php endif; ?>
                        · <?= $ts ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

</body>
</html>