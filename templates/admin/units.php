<?php require_once __DIR__ . '/../../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Юниты — Админ — <?= APP_NAME ?></title>
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

        .unit-card { background:#1a1a1a; border:1px solid #333; border-radius:8px; margin-bottom:15px; overflow:hidden; }
        .unit-card-header { background:#252525; padding:12px 20px; display:flex; align-items:center; gap:12px; border-bottom:1px solid #333; }
        .unit-icon { font-size:28px; }
        .unit-name { font-size:16px; font-weight:bold; color:#d4a843; }
        .unit-type-badge { padding:3px 10px; border-radius:10px; font-size:11px; }
        .badge-infantry { background:#3a1a1a; color:#f44; border:1px solid #8a1a1a; }
        .badge-cavalry  { background:#1a1a3a; color:#88f; border:1px solid #2a2a8a; }
        .unit-card-body { padding:20px; }

        .fields-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(160px,1fr));
            gap:12px;
        }
        .field-group { display:flex; flex-direction:column; gap:5px; }
        .field-label { font-size:11px; color:#888; }
        .field-input {
            padding:8px; background:#252525; color:#ddd;
            border:1px solid #444; border-radius:4px; font-size:13px; width:100%;
        }
        .field-input:focus { border-color:#d4a843; outline:none; }
        .field-input.speed-field { border-color:#fa4; color:#fa4; }

        .btn { display:inline-flex; align-items:center; gap:5px; padding:8px 18px; border-radius:4px; font-size:13px; border:none; cursor:pointer; transition:0.2s; text-decoration:none; }
        .btn-primary { background:#3a6a1a; color:#fff; }
        .btn-primary:hover { background:#4a8a2a; }
        .btn-warning { background:#6a5a1a; color:#fff; }
        .btn-warning:hover { background:#8a7a2a; }

        .save-bar {
            position:sticky; bottom:0; background:#1a1a1a;
            border-top:2px solid #333; padding:15px 25px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .save-bar-hint { font-size:12px; color:#666; }
        .speed-note { color:#fa4; font-size:11px; }

        .alert { padding:12px 16px; border-radius:6px; margin-bottom:15px; font-size:13px; }
        .alert-success { background:#1a3a1a; border:1px solid #4a4; color:#4f4; }

        @media(max-width:900px) { .admin-layout{grid-template-columns:1fr;} .admin-sidebar{height:auto;position:static;} .fields-grid{grid-template-columns:repeat(2,1fr);} }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-header">
            <div class="admin-title">⚔ Настройки юнитов</div>
            <a href="?page=admin&action=reset_units"
               class="btn btn-warning"
               onclick="return confirm('Сбросить всё к значениям по умолчанию?')">
                🔄 Сбросить к дефолту
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php
        $unit_icons = [
            'spear'    => '🔱',
            'sword'    => '⚔️',
            'axe'      => '🪓',
            'scout'    => '🔍',
            'light'    => '🐎',
            'heavy'    => '🦄',
            'ram'      => '🪵',
            'catapult' => '💣'
        ];
        ?>

        <form method="POST" action="?page=admin&action=save_units">
            <?php foreach ($units as $u): ?>
            <div class="unit-card">
                <div class="unit-card-header">
                    <div class="unit-icon"><?= $unit_icons[$u['type']] ?? '👤' ?></div>
                    <div>
                        <div class="unit-name"><?= htmlspecialchars($u['name']) ?></div>
                        <div style="font-size:11px;color:#666;">ID: <?= $u['type'] ?></div>
                    </div>
                    <span class="unit-type-badge <?= $u['unit_type']==='cavalry'?'badge-cavalry':'badge-infantry' ?>">
                        <?= $u['unit_type']==='cavalry' ? '🐎 Кавалерия' : '🚶 Пехота' ?>
                    </span>
                </div>
                <div class="unit-card-body">
                    <div class="fields-grid">
                        <div class="field-group">
                            <label class="field-label">Название</label>
                            <input type="text" class="field-input"
                                   name="units[<?= $u['type'] ?>][name]"
                                   value="<?= htmlspecialchars($u['name']) ?>">
                        </div>
                        <div class="field-group">
                            <label class="field-label">⚔ Атака</label>
                            <input type="number" class="field-input"
                                   name="units[<?= $u['type'] ?>][attack]"
                                   value="<?= $u['attack'] ?>" min="0">
                        </div>
                        <div class="field-group">
                            <label class="field-label">🛡 Защита (пехота)</label>
                            <input type="number" class="field-input"
                                   name="units[<?= $u['type'] ?>][def_inf]"
                                   value="<?= $u['def_inf'] ?>" min="0">
                        </div>
                        <div class="field-group">
                            <label class="field-label">🛡 Защита (кавалерия)</label>
                            <input type="number" class="field-input"
                                   name="units[<?= $u['type'] ?>][def_cav]"
                                   value="<?= $u['def_cav'] ?>" min="0">
                        </div>
                        <div class="field-group">
                            <label class="field-label speed-note">
                                ⚡ Скорость (мин/кл) ↑
                            </label>
                            <input type="number" class="field-input speed-field"
                                   name="units[<?= $u['type'] ?>][speed]"
                                   value="<?= $u['speed'] ?>" min="1" max="999">
                        </div>
                        <div class="field-group">
                            <label class="field-label">📦 Грузоподъёмность</label>
                            <input type="number" class="field-input"
                                   name="units[<?= $u['type'] ?>][carry]"
                                   value="<?= $u['carry'] ?>" min="0">
                        </div>
                        <div class="field-group">
                            <label class="field-label">👥 Население</label>
                            <input type="number" class="field-input"
                                   name="units[<?= $u['type'] ?>][pop]"
                                   value="<?= $u['pop'] ?>" min="1">
                        </div>
                        <div class="field-group">
                            <label class="field-label">⏱ Тренировка (сек)</label>
                            <input type="number" class="field-input"
                                   name="units[<?= $u['type'] ?>][train_time]"
                                   value="<?= $u['train_time'] ?>" min="1">
                        </div>
                        <div class="field-group">
                            <label class="field-label">🪵 Стоимость (дерево)</label>
                            <input type="number" class="field-input"
                                   name="units[<?= $u['type'] ?>][wood]"
                                   value="<?= $u['wood'] ?>" min="0">
                        </div>
                        <div class="field-group">
                            <label class="field-label">🪨 Стоимость (камень)</label>
                            <input type="number" class="field-input"
                                   name="units[<?= $u['type'] ?>][stone]"
                                   value="<?= $u['stone'] ?>" min="0">
                        </div>
                        <div class="field-group">
                            <label class="field-label">⛏ Стоимость (железо)</label>
                            <input type="number" class="field-input"
                                   name="units[<?= $u['type'] ?>][iron]"
                                   value="<?= $u['iron'] ?>" min="0">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Тип</label>
                            <select class="field-input"
                                    name="units[<?= $u['type'] ?>][unit_type]">
                                <option value="infantry" <?= $u['unit_type']==='infantry'?'selected':'' ?>>Пехота</option>
                                <option value="cavalry"  <?= $u['unit_type']==='cavalry' ?'selected':'' ?>>Кавалерия</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="save-bar">
                <div class="save-bar-hint">
                    ⚡ <strong style="color:#fa4;">Скорость</strong> —
                    чем меньше, тем быстрее (разведчик=9, таран=30)
                </div>
                <button type="submit" class="btn btn-primary">
                    💾 Сохранить изменения
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>