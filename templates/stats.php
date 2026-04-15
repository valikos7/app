<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Статистика <?= htmlspecialchars($player['username']) ?> — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1100px; margin:20px auto; padding:0 15px; }

        .page-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:20px;
        }
        .page-title { font-size:24px; font-weight:bold; color:#d4a843; }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .card-body { padding:20px; }

        /* Профиль */
        .profile-hero {
            display:flex; gap:20px; align-items:flex-start;
            flex-wrap:wrap;
        }
        .avatar-big {
            width:90px; height:90px; background:#3a2c10;
            border:3px solid #d4a843; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:42px; flex-shrink:0;
        }
        .profile-info { flex:1; }
        .profile-name { font-size:26px; font-weight:bold; color:#d4a843; }
        .profile-rank { color:#888; font-size:13px; margin-top:5px; }
        .profile-meta {
            display:flex; gap:15px; flex-wrap:wrap;
            margin-top:10px; font-size:13px;
        }
        .profile-meta span { color:#888; }
        .profile-meta strong { color:#d4a843; }

        /* Статистика сетка */
        .stats-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(160px,1fr));
            gap:12px; margin-bottom:20px;
        }
        .stat-box {
            background:#1a1a0a; border:1px solid #444;
            border-radius:8px; padding:15px; text-align:center;
            transition:0.2s;
        }
        .stat-box:hover { border-color:#8b6914; }
        .stat-box-icon { font-size:28px; margin-bottom:6px; }
        .stat-box-value { font-size:22px; font-weight:bold; color:#d4a843; }
        .stat-box-label { font-size:11px; color:#888; margin-top:3px; }
        .stat-box-sub { font-size:10px; color:#666; margin-top:2px; }

        /* Боевая статистика */
        .battle-grid {
            display:grid; grid-template-columns:1fr 1fr;
            gap:15px;
        }
        .battle-section { }
        .battle-title {
            font-size:14px; font-weight:bold;
            margin-bottom:12px; padding-bottom:8px;
            border-bottom:1px solid #444;
        }
        .battle-row {
            display:flex; justify-content:space-between;
            padding:7px 0; border-bottom:1px solid #222;
            font-size:13px;
        }
        .battle-row:last-child { border-bottom:none; }
        .battle-label { color:#888; }
        .battle-val   { font-weight:bold; }
        .val-green { color:#4f4; }
        .val-red   { color:#f44; }
        .val-gold  { color:#d4a843; }
        .val-blue  { color:#88f; }

        /* Прогресс бар */
        .win-rate-bar {
            height:10px; background:#333; border-radius:5px;
            overflow:hidden; margin:8px 0;
        }
        .win-rate-fill {
            height:100%; border-radius:5px;
            background:linear-gradient(90deg, #4f4, #2a8a2a);
            transition:0.5s;
        }

        /* График очков */
        .chart-container {
            position:relative; height:200px;
            background:#1a1a0a; border-radius:6px;
            padding:10px; overflow:hidden;
        }
        .chart-canvas { width:100%; height:100%; }

        /* Достижения */
        .achievements-grid {
            display:flex; flex-wrap:wrap; gap:10px;
        }
        .achievement {
            background:#1a1a0a; border:2px solid #5a4a20;
            border-radius:8px; padding:12px 15px;
            text-align:center; min-width:100px;
            transition:0.2s;
        }
        .achievement:hover { border-color:#d4a843; }
        .achievement.done { border-color:#d4a843; background:#2a2010; }
        .ach-icon { font-size:30px; margin-bottom:5px; }
        .ach-title { font-size:12px; font-weight:bold; color:#d4a843; }
        .ach-desc  { font-size:10px; color:#888; margin-top:3px; }

        /* Таблица деревень */
        table { width:100%; border-collapse:collapse; }
        th { background:#3a2c10; color:#d4a843; padding:10px 12px; text-align:left; font-size:13px; }
        td { padding:9px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#2a2010; }

        .btn {
            display:inline-block; padding:6px 14px; border-radius:4px;
            font-size:12px; text-decoration:none; border:1px solid #8b6914;
            background:#5a4a1a; color:#d4a843; transition:0.2s;
        }
        .btn:hover { background:#7a6a2a; }
        .btn-red { background:#5a1a1a; border-color:#8a1a1a; color:#f66; }
        .btn-red:hover { background:#7a2a2a; }

        @media(max-width:700px) {
            .battle-grid { grid-template-columns:1fr; }
            .stats-grid  { grid-template-columns:repeat(2,1fr); }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <div class="page-header">
        <div class="page-title">
            📊 Статистика игрока
        </div>
        <div style="display:flex; gap:8px;">
            <?php if ($is_own): ?>
            <a href="?page=profile" class="btn">← Профиль</a>
            <?php else: ?>
            <a href="?page=player&id=<?= $player['id'] ?>" class="btn">
                👤 Профиль
            </a>
            <a href="?page=messages&action=compose&to=<?= urlencode($player['username']) ?>"
               class="btn">✉ Написать</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Профиль игрока -->
    <div class="card">
        <div class="card-body">
            <div class="profile-hero">
                <div class="avatar-big">👑</div>
                <div class="profile-info">
                    <div class="profile-name">
                        <?= htmlspecialchars($player['username']) ?>
                        <?php if (!empty($player['is_admin'])): ?>
                            <span style="font-size:14px; color:#d4a843;">[Администратор]</span>
                        <?php endif; ?>
                    </div>
                    <div class="profile-rank">
                        🏆 Место в рейтинге: <strong style="color:#d4a843;">#<?= $rank ?></strong>
                        <?php if ($alliance): ?>
                        &nbsp;·&nbsp;
                        🏰 Альянс:
                        <a href="?page=alliance&id=<?= $alliance['id'] ?>"
                           style="color:#d4a843; text-decoration:none;">
                            [<?= htmlspecialchars($alliance['tag']) ?>]
                            <?= htmlspecialchars($alliance['name']) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="profile-meta">
                        <span>⭐ Очков: <strong><?= number_format($player['points'] ?? 0) ?></strong></span>
                        <span>🏘 Деревень: <strong><?= $player['villages'] ?? 0 ?></strong></span>
                        <span>📅 С: <strong><?= date('d.m.Y', $player['join_date'] ?? time()) ?></strong></span>
                        <span>
                            <?php $is_online = ($player['last_activity'] >= time()-300); ?>
                            <?= $is_online
                                ? '<span style="color:#0f0;">● Онлайн</span>'
                                : '<span style="color:#555;">● ' . date('d.m H:i', $player['last_activity']) . '</span>'
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Общая статистика -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-box-icon">⚔</div>
            <div class="stat-box-value val-red">
                <?= number_format($stats['attacks_sent']) ?>
            </div>
            <div class="stat-box-label">Атак отправлено</div>
            <div class="stat-box-sub">
                <?= $stats['attacks_won'] ?> побед
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">🛡</div>
            <div class="stat-box-value val-green">
                <?= number_format($stats['defenses_total']) ?>
            </div>
            <div class="stat-box-label">Атак отражено</div>
            <div class="stat-box-sub">
                <?= $stats['defenses_won'] ?> успешно
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">🔍</div>
            <div class="stat-box-value val-blue">
                <?= number_format($stats['spies_sent']) ?>
            </div>
            <div class="stat-box-label">Разведок</div>
            <div class="stat-box-sub">
                <?= $stats['spies_success'] ?> успешных
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">💰</div>
            <div class="stat-box-value val-gold">
                <?= number_format($stats['resources_looted']) ?>
            </div>
            <div class="stat-box-label">Ресурсов украдено</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">👥</div>
            <div class="stat-box-value val-green">
                <?= number_format($stats['units_trained']) ?>
            </div>
            <div class="stat-box-label">Юнитов обучено</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">💀</div>
            <div class="stat-box-value val-red">
                <?= number_format($stats['units_lost']) ?>
            </div>
            <div class="stat-box-label">Юнитов потеряно</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">🏗</div>
            <div class="stat-box-value val-gold">
                <?= number_format($stats['buildings_built']) ?>
            </div>
            <div class="stat-box-label">Зданий построено</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">⭐</div>
            <div class="stat-box-value val-gold">
                <?= number_format($player['points']) ?>
            </div>
            <div class="stat-box-label">Очков всего</div>
            <div class="stat-box-sub">#<?= $rank ?> в рейтинге</div>
        </div>
    </div>

    <!-- Боевая статистика -->
    <div class="card">
        <div class="card-header">⚔ Боевая статистика</div>
        <div class="card-body">
            <div class="battle-grid">
                <!-- Атаки -->
                <div class="battle-section">
                    <div class="battle-title" style="color:#f44;">
                        ⚔ Нападения
                    </div>
                    <?php
                    $att_total = $stats['attacks_sent'] ?: 1;
                    $att_rate  = round(($stats['attacks_won'] / $att_total) * 100);
                    ?>
                    <div class="battle-row">
                        <span class="battle-label">Всего атак</span>
                        <span class="battle-val val-gold">
                            <?= $stats['attacks_sent'] ?>
                        </span>
                    </div>
                    <div class="battle-row">
                        <span class="battle-label">Побед</span>
                        <span class="battle-val val-green">
                            <?= $stats['attacks_won'] ?>
                        </span>
                    </div>
                    <div class="battle-row">
                        <span class="battle-label">Поражений</span>
                        <span class="battle-val val-red">
                            <?= $stats['attacks_lost'] ?>
                        </span>
                    </div>
                    <div class="battle-row">
                        <span class="battle-label">% побед</span>
                        <span class="battle-val"
                              style="color:<?= $att_rate >= 50 ? '#4f4':'#f44' ?>">
                            <?= $att_rate ?>%
                        </span>
                    </div>
                    <div class="win-rate-bar">
                        <div class="win-rate-fill"
                             style="width:<?= $att_rate ?>%;
                                    background:linear-gradient(90deg,
                                        <?= $att_rate >= 50 ? '#2a8a2a,#4f4' : '#8a2a2a,#f44' ?>);">
                        </div>
                    </div>
                    <div class="battle-row">
                        <span class="battle-label">Ресурсов украдено</span>
                        <span class="battle-val val-gold">
                            <?= number_format($stats['resources_looted']) ?>
                        </span>
                    </div>
                </div>

                <!-- Защита -->
                <div class="battle-section">
                    <div class="battle-title" style="color:#4f4;">
                        🛡 Защита
                    </div>
                    <?php
                    $def_total = $stats['defenses_total'] ?: 1;
                    $def_rate  = round(($stats['defenses_won'] / $def_total) * 100);
                    ?>
                    <div class="battle-row">
                        <span class="battle-label">Всего защит</span>
                        <span class="battle-val val-gold">
                            <?= $stats['defenses_total'] ?>
                        </span>
                    </div>
                    <div class="battle-row">
                        <span class="battle-label">Отбито</span>
                        <span class="battle-val val-green">
                            <?= $stats['defenses_won'] ?>
                        </span>
                    </div>
                    <div class="battle-row">
                        <span class="battle-label">Проиграно</span>
                        <span class="battle-val val-red">
                            <?= $stats['defenses_lost'] ?>
                        </span>
                    </div>
                    <div class="battle-row">
                        <span class="battle-label">% успешных</span>
                        <span class="battle-val"
                              style="color:<?= $def_rate >= 50 ? '#4f4':'#f44' ?>">
                            <?= $def_rate ?>%
                        </span>
                    </div>
                    <div class="win-rate-bar">
                        <div class="win-rate-fill"
                             style="width:<?= $def_rate ?>%;
                                    background:linear-gradient(90deg,
                                        <?= $def_rate >= 50 ? '#2a4a8a,#44f' : '#8a2a2a,#f44' ?>);">
                        </div>
                    </div>
                    <div class="battle-row">
                        <span class="battle-label">Ресурсов потеряно</span>
                        <span class="battle-val val-red">
                            <?= number_format($stats['resources_lost']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Армия -->
            <div style="margin-top:15px; padding-top:15px; border-top:1px solid #444;">
                <div style="font-size:14px; font-weight:bold; color:#d4a843; margin-bottom:12px;">
                    ⚔ Армия
                </div>
                <div style="display:flex; gap:30px; flex-wrap:wrap;">
                    <div class="battle-row" style="flex:1; min-width:200px;">
                        <span class="battle-label">Юнитов обучено</span>
                        <span class="battle-val val-green">
                            <?= number_format($stats['units_trained']) ?>
                        </span>
                    </div>
                    <div class="battle-row" style="flex:1; min-width:200px;">
                        <span class="battle-label">Юнитов потеряно</span>
                        <span class="battle-val val-red">
                            <?= number_format($stats['units_lost']) ?>
                        </span>
                    </div>
                    <div class="battle-row" style="flex:1; min-width:200px;">
                        <span class="battle-label">Разведок успешных</span>
                        <span class="battle-val val-blue">
                            <?= $stats['spies_success'] ?> / <?= $stats['spies_sent'] ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- График очков -->
    <div class="card">
        <div class="card-header">
            📈 График развития
            <span style="font-size:12px; color:#aaa;">
                последние <?= count($points_history) ?> записей
            </span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="pointsChart" class="chart-canvas"></canvas>
            </div>
        </div>
    </div>

    <!-- Достижения -->
    <?php if (!empty($achievements)): ?>
    <div class="card">
        <div class="card-header">
            🏅 Достижения
            <span style="font-size:12px; color:#aaa;">
                <?= count($achievements) ?> разблокировано
            </span>
        </div>
        <div class="card-body">
            <div class="achievements-grid">
                <?php foreach ($achievements as $ach): ?>
                <div class="achievement done" title="<?= htmlspecialchars($ach['desc']) ?>">
                    <div class="ach-icon"><?= $ach['icon'] ?></div>
                    <div class="ach-title"><?= htmlspecialchars($ach['title']) ?></div>
                    <div class="ach-desc"><?= htmlspecialchars($ach['desc']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Деревни -->
    <div class="card">
        <div class="card-header">
            🏘 Деревни (<?= count($villages) ?>)
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($villages)): ?>
                <div style="padding:20px; text-align:center; color:#666;">
                    Деревень нет
                </div>
            <?php else: ?>
            <table>
                <tr>
                    <th>Деревня</th>
                    <th>Координаты</th>
                    <th>Континент</th>
                    <th>Очки</th>
                    <th>Действия</th>
                </tr>
                <?php foreach ($villages as $v): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($v['name']) ?></strong></td>
                    <td style="color:#888;"><?= $v['x'] ?>|<?= $v['y'] ?></td>
                    <td>K<?= $v['continent'] ?? '?' ?></td>
                    <td style="color:#d4a843; font-weight:bold;">
                        <?= number_format($v['points']) ?>
                    </td>
                    <td>
                        <a href="?page=map&x=<?= $v['x'] ?>&y=<?= $v['y'] ?>"
                           class="btn">🗺</a>
                        <?php if (!$is_own): ?>
                        <a href="?page=attack&target=<?= $v['id'] ?>"
                           class="btn btn-red">⚔</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
// === ГРАФИК ОЧКОВ ===
const historyData = <?= json_encode(array_values($points_history)) ?>;

function drawChart() {
    const canvas  = document.getElementById('pointsChart');
    if (!canvas) return;

    const ctx     = canvas.getContext('2d');
    const W       = canvas.parentElement.clientWidth - 20;
    const H       = 180;
    canvas.width  = W;
    canvas.height = H;

    if (historyData.length < 2) {
        ctx.fillStyle = '#666';
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Недостаточно данных для графика', W/2, H/2);
        return;
    }

    const points  = historyData.map(d => d.points);
    const maxPts  = Math.max(...points) || 1;
    const minPts  = Math.min(...points);
    const range   = maxPts - minPts || 1;

    const padL = 50, padR = 15, padT = 15, padB = 30;
    const chartW = W - padL - padR;
    const chartH = H - padT - padB;

    // Фон сетки
    ctx.strokeStyle = '#333';
    ctx.lineWidth   = 1;
    for (let i = 0; i <= 4; i++) {
        const y = padT + (chartH / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padL, y);
        ctx.lineTo(padL + chartW, y);
        ctx.stroke();

        const val = Math.round(maxPts - (range / 4) * i);
        ctx.fillStyle  = '#666';
        ctx.font       = '10px Arial';
        ctx.textAlign  = 'right';
        ctx.fillText(val >= 1000 ? Math.round(val/1000)+'K' : val, padL - 5, y + 4);
    }

    // Линия графика
    ctx.beginPath();
    ctx.strokeStyle = '#d4a843';
    ctx.lineWidth   = 2;

    historyData.forEach((d, i) => {
        const x = padL + (i / (historyData.length - 1)) * chartW;
        const y = padT + ((maxPts - d.points) / range) * chartH;
        if (i === 0) ctx.moveTo(x, y);
        else         ctx.lineTo(x, y);
    });
    ctx.stroke();

    // Заливка под линией
    ctx.lineTo(padL + chartW, padT + chartH);
    ctx.lineTo(padL, padT + chartH);
    ctx.closePath();
    ctx.fillStyle = 'rgba(212,168,67,0.1)';
    ctx.fill();

    // Точки
    historyData.forEach((d, i) => {
        const x = padL + (i / (historyData.length - 1)) * chartW;
        const y = padT + ((maxPts - d.points) / range) * chartH;
        ctx.beginPath();
        ctx.arc(x, y, 3, 0, Math.PI * 2);
        ctx.fillStyle   = '#d4a843';
        ctx.fill();
    });

    // Метки дат
    ctx.fillStyle  = '#555';
    ctx.font       = '9px Arial';
    ctx.textAlign  = 'center';
    const step = Math.max(1, Math.floor(historyData.length / 5));
    historyData.forEach((d, i) => {
        if (i % step === 0 || i === historyData.length - 1) {
            const x   = padL + (i / (historyData.length - 1)) * chartW;
            const dt  = new Date(d.recorded_at * 1000);
            const lbl = (dt.getMonth()+1) + '/' + dt.getDate();
            ctx.fillText(lbl, x, H - 8);
        }
    });
}

window.addEventListener('resize', drawChart);
drawChart();
</script>

</body>
</html>