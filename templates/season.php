<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сезон — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1000px; margin:20px auto; padding:0 15px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
        .page-title  { font-size:24px; font-weight:bold; color:#d4a843; }
        .card { background:#2a2a1a; border:2px solid #5a4a20; border-radius:10px; overflow:hidden; margin-bottom:15px; }
        .card-header { background:#3a2c10; padding:12px 16px; font-weight:bold; color:#d4a843; font-size:15px; display:flex; justify-content:space-between; align-items:center; }
        .card-body   { padding:20px; }

        /* Герой сезона */
        .season-hero {
            background:linear-gradient(135deg,#2a1500,#1a1a0a);
            border:2px solid #d4a843; border-radius:12px;
            padding:25px; text-align:center; margin-bottom:20px;
        }
        .season-icon { font-size:64px; margin-bottom:10px; }
        .season-name { font-size:24px; font-weight:bold; color:#d4a843; margin-bottom:8px; }
        .season-timer-box {
            background:rgba(0,0,0,0.3); border:1px solid #d4a843;
            border-radius:8px; padding:12px 20px; display:inline-block; margin:12px auto;
        }
        .season-timer { font-size:32px; font-weight:bold; color:#d4a843; font-family:monospace; }
        .season-prize {
            background:#2a2010; border:1px solid #8b6914;
            border-radius:8px; padding:15px; margin-top:15px;
            font-size:14px; color:#aaa;
        }
        .season-prize h3 { color:#d4a843; margin-bottom:8px; }

        /* Статистика */
        .stats-row {
            display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px;
        }
        .stat-box {
            background:#1a1a0a; border:1px solid #444;
            border-radius:8px; padding:14px; text-align:center;
        }
        .stat-num { font-size:24px; font-weight:bold; color:#d4a843; }
        .stat-lbl { font-size:11px; color:#888; margin-top:4px; }

        /* Моя позиция */
        .my-position {
            background:#1a2a1a; border:2px solid #2a8a2a;
            border-radius:8px; padding:15px; margin-bottom:15px;
            display:flex; align-items:center; gap:15px;
        }
        .my-pos-num { font-size:36px; font-weight:bold; color:#4f4; }
        .my-pos-info { flex:1; }

        /* Таблица */
        table { width:100%; border-collapse:collapse; }
        th { background:#3a2c10; color:#d4a843; padding:10px 12px; text-align:left; font-size:12px; }
        td { padding:9px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#2a2010; }
        tr.me td { background:#1a2a1a; border-left:3px solid #4f4; }
        tr.top1 td { background:#2a2010; }
        tr.top2 td { background:#1a1a2a; }
        tr.top3 td { background:#1a2a1a; }

        /* История */
        .hist-item {
            display:flex; align-items:center; gap:12px;
            padding:10px 0; border-bottom:1px solid #333; font-size:13px;
        }
        .hist-item:last-child { border-bottom:none; }
        .hist-num { font-size:20px; font-weight:bold; color:#d4a843; min-width:30px; text-align:center; }

        @media(max-width:600px) { .stats-row{grid-template-columns:1fr 1fr;} }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <div class="page-header">
        <div class="page-title">🏆 Сезон</div>
    </div>

    <!-- Текущий сезон -->
    <?php if ($season): ?>
    <div class="season-hero">
        <div class="season-icon">🏆</div>
        <div class="season-name"><?= htmlspecialchars($season['name']) ?></div>
        <div style="font-size:13px;color:#888;margin-bottom:12px;">
            Начался: <?= date('d.m.Y', $season['started_at']) ?>
            · Завершится: <?= date('d.m.Y', $season['ends_at']) ?>
        </div>

        <?php $rem_s = max(0, $season['ends_at'] - time()); ?>
        <div class="season-timer-box">
            <div style="font-size:11px;color:#888;margin-bottom:4px;">До конца сезона:</div>
            <div class="season-timer" id="seasonTimer" data-end="<?= $season['ends_at'] ?>">
                <?= floor($rem_s/86400) ?>д <?= gmdate('H:i:s', $rem_s % 86400) ?>
            </div>
        </div>

        <div class="season-prize">
            <h3>🎁 Призы за победу в сезоне:</h3>
            🥇 1-е место: 100 000 ресурсов каждого типа + достижение «Чемпион сезона»<br>
            🥈 2-е место: 50 000 ресурсов каждого типа<br>
            🥉 3-е место: 25 000 ресурсов каждого типа<br>
            <br>
            <span style="color:#888;font-size:12px;">
                Победитель определяется по количеству очков на момент окончания сезона
            </span>
        </div>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:40px;color:#666;">
        <div style="font-size:40px;margin-bottom:10px;">🌙</div>
        Активного сезона нет
    </div>
    <?php endif; ?>

    <!-- Статистика сезона -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-num"><?= number_format($season_stats['total_players']) ?></div>
            <div class="stat-lbl">👥 Участников</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?= number_format($season_stats['total_battles']) ?></div>
            <div class="stat-lbl">⚔ Сражений</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?= number_format($season_stats['total_villages']) ?></div>
            <div class="stat-lbl">🏘 Деревень</div>
        </div>
    </div>

    <!-- Моя позиция -->
    <div class="my-position">
        <div class="my-pos-num">#<?= $my_position ?></div>
        <div class="my-pos-info">
            <div style="font-weight:bold;color:#d4a843;">Ваша позиция в текущем сезоне</div>
            <div style="font-size:12px;color:#888;margin-top:4px;">
                Продолжайте развиваться чтобы подняться выше!
            </div>
        </div>
        <a href="?page=ranking" style="padding:8px 16px;background:#3a2c10;color:#d4a843;
           text-decoration:none;border-radius:5px;border:1px solid #8b6914;font-size:13px;">
            Полный рейтинг →
        </a>
    </div>

    <!-- Топ 50 -->
    <div class="card">
        <div class="card-header">
            🏆 Рейтинг сезона — Топ 50
            <span style="font-size:12px;color:#888;">По очкам</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table>
                <tr>
                    <th>#</th>
                    <th>Игрок</th>
                    <th>Альянс</th>
                    <th>Ранг</th>
                    <th>Захваты</th>
                    <th>Очки</th>
                    <th>Деревень</th>
                </tr>
                <?php foreach ($season_leaders as $i => $p):
                    $is_me   = ($p['id']==$_SESSION['user_id']);
                    $row_cls = $is_me?'me':($i===0?'top1':($i===1?'top2':($i===2?'top3':'')));
                    $icons   = ['🥇','🥈','🥉'];
                    $rank_r  = $all_ranks[$p['rank_id']??1]??$all_ranks[1];
                ?>
                <tr class="<?= $row_cls ?>">
                    <td style="font-weight:bold;">
                        <?= $icons[$i]??($i+1) ?>
                    </td>
                    <td>
                        <a href="?page=player&id=<?= $p['id'] ?>"
                           style="color:<?= $is_me?'#4f4':'#d4a843' ?>;text-decoration:none;">
                            <?= htmlspecialchars($p['username']) ?>
                        </a>
                        <?= $is_me?'<span style="color:#888;font-size:10px;">(вы)</span>':'' ?>
                    </td>
                    <td style="color:#888;font-size:12px;">
                        <?= $p['alliance_tag']?'['.htmlspecialchars($p['alliance_tag']).']':'—' ?>
                    </td>
                    <td>
                        <span style="color:<?= $rank_r['color'] ?>;">
                            <?= $rank_r['icon'] ?> <?= $rank_r['name'] ?>
                        </span>
                    </td>
                    <td style="color:#f44;"><?= $p['captures'] ?></td>
                    <td style="color:#d4a843;font-weight:bold;"><?= number_format($p['points']) ?></td>
                    <td><?= $p['villages'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- История сезонов -->
    <?php if (!empty($season_history)): ?>
    <div class="card">
        <div class="card-header">📋 История сезонов</div>
        <div class="card-body">
            <?php foreach ($season_history as $s):
                $is_active = ($s['status']==='active');
            ?>
            <div class="hist-item">
                <div class="hist-num"><?= $s['number'] ?></div>
                <div style="flex:1;">
                    <div style="font-weight:bold;color:#d4a843;">
                        <?= htmlspecialchars($s['name']) ?>
                        <?= $is_active?'<span style="color:#4f4;font-size:11px;">● Активный</span>':'' ?>
                    </div>
                    <div style="font-size:11px;color:#666;">
                        <?= date('d.m.Y', $s['started_at']) ?>
                        — <?= date('d.m.Y', $s['ends_at']) ?>
                    </div>
                </div>
                <?php if ($s['winner_name']&&!$is_active): ?>
                <div style="font-size:12px;text-align:right;">
                    🥇 <a href="#" style="color:#d4a843;text-decoration:none;">
                        <?= htmlspecialchars($s['winner_name']) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
const seasonTimerEl = document.getElementById('seasonTimer');
if (seasonTimerEl) {
    const end = parseInt(seasonTimerEl.dataset.end);
    function updateSeasonTimer() {
        const rem = Math.max(0, end - Math.floor(Date.now()/1000));
        const d   = Math.floor(rem/86400);
        const h   = Math.floor((rem%86400)/3600);
        const m   = Math.floor((rem%3600)/60);
        const s   = rem%60;
        seasonTimerEl.textContent =
            `${d}д ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    }
    setInterval(updateSeasonTimer,1000);
    updateSeasonTimer();
}
</script>

</body>
</html>