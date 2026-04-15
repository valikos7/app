<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Альянсы — <?= APP_NAME ?></title>
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

        table { width:100%; border-collapse:collapse; }
        th {
            background:#3a2c10; color:#d4a843;
            padding:10px 12px; text-align:left; font-size:13px;
        }
        td { padding:10px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#2a2010; }

        .btn {
            display:inline-block; padding:6px 16px;
            background:#5a4a1a; color:#ddd;
            text-decoration:none; border-radius:4px;
            font-size:12px; border:1px solid #8b6914; transition:0.2s;
        }
        .btn:hover { background:#7a6a2a; }
        .btn-green { background:#1a5a1a; border-color:#4a8a4a; }
        .btn-green:hover { background:#2a7a2a; }

        .alliance-tag {
            display:inline-block; background:#3a2c10;
            border:1px solid #8b6914; border-radius:4px;
            padding:2px 8px; color:#d4a843; font-weight:bold;
            font-size:12px; margin-right:6px;
        }

        .my-alliance-card {
            background:#1a2a1a; border:2px solid #4a8a4a;
            border-radius:8px; padding:20px; margin-bottom:15px;
        }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
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
        <a href="?page=messages">Сообщения</a>
    </div>
    <div><a href="?page=logout" style="color:#c00;">Выход</a></div>
</div>

<div class="container">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Мой альянс -->
    <?php if ($my_alliance): ?>
    <div class="my-alliance-card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <span class="alliance-tag"><?= htmlspecialchars($my_alliance['tag']) ?></span>
                <strong style="font-size:18px; color:#d4a843;">
                    <?= htmlspecialchars($my_alliance['name']) ?>
                </strong>
                <span style="color:#888; font-size:13px; margin-left:10px;">
                    (вы состоите в этом альянсе)
                </span>
            </div>
            <div style="display:flex; gap:8px;">
                <a href="?page=alliance&id=<?= $my_alliance['id'] ?>" class="btn">
                    Управление
                </a>
                <a href="?page=alliance&action=leave" class="btn"
                   style="background:#5a1a1a; border-color:#8b1a1a;"
                   onclick="return confirm('Покинуть альянс?')">
                    Покинуть
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-header">⚔ Создать альянс</div>
        <div class="card-body" style="display:flex; gap:15px; align-items:center;">
            <div style="flex:1; color:#aaa; font-size:13px;">
                У вас нет альянса. Создайте свой или вступите в существующий!
            </div>
            <a href="?page=alliance&action=create" class="btn btn-green">
                + Создать альянс
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Список альянсов -->
    <div class="card">
        <div class="card-header">
            🏰 Все альянсы
            <span style="font-size:12px; color:#aaa;">
                Топ-50 по очкам
            </span>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($alliances)): ?>
                <div style="padding:30px; text-align:center; color:#666;">
                    Альянсов пока нет. Будьте первым!
                </div>
            <?php else: ?>
            <table>
                <tr>
                    <th>#</th>
                    <th>Альянс</th>
                    <th>Лидер</th>
                    <th>Игроков</th>
                    <th>Очки</th>
                    <th>Действия</th>
                </tr>
                <?php foreach ($alliances as $i => $a): ?>
                <tr>
                    <td style="color:#888;"><?= $i + 1 ?></td>
                    <td>
                        <span class="alliance-tag">
                            <?= htmlspecialchars($a['tag']) ?>
                        </span>
                        <a href="?page=alliance&id=<?= $a['id'] ?>"
                           style="color:#d4a843; text-decoration:none;">
                            <?= htmlspecialchars($a['name']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($a['leader_name'] ?? '?') ?></td>
                    <td><?= $a['members_count'] ?></td>
                    <td style="color:#d4a843; font-weight:bold;">
                        <?= number_format($a['points']) ?>
                    </td>
                    <td>
                        <a href="?page=alliance&id=<?= $a['id'] ?>" class="btn">
                            Просмотр
                        </a>
                        <?php if (!$my_alliance): ?>
                        <a href="?page=alliance&action=join&id=<?= $a['id'] ?>"
                           class="btn btn-green"
                           onclick="return confirm('Вступить в альянс?')">
                            Вступить
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>
</body>
</html>