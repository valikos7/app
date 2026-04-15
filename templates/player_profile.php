<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($player['username']) ?> — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .top-bar {
            background:#2c1f0e; border-bottom:3px solid #8b6914;
            padding:8px 20px; display:flex;
            justify-content:space-between; align-items:center;
        }
        .top-bar a { color:#d4a843; text-decoration:none; margin:0 10px; font-size:14px; }
        .top-bar a:hover { color:#fff; }

        .container {
            max-width:1000px; margin:20px auto; padding:0 15px;
            display:grid; grid-template-columns:300px 1fr; gap:20px;
        }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:14px;
        }
        .card-body { padding:15px; }

        .avatar {
            width:100px; height:100px;
            background:#3a2c10; border:3px solid #d4a843;
            border-radius:50%; display:flex;
            align-items:center; justify-content:center;
            font-size:48px; margin:0 auto 15px;
        }
        .player-name {
            text-align:center; font-size:24px;
            font-weight:bold; color:#d4a843;
        }
        .player-status {
            text-align:center; font-size:12px;
            margin-top:5px;
        }
        .online { color:#0f0; }
        .offline { color:#666; }

        .stat-row {
            display:flex; justify-content:space-between;
            padding:8px 0; border-bottom:1px solid #333; font-size:13px;
        }
        .stat-row:last-child { border-bottom:none; }
        .stat-label { color:#888; }
        .stat-value { font-weight:bold; color:#d4a843; }

        .alliance-badge {
            text-align:center; margin:12px 0;
            padding:10px; background:#1a1a0a;
            border:1px solid #5a4a20; border-radius:6px;
        }
        .alliance-tag-big {
            font-size:22px; font-weight:bold;
            color:#d4a843;
        }

        table { width:100%; border-collapse:collapse; }
        th {
            background:#3a2c10; color:#d4a843;
            padding:10px 12px; text-align:left; font-size:13px;
        }
        td { padding:10px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#2a2010; }

        .btn {
            display:inline-block; padding:8px 18px;
            background:#5a4a1a; color:#ddd;
            text-decoration:none; border-radius:4px;
            font-size:12px; border:1px solid #8b6914; transition:0.2s;
        }
        .btn:hover { background:#7a6a2a; }
        .btn-red { background:#5a1a1a; border-color:#8b1a1a; }
        .btn-red:hover { background:#7a2a2a; }
        .btn-blue { background:#1a1a5a; border-color:#1a1a8b; }
        .btn-blue:hover { background:#2a2a7a; }

        .rank-badge {
            display:inline-block; padding:4px 12px;
            background:#3a2c10; border:1px solid #d4a843;
            border-radius:12px; color:#d4a843;
            font-size:13px; font-weight:bold;
            margin:5px auto; text-align:center;
        }

        @media(max-width:768px) {
            .container { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div>
        <strong style="color:#d4a843;">⚔ <?= APP_NAME ?></strong>
        <a href="?page=home">Главная</a>
        <a href="?page=profile">Профиль</a>
        <a href="?page=map">Карта</a>
        <a href="?page=ranking">Рейтинг</a>
        <a href="?page=alliances">Альянсы</a>
    </div>
    <div><a href="?page=logout" style="color:#c00;">Выход</a></div>
</div>

<div class="container">

    <!-- Левая колонка -->
    <div>
        <div class="card">
            <div class="card-body">
                <div class="avatar">👤</div>
                <div class="player-name">
                    <?= htmlspecialchars($player['username']) ?>
                </div>

                <?php
                $is_online = ($player['last_activity'] >= time() - 300);
                ?>
                <div class="player-status">
                    <?php if ($is_online): ?>
                        <span class="online">● Онлайн</span>
                    <?php else: ?>
                        <span class="offline">
                            Был: <?= date('d.m.Y H:i', $player['last_activity']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Позиция в рейтинге -->
                <div style="text-align:center; margin:12px 0;">
                    <div class="rank-badge">🏆 #<?= $player_rank ?> в рейтинге</div>
                </div>

                <!-- Альянс -->
                <?php if ($alliance): ?>
                <div class="alliance-badge">
                    <div style="font-size:11px; color:#888; margin-bottom:5px;">Альянс</div>
                    <div class="alliance-tag-big">
                        [<?= htmlspecialchars($alliance['tag']) ?>]
                    </div>
                    <a href="?page=alliance&id=<?= $alliance['id'] ?>"
                       style="color:#aaa; font-size:13px; text-decoration:none;">
                        <?= htmlspecialchars($alliance['name']) ?>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Статистика -->
                <div style="margin-top:15px;">
                    <div class="stat-row">
                        <span class="stat-label">Очки</span>
                        <span class="stat-value">
                            <?= number_format($player['points']) ?>
                        </span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Деревень</span>
                        <span class="stat-value"><?= $player['villages'] ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">На сервере с</span>
                        <span class="stat-value">
                            <?= date('d.m.Y', $player['join_date']) ?>
                        </span>
                    </div>
                </div>

                <!-- Действия -->
                <div style="margin-top:15px; display:flex; flex-direction:column; gap:8px;">
                    <?php if ($player['id'] != $_SESSION['user_id']): ?>
                        <a href="?page=messages&action=compose&to=<?= urlencode($player['username']) ?>"
                           class="btn" style="text-align:center;">
                            ✉ Написать сообщение
                        </a>
                        <a href="?page=map&x=<?= $first_village['x'] ?? 0 ?>&y=<?= $first_village['y'] ?? 0 ?>"
                           class="btn btn-blue" style="text-align:center;">
                            🗺 Найти на карте
                        </a>
                    <?php else: ?>
                        <a href="?page=profile" class="btn" style="text-align:center;">
                            ⚙ Мой профиль
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Правая колонка -->
    <div>
        <!-- Деревни игрока -->
        <div class="card">
            <div class="card-header">
                🏘 Деревни игрока
                <span style="float:right; font-size:12px; color:#aaa;">
                    <?= count($villages) ?> шт.
                </span>
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
                               class="btn">🗺 Карта</a>
                            <?php if ($player['id'] != $_SESSION['user_id']): ?>
                            <a href="?page=attack&target=<?= $v['id'] ?>"
                               class="btn btn-red">⚔ Атака</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Медали/Достижения -->
        <div class="card">
            <div class="card-header">🏅 Достижения</div>
            <div class="card-body">
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <?php
                    $achievements = [];

                    if ($player['points'] >= 100)
                        $achievements[] = ['🌱', 'Новичок', 'Набрал 100 очков'];
                    if ($player['points'] >= 1000)
                        $achievements[] = ['⚔', 'Воин', 'Набрал 1000 очков'];
                    if ($player['points'] >= 10000)
                        $achievements[] = ['👑', 'Король', 'Набрал 10000 очков'];
                    if ($player['villages'] >= 2)
                        $achievements[] = ['🏘', 'Колонист', '2+ деревни'];
                    if ($player['villages'] >= 5)
                        $achievements[] = ['🏰', 'Правитель', '5+ деревень'];
                    if ($alliance)
                        $achievements[] = ['🤝', 'Союзник', 'Состоит в альянсе'];
                    if ($player_rank <= 10)
                        $achievements[] = ['🏆', 'Элита', 'Топ-10 игроков'];

                    if (empty($achievements)):
                    ?>
                        <div style="color:#666; font-size:13px;">
                            Достижений пока нет. Играйте активнее!
                        </div>
                    <?php else: ?>
                        <?php foreach ($achievements as $ach): ?>
                        <div style="background:#1a1a0a; border:1px solid #444;
                                    border-radius:8px; padding:10px 15px;
                                    text-align:center; min-width:100px;"
                             title="<?= $ach[2] ?>">
                            <div style="font-size:28px;"><?= $ach[0] ?></div>
                            <div style="font-size:11px; color:#d4a843; margin-top:4px;">
                                <?= $ach[1] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>