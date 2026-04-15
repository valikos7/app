<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ панель — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#0f0f0f; color:#ddd; }

        .admin-layout {
            display:grid; grid-template-columns:220px 1fr; min-height:100vh;
        }

        /* Сайдбар */
        .admin-sidebar {
            background:#1a1a1a; border-right:2px solid #333;
            padding:0; position:sticky; top:0; height:100vh;
            overflow-y:auto;
        }
        .admin-logo {
            padding:18px 20px; font-size:16px; font-weight:bold;
            color:#d4a843; border-bottom:1px solid #333;
            display:flex; align-items:center; gap:8px;
        }
        .admin-nav { padding:10px 0; }
        .admin-nav a {
            display:flex; align-items:center; gap:8px;
            padding:10px 20px; color:#aaa; text-decoration:none;
            font-size:13px; transition:0.2s;
            border-left:3px solid transparent;
        }
        .admin-nav a:hover,
        .admin-nav a.active {
            background:#252525; color:#d4a843;
            border-left-color:#d4a843;
        }
        .nav-section {
            padding:10px 20px 4px; font-size:10px; color:#555;
            text-transform:uppercase; letter-spacing:1px; margin-top:5px;
        }
        .nav-divider {
            height:1px; background:#333; margin:8px 0;
        }

        /* Основной контент */
        .admin-main { padding:25px; overflow-y:auto; }

        .admin-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:25px;
            padding-bottom:15px; border-bottom:2px solid #333;
        }
        .admin-title { font-size:22px; color:#d4a843; font-weight:bold; }
        .admin-time  { font-size:12px; color:#555; }

        /* Статистика */
        .stats-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(160px,1fr));
            gap:12px; margin-bottom:25px;
        }
        .stat-card {
            background:#1a1a1a; border:1px solid #333;
            border-radius:8px; padding:18px; text-align:center;
            transition:0.2s; cursor:default;
        }
        .stat-card:hover { border-color:#d4a843; }
        .stat-icon   { font-size:30px; margin-bottom:8px; }
        .stat-value  { font-size:26px; font-weight:bold; color:#d4a843; }
        .stat-label  { font-size:11px; color:#888; margin-top:4px; }

        /* Карточки */
        .admin-card {
            background:#1a1a1a; border:1px solid #333;
            border-radius:8px; overflow:hidden; margin-bottom:20px;
        }
        .admin-card-header {
            background:#252525; padding:12px 16px;
            font-weight:bold; color:#d4a843; font-size:14px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .admin-card-body { padding:16px; }

        /* Таблицы */
        table { width:100%; border-collapse:collapse; }
        th {
            background:#252525; color:#d4a843;
            padding:10px 12px; text-align:left; font-size:12px;
            white-space:nowrap;
        }
        td { padding:9px 12px; border-bottom:1px solid #222; font-size:12px; }
        tr:hover td { background:#1f1f1f; }
        tr:last-child td { border-bottom:none; }

        /* Кнопки */
        .btn {
            display:inline-flex; align-items:center; gap:5px;
            padding:6px 14px; border-radius:4px; font-size:12px;
            border:none; cursor:pointer; transition:0.2s; text-decoration:none;
        }
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

        /* Формы */
        input[type="text"],input[type="number"],textarea,select {
            padding:8px 10px; background:#252525; color:#ddd;
            border:1px solid #444; border-radius:4px; font-size:13px;
            font-family:'Segoe UI',Arial; transition:0.2s;
        }
        input:focus,textarea:focus,select:focus {
            border-color:#d4a843; outline:none;
        }

        /* Алерты */
        .alert {
            padding:12px 16px; border-radius:6px;
            margin-bottom:15px; font-size:13px;
        }
        .alert-success { background:#1a3a1a; border:1px solid #4a4; color:#4f4; }
        .alert-error   { background:#3a1a1a; border:1px solid #a44; color:#f44; }
        .alert-warning { background:#3a2a0a; border:1px solid #8a6; color:#fa4; }

        /* Онлайн */
        .online-dot  { color:#0f0; font-size:10px; }
        .offline-dot { color:#555; font-size:10px; }

        /* Быстрые инструменты */
        .tools-grid {
            display:grid; grid-template-columns:1fr 1fr; gap:15px;
        }

        /* Форма генератора */
        .gen-form {
            display:flex; gap:10px; align-items:center; flex-wrap:wrap;
        }
        .gen-form input { width:80px; text-align:center; }

        /* Рассылка */
        .broadcast-form { display:flex; flex-direction:column; gap:10px; }
        .broadcast-form input,
        .broadcast-form textarea { width:100%; }
        .broadcast-form textarea { height:80px; resize:vertical; }

        /* Активное событие */
        .event-badge {
            display:inline-flex; align-items:center; gap:6px;
            padding:5px 12px; border-radius:6px;
            font-size:13px; font-weight:bold;
        }

        @media(max-width:900px) {
            .admin-layout { grid-template-columns:1fr; }
            .admin-sidebar { height:auto; position:static; }
            .tools-grid { grid-template-columns:1fr; }
            .stats-grid { grid-template-columns:repeat(2,1fr); }
        }
    </style>
</head>
<body>

<div class="admin-layout">

    <!-- Сайдбар -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Основной контент -->
    <div class="admin-main">

        <div class="admin-header">
            <div class="admin-title">📊 Дашборд</div>
            <div class="admin-time"><?= date('d.m.Y H:i:s') ?></div>
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
        <?php if (!empty($stats['active_event'])): ?>
        <div class="admin-card" style="border-color:#d4a843;margin-bottom:20px;">
            <div class="admin-card-body" style="display:flex;align-items:center;gap:15px;">
                <span style="font-size:32px;"><?= $stats['active_event']['icon'] ?></span>
                <div style="flex:1;">
                    <div style="font-size:14px;font-weight:bold;color:#d4a843;">
                        🌍 Активное событие: <?= htmlspecialchars($stats['active_event']['title']) ?>
                    </div>
                    <div style="font-size:12px;color:#888;margin-top:3px;">
                        До: <?= date('d.m.Y H:i', $stats['active_event']['ends_at']) ?>
                        · Осталось: <?= gmdate('H:i:s', max(0,$stats['active_event']['ends_at']-time())) ?>
                    </div>
                </div>
                <a href="?page=admin&action=stop_event&event_id=<?= $stats['active_event']['id'] ?>"
                   class="btn btn-danger"
                   onclick="return confirm('Завершить событие досрочно?')">
                    Завершить
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                <div class="stat-label">Игроков</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#0f0;">🟢</div>
                <div class="stat-value" style="color:#0f0;"><?= $stats['online'] ?></div>
                <div class="stat-label">Онлайн</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏘</div>
                <div class="stat-value"><?= number_format($stats['total_villages']) ?></div>
                <div class="stat-label">Деревень</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏚</div>
                <div class="stat-value"><?= number_format($stats['barbarian_villages']) ?></div>
                <div class="stat-label">Варваров</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏰</div>
                <div class="stat-value"><?= number_format($stats['total_alliances']) ?></div>
                <div class="stat-label">Альянсов</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚔</div>
                <div class="stat-value"><?= number_format($stats['active_movements']) ?></div>
                <div class="stat-label">Активных атак</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🦸</div>
                <div class="stat-value"><?= number_format($stats['alive_heroes']) ?></div>
                <div class="stat-label">Живых героев</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?= number_format($stats['today_actions']) ?></div>
                <div class="stat-label">Действий сегодня</div>
            </div>
        </div>

        <!-- Инструменты -->
        <div class="tools-grid">

            <!-- Последние регистрации -->
            <div class="admin-card">
                <div class="admin-card-header">
                    👥 Последние регистрации
                    <a href="?page=admin&section=players" class="btn btn-info">Все →</a>
                </div>
                <table>
                    <tr>
                        <th>Игрок</th>
                        <th>Очки</th>
                        <th>Статус</th>
                    </tr>
                    <?php foreach ($recent_users as $u):
                        $is_online = ($u['last_activity'] >= time()-300);
                    ?>
                    <tr>
                        <td>
                            <a href="?page=player&id=<?= $u['id'] ?>"
                               target="_blank"
                               style="color:#d4a843;text-decoration:none;">
                                <?= htmlspecialchars($u['username']) ?>
                            </a>
                            <?php if (!empty($u['is_admin'])): ?>
                                <span style="font-size:10px;color:#d4a843;">[A]</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($u['points']) ?></td>
                        <td>
                            <?php if ($is_online): ?>
                                <span class="online-dot">● Онлайн</span>
                            <?php else: ?>
                                <span class="offline-dot">●</span>
                                <span style="color:#555;font-size:10px;">
                                    <?= date('d.m H:i', $u['last_activity']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Топ игроков -->
            <div class="admin-card">
                <div class="admin-card-header">🏆 Топ игроков</div>
                <table>
                    <tr>
                        <th>#</th>
                        <th>Игрок</th>
                        <th>Очки</th>
                        <th>Дер.</th>
                    </tr>
                    <?php foreach ($top_users as $i => $u): ?>
                    <tr>
                        <td style="color:#666;"><?= $i+1 ?></td>
                        <td>
                            <a href="?page=player&id=<?= $u['id'] ?>"
                               target="_blank"
                               style="color:#d4a843;text-decoration:none;">
                                <?= htmlspecialchars($u['username']) ?>
                            </a>
                        </td>
                        <td style="color:#d4a843;font-weight:bold;">
                            <?= number_format($u['points']) ?>
                        </td>
                        <td><?= $u['villages'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Быстрые действия -->
        <div class="tools-grid">

            <!-- Генератор варваров -->
            <div class="admin-card">
                <div class="admin-card-header">🏚 Генератор варваров</div>
                <div class="admin-card-body">
                    <form method="POST"
                          action="?page=admin&action=generate_barbarians"
                          class="gen-form"
                          onsubmit="return confirm('Сгенерировать варваров?')">
                        <label style="color:#888;font-size:13px;">Количество:</label>
                        <input type="number" name="count" value="50" min="1" max="500">
                        <button type="submit" class="btn btn-warning">
                            🏚 Генерировать
                        </button>
                    </form>
                    <div style="font-size:11px;color:#555;margin-top:8px;">
                        Сейчас варваров: <strong style="color:#888;">
                            <?= number_format($stats['barbarian_villages']) ?>
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Рассылка -->
            <div class="admin-card">
                <div class="admin-card-header">✉ Рассылка всем игрокам</div>
                <div class="admin-card-body">
                    <form method="POST"
                          action="?page=admin&action=broadcast"
                          class="broadcast-form"
                          onsubmit="return confirm('Отправить всем игрокам?')">
                        <input type="text" name="subject" placeholder="Тема сообщения" required>
                        <textarea name="content" placeholder="Текст..." required></textarea>
                        <button type="submit" class="btn btn-primary">
                            ✉ Отправить всем
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Управление событиями -->
        <div class="admin-card">
            <div class="admin-card-header">
                🌍 Быстрый запуск события
                <a href="?page=admin&section=events" class="btn btn-gold">
                    Управление →
                </a>
            </div>
            <div class="admin-card-body">
                <?php
                $ev_list = [
                    'gold_rush'          => ['💰','Золотая лихорадка'],
                    'barbarian_invasion' => ['⚔','Нашествие варваров'],
                    'tournament'         => ['🏆','Турнир воинов'],
                    'caravan'            => ['🚚','Торговый Караван'],
                    'plague'             => ['☠','Чума'],
                    'blessing'           => ['✨','Благословение'],
                ];
                ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php foreach ($ev_list as $type => $info): ?>
                    <form method="POST" action="?page=admin&action=start_event" style="display:inline;">
                        <input type="hidden" name="type" value="<?= $type ?>">
                        <button type="submit" class="btn btn-gold"
                                onclick="return confirm('Запустить «<?= $info[1] ?>»?')">
                            <?= $info[0] ?> <?= $info[1] ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                    <form method="POST" action="?page=admin&action=start_event" style="display:inline;">
                        <input type="hidden" name="type" value="">
                        <button type="submit" class="btn btn-info"
                                onclick="return confirm('Случайное событие?')">
                            🎲 Случайное
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>