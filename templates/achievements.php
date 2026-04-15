<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Достижения — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1100px; margin:20px auto; padding:0 15px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
        .page-title  { font-size:24px; font-weight:bold; color:#d4a843; }

        .card { background:#2a2a1a; border:2px solid #5a4a20; border-radius:10px; overflow:hidden; margin-bottom:15px; }
        .card-header { background:#3a2c10; padding:12px 16px; font-weight:bold; color:#d4a843; font-size:15px; display:flex; justify-content:space-between; align-items:center; }
        .card-body   { padding:20px; }

        /* Прогресс */
        .progress-hero {
            background:linear-gradient(135deg,#2a2010,#1a1a0a);
            border:2px solid #d4a843; border-radius:10px;
            padding:20px; margin-bottom:20px; text-align:center;
        }
        .progress-nums { font-size:48px; font-weight:bold; color:#d4a843; }
        .progress-bar-big { height:16px; background:#333; border-radius:8px; overflow:hidden; margin:12px auto; max-width:500px; }
        .progress-fill-big { height:100%; background:linear-gradient(90deg,#8b6914,#d4a843); transition:0.5s; }
        .progress-sub { font-size:13px; color:#888; }

        /* Категории */
        .cat-title { font-size:16px; font-weight:bold; color:#d4a843; margin:20px 0 12px; padding-bottom:8px; border-bottom:1px solid #5a4a20; }

        /* Сетка достижений */
        .ach-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px; margin-bottom:20px; }
        .ach-card {
            background:#1a1a0a; border:2px solid #333; border-radius:8px;
            padding:14px; transition:0.2s; text-align:center;
        }
        .ach-card:hover { border-color:#8b6914; transform:translateY(-2px); }
        .ach-card.unlocked { border-color:#d4a843; background:#2a2010; }
        .ach-card.locked   { opacity:0.5; }

        .ach-icon  { font-size:36px; margin-bottom:8px; }
        .ach-name  { font-size:13px; font-weight:bold; color:#d4a843; margin-bottom:4px; }
        .ach-desc  { font-size:11px; color:#888; line-height:1.4; margin-bottom:8px; }
        .ach-pts   { font-size:12px; color:#d4a843; font-weight:bold; }
        .ach-date  { font-size:10px; color:#4f4; margin-top:5px; }
        .ach-lock  { font-size:10px; color:#555; margin-top:5px; }

        /* Топ */
        table { width:100%; border-collapse:collapse; }
        th { background:#3a2c10; color:#d4a843; padding:10px 12px; text-align:left; font-size:12px; }
        td { padding:9px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#2a2010; }
        tr.me td { background:#1a2a1a; border-left:3px solid #4f4; }

        .alert-success { background:#1a3a1a; border:1px solid #4a4; border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0; }

        @media(max-width:600px) { .ach-grid{grid-template-columns:repeat(2,1fr);} }
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
        <div class="page-title">🏅 Достижения</div>
    </div>

    <!-- Прогресс -->
    <div class="progress-hero">
        <div class="progress-nums"><?= $done ?>/<?= $total ?></div>
        <div style="font-size:14px;color:#888;margin-bottom:10px;">
            Очков: <strong style="color:#d4a843;"><?= $earned_pts ?></strong> / <?= $total_pts ?>
        </div>
        <div class="progress-bar-big">
            <div class="progress-fill-big" style="width:<?= $total>0?round($done/$total*100):0 ?>%;"></div>
        </div>
        <div class="progress-sub">
            Разблокировано <?= round($total>0?$done/$total*100:0) ?>% достижений
        </div>
    </div>

    <!-- По категориям -->
    <?php foreach ($categories as $cat_key => $cat): ?>
    <?php if (empty($cat['items'])) continue; ?>

    <div class="cat-title"><?= $cat['name'] ?></div>
    <div class="ach-grid">
        <?php foreach ($cat['items'] as $ach):
            $is_unlocked = $ach['unlocked'];
        ?>
        <div class="ach-card <?= $is_unlocked?'unlocked':'locked' ?>">
            <div class="ach-icon"><?= $ach['icon'] ?></div>
            <div class="ach-name"><?= htmlspecialchars($ach['name']) ?></div>
            <div class="ach-desc"><?= htmlspecialchars($ach['description']) ?></div>
            <div class="ach-pts">+<?= $ach['points'] ?> очков</div>
            <?php if ($is_unlocked): ?>
                <div class="ach-date">
                    ✅ <?= date('d.m.Y', $ach['unlocked_at']) ?>
                </div>
            <?php else: ?>
                <div class="ach-lock">🔒 Заблокировано</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <!-- Топ по достижениям -->
    <?php if (!empty($leaders)): ?>
    <div class="card">
        <div class="card-header">
            🏆 Топ по достижениям
            <span style="font-size:12px;color:#888;"><?= count($leaders) ?> игроков</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table>
                <tr>
                    <th>#</th>
                    <th>Игрок</th>
                    <th>Достижений</th>
                    <th>Очков</th>
                </tr>
                <?php foreach ($leaders as $i => $l):
                    $is_me = ($l['id']==$_SESSION['user_id']);
                ?>
                <tr class="<?= $is_me?'me':'' ?>">
                    <td style="color:#666;"><?= $i+1 ?></td>
                    <td>
                        <a href="?page=player&id=<?= $l['id'] ?>"
                           style="color:#d4a843;text-decoration:none;">
                            <?= htmlspecialchars($l['username']) ?>
                        </a>
                        <?= $is_me?'<span style="color:#888;font-size:11px;">(вы)</span>':'' ?>
                    </td>
                    <td style="color:#d4a843;"><?= $l['ach_count'] ?></td>
                    <td style="color:#d4a843;font-weight:bold;"><?= number_format($l['ach_points']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
