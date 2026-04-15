<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Объявления — Админ — <?= APP_NAME ?></title>
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
        input[type="text"],textarea { width:100%; padding:10px; background:#252525; color:#ddd; border:1px solid #444; border-radius:4px; font-size:13px; font-family:'Segoe UI',Arial; margin-bottom:12px; }
        input:focus,textarea:focus { border-color:#d4a843; outline:none; }
        textarea { height:120px; resize:vertical; }
        .btn { display:inline-flex; align-items:center; gap:5px; padding:8px 18px; border-radius:4px; font-size:13px; border:none; cursor:pointer; transition:0.2s; text-decoration:none; }
        .btn-primary { background:#3a6a1a; color:#fff; }
        .btn-primary:hover { background:#4a8a2a; }
        .btn-danger { background:#6a1a1a; color:#fff; }
        .btn-danger:hover { background:#8a2a2a; }
        .ann-item { background:#252525; border:1px solid #333; border-radius:8px; padding:16px; margin-bottom:12px; }
        .ann-item-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; }
        .ann-title { font-size:15px; font-weight:bold; color:#d4a843; }
        .ann-meta  { font-size:11px; color:#666; margin-bottom:8px; }
        .ann-content { font-size:13px; color:#aaa; line-height:1.6; white-space:pre-wrap; }
        .alert { padding:12px 16px; border-radius:6px; margin-bottom:15px; font-size:13px; }
        .alert-success { background:#1a3a1a; border:1px solid #4a4; color:#4f4; }
        .alert-error   { background:#3a1a1a; border:1px solid #a44; color:#f44; }
        @media(max-width:900px) { .admin-layout{grid-template-columns:1fr;} .admin-sidebar{height:auto;position:static;} }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <div class="admin-title">📢 Объявления</div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Форма создания -->
        <div class="admin-card">
            <div class="admin-card-header">📝 Новое объявление</div>
            <div class="admin-card-body">
                <form method="POST" action="?page=admin&section=announcements">
                    <label style="color:#888;font-size:12px;display:block;margin-bottom:4px;">
                        Заголовок:
                    </label>
                    <input type="text" name="title" placeholder="Заголовок объявления" required>

                    <label style="color:#888;font-size:12px;display:block;margin-bottom:4px;">
                        Текст:
                    </label>
                    <textarea name="content" placeholder="Текст объявления..." required></textarea>

                    <label style="color:#888;font-size:12px;display:block;margin-bottom:4px;">
                        Автор:
                    </label>
                    <input type="text" name="author" placeholder="Автор" value="Администрация">

                    <button type="submit" class="btn btn-primary">
                        📢 Опубликовать
                    </button>
                </form>
            </div>
        </div>

        <!-- Список -->
        <div class="admin-card">
            <div class="admin-card-header">
                Все объявления
                <span style="font-size:12px;color:#888;">
                    <?= count($announcements) ?> шт.
                </span>
            </div>
            <div class="admin-card-body">
                <?php if (empty($announcements)): ?>
                    <div style="color:#666;text-align:center;padding:20px;">
                        Объявлений нет
                    </div>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                    <div class="ann-item">
                        <div class="ann-item-header">
                            <div>
                                <div class="ann-title">
                                    <?= htmlspecialchars($a['title']) ?>
                                </div>
                                <div class="ann-meta">
                                    ✍ <?= htmlspecialchars($a['author'] ?? 'Администрация') ?>
                                    · 🕐 <?= date('d.m.Y H:i', $a['time']) ?>
                                </div>
                            </div>
                            <a href="?page=admin&section=announcements&delete_ann=<?= $a['id'] ?>"
                               class="btn btn-danger"
                               style="padding:5px 10px;font-size:11px;"
                               onclick="return confirm('Удалить объявление?')">
                                🗑 Удалить
                            </a>
                        </div>
                        <div class="ann-content">
                            <?= htmlspecialchars($a['content']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
