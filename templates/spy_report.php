<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчёт шпиона — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#0f0f1a; color:#ddd; }
        .container { max-width:900px; margin:20px auto; padding:0 15px; }
        .page-title { font-size:24px; font-weight:bold; color:#88f; margin-bottom:20px; }
        .card { background:#1a1a2a; border:2px solid #3a3a6a; border-radius:10px; overflow:hidden; margin-bottom:15px; }
        .card-header { background:#252540; padding:12px 16px; font-weight:bold; color:#88f; font-size:15px; display:flex; justify-content:space-between; align-items:center; }
        .card-body   { padding:20px; }
        table { width:100%; border-collapse:collapse; }
        th { background:#252540; color:#88f; padding:10px 12px; text-align:left; font-size:12px; }
        td { padding:9px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#1f1f3a; }
        .btn { display:inline-block; padding:8px 18px; border-radius:5px; font-size:13px; text-decoration:none; background:#4a1a5a; color:#c8a; border:1px solid #8a2a8a; }
        .btn:hover { background:#6a2a8a; }
        .online { color:#0f0; font-size:11px; }
        .offline{ color:#555; font-size:11px; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <div class="page-title">
        🕵 Отчёт шпиона: [<?= htmlspecialchars($target_alliance['tag']) ?>]
        <?= htmlspecialchars($target_alliance['name']) ?>
    </div>

    <!-- Основная инфо -->
    <div class="card">
        <div class="card-header">📊 Информация об альянсе</div>
        <div class="card-body">
            <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;">
                <div>👥 Участников: <strong style="color:#c8a;"><?= $target_alliance['members_count'] ?></strong></div>
                <div>⭐ Очков: <strong style="color:#c8a;"><?= number_format($target_alliance['points']) ?></strong></div>
                <div>📅 Основан: <strong style="color:#c8a;"><?= date('d.m.Y', $target_alliance['created_at']) ?></strong></div>
            </div>
        </div>
    </div>

    <!-- Участники -->
    <div class="card">
        <div class="card-header">
            👥 Участники
            <span style="font-size:12px;color:#888;"><?= count($members) ?> чел.</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table>
                <tr>
                    <th>Роль</th>
                    <th>Игрок</th>
                    <th>Очки</th>
                    <th>Деревень</th>
                    <th>Статус</th>
                </tr>
                <?php foreach ($members as $m):
                    $is_online = ($m['last_activity'] >= time()-300);
                    $role_icons=['leader'=>'👑','officer'=>'⭐','member'=>'👤'];
                ?>
                <tr>
                    <td><?= $role_icons[$m['role']]??'👤' ?></td>
                    <td>
                        <a href="?page=player&id=<?= $m['id'] ?>" style="color:#c8a;text-decoration:none;">
                            <?= htmlspecialchars($m['username']) ?>
                        </a>
                    </td>
                    <td style="color:#88f;"><?= number_format($m['points']) ?></td>
                    <td><?= $m['villages'] ?></td>
                    <td>
                        <?php if ($is_online): ?>
                            <span class="online">● Онлайн</span>
                        <?php else: ?>
                            <span class="offline">● <?= date('d.m H:i', $m['last_activity']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Движение войск (если есть технология) -->
    <?php if (!empty($movements)): ?>
    <div class="card">
        <div class="card-header">
            🚶 Движение войск
            <span style="font-size:12px;color:#888;">Требует технологию «Шпионаж»</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table>
                <tr>
                    <th>Тип</th>
                    <th>Откуда</th>
                    <th>Куда</th>
                    <th>Прибытие</th>
                </tr>
                <?php foreach ($movements as $mov):
                    $type_names=['attack'=>'⚔ Атака','return'=>'🔙 Возврат','support'=>'🛡 Поддержка','scout'=>'🔍 Разведка'];
                    $rem=max(0,$mov['arrival_time']-time());
                ?>
                <tr>
                    <td><?= $type_names[$mov['type']]??$mov['type'] ?></td>
                    <td style="color:#888;"><?= htmlspecialchars($mov['from_name']??'?') ?></td>
                    <td style="color:#888;"><?= htmlspecialchars($mov['to_name']??'?') ?></td>
                    <td style="color:#88f;font-family:monospace;">
                        <?= floor($rem/60) ?>м <?= $rem%60 ?>с
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-header">🚶 Движение войск</div>
        <div class="card-body" style="color:#555;text-align:center;padding:20px;">
            Для просмотра движений войск требуется технология «Шпионаж» в ветке Дипломатии
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-top:15px;">
        <a href="?page=spy_network" class="btn">← Назад к сети</a>
    </div>

</div>
</body>
</html>