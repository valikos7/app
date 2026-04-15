<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Игроки — Админ — <?= APP_NAME ?></title>
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
        .admin-card-body { padding:16px; }
        table { width:100%; border-collapse:collapse; }
        th { background:#252525; color:#d4a843; padding:10px 12px; text-align:left; font-size:12px; white-space:nowrap; }
        td { padding:9px 12px; border-bottom:1px solid #222; font-size:12px; }
        tr:hover td { background:#1f1f1f; }
        tr:last-child td { border-bottom:none; }
        .btn { display:inline-flex; align-items:center; gap:4px; padding:5px 10px; border-radius:4px; font-size:11px; border:none; cursor:pointer; transition:0.2s; text-decoration:none; }
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
        input[type="text"],input[type="number"],input[type="password"],select,textarea {
            padding:8px 10px; background:#252525; color:#ddd; border:1px solid #444; border-radius:4px; font-size:13px; font-family:'Segoe UI',Arial;
        }
        input:focus,select:focus { border-color:#d4a843; outline:none; }
        .badge { display:inline-block; padding:2px 7px; border-radius:8px; font-size:10px; font-weight:bold; }
        .badge-admin  { background:#5a4a1a; color:#d4a843; }
        .badge-banned { background:#5a1a1a; color:#f44; }
        .online-dot  { color:#0f0; font-size:10px; }
        .offline-dot { color:#555; font-size:10px; }
        .search-form { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
        .search-form input { flex:1; min-width:200px; }
        .pagination { display:flex; gap:5px; margin-top:15px; flex-wrap:wrap; }
        .pagination a { padding:6px 12px; background:#1a1a1a; color:#aaa; text-decoration:none; border-radius:4px; border:1px solid #333; font-size:12px; }
        .pagination a.active { background:#5a4a1a; color:#d4a843; border-color:#8b6914; }
        .pagination a:hover  { background:#252525; }
        .alert { padding:12px 16px; border-radius:6px; margin-bottom:15px; font-size:13px; }
        .alert-success { background:#1a3a1a; border:1px solid #4a4; color:#4f4; }
        .alert-error   { background:#3a1a1a; border:1px solid #a44; color:#f44; }
        .modal { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:1000; align-items:center; justify-content:center; padding:20px; }
        .modal.active { display:flex; }
        .modal-box { background:#1a1a1a; border:2px solid #444; border-radius:10px; padding:25px; min-width:320px; max-width:450px; width:100%; }
        .modal-box h3 { color:#d4a843; margin-bottom:15px; font-size:16px; }
        .modal-close { float:right; background:none; border:none; color:#888; cursor:pointer; font-size:18px; }
        .modal-form { display:flex; flex-direction:column; gap:10px; }
        .modal-form input { width:100%; }
        @media(max-width:900px) { .admin-layout{grid-template-columns:1fr;} .admin-sidebar{height:auto;position:static;} }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <div class="admin-title">👥 Управление игроками</div>
            <span style="color:#888;font-size:13px;">
                Всего: <?= number_format($total) ?>
            </span>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Поиск -->
        <form class="search-form" method="GET">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="section" value="players">
            <input type="text" name="search"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Поиск по имени или email...">
            <button type="submit" class="btn btn-gold">🔍 Найти</button>
            <?php if ($search): ?>
            <a href="?page=admin&section=players" class="btn btn-warning">✕ Сброс</a>
            <?php endif; ?>
        </form>

        <div class="admin-card">
            <div class="admin-card-header">
                Список игроков
                <span style="font-size:12px;color:#888;">
                    Стр. <?= $page_num ?> / <?= $total_pages ?>
                </span>
            </div>
            <div style="overflow-x:auto;">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Игрок</th>
                    <th>Email</th>
                    <th>Очки</th>
                    <th>Дер.</th>
                    <th>Альянс</th>
                    <th>Последний вход</th>
                    <th>Действия</th>
                </tr>
                <?php foreach ($players as $p):
                    $is_online = ($p['last_activity'] >= time()-300);
                ?>
                <tr>
                    <td style="color:#555;"><?= $p['id'] ?></td>
                    <td>
                        <a href="?page=player&id=<?= $p['id'] ?>"
                           target="_blank"
                           style="color:#d4a843;text-decoration:none;font-weight:bold;">
                            <?= htmlspecialchars($p['username']) ?>
                        </a>
                        <?php if (!empty($p['is_admin'])): ?>
                            <span class="badge badge-admin">ADMIN</span>
                        <?php endif; ?>
                        <?php if (!empty($p['is_banned'])): ?>
                            <span class="badge badge-banned">БАН</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#555;font-size:11px;">
                        <?= htmlspecialchars($p['email']) ?>
                    </td>
                    <td style="color:#d4a843;">
                        <?= number_format($p['points']) ?>
                    </td>
                    <td><?= $p['villages'] ?></td>
                    <td style="color:#888;font-size:11px;">
                        <?= htmlspecialchars($p['alliance_name'] ?? '—') ?>
                    </td>
                    <td>
                        <?php if ($is_online): ?>
                            <span class="online-dot">● Онлайн</span>
                        <?php else: ?>
                            <span class="offline-dot">●</span>
                            <span style="color:#555;font-size:11px;">
                                <?= date('d.m H:i', $p['last_activity']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <!-- Ресурсы -->
                            <button class="btn btn-warning"
                                    onclick="openResources(<?= $p['id'] ?>,'<?= htmlspecialchars($p['username']) ?>')"
                                    title="Добавить ресурсы">
                                💰
                            </button>
                            <!-- Пароль -->
                            <button class="btn btn-info"
                                    onclick="openPassword(<?= $p['id'] ?>,'<?= htmlspecialchars($p['username']) ?>')"
                                    title="Сменить пароль">
                                🔑
                            </button>
                            <!-- Бан/разбан -->
                            <?php if (empty($p['is_banned'])): ?>
                            <form method="POST" action="?page=admin&action=player_action" style="display:inline;">
                                <input type="hidden" name="action" value="ban">
                                <input type="hidden" name="player_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-danger" title="Забанить"
                                        onclick="return confirm('Забанить игрока?')">
                                    🚫
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" action="?page=admin&action=player_action" style="display:inline;">
                                <input type="hidden" name="action" value="unban">
                                <input type="hidden" name="player_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-primary" title="Разбанить">
                                    ✅
                                </button>
                            </form>
                            <?php endif; ?>
                            <!-- Удалить -->
                            <form method="POST" action="?page=admin&action=player_action" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="player_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-danger" title="Удалить"
                                        onclick="return confirm('Удалить игрока навсегда?')">
                                    🗑
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            </div>

            <!-- Пагинация -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination" style="padding:12px 16px;">
                <?php if ($page_num > 1): ?>
                <a href="?page=admin&section=players&p=<?= $page_num-1 ?>&search=<?= urlencode($search) ?>">
                    ← Пред.
                </a>
                <?php endif; ?>

                <?php
                $start = max(1, $page_num-2);
                $end   = min($total_pages, $page_num+2);
                for ($i=$start;$i<=$end;$i++): ?>
                <a href="?page=admin&section=players&p=<?= $i ?>&search=<?= urlencode($search) ?>"
                   class="<?= $i==$page_num?'active':'' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($page_num < $total_pages): ?>
                <a href="?page=admin&section=players&p=<?= $page_num+1 ?>&search=<?= urlencode($search) ?>">
                    След. →
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модалка: Ресурсы -->
<div class="modal" id="modalResources">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modalResources')">✕</button>
        <h3>💰 Добавить ресурсы</h3>
        <p id="resourcesTarget" style="color:#888;font-size:13px;margin-bottom:15px;"></p>
        <form method="POST" action="?page=admin&action=player_action" class="modal-form">
            <input type="hidden" name="action" value="add_resources">
            <input type="hidden" name="player_id" id="resourcesPlayerId">
            <input type="number" name="wood"  placeholder="🪵 Дерево"  value="5000">
            <input type="number" name="stone" placeholder="🪨 Камень"  value="5000">
            <input type="number" name="iron"  placeholder="⛏ Железо"  value="5000">
            <button type="submit" class="btn btn-primary" style="padding:10px;">
                ✅ Добавить ресурсы
            </button>
        </form>
    </div>
</div>

<!-- Модалка: Пароль -->
<div class="modal" id="modalPassword">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modalPassword')">✕</button>
        <h3>🔑 Сменить пароль</h3>
        <p id="passwordTarget" style="color:#888;font-size:13px;margin-bottom:15px;"></p>
        <form method="POST" action="?page=admin&action=player_action" class="modal-form">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="player_id" id="passwordPlayerId">
            <input type="text" name="new_password" placeholder="Новый пароль (мин. 6 символов)">
            <button type="submit" class="btn btn-warning" style="padding:10px;">
                🔑 Сменить пароль
            </button>
        </form>
    </div>
</div>

<script>
function openResources(id, name) {
    document.getElementById('resourcesPlayerId').value = id;
    document.getElementById('resourcesTarget').textContent = 'Игрок: ' + name;
    document.getElementById('modalResources').classList.add('active');
}
function openPassword(id, name) {
    document.getElementById('passwordPlayerId').value = id;
    document.getElementById('passwordTarget').textContent = 'Игрок: ' + name;
    document.getElementById('modalPassword').classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => {
        if (e.target === m) m.classList.remove('active');
    });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
    }
});
</script>

</body>
</html>