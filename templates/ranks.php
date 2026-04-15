<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ранги — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1000px; margin:20px auto; padding:0 15px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .page-title  { font-size:24px; font-weight:bold; color:#d4a843; }
        .card { background:#2a2a1a; border:2px solid #5a4a20; border-radius:10px; overflow:hidden; margin-bottom:15px; }
        .card-header { background:#3a2c10; padding:12px 16px; font-weight:bold; color:#d4a843; font-size:15px; display:flex; justify-content:space-between; align-items:center; }
        .card-body { padding:20px; }

        /* Текущий ранг */
        .rank-hero {
            text-align:center; padding:25px;
            background:linear-gradient(135deg,#2a2010,#1a1a0a);
            border-radius:10px; margin-bottom:20px;
            border:2px solid;
        }
        .rank-icon-big { font-size:72px; margin-bottom:10px; display:block; }
        .rank-name-big { font-size:28px; font-weight:bold; margin-bottom:8px; }
        .rank-points   { font-size:14px; color:#888; margin-bottom:15px; }

        /* Прогресс */
        .rank-progress { max-width:400px; margin:0 auto; }
        .prog-label { display:flex; justify-content:space-between; font-size:12px; color:#888; margin-bottom:5px; }
        .prog-bar { height:12px; background:#333; border-radius:6px; overflow:hidden; }
        .prog-fill { height:100%; border-radius:6px; transition:0.5s; }

        /* Бонусы */
        .bonus-grid { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-top:15px; }
        .bonus-chip {
            background:rgba(0,0,0,0.3); border-radius:6px;
            padding:6px 14px; font-size:13px; font-weight:bold;
        }

        /* Все ранги */
        .ranks-grid {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
            gap:10px;
        }
        .rank-card {
            background:#1a1a0a; border:2px solid #333;
            border-radius:8px; padding:14px; text-align:center; transition:0.2s;
        }
        .rank-card:hover { border-color:#8b6914; }
        .rank-card.current { border-width:3px; background:#2a2010; }
        .rank-card.achieved { border-color:#5a4a20; }
        .rank-card.locked { opacity:0.5; }
        .rank-card-icon { font-size:32px; margin-bottom:6px; }
        .rank-card-name { font-size:14px; font-weight:bold; margin-bottom:4px; }
        .rank-card-req  { font-size:11px; color:#888; margin-bottom:8px; }
        .rank-card-bonus{ font-size:10px; line-height:1.6; color:#888; }

        /* Таблица лидеров */
        table { width:100%; border-collapse:collapse; }
        th { background:#3a2c10; color:#d4a843; padding:10px 12px; text-align:left; font-size:12px; }
        td { padding:9px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#2a2010; }
        tr:last-child td { border-bottom:none; }
        tr.me td { background:#1a2a1a; border-left:3px solid #4f4; }

        .alert-success { background:#1a3a1a; border:1px solid #4a4; border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0; }
        .alert-error   { background:#3a1a1a; border:1px solid #a44; border-radius:6px; padding:12px; margin-bottom:15px; color:#f66; }

        @media(max-width:600px) {
            .ranks-grid { grid-template-columns:repeat(2,1fr); }
            .bonus-grid { flex-direction:column; align-items:center; }
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

    <div class="page-header">
        <div class="page-title">🏆 Система рангов</div>
    </div>

    <!-- Текущий ранг -->
    <div class="rank-hero" style="border-color:<?= $current_rank['color'] ?>;">
        <span class="rank-icon-big"><?= $current_rank['icon'] ?></span>
        <div class="rank-name-big" style="color:<?= $current_rank['color'] ?>;">
            <?= htmlspecialchars($current_rank['name']) ?>
        </div>
        <div class="rank-points">
            ⭐ <?= number_format($points) ?> очков
            · Ранг #<?= $rank_data['rank_id'] ?>/<?= count($all_ranks) ?>
        </div>

        <?php if ($next_rank): ?>
        <div class="rank-progress">
            <div class="prog-label">
                <span><?= $current_rank['name'] ?></span>
                <span><?= number_format($next_rank['min_points']) ?> → <?= $next_rank['name'] ?></span>
            </div>
            <div class="prog-bar">
                <div class="prog-fill" style="width:<?= $progress_pct ?>%;background:linear-gradient(90deg,<?= $current_rank['color'] ?>,<?= $next_rank['color'] ?>);"></div>
            </div>
            <div style="text-align:center;font-size:11px;color:#888;margin-top:5px;">
                <?= $progress_pct ?>% · Осталось: <?= number_format(max(0,$next_rank['min_points']-$points)) ?> очков
            </div>
        </div>
        <?php else: ?>
        <div style="color:#d4a843;font-size:16px;margin-top:10px;">
            🏆 Максимальный ранг достигнут!
        </div>
        <?php endif; ?>

        <!-- Бонусы текущего ранга -->
        <?php if (!empty($current_rank['bonus'])): ?>
        <div class="bonus-grid">
            <?php
            $bonus_names=['production'=>'⛏ Произв.','attack'=>'⚔ Атака','defense'=>'🛡 Защита','speed'=>'⚡ Скорость'];
            foreach ($current_rank['bonus'] as $key=>$val): ?>
            <div class="bonus-chip" style="border:1px solid <?= $current_rank['color'] ?>;">
                <?= $bonus_names[$key]??$key ?>: <strong style="color:<?= $current_rank['color'] ?>;">+<?= $val ?>%</strong>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="color:#555;font-size:13px;margin-top:10px;">Нет бонусов для этого ранга</div>
        <?php endif; ?>
    </div>

    <!-- Все ранги -->
    <div class="card">
        <div class="card-header">📋 Все ранги</div>
        <div class="card-body">
            <div class="ranks-grid">
                <?php foreach ($all_ranks as $rank_id => $rank):
                    $is_current  = ($rank_id == $rank_data['rank_id']);
                    $is_achieved = ($rank_id <= $rank_data['rank_id']);
                    $card_class  = 'rank-card';
                    if ($is_current)  $card_class .= ' current';
                    elseif ($is_achieved) $card_class .= ' achieved';
                    else $card_class .= ' locked';
                ?>
                <div class="<?= $card_class ?>" style="border-color:<?= $is_current||$is_achieved?$rank['color']:'#333' ?>;">
                    <div class="rank-card-icon"><?= $rank['icon'] ?></div>
                    <div class="rank-card-name" style="color:<?= $rank['color'] ?>;">
                        <?= $rank['name'] ?>
                        <?= $is_current ? '<span style="font-size:10px;"> ◄ Вы</span>' : '' ?>
                    </div>
                    <div class="rank-card-req">
                        ≥ <?= number_format($rank['min_points']) ?> очков
                    </div>
                    <?php if (!empty($rank['bonus'])): ?>
                    <div class="rank-card-bonus">
                        <?php foreach ($rank['bonus'] as $key=>$val): ?>
                        <?= $bonus_names[$key]??$key ?>: +<?= $val ?>%<br>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="rank-card-bonus" style="color:#555;">Нет бонусов</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Таблица лидеров по рангу -->
    <div class="card">
        <div class="card-header">
            🏆 Лидеры по рангу
            <span style="font-size:12px;color:#888;"><?= count($rank_leaders) ?> игроков</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table>
                <tr>
                    <th>#</th>
                    <th>Ранг</th>
                    <th>Игрок</th>
                    <th>Очки</th>
                    <th>Деревень</th>
                </tr>
                <?php foreach ($rank_leaders as $i => $leader):
                    $r   = $all_ranks[$leader['rank_id']] ?? $all_ranks[1];
                    $isme= ($leader['user_id']==$_SESSION['user_id']);
                ?>
                <tr class="<?= $isme?'me':'' ?>">
                    <td style="color:#666;"><?= $i+1 ?></td>
                    <td>
                        <span style="color:<?= $r['color'] ?>;"><?= $r['icon'] ?> <?= $r['name'] ?></span>
                    </td>
                    <td>
                        <a href="?page=player&id=<?= $leader['user_id'] ?>"
                           style="color:#d4a843;text-decoration:none;">
                            <?= htmlspecialchars($leader['username']) ?>
                        </a>
                        <?= $isme?'<span style="color:#888;font-size:11px;">(вы)</span>':'' ?>
                    </td>
                    <td style="color:#d4a843;font-weight:bold;"><?= number_format($leader['points']) ?></td>
                    <td><?= $leader['villages'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

</div>
</body>
</html>