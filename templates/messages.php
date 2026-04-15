<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сообщения — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .top-bar {
            background:#2c1f0e; border-bottom:3px solid #8b6914;
            padding:8px 20px; display:flex;
            justify-content:space-between; align-items:center;
        }
        .top-bar a { color:#d4a843; text-decoration:none; margin:0 10px; font-size:14px; }
        .container {
            max-width:900px; margin:20px auto; padding:0 15px;
            display:grid; grid-template-columns:220px 1fr; gap:15px;
        }
        .sidebar-menu .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden;
        }
        .menu-item {
            display:block; padding:12px 15px;
            color:#aaa; text-decoration:none;
            border-bottom:1px solid #333; transition:0.2s;
            font-size:13px;
        }
        .menu-item:hover, .menu-item.active {
            background:#3a2c10; color:#d4a843;
        }
        .unread-badge {
            float:right; background:#c00; color:#fff;
            border-radius:10px; padding:1px 7px; font-size:11px;
        }
        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843;
            display:flex; justify-content:space-between; align-items:center;
        }
        .msg-item {
            padding:12px 15px; border-bottom:1px solid #333;
            cursor:pointer; transition:0.2s;
            display:flex; gap:12px; align-items:center;
        }
        .msg-item:hover { background:#2a2010; }
        .msg-item.unread { background:#1a2a1a; border-left:3px solid #4a4; }
        .msg-icon { font-size:20px; min-width:25px; text-align:center; }
        .msg-info { flex:1; }
        .msg-subject {
            font-weight:bold; font-size:14px; color:#ddd;
        }
        .msg-item.unread .msg-subject { color:#4f4; }
        .msg-from { font-size:11px; color:#888; margin-top:3px; }
        .msg-time { font-size:11px; color:#666; white-space:nowrap; }
        .btn {
            display:inline-block; padding:8px 20px;
            background:#5a4a1a; color:#ddd;
            text-decoration:none; border-radius:4px;
            font-size:13px; border:1px solid #8b6914; transition:0.2s;
        }
        .btn:hover { background:#7a6a2a; }
        .btn-green { background:#1a5a1a; border-color:#4a8a4a; }
        .empty { padding:30px; text-align:center; color:#666; }

        @media(max-width:600px) {
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
        <a href="?page=messages">
            Сообщения
            <?php if (($unread ?? 0) > 0): ?>
                <span style="background:#c00;color:#fff;border-radius:10px;
                             padding:1px 6px;font-size:10px;">
                    <?= $unread ?>
                </span>
            <?php endif; ?>
        </a>
    </div>
    <div><a href="?page=logout" style="color:#c00;">Выход</a></div>
</div>

<div class="container">

    <!-- Боковое меню -->
    <div class="sidebar-menu">
        <div class="card">
            <a href="?page=messages&action=compose" class="menu-item btn-green"
               style="text-align:center; display:block;">
                ✉ Написать
            </a>
            <a href="?page=messages"
               class="menu-item <?= $folder === 'inbox' ? 'active' : '' ?>">
                📥 Входящие
                <?php if (($unread ?? 0) > 0): ?>
                    <span class="unread-badge"><?= $unread ?></span>
                <?php endif; ?>
            </a>
            <a href="?page=messages&folder=sent"
               class="menu-item <?= $folder === 'sent' ? 'active' : '' ?>">
                📤 Отправленные
            </a>
        </div>
    </div>

    <!-- Список сообщений -->
    <div>
        <div class="card">
            <div class="card-header">
                <?= $folder === 'inbox' ? '📥 Входящие' : '📤 Отправленные' ?>
                <a href="?page=messages&action=compose" class="btn btn-green">
                    + Написать
                </a>
            </div>

            <?php if (empty($messages)): ?>
                <div class="empty">
                    <div style="font-size:48px; margin-bottom:10px;">📭</div>
                    <?= $folder === 'inbox' ? 'Нет входящих сообщений' : 'Нет отправленных' ?>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $m):
                    $is_unread = (!$m['is_read'] && $folder === 'inbox');
                    $time_diff = time() - $m['time'];
                    if ($time_diff < 60)       $time_str = "только что";
                    elseif ($time_diff < 3600) $time_str = floor($time_diff/60) . " мин.";
                    elseif ($time_diff < 86400)$time_str = floor($time_diff/3600) . " ч.";
                    else                       $time_str = date('d.m.Y', $m['time']);
                ?>
                <div class="msg-item <?= $is_unread ? 'unread' : '' ?>"
                     onclick="location='?page=messages&action=view&msg_id=<?= $m['id'] ?>'">
                    <div class="msg-icon">
                        <?= $is_unread ? '📩' : '📨' ?>
                    </div>
                    <div class="msg-info">
                        <div class="msg-subject">
                            <?= htmlspecialchars($m['subject']) ?>
                        </div>
                        <div class="msg-from">
                            <?php if ($folder === 'inbox'): ?>
                                От: <?= htmlspecialchars($m['from_name'] ?? '?') ?>
                            <?php else: ?>
                                Кому: <?= htmlspecialchars($m['to_name'] ?? '?') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="msg-time"><?= $time_str ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
</body>
</html>