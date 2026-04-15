<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Рейтинг — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .top-bar {
            background:#2c1f0e; border-bottom:3px solid #8b6914;
            padding:8px 20px; display:flex;
            justify-content:space-between; align-items:center;
        }
        .top-bar a { color:#d4a843; text-decoration:none; margin:0 10px; font-size:14px; }
        .container { max-width:900px; margin:20px auto; padding:0 15px; }
        .tabs {
            display:flex; gap:5px; margin-bottom:15px;
        }
        .tab {
            padding:10px 25px; background:#2a2a1a;
            border:2px solid #5a4a20; border-radius:6px;
            color:#aaa; text-decoration:none; font-size:14px;
            transition:0.2s;
        }
        .tab.active { background:#3a2c10; color:#d4a843; border-color:#8b6914; }
        .tab:hover { background:#3a2c10; color:#d4a843; }
        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843;
        }
        table { width:100%; border-collapse:collapse; }
        th {
            background:#3a2c10; color:#d4a843;
            padding:10px 12px; text-align:left; font-size:13px;
        }
        td { padding:10px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#2a2010; }
        tr.me td { background:#1a2a1a; border-left:3px solid #4a4; }

        .rank-1 { color:#ffd700; font-size:18px; }
        .rank-2 { color:#c0c0c0; font-size:16px; }
        .rank-3 { color:#cd7f32; font-size:14px; }

        .alliance-tag {
            display:inline-block; background:#3a2c10;
            border:1px solid #8b6914; border-radius:3px;
            padding:1px 6px; color:#d4a843; font-size:11px;
        }

        .online-dot { color:#0f0; font-size:10px; }
        .offline-dot { color:#555; font-size:10px; }

        .my-rank-banner {
            background:#1a2a1a; border:2px solid #4a8a4a;
            border-radius:8px; padding:15px 20px; margin-bottom:15px;
            display:flex; align-items:center; gap:15px;
        }
        .my-rank-num {
            font-size:36px; font-weight:bold; color:#d4a843;
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

    <!-- Моя позиция -->
    <div class="my-rank-banner">
        <div class="my-rank-num">#<?= $my_rank ?></div>
        <div>
            <div style="font-size:15px; color:#ddd;">Ваша позиция в рейтинге</div>
            <div style="font-size:12px; color:#888; margin-top:3px;">
                Продолжайте развивать деревни, чтобы подняться выше!
            </div>
        </div>
    </div>

    <!-- Вкладки -->
    <div class="tabs">
        <a href="?page=ranking&tab=players"
           class="tab <?= $tab === 'players' ? 'active' : '' ?>">
            👤 Игроки
        </a>
        <a href="?page=ranking&tab=alliances"
           class="tab <?= $tab === 'alliances' ? 'active' : '' ?>">
            🏰 Альянсы
        </a>
    </div>

    <!-- Рейтинг игроков -->
    <?php if ($tab === 'players'): ?>
    <div class="card">
        <div class="card-header">
            👤 Топ-100 игроков
        </div>
        <table>
            <tr>
                <th>#</th>
                <th>Игрок</th>
                <th>Альянс</th>
                <th>Деревень</th>
                <th>Очки</th>
                <th>Статус</th>
            </tr>
            <?php foreach ($players as $i => $p):
                $is_me = ($p['id'] == $_SESSION['user_id']);
                $is_online = ($p['last_activity'] >= time() - 300);
                $rank_icon = '';
                if ($i === 0) $rank_icon = '🥇';
                elseif ($i === 1) $rank_icon = '🥈';
                elseif ($i === 2) $rank_icon = '🥉';
                else $rank_icon = $i + 1;
            ?>
            <tr class="<?= $is_me ? 'me' : '' ?>">
                <td>
                    <?php if ($i < 3): ?>
                        <span class="rank-<?= $i+1 ?>"><?= $rank_icon ?></span>
                    <?php else: ?>
                        <span style="color:#666;"><?= $i+1 ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?page=player&id=<?= $p['id'] ?>"
                       style="color:<?= $is_me ? '#4f4' : '#d4a843' ?>;text-decoration:none;">
                        <?= htmlspecialchars($p['username']) ?>
                    </a>
                    <?php if ($is_me): ?>
                        <span style="color:#888;font-size:11px;">(вы)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($p['alliance_tag']): ?>
                        <span class="alliance-tag">
                            <?= htmlspecialchars($p['alliance_tag']) ?>
                        </span>
                        <?= htmlspecialchars($p['alliance_name']) ?>
                    <?php else: ?>
                        <span style="color:#555;">—</span>
                    <?php endif; ?>
                </td>
                <td><?= $p['villages'] ?></td>
                <td style="font-weight:bold; color:#d4a843;">
                    <?= number_format($p['points']) ?>
                </td>
                <td>
                    <?php if ($is_online): ?>
                        <span class="online-dot">●</span> Онлайн
                    <?php else: ?>
                        <span class="offline-dot">●</span>
                        <?= date('d.m H:i', $p['last_activity']) ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Рейтинг альянсов -->
    <?php elseif ($tab === 'alliances'): ?>
    <div class="card">
        <div class="card-header">🏰 Топ-50 альянсов</div>
        <table>
            <tr>
                <th>#</th>
                <th>Альянс</th>
                <th>Лидер</th>
                <th>Участников</th>
                <th>Очки</th>
            </tr>
            <?php foreach ($alliances_rank as $i => $a): ?>
            <tr>
                <td>
                    <?php if ($i === 0) echo '🥇';
                    elseif ($i === 1) echo '🥈';
                    elseif ($i === 2) echo '🥉';
                    else echo '<span style="color:#666;">' . ($i+1) . '</span>'; ?>
                </td>
                <td>
                    <span class="alliance-tag">
                        <?= htmlspecialchars($a['tag']) ?>
                    </span>
                    <a href="?page=alliance&id=<?= $a['id'] ?>"
                       style="color:#d4a843;text-decoration:none;">
                        <?= htmlspecialchars($a['name']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($a['leader_name'] ?? '?') ?></td>
                <td><?= $a['members_count'] ?></td>
                <td style="font-weight:bold; color:#d4a843;">
                    <?= number_format($a['points']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

</div>
</body>
</html>