<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Квесты — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1000px; margin:20px auto; padding:0 15px; }

        .page-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:20px;
        }
        .page-title { font-size:24px; font-weight:bold; color:#d4a843; }

        /* Статистика */
        .quest-stats {
            display:grid; grid-template-columns:repeat(3,1fr);
            gap:12px; margin-bottom:20px;
        }
        .quest-stat {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; padding:14px; text-align:center;
        }
        .quest-stat-icon  { font-size:28px; margin-bottom:6px; }
        .quest-stat-val   { font-size:20px; font-weight:bold; color:#d4a843; }
        .quest-stat-label { font-size:11px; color:#888; margin-top:3px; }

        /* Таймер сброса */
        .reset-timers {
            display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;
        }
        .reset-timer {
            background:#2a2a1a; border:1px solid #5a4a20;
            border-radius:6px; padding:10px 16px;
            display:flex; align-items:center; gap:10px; font-size:13px;
        }
        .reset-timer-icon { font-size:20px; }
        .reset-timer-text { color:#888; }
        .reset-timer-val  { font-weight:bold; color:#d4a843; font-family:monospace; }

        /* Табы */
        .quest-tabs {
            display:flex; gap:5px; margin-bottom:20px; flex-wrap:wrap;
        }
        .quest-tab {
            padding:9px 20px; border-radius:6px;
            border:2px solid #5a4a20; color:#aaa;
            text-decoration:none; font-size:13px; transition:0.2s;
            position:relative;
        }
        .quest-tab.active, .quest-tab:hover {
            background:#3a2c10; color:#d4a843; border-color:#8b6914;
        }
        .tab-badge {
            position:absolute; top:-6px; right:-6px;
            background:#c00; color:#fff; border-radius:10px;
            padding:1px 6px; font-size:10px; font-weight:bold;
        }
        .tab-badge.done { background:#2a8a2a; }

        /* Квест карточка */
        .quest-card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; padding:16px; margin-bottom:10px;
            transition:0.2s; display:flex; gap:15px; align-items:flex-start;
        }
        .quest-card:hover { border-color:#8b6914; }
        .quest-card.completed {
            border-color:#2a8a2a; background:#1a2a1a;
        }
        .quest-card.rewarded {
            border-color:#333; background:#1a1a1a; opacity:0.7;
        }

        .quest-icon {
            font-size:36px; flex-shrink:0; text-align:center;
            width:50px; line-height:1;
        }

        .quest-info { flex:1; min-width:0; }
        .quest-title {
            font-size:15px; font-weight:bold; color:#d4a843;
            margin-bottom:4px;
        }
        .quest-desc {
            font-size:12px; color:#888; margin-bottom:10px; line-height:1.5;
        }

        /* Прогресс */
        .quest-progress-bar {
            height:8px; background:#333; border-radius:4px;
            overflow:hidden; margin-bottom:6px;
        }
        .quest-progress-fill {
            height:100%; border-radius:4px; transition:0.5s;
        }
        .fill-active    { background:linear-gradient(90deg,#8b6914,#d4a843); }
        .fill-completed { background:linear-gradient(90deg,#2a8a2a,#4f4); }
        .fill-rewarded  { background:#333; }

        .quest-progress-text {
            font-size:11px; color:#888;
            display:flex; justify-content:space-between;
        }

        /* Награда */
        .quest-reward {
            display:flex; gap:8px; flex-wrap:wrap;
            margin-top:8px; font-size:12px;
        }
        .reward-item {
            background:#1a1a0a; border:1px solid #444;
            border-radius:4px; padding:3px 8px;
            display:flex; align-items:center; gap:4px;
        }
        .reward-item.exp { border-color:#2a2a8a; color:#88f; }

        /* Кнопки */
        .btn-claim {
            padding:8px 18px; background:#2a8a2a; color:#fff;
            border:none; border-radius:5px; cursor:pointer;
            font-size:13px; transition:0.2s; white-space:nowrap;
            flex-shrink:0;
        }
        .btn-claim:hover { background:#3a9a3a; }

        .quest-done-label {
            color:#4f4; font-size:13px; font-weight:bold;
            white-space:nowrap; flex-shrink:0; padding:8px 0;
        }
        .quest-rewarded-label {
            color:#555; font-size:12px; white-space:nowrap; flex-shrink:0; padding:8px 0;
        }

        /* Выбор деревни */
        .village-select-bar {
            background:#2a2a1a; border:1px solid #5a4a20;
            border-radius:6px; padding:12px 16px;
            display:flex; align-items:center; gap:12px;
            margin-bottom:15px; flex-wrap:wrap;
        }
        .village-select-bar label { color:#888; font-size:13px; }
        .village-select-bar select {
            padding:7px 10px; background:#1a1a0a; color:#ddd;
            border:1px solid #444; border-radius:4px; font-size:13px;
        }

        /* Пустые */
        .quest-empty {
            text-align:center; padding:40px; color:#666;
        }
        .quest-empty-icon { font-size:40px; margin-bottom:10px; }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        @media(max-width:600px) {
            .quest-stats { grid-template-columns:repeat(2,1fr); }
            .quest-card  { flex-direction:column; }
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
        <div class="page-title">📋 Квесты и задания</div>
    </div>

    <!-- Статистика -->
    <div class="quest-stats">
        <div class="quest-stat">
            <div class="quest-stat-icon">📅</div>
            <div class="quest-stat-val">
                <?= $stats['daily_done'] ?>/<?= $stats['daily_total'] ?>
            </div>
            <div class="quest-stat-label">Ежедневных</div>
        </div>
        <div class="quest-stat">
            <div class="quest-stat-icon">📆</div>
            <div class="quest-stat-val">
                <?= $stats['weekly_done'] ?>/<?= $stats['weekly_total'] ?>
            </div>
            <div class="quest-stat-label">Еженедельных</div>
        </div>
        <div class="quest-stat">
            <div class="quest-stat-icon">📚</div>
            <div class="quest-stat-val">
                <?= $stats['tutorial_done'] ?>/<?= $stats['tutorial_total'] ?>
            </div>
            <div class="quest-stat-label">Обучающих</div>
        </div>
    </div>

    <!-- Таймеры сброса -->
    <div class="reset-timers">
        <div class="reset-timer">
            <span class="reset-timer-icon">📅</span>
            <span class="reset-timer-text">Сброс ежедневных:</span>
            <span class="reset-timer-val" id="dailyTimer"
                  data-end="<?= $next_reset ?>">
                <?= gmdate('H:i:s', $next_reset - time()) ?>
            </span>
        </div>
        <div class="reset-timer">
            <span class="reset-timer-icon">📆</span>
            <span class="reset-timer-text">Сброс еженедельных:</span>
            <span class="reset-timer-val" id="weeklyTimer"
                  data-end="<?= $next_weekly ?>">
                <?php
                $diff = $next_weekly - time();
                $d = floor($diff/86400);
                $h = floor(($diff%86400)/3600);
                echo "{$d}д {$h}ч";
                ?>
            </span>
        </div>
    </div>

    <!-- Выбор деревни для наград -->
    <?php
    $stmt = $db->prepare("SELECT id, name FROM villages WHERE userid=? ORDER BY id");
    $stmt->execute([$_SESSION['user_id']]);
    $my_villages = $stmt->fetchAll();
    $selected_village = (int)($_GET['village_id'] ?? ($my_villages[0]['id'] ?? 0));
    ?>
    <div class="village-select-bar">
        <label>🏘 Получать ресурсы в деревню:</label>
        <select id="villageSelect" onchange="updateVillageLinks()">
            <?php foreach ($my_villages as $v): ?>
            <option value="<?= $v['id'] ?>"
                    <?= $v['id']==$selected_village?'selected':'' ?>>
                <?= htmlspecialchars($v['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Табы -->
    <?php $active_tab = $_GET['tab'] ?? 'daily'; ?>
    <div class="quest-tabs">
        <?php
        $pending_daily  = count(array_filter($daily,  fn($q)=>$q['completed']&&!$q['rewarded']));
        $pending_weekly = count(array_filter($weekly, fn($q)=>$q['completed']&&!$q['rewarded']));
        $pending_tut    = count(array_filter($tutorial,fn($q)=>$q['completed']&&!$q['rewarded']));
        ?>
        <a href="?page=quests&tab=daily&village_id=<?= $selected_village ?>"
           class="quest-tab <?= $active_tab==='daily'?'active':'' ?>">
            📅 Ежедневные
            (<?= $stats['daily_done'] ?>/<?= $stats['daily_total'] ?>)
            <?php if ($pending_daily > 0): ?>
                <span class="tab-badge done"><?= $pending_daily ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=quests&tab=weekly&village_id=<?= $selected_village ?>"
           class="quest-tab <?= $active_tab==='weekly'?'active':'' ?>">
            📆 Еженедельные
            (<?= $stats['weekly_done'] ?>/<?= $stats['weekly_total'] ?>)
            <?php if ($pending_weekly > 0): ?>
                <span class="tab-badge done"><?= $pending_weekly ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=quests&tab=tutorial&village_id=<?= $selected_village ?>"
           class="quest-tab <?= $active_tab==='tutorial'?'active':'' ?>">
            📚 Обучение
            (<?= $stats['tutorial_done'] ?>/<?= $stats['tutorial_total'] ?>)
            <?php if ($pending_tut > 0): ?>
                <span class="tab-badge done"><?= $pending_tut ?></span>
            <?php endif; ?>
        </a>
    </div>

    <?php
    // Выбираем активный список
    $active_quests = match($active_tab) {
        'weekly'   => $weekly,
        'tutorial' => $tutorial,
        default    => $daily,
    };

    $tab_descriptions = [
        'daily'    => '📅 Ежедневные квесты сбрасываются каждую ночь в 00:00.',
        'weekly'   => '📆 Еженедельные квесты сбрасываются каждый понедельник.',
        'tutorial' => '📚 Обучающие квесты выполняются один раз и не сбрасываются.',
    ];
    ?>

    <div style="font-size:12px;color:#666;margin-bottom:15px;">
        <?= $tab_descriptions[$active_tab] ?>
    </div>

    <!-- Список квестов -->
    <?php if (empty($active_quests)): ?>
    <div class="quest-empty">
        <div class="quest-empty-icon">📋</div>
        Квестов нет
    </div>
    <?php else: ?>
    <?php foreach ($active_quests as $q):
        $pct      = $q['goal'] > 0 ? min(100, round($q['progress']/$q['goal']*100)) : 0;
        $is_done  = (bool)$q['completed'];
        $is_rwd   = (bool)$q['rewarded'];

        $card_class = 'quest-card';
        if ($is_rwd)  $card_class .= ' rewarded';
        elseif ($is_done) $card_class .= ' completed';

        $fill_class = 'fill-active';
        if ($is_rwd)  $fill_class = 'fill-rewarded';
        elseif ($is_done) $fill_class = 'fill-completed';
    ?>
    <div class="<?= $card_class ?>">
        <div class="quest-icon"><?= $q['icon'] ?></div>

        <div class="quest-info">
            <div class="quest-title"><?= htmlspecialchars($q['title']) ?></div>
            <div class="quest-desc"><?= htmlspecialchars($q['description']) ?></div>

            <!-- Прогресс -->
            <div class="quest-progress-bar">
                <div class="quest-progress-fill <?= $fill_class ?>"
                     style="width:<?= $pct ?>%;"></div>
            </div>
            <div class="quest-progress-text">
                <span>
                    <?= number_format($q['progress']) ?> /
                    <?= number_format($q['goal']) ?>
                    <?= $is_done ? '✅' : '' ?>
                </span>
                <span><?= $pct ?>%</span>
            </div>

            <!-- Награда -->
            <div class="quest-reward">
                <?php if ($q['reward_wood']  > 0): ?>
                    <div class="reward-item">🪵 <?= number_format($q['reward_wood']) ?></div>
                <?php endif; ?>
                <?php if ($q['reward_stone'] > 0): ?>
                    <div class="reward-item">🪨 <?= number_format($q['reward_stone']) ?></div>
                <?php endif; ?>
                <?php if ($q['reward_iron']  > 0): ?>
                    <div class="reward-item">⛏ <?= number_format($q['reward_iron']) ?></div>
                <?php endif; ?>
                <?php if ($q['reward_exp']   > 0): ?>
                    <div class="reward-item exp">✨ <?= $q['reward_exp'] ?> опыта</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Кнопка / Статус -->
        <div style="flex-shrink:0;">
            <?php if ($is_rwd): ?>
                <div class="quest-rewarded-label">✓ Получено</div>
            <?php elseif ($is_done): ?>
                <a href="?page=quests&action=claim&quest_id=<?= $q['id'] ?>&village_id=<?= $selected_village ?>&tab=<?= $active_tab ?>"
                   class="btn-claim"
                   id="claimBtn_<?= $q['id'] ?>">
                    🎁 Забрать
                </a>
            <?php else: ?>
                <div style="font-size:12px;color:#555;padding:8px 0;text-align:center;">
                    В процессе
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>

<script>
// Таймеры сброса
function updateTimers() {
    const now = Math.floor(Date.now() / 1000);

    ['dailyTimer','weeklyTimer'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const end = parseInt(el.dataset.end);
        const rem = Math.max(0, end - now);
        const h   = Math.floor(rem / 3600);
        const m   = Math.floor((rem % 3600) / 60);
        const s   = rem % 60;
        if (id === 'dailyTimer') {
            el.textContent =
                `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        } else {
            const d = Math.floor(rem / 86400);
            const hr = Math.floor((rem % 86400) / 3600);
            el.textContent = `${d}д ${hr}ч`;
        }
    });
}
setInterval(updateTimers, 1000);
updateTimers();

// Обновление ссылок при смене деревни
function updateVillageLinks() {
    const vid = document.getElementById('villageSelect').value;

    // Обновляем ссылки "Забрать"
    document.querySelectorAll('[id^="claimBtn_"]').forEach(btn => {
        const url = new URL(btn.href);
        url.searchParams.set('village_id', vid);
        btn.href = url.toString();
    });

    // Обновляем табы
    document.querySelectorAll('.quest-tab').forEach(tab => {
        const url = new URL(tab.href, window.location.origin);
        url.searchParams.set('village_id', vid);
        tab.href = url.toString();
    });
}
</script>

</body>
</html>