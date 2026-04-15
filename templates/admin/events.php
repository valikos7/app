<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>События — Админ — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#0f0f0f; color:#ddd; }
        .admin-layout { display:grid; grid-template-columns:220px 1fr; min-height:100vh; }
        .admin-sidebar { background:#1a1a1a; border-right:2px solid #333; padding:0; position:sticky; top:0; height:100vh; overflow-y:auto; }
        .admin-logo { padding:18px 20px; font-size:16px; font-weight:bold; color:#d4a843; border-bottom:1px solid #333; }
        .admin-nav a { display:flex; align-items:center; gap:8px; padding:10px 20px; color:#aaa; text-decoration:none; font-size:13px; transition:0.2s; border-left:3px solid transparent; }
        .admin-nav a:hover, .admin-nav a.active { background:#252525; color:#d4a843; border-left-color:#d4a843; }
        .nav-section { padding:10px 20px 4px; font-size:10px; color:#555; text-transform:uppercase; letter-spacing:1px; }
        .nav-divider { height:1px; background:#333; margin:8px 0; }
        .admin-main { padding:25px; }
        .admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:15px; border-bottom:2px solid #333; }
        .admin-title { font-size:22px; color:#d4a843; font-weight:bold; }
        .admin-card { background:#1a1a1a; border:1px solid #333; border-radius:8px; overflow:hidden; margin-bottom:20px; }
        .admin-card-header { background:#252525; padding:12px 16px; font-weight:bold; color:#d4a843; font-size:14px; display:flex; justify-content:space-between; align-items:center; }
        .admin-card-body { padding:20px; }
        table { width:100%; border-collapse:collapse; }
        th { background:#252525; color:#d4a843; padding:10px 12px; text-align:left; font-size:12px; }
        td { padding:9px 12px; border-bottom:1px solid #222; font-size:12px; }
        tr:last-child td { border-bottom:none; }
        .btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:4px; font-size:12px; border:none; cursor:pointer; transition:0.2s; text-decoration:none; }
        .btn-primary { background:#3a6a1a; color:#fff; }
        .btn-primary:hover { background:#4a8a2a; }
        .btn-danger  { background:#6a1a1a; color:#fff; }
        .btn-danger:hover  { background:#8a2a2a; }
        .btn-warning { background:#6a5a1a; color:#fff; }
        .btn-warning:hover { background:#8a7a2a; }
        .btn-info    { background:#1a3a6a; color:#fff; }
        .btn-info:hover    { background:#2a5a8a; }
        .btn-gold    { background:#5a4a1a; color:#d4a843; border:1px solid #8b6914; }
        .btn-gold:hover    { background:#7a6a2a; }

        .event-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:12px; margin-bottom:15px; }
        .event-card { background:#252525; border:2px solid #333; border-radius:8px; padding:16px; text-align:center; transition:0.2s; }
        .event-card:hover { border-color:#8b6914; }
        .event-card.current-active { border-color:#d4a843; background:#2a2010; }
        .event-icon { font-size:36px; margin-bottom:8px; }
        .event-name { font-size:13px; font-weight:bold; color:#d4a843; margin-bottom:4px; }
        .event-dur  { font-size:11px; color:#888; margin-bottom:12px; }

        .active-event-banner {
            background:#2a2010; border:2px solid #d4a843;
            border-radius:8px; padding:18px;
            display:flex; align-items:center; gap:15px;
            margin-bottom:20px;
        }

        .alert { padding:12px 16px; border-radius:6px; margin-bottom:15px; font-size:13px; }
        .alert-success { background:#1a3a1a; border:1px solid #4a4; color:#4f4; }
        .alert-error   { background:#3a1a1a; border:1px solid #a44; color:#f44; }

        @media(max-width:900px) {
            .admin-layout{grid-template-columns:1fr;}
            .admin-sidebar{height:auto;position:static;}
            .event-grid{grid-template-columns:repeat(2,1fr);}
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <div class="admin-title">🌍 Мировые события</div>
            <a href="?page=events" target="_blank" class="btn btn-gold">
                Страница событий →
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Активное событие -->
        <div class="admin-card">
            <div class="admin-card-header">🌍 Текущее событие</div>
            <div class="admin-card-body">
                <?php if ($active): ?>
                <div class="active-event-banner">
                    <span style="font-size:40px;"><?= $active['icon'] ?></span>
                    <div style="flex:1;">
                        <div style="font-size:16px;font-weight:bold;color:#d4a843;">
                            <?= htmlspecialchars($active['title']) ?>
                        </div>
                        <div style="font-size:12px;color:#888;margin-top:4px;">
                            Тип: <strong><?= $active['type'] ?></strong>
                            · Начало: <?= date('d.m.Y H:i', $active['started_at']) ?>
                        </div>
                        <div style="font-size:12px;color:#888;margin-top:2px;">
                            Конец: <?= date('d.m.Y H:i', $active['ends_at']) ?>
                            · <span style="color:#4f4;">Осталось:
                                <?= gmdate('H:i:s', max(0,$active['ends_at']-time())) ?>
                            </span>
                        </div>
                    </div>
                    <a href="?page=admin&action=stop_event&event_id=<?= $active['id'] ?>"
                       class="btn btn-danger"
                       onclick="return confirm('Завершить событие досрочно?')">
                        ⏹ Завершить
                    </a>
                </div>
                <?php else: ?>
                <div style="color:#666;text-align:center;padding:20px;">
                    Нет активных событий
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Запустить событие -->
        <div class="admin-card">
            <div class="admin-card-header">⚡ Запустить событие</div>
            <div class="admin-card-body">

                <?php
                $ev_types = [
                    'gold_rush'          => ['💰','Золотая лихорадка','6 ч.',   '#d4a843'],
                    'barbarian_invasion' => ['⚔', 'Нашествие варваров','12 ч.', '#f44'],
                    'tournament'         => ['🏆','Турнир воинов',    '7 дн.',  '#d4a843'],
                    'caravan'            => ['🚚','Торговый Караван', '4 ч.',   '#0dd'],
                    'plague'             => ['☠', 'Чума',             '3 ч.',   '#8a4'],
                    'blessing'           => ['✨','Благословение',    '4 ч.',   '#88f'],
                ];
                ?>

                <div class="event-grid">
                    <?php foreach ($ev_types as $type => $info):
                        $is_cur = ($active && $active['type'] === $type);
                    ?>
                    <div class="event-card <?= $is_cur?'current-active':'' ?>">
                        <div class="event-icon"><?= $info[0] ?></div>
                        <div class="event-name" style="color:<?= $info[3] ?>;">
                            <?= $info[1] ?>
                            <?= $is_cur ? '<span style="color:#4f4;font-size:10px;">● Активно</span>' : '' ?>
                        </div>
                        <div class="event-dur">⏱ <?= $info[2] ?></div>
                        <form method="POST" action="?page=admin&action=start_event">
                            <input type="hidden" name="type" value="<?= $type ?>">
                            <button type="submit"
                                    class="btn btn-primary"
                                    style="width:100%;"
                                    onclick="return confirm('Запустить «<?= $info[1] ?>»?\n(Текущее событие будет завершено)')">
                                ▶ Запустить
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Случайное -->
                <form method="POST" action="?page=admin&action=start_event" style="margin-top:5px;">
                    <input type="hidden" name="type" value="">
                    <button type="submit" class="btn btn-info"
                            onclick="return confirm('Запустить случайное событие?')">
                        🎲 Случайное событие
                    </button>
                </form>
            </div>
        </div>

        <!-- История -->
        <?php if (!empty($events)): ?>
        <div class="admin-card">
            <div class="admin-card-header">
                📋 История событий
                <span style="font-size:12px;color:#888;"><?= count($events) ?></span>
            </div>
            <table>
                <tr>
                    <th>Событие</th>
                    <th>Тип</th>
                    <th>Начало</th>
                    <th>Конец</th>
                    <th>Статус</th>
                </tr>
                <?php foreach ($events as $e):
                    $is_act = ($e['status']==='active' && $e['ends_at']>time());
                ?>
                <tr>
                    <td>
                        <?= $e['icon'] ?>
                        <?= htmlspecialchars($e['title']) ?>
                    </td>
                    <td style="color:#888;"><?= $e['type'] ?></td>
                    <td style="color:#888;"><?= date('d.m.Y H:i', $e['started_at']) ?></td>
                    <td style="color:#888;"><?= date('d.m.Y H:i', $e['ends_at']) ?></td>
                    <td>
                        <?php if ($is_act): ?>
                            <span style="color:#4f4;">● Активно</span>
                        <?php else: ?>
                            <span style="color:#555;">✓ Завершено</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

    </div>
</div>
</body>
</html>