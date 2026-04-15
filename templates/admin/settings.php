<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Настройки — Админ — <?= APP_NAME ?></title>
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
        .admin-card-header { background:#252525; padding:12px 16px; font-weight:bold; color:#d4a843; font-size:14px; }
        .admin-card-body { padding:20px; }
        .setting-row { display:flex; align-items:center; gap:15px; padding:12px 0; border-bottom:1px solid #222; }
        .setting-row:last-child { border-bottom:none; }
        .setting-info { flex:1; }
        .setting-key  { font-size:13px; color:#aaa; font-family:monospace; }
        .setting-desc { font-size:11px; color:#555; margin-top:3px; }
        .setting-input { width:180px; padding:8px; background:#252525; color:#ddd; border:1px solid #444; border-radius:4px; font-size:13px; }
        .setting-input:focus { border-color:#d4a843; outline:none; }
        .btn { display:inline-flex; align-items:center; gap:5px; padding:10px 25px; border-radius:4px; font-size:13px; border:none; cursor:pointer; transition:0.2s; }
        .btn-primary { background:#3a6a1a; color:#fff; }
        .btn-primary:hover { background:#4a8a2a; }
        .alert { padding:12px 16px; border-radius:6px; margin-bottom:15px; font-size:13px; }
        .alert-success { background:#1a3a1a; border:1px solid #4a4; color:#4f4; }
        .save-bar { position:sticky; bottom:0; background:#1a1a1a; border-top:2px solid #333; padding:15px 25px; display:flex; justify-content:space-between; align-items:center; margin-top:20px; }
        .save-bar-hint { font-size:12px; color:#666; }
        @media(max-width:900px) { .admin-layout{grid-template-columns:1fr;} .admin-sidebar{height:auto;position:static;} }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <div class="admin-title">⚙ Игровые настройки</div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php
        $descriptions = [
            'base_wood_per_hour'  => 'Базовое производство дерева в час (без зданий)',
            'base_stone_per_hour' => 'Базовое производство камня в час (без зданий)',
            'base_iron_per_hour'  => 'Базовое производство железа в час (без зданий)',
            'train_time_spear'    => 'Время тренировки копейщика (секунды)',
            'train_time_sword'    => 'Время тренировки мечника (секунды)',
            'train_time_axe'      => 'Время тренировки топорщика (секунды)',
            'train_time_scout'    => 'Время тренировки разведчика (секунды)',
            'train_time_light'    => 'Время тренировки лёгкой кав. (секунды)',
            'train_time_heavy'    => 'Время тренировки тяжёлой кав. (секунды)',
            'train_time_ram'      => 'Время тренировки тарана (секунды)',
            'train_time_catapult' => 'Время тренировки катапульты (секунды)',
        ];
        ?>

        <form method="POST" action="?page=admin&section=settings">
            <div class="admin-card">
                <div class="admin-card-header">⚙ Параметры игры</div>
                <div class="admin-card-body">
                    <?php foreach ($game_settings as $s):
                        // Пропускаем системные ключи событий/зелий
                        if (strpos($s['key'], 'active_event') === 0) continue;
                        if (strpos($s['key'], 'potion_')      === 0) continue;
                    ?>
                    <div class="setting-row">
                        <div class="setting-info">
                            <div class="setting-key"><?= htmlspecialchars($s['key']) ?></div>
                            <div class="setting-desc">
                                <?= $descriptions[$s['key']] ?? 'Игровой параметр' ?>
                            </div>
                        </div>
                        <input type="number"
                               class="setting-input"
                               name="settings[<?= htmlspecialchars($s['key']) ?>]"
                               value="<?= htmlspecialchars($s['value']) ?>"
                               min="0">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="save-bar">
                <div class="save-bar-hint">
                    💡 Изменения применяются сразу для новых действий
                </div>
                <button type="submit" class="btn btn-primary">
                    💾 Сохранить настройки
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>