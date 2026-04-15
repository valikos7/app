<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($alliance['name']) ?> — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }

        .container { max-width:1000px; margin:20px auto; padding:0 15px; }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .card-body { padding:20px; }

        /* Шапка альянса */
        .alliance-hero {
            display:flex; gap:20px; align-items:flex-start;
            flex-wrap:wrap;
        }
        .alliance-tag-big {
            background:#3a2c10; border:3px solid #d4a843;
            border-radius:10px; padding:15px 25px;
            font-size:36px; font-weight:bold; color:#d4a843;
            text-align:center; min-width:100px; flex-shrink:0;
        }
        .alliance-info { flex:1; }
        .alliance-name {
            font-size:26px; font-weight:bold; color:#d4a843; margin-bottom:8px;
        }
        .alliance-meta {
            color:#888; font-size:13px; margin-bottom:5px;
            display:flex; gap:20px; flex-wrap:wrap;
        }
        .alliance-meta span { display:flex; align-items:center; gap:5px; }
        .alliance-desc {
            margin-top:12px; padding:12px 15px;
            background:#1a1a0a; border-left:3px solid #8b6914;
            border-radius:0 6px 6px 0; font-size:13px;
            color:#ccc; line-height:1.6;
        }

        /* Кнопки действий */
        .alliance-actions {
            display:flex; gap:8px; flex-wrap:wrap; margin-top:15px;
        }
        .btn {
            display:inline-block; padding:8px 16px;
            border-radius:5px; font-size:13px;
            text-decoration:none; border:none;
            cursor:pointer; transition:0.2s;
        }
        .btn-gold    { background:#5a4a1a; color:#d4a843; border:1px solid #8b6914; }
        .btn-gold:hover    { background:#7a6a2a; }
        .btn-green   { background:#1a5a1a; color:#4f4; border:1px solid #2a8a2a; }
        .btn-green:hover   { background:#2a7a2a; }
        .btn-red     { background:#5a1a1a; color:#f66; border:1px solid #8a1a1a; }
        .btn-red:hover     { background:#7a2a2a; }
        .btn-blue    { background:#1a1a5a; color:#88f; border:1px solid #2a2a8a; }
        .btn-blue:hover    { background:#2a2a7a; }
        .btn-purple  { background:#3a1a5a; color:#c8a; border:1px solid #6a2a8a; }
        .btn-purple:hover  { background:#5a2a7a; }

        /* Статистика */
        .stats-row {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(150px,1fr));
            gap:10px; margin-bottom:20px;
        }
        .stat-box {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:12px; text-align:center;
        }
        .stat-box-icon  { font-size:24px; margin-bottom:5px; }
        .stat-box-value { font-size:20px; font-weight:bold; color:#d4a843; }
        .stat-box-label { font-size:11px; color:#888; margin-top:3px; }

        /* Таблица участников */
        table { width:100%; border-collapse:collapse; }
        th {
            background:#3a2c10; color:#d4a843;
            padding:10px 12px; text-align:left; font-size:13px;
        }
        td { padding:10px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#2a2010; }

        .role-badge {
            display:inline-block; padding:2px 8px;
            border-radius:10px; font-size:11px; font-weight:bold;
        }
        .role-leader  { background:#5a4a1a; color:#d4a843; border:1px solid #8b6914; }
        .role-officer { background:#1a1a5a; color:#88f;    border:1px solid #2a2a8a; }
        .role-member  { background:#2a2a2a; color:#888;    border:1px solid #444; }

        .online-badge  { color:#0f0; font-size:11px; }
        .offline-badge { color:#555; font-size:11px; }

        /* Описание */
        .desc-empty { color:#666; font-style:italic; }

        /* Алерты */
        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        @media(max-width:600px) {
            .alliance-hero  { flex-direction:column; }
            .alliance-actions { flex-direction:column; }
            .stats-row      { grid-template-columns:repeat(2,1fr); }
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

    <!-- Шапка альянса -->
    <div class="card">
        <div class="card-header">
            🏰 Информация об альянсе
            <?php if ($my_role === 'leader'): ?>
                <span style="font-size:12px; color:#d4a843;">
                    👑 Вы — Лидер
                </span>
            <?php elseif ($my_role === 'officer'): ?>
                <span style="font-size:12px; color:#88f;">
                    ⭐ Вы — Офицер
                </span>
            <?php elseif ($my_role === 'member'): ?>
                <span style="font-size:12px; color:#888;">
                    👤 Вы — Участник
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body">

            <div class="alliance-hero">
                <div class="alliance-tag-big">
                    [<?= htmlspecialchars($alliance['tag']) ?>]
                </div>
                <div class="alliance-info">
                    <div class="alliance-name">
                        <?= htmlspecialchars($alliance['name']) ?>
                    </div>
                    <div class="alliance-meta">
                        <span>
                            👑 Лидер:
                            <a href="?page=player&id=<?= $alliance['leader_id'] ?>"
                               style="color:#d4a843; text-decoration:none;">
                                <?= htmlspecialchars($alliance['leader_name'] ?? '?') ?>
                            </a>
                        </span>
                        <span>
                            👥 Участников: <strong><?= $alliance['members_count'] ?></strong>
                        </span>
                        <span>
                            ⭐ Очков: <strong style="color:#d4a843;">
                                <?= number_format($alliance['points']) ?>
                            </strong>
                        </span>
                        <span>
                            📅 Основан: <?= date('d.m.Y', $alliance['created_at']) ?>
                        </span>
                    </div>

                    <?php if (!empty($alliance['description'])): ?>
                        <div class="alliance-desc">
                            <?= nl2br(htmlspecialchars($alliance['description'])) ?>
                        </div>
                    <?php else: ?>
                        <div class="alliance-desc desc-empty">
                            Описание не указано
                        </div>
                    <?php endif; ?>

                    <!-- Кнопки -->
                    <div class="alliance-actions">
                        <?php if ($my_role): ?>
                            <!-- Уже в альянсе -->
                            <a href="?page=alliance_chat" class="btn btn-gold">
                                💬 Чат альянса
                            </a>
                            <a href="?page=diplomacy" class="btn btn-purple">
                                🤝 Дипломатия
                            </a>
                            <?php if ($my_role === 'leader' || $my_role === 'officer'): ?>
                                <a href="?page=messages&action=compose"
                                   class="btn btn-blue">
                                    ✉ Написать участникам
                                </a>
                            <?php endif; ?>
                            <?php if ($my_role !== 'leader'): ?>
                                <a href="?page=alliance&action=leave"
                                   class="btn btn-red"
                                   onclick="return confirm('Покинуть альянс?')">
                                    🚪 Покинуть альянс
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- Не в альянсе -->
                            <a href="?page=alliance&action=join&id=<?= $alliance['id'] ?>"
                               class="btn btn-green"
                               onclick="return confirm('Вступить в альянс?')">
                                ✅ Вступить в альянс
                            </a>
                        <?php endif; ?>
                        <a href="?page=alliances" class="btn btn-gold">
                            ← Все альянсы
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-box-icon">👥</div>
            <div class="stat-box-value"><?= count($members) ?></div>
            <div class="stat-box-label">Участников</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">⭐</div>
            <div class="stat-box-value">
                <?= number_format($alliance['points']) ?>
            </div>
            <div class="stat-box-label">Очков альянса</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon" style="color:#0f0;">🟢</div>
            <div class="stat-box-value" style="color:#0f0;">
                <?= count(array_filter($members,
                    fn($m) => $m['last_activity'] >= time() - 300)) ?>
            </div>
            <div class="stat-box-label">Онлайн</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">🏘</div>
            <div class="stat-box-value">
                <?= array_sum(array_column($members, 'villages')) ?>
            </div>
            <div class="stat-box-label">Всего деревень</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">🏆</div>
            <div class="stat-box-value">
                <?php
                // Средние очки
                $total_pts = array_sum(array_column($members, 'points'));
                $avg = count($members) > 0
                    ? round($total_pts / count($members))
                    : 0;
                echo number_format($avg);
                ?>
            </div>
            <div class="stat-box-label">Среднее очков</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-icon">📅</div>
            <div class="stat-box-value" style="font-size:14px;">
                <?= date('d.m.Y', $alliance['created_at']) ?>
            </div>
            <div class="stat-box-label">Дата основания</div>
        </div>
    </div>

    <!-- Список участников -->
    <div class="card">
        <div class="card-header">
            👥 Участники альянса
            <span style="font-size:12px; color:#aaa;">
                <?= count($members) ?> чел.
            </span>
        </div>
        <div class="card-body" style="padding:0;">
            <table>
                <tr>
                    <th>#</th>
                    <th>Игрок</th>
                    <th>Роль</th>
                    <th>Очки</th>
                    <th>Деревень</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
                <?php foreach ($members as $i => $m):
                    $is_online = ($m['last_activity'] >= time() - 300);
                    $is_me     = ($m['user_id'] == $_SESSION['user_id']);

                    $role_labels = [
                        'leader'  => '<span class="role-badge role-leader">👑 Лидер</span>',
                        'officer' => '<span class="role-badge role-officer">⭐ Офицер</span>',
                        'member'  => '<span class="role-badge role-member">👤 Участник</span>',
                    ];
                ?>
                <tr style="<?= $is_me ? 'background:#1a2a1a;' : '' ?>">
                    <td style="color:#666; font-size:12px;">
                        <?= $i + 1 ?>
                    </td>
                    <td>
                        <a href="?page=player&id=<?= $m['user_id'] ?>"
                           style="color:<?= $is_me ? '#4f4' : '#d4a843' ?>;
                                  text-decoration:none; font-weight:bold;">
                            <?= htmlspecialchars($m['username']) ?>
                        </a>
                        <?php if ($is_me): ?>
                            <span style="color:#888; font-size:11px;">(вы)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $role_labels[$m['role']] ?? $m['role'] ?>
                    </td>
                    <td style="color:#d4a843; font-weight:bold;">
                        <?= number_format($m['points']) ?>
                    </td>
                    <td><?= $m['villages'] ?></td>
                    <td>
                        <?php if ($is_online): ?>
                            <span class="online-badge">● Онлайн</span>
                        <?php else: ?>
                            <span class="offline-badge">
                                ● <?= date('d.m H:i', $m['last_activity']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$is_me): ?>
                            <a href="?page=messages&action=compose&to=<?= urlencode($m['username']) ?>"
                               class="btn btn-gold"
                               style="padding:4px 10px; font-size:11px;">
                                ✉
                            </a>
                        <?php endif; ?>

                        <?php if (($my_role === 'leader' || $my_role === 'officer')
                               && !$is_me
                               && $m['role'] !== 'leader'): ?>
                            <!-- Повысить до офицера -->
                            <?php if ($my_role === 'leader' && $m['role'] === 'member'): ?>
                            <a href="?page=alliance&action=promote&id=<?= $alliance['id'] ?>&user=<?= $m['user_id'] ?>"
                               class="btn btn-blue"
                               style="padding:4px 10px; font-size:11px;"
                               onclick="return confirm('Повысить до офицера?')">
                                ⭐
                            </a>
                            <?php endif; ?>

                            <!-- Исключить -->
                            <a href="?page=alliance&action=kick&id=<?= $alliance['id'] ?>&kick=<?= $m['user_id'] ?>"
                               class="btn btn-red"
                               style="padding:4px 10px; font-size:11px;"
                               onclick="return confirm('Исключить из альянса?')">
                                ✕
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

</div>

</body>
</html>