<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>События — <?= APP_NAME ?></title>
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

        /* Активное событие */
        .event-active {
            border-radius:10px; padding:25px; margin-bottom:20px;
            text-align:center; position:relative; overflow:hidden;
        }
        .event-active::before {
            content:''; position:absolute; top:-50%; left:-50%;
            width:200%; height:200%;
            background:radial-gradient(circle,rgba(255,255,255,0.03) 0%,transparent 70%);
            animation:pulse 3s infinite;
        }
        @keyframes pulse {
            0%,100% { transform:scale(1); opacity:1; }
            50%      { transform:scale(1.05); opacity:0.7; }
        }

        .event-icon  { font-size:64px; margin-bottom:12px; display:block; }
        .event-title { font-size:28px; font-weight:bold; margin-bottom:10px; }
        .event-desc  { font-size:14px; opacity:0.9; line-height:1.7; margin-bottom:20px; max-width:600px; margin-left:auto; margin-right:auto; }

        .event-timer-box {
            background:rgba(0,0,0,0.3); border-radius:8px;
            padding:15px; display:inline-block; min-width:220px;
        }
        .event-timer-label { font-size:12px; opacity:0.7; margin-bottom:5px; }
        .event-timer { font-size:36px; font-weight:bold; font-family:monospace; }

        /* Прогресс */
        .event-progress {
            height:6px; background:rgba(0,0,0,0.3);
            border-radius:3px; overflow:hidden;
            margin:15px auto; max-width:400px;
        }
        .event-progress-fill {
            height:100%; border-radius:3px; transition:0.5s;
        }

        /* Эффекты */
        .effects-grid {
            display:flex; gap:12px; flex-wrap:wrap;
            justify-content:center; margin-top:15px;
        }
        .effect-item {
            background:rgba(0,0,0,0.3); border-radius:8px;
            padding:10px 18px; font-size:14px; font-weight:bold;
        }

        /* Рейтинг турнира */
        .tournament-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
            gap:10px;
        }
        .rank-item {
            display:flex; align-items:center; gap:12px;
            padding:12px; background:#1a1a0a;
            border:1px solid #333; border-radius:6px; transition:0.2s;
        }
        .rank-item:hover { border-color:#8b6914; }
        .rank-pos  { font-size:22px; min-width:36px; text-align:center; }
        .rank-name { flex:1; font-size:14px; color:#d4a843; text-decoration:none; }
        .rank-name:hover { text-decoration:underline; }
        .rank-score{ font-size:15px; font-weight:bold; color:#d4a843; white-space:nowrap; }

        /* История */
        .history-item {
            display:flex; align-items:center; gap:12px;
            padding:10px 0; border-bottom:1px solid #333; font-size:13px;
        }
        .history-item:last-child { border-bottom:none; }
        .history-icon { font-size:24px; min-width:30px; text-align:center; }
        .history-info { flex:1; }
        .history-title{ font-weight:bold; color:#d4a843; }
        .history-time { font-size:11px; color:#666; margin-top:3px; }
        .status-active { color:#4f4; }
        .status-ended  { color:#888; }

        /* Нет события */
        .no-event {
            text-align:center; padding:50px;
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:10px; margin-bottom:15px;
        }
        .no-event-icon  { font-size:64px; margin-bottom:15px; }
        .no-event-title { font-size:20px; color:#d4a843; margin-bottom:10px; }
        .no-event-desc  { color:#888; font-size:14px; line-height:1.6; }

        /* Предстоящие */
        .upcoming-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
            gap:12px;
        }
        .upcoming-item {
            background:#1a1a0a; border:1px solid #444;
            border-radius:8px; padding:15px; text-align:center; transition:0.2s;
        }
        .upcoming-item:hover { border-color:#8b6914; transform:translateY(-2px); }
        .upcoming-icon  { font-size:32px; margin-bottom:8px; }
        .upcoming-name  { font-size:13px; font-weight:bold; color:#d4a843; margin-bottom:4px; }
        .upcoming-desc  { font-size:11px; color:#888; line-height:1.4; }
        .upcoming-dur   { font-size:10px; color:#666; margin-top:6px; }

        /* Мои очки в турнире */
        .my-score-box {
            background:rgba(0,0,0,0.3); border-radius:8px;
            padding:12px 20px; display:inline-block; margin-top:12px;
            font-size:14px;
        }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }

        @media(max-width:600px) {
            .effects-grid  { flex-direction:column; align-items:center; }
            .upcoming-grid { grid-template-columns:repeat(2,1fr); }
            .event-title   { font-size:20px; }
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
        <div class="page-title">🌍 Мировые события</div>
    </div>

    <!-- АКТИВНОЕ СОБЫТИЕ -->
    <?php if ($active_event):
        $cfg       = $event_types[$active_event['type']] ?? [];
        $color     = $cfg['color']  ?? '#d4a843';
        $bg        = $cfg['bg']     ?? '#2a2010';
        $border    = $cfg['border'] ?? '#8b6914';
        $remaining = max(0, $active_event['ends_at'] - time());
        $total_dur = $active_event['ends_at'] - $active_event['started_at'];
        $progress  = $total_dur > 0
            ? min(100, round((($total_dur - $remaining) / $total_dur) * 100))
            : 0;
    ?>

    <div class="event-active" style="background:<?= $bg ?>;border:2px solid <?= $border ?>;">
        <span class="event-icon"><?= $active_event['icon'] ?></span>
        <div class="event-title" style="color:<?= $color ?>;">
            <?= htmlspecialchars($active_event['title']) ?>
        </div>
        <div class="event-desc">
            <?= nl2br(htmlspecialchars($active_event['description'])) ?>
        </div>

        <!-- Прогресс -->
        <div class="event-progress">
            <div class="event-progress-fill"
                 style="width:<?= $progress ?>%;background:<?= $color ?>;">
            </div>
        </div>

        <!-- Таймер -->
        <div class="event-timer-box" style="border:1px solid <?= $border ?>;">
            <div class="event-timer-label">⏱ До конца события:</div>
            <div class="event-timer"
                 id="eventTimer"
                 style="color:<?= $color ?>;"
                 data-end="<?= $active_event['ends_at'] ?>">
                <?= gmdate('H:i:s', $remaining) ?>
            </div>
        </div>

        <!-- Мои очки в турнире -->
        <?php if ($active_event['type'] === 'tournament' && $my_rank): ?>
        <div class="my-score-box">
            ⭐ Ваш счёт: <strong style="color:<?= $color ?>;">
                <?= number_format($my_rank['score'] ?? 0) ?>
            </strong>
            &nbsp;·&nbsp;
            🏆 Место: <strong style="color:<?= $color ?>;">
                #<?= $my_rank['user_rank'] ?? '?' ?>
            </strong>
        </div>
        <?php endif; ?>

        <!-- Эффекты -->
        <?php
        $effects = [];
        switch ($active_event['type']) {
            case 'gold_rush':
                $effects = [['💰','Производство x2'],['⚡','Стройте быстрее!']];
                break;
            case 'plague':
                $effects = [['☠','Производство -30%'],['💊','Постройте лечебницу']];
                break;
            case 'blessing':
                $effects = [['✨','Строительство x2'],['⚔','Тренировка x2']];
                break;
            case 'barbarian_invasion':
                $effects = [['⚔','Усиленные варвары'],['💰','x2 лут с варваров']];
                break;
            case 'tournament':
                $effects = [['🏆','PvP рейтинг активен'],['🎁','Призы топ-3 игрокам']];
                break;
            case 'caravan':
                $effects = [['🚚','Выгодный курс на рынке'],['💎','Редкие предложения']];
                break;
        }
        ?>
        <?php if (!empty($effects)): ?>
        <div class="effects-grid">
            <?php foreach ($effects as $eff): ?>
            <div class="effect-item"><?= $eff[0] ?> <?= $eff[1] ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Рейтинг турнира -->
    <?php if ($active_event['type'] === 'tournament' && !empty($tournament_rank)): ?>
    <div class="card">
        <div class="card-header">
            🏆 Рейтинг турнира
            <span style="font-size:12px;color:#aaa;">
                <?= count($tournament_rank) ?> участников
            </span>
        </div>
        <div class="card-body">
            <div class="tournament-grid">
                <?php foreach ($tournament_rank as $i => $p):
                    $place_icons = ['🥇','🥈','🥉'];
                    $place_icon  = $place_icons[$i] ?? ($i+1);
                    $is_me       = ($p['user_id'] == $_SESSION['user_id']);
                ?>
                <div class="rank-item"
                     style="<?= $is_me ? 'border-color:#d4a843;background:#2a2010;' : '' ?>">
                    <div class="rank-pos"><?= $place_icon ?></div>
                    <div>
                        <a href="?page=player&id=<?= $p['user_id'] ?>"
                           class="rank-name"
                           style="color:<?= $is_me?'#4f4':'#d4a843' ?>">
                            <?= htmlspecialchars($p['username']) ?>
                        </a>
                        <?php if ($is_me): ?>
                            <span style="color:#888;font-size:11px;">(вы)</span>
                        <?php endif; ?>
                    </div>
                    <div class="rank-score">
                        <?= number_format($p['score']) ?> оч.
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Призовые места -->
            <div style="margin-top:20px;padding:15px;background:#1a1a0a;
                        border:1px solid #444;border-radius:8px;">
                <div style="font-weight:bold;color:#d4a843;margin-bottom:10px;">
                    🎁 Призы за победу:
                </div>
                <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;">
                    <span>🥇 🪵50k 🪨50k ⛏50k</span>
                    <span>🥈 🪵25k 🪨25k ⛏25k</span>
                    <span>🥉 🪵10k 🪨10k ⛏10k</span>
                </div>
                <div style="font-size:11px;color:#666;margin-top:8px;">
                    Ресурсы выдаются автоматически после завершения турнира.
                    Очки начисляются за победы в PvP атаках.
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>

    <!-- НЕТ АКТИВНОГО СОБЫТИЯ -->
    <div class="no-event">
        <div class="no-event-icon">🌙</div>
        <div class="no-event-title">Сейчас нет активных событий</div>
        <div class="no-event-desc">
            Мировые события происходят каждые 2-3 дня.<br>
            Следите за уведомлениями в отчётах!
        </div>
    </div>

    <?php endif; ?>

    <!-- ВСЕ ВОЗМОЖНЫЕ СОБЫТИЯ -->
    <div class="card">
        <div class="card-header">📅 Возможные события</div>
        <div class="card-body">
            <div class="upcoming-grid">
                <?php foreach ($event_types as $type => $cfg):
                    $is_active = ($active_event && $active_event['type'] === $type);
                ?>
                <div class="upcoming-item"
                     style="<?= $is_active?'border-color:'.$cfg['border'].';background:'.$cfg['bg'].';':'' ?>">
                    <div class="upcoming-icon"><?= $cfg['icon'] ?></div>
                    <div class="upcoming-name" style="color:<?= $cfg['color'] ?>;">
                        <?= htmlspecialchars($cfg['title']) ?>
                        <?= $is_active ? '<span style="color:#4f4;font-size:10px;">● Активно</span>' : '' ?>
                    </div>
                    <div class="upcoming-desc">
                        <?= mb_substr(htmlspecialchars($cfg['description']), 0, 80) ?>...
                    </div>
                    <div class="upcoming-dur">
                        ⏱ <?= round($cfg['duration'] / 3600) ?> ч.
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ИСТОРИЯ СОБЫТИЙ -->
    <?php if (!empty($history)): ?>
    <div class="card">
        <div class="card-header">
            📋 История событий
            <span style="font-size:12px;color:#aaa;"><?= count($history) ?></span>
        </div>
        <div class="card-body">
            <?php foreach ($history as $h):
                $is_act = ($h['status']==='active' && $h['ends_at']>time());
            ?>
            <div class="history-item">
                <div class="history-icon"><?= $h['icon'] ?></div>
                <div class="history-info">
                    <div class="history-title">
                        <?= htmlspecialchars($h['title']) ?>
                    </div>
                    <div class="history-time">
                        📅 <?= date('d.m.Y H:i', $h['started_at']) ?>
                        → <?= date('d.m.Y H:i', $h['ends_at']) ?>
                        &nbsp;·&nbsp;
                        ⏱ <?= round(($h['ends_at']-$h['started_at'])/3600) ?> ч.
                    </div>
                </div>
                <div class="<?= $is_act?'status-active':'status-ended' ?>">
                    <?= $is_act ? '● Активно' : '✓ Завершено' ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Таймер события
const eventTimerEl = document.getElementById('eventTimer');
if (eventTimerEl) {
    const end = parseInt(eventTimerEl.dataset.end);

    function updateEventTimer() {
        const rem = Math.max(0, end - Math.floor(Date.now()/1000));
        const h   = Math.floor(rem / 3600);
        const m   = Math.floor((rem % 3600) / 60);
        const s   = rem % 60;
        eventTimerEl.textContent =
            `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (rem <= 0) location.reload();
    }

    setInterval(updateEventTimer, 1000);
    updateEventTimer();
}
</script>

</body>
</html>