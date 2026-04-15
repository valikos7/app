<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Поддержка — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }

        .container { max-width:1000px; margin:20px auto; padding:0 15px; }

        .page-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:20px;
        }
        .page-title { font-size:24px; font-weight:bold; color:#d4a843; }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .card-body { padding:0; }

        /* Табы */
        .tabs {
            display:flex; gap:5px; margin-bottom:20px;
        }
        .tab {
            padding:10px 20px; border-radius:6px;
            text-decoration:none; font-size:14px;
            border:2px solid #5a4a20; color:#aaa;
            transition:0.2s; cursor:pointer;
        }
        .tab.active, .tab:hover {
            background:#3a2c10; color:#d4a843;
            border-color:#8b6914;
        }

        /* Элемент поддержки */
        .support-item {
            display:flex; align-items:center; gap:15px;
            padding:15px; border-bottom:1px solid #333;
            transition:0.2s;
        }
        .support-item:last-child { border-bottom:none; }
        .support-item:hover { background:#2a2010; }

        .support-icon {
            font-size:32px; flex-shrink:0; text-align:center;
            width:40px;
        }

        .support-info { flex:1; }
        .support-title {
            font-size:14px; font-weight:bold; color:#d4a843;
            margin-bottom:4px;
        }
        .support-meta {
            font-size:12px; color:#888; margin-bottom:6px;
        }

        /* Войска мини */
        .troops-mini {
            display:flex; flex-wrap:wrap; gap:6px;
        }
        .troop-chip {
            background:#1a1a0a; border:1px solid #444;
            border-radius:4px; padding:3px 8px;
            font-size:11px; color:#ccc;
            display:flex; align-items:center; gap:4px;
        }
        .troop-chip-icon { font-size:14px; }
        .troop-chip-count { color:#d4a843; font-weight:bold; }

        /* Кнопки */
        .btn {
            padding:6px 14px; border-radius:4px;
            font-size:12px; border:none; cursor:pointer;
            text-decoration:none; transition:0.2s;
            display:inline-block;
        }
        .btn-recall {
            background:#5a1a1a; color:#f66;
            border:1px solid #8a1a1a;
        }
        .btn-recall:hover { background:#7a2a2a; }
        .btn-map {
            background:#3a2c10; color:#d4a843;
            border:1px solid #8b6914;
        }
        .btn-map:hover { background:#5a4a20; }

        /* Пустой список */
        .empty {
            padding:40px; text-align:center; color:#666;
        }
        .empty-icon { font-size:48px; margin-bottom:10px; }

        /* Суммарная мощь */
        .power-bar {
            background:#1a1a0a; border-top:1px solid #333;
            padding:12px 15px; display:flex;
            gap:20px; flex-wrap:wrap; font-size:13px;
        }
        .power-item { display:flex; align-items:center; gap:6px; }
        .power-label { color:#888; }
        .power-value { color:#d4a843; font-weight:bold; }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        @media(max-width:600px) {
            .support-item { flex-direction:column; align-items:flex-start; }
            .tabs { flex-wrap:wrap; }
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
        <div class="page-title">🛡 Управление поддержкой</div>
    </div>

    <!-- Табы -->
    <?php $active_tab = $_GET['tab'] ?? 'sent'; ?>
    <div class="tabs">
        <a href="?page=support&tab=sent"
           class="tab <?= $active_tab === 'sent' ? 'active' : '' ?>">
            📤 Отправлена мной
            <span style="color:#888; font-size:11px;">
                (<?= count($sent_supports) ?>)
            </span>
        </a>
        <a href="?page=support&tab=received"
           class="tab <?= $active_tab === 'received' ? 'active' : '' ?>">
            📥 Получена от союзников
            <span style="color:#888; font-size:11px;">
                (<?= count($received_supports) ?>)
            </span>
        </a>
        <a href="?page=support&tab=own"
           class="tab <?= $active_tab === 'own' ? 'active' : '' ?>">
            🏘 В своих деревнях
            <span style="color:#888; font-size:11px;">
                (<?= count($own_supports) ?>)
            </span>
        </a>
    </div>

    <?php
    $unit_icons = [
        'spear'=>'🔱', 'sword'=>'⚔️', 'axe'=>'🪓',
        'scout'=>'🔍', 'light'=>'🐎', 'heavy'=>'🦄',
        'ram'=>'🪵',   'catapult'=>'💣'
    ];
    $unit_names = [
        'spear'=>'Коп.', 'sword'=>'Меч.', 'axe'=>'Топ.',
        'scout'=>'Разв.','light'=>'Л.кав.','heavy'=>'Т.кав.',
        'ram'=>'Тар.',   'catapult'=>'Кат.'
    ];

    // Считаем суммарные войска
    function sumTroops($supports) {
        $total = [];
        foreach ($supports as $s) {
            $t = json_decode($s['troops'], true);
            foreach ($t as $type => $count) {
                $total[$type] = ($total[$type] ?? 0) + $count;
            }
        }
        return $total;
    }
    ?>

    <!-- ТАБ: ОТПРАВЛЕНА МНЕ -->
    <?php if ($active_tab === 'sent'): ?>
    <div class="card">
        <div class="card-header">
            📤 Поддержка отправленная мной
            <span style="font-size:12px; color:#aaa;">
                Всего: <?= count($sent_supports) ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (empty($sent_supports)): ?>
                <div class="empty">
                    <div class="empty-icon">🛡</div>
                    Вы не отправляли поддержку
                </div>
            <?php else: ?>
                <?php foreach ($sent_supports as $s):
                    $troops = json_decode($s['troops'], true);
                    $total  = array_sum($troops);
                ?>
                <div class="support-item">
                    <div class="support-icon">🛡</div>
                    <div class="support-info">
                        <div class="support-title">
                            <?= htmlspecialchars($s['from_name'] ?? '?') ?>
                            →
                            <?= htmlspecialchars($s['to_name'] ?? '?') ?>
                            <span style="color:#888; font-size:12px;">
                                (владелец: <?= htmlspecialchars($s['to_owner'] ?? '?') ?>)
                            </span>
                        </div>
                        <div class="support-meta">
                            📍 <?= $s['fx'] ?>|<?= $s['fy'] ?>
                            → <?= $s['tx'] ?>|<?= $s['ty'] ?>
                            &nbsp;·&nbsp;
                            👥 Войск: <?= $total ?>
                        </div>
                        <div class="troops-mini">
                            <?php foreach ($troops as $type => $count):
                                if ($count <= 0) continue;
                            ?>
                            <div class="troop-chip">
                                <span class="troop-chip-icon">
                                    <?= $unit_icons[$type] ?? '👤' ?>
                                </span>
                                <span><?= $unit_names[$type] ?? $type ?>:</span>
                                <span class="troop-chip-count"><?= $count ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <a href="?page=map&x=<?= $s['tx'] ?>&y=<?= $s['ty'] ?>"
                           class="btn btn-map">
                            🗺 На карте
                        </a>
                        <a href="?page=support&action=recall&id=<?= $s['id'] ?>"
                           class="btn btn-recall"
                           onclick="return confirm('Отозвать поддержку? Войска вернутся домой.')">
                            ↩ Отозвать
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Суммарная мощь -->
                <?php $total_troops = sumTroops($sent_supports); ?>
                <div class="power-bar">
                    <div class="power-item">
                        <span class="power-label">Всего войск в поддержке:</span>
                    </div>
                    <?php foreach ($total_troops as $type => $count):
                        if ($count <= 0) continue;
                    ?>
                    <div class="power-item">
                        <span><?= $unit_icons[$type] ?? '' ?></span>
                        <span class="power-value"><?= $count ?></span>
                        <span class="power-label"><?= $unit_names[$type] ?? $type ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ТАБ: ПОЛУЧЕНА -->
    <?php elseif ($active_tab === 'received'): ?>
    <div class="card">
        <div class="card-header">
            📥 Поддержка полученная от союзников
            <span style="font-size:12px; color:#aaa;">
                Всего: <?= count($received_supports) ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (empty($received_supports)): ?>
                <div class="empty">
                    <div class="empty-icon">🤝</div>
                    Никто не прислал поддержку
                    <br>
                    <span style="font-size:12px; margin-top:8px; display:block;">
                        Попросите союзников из альянса!
                    </span>
                </div>
            <?php else: ?>
                <?php foreach ($received_supports as $s):
                    $troops = json_decode($s['troops'], true);
                    $total  = array_sum($troops);
                ?>
                <div class="support-item">
                    <div class="support-icon">🤝</div>
                    <div class="support-info">
                        <div class="support-title">
                            <span style="color:#4f4;">
                                <?= htmlspecialchars($s['from_owner'] ?? '?') ?>
                            </span>
                            → <?= htmlspecialchars($s['to_name'] ?? '?') ?>
                        </div>
                        <div class="support-meta">
                            Из: <?= htmlspecialchars($s['from_name'] ?? '?') ?>
                            (<?= $s['fx'] ?>|<?= $s['fy'] ?>)
                            &nbsp;·&nbsp;
                            👥 Войск: <?= $total ?>
                        </div>
                        <div class="troops-mini">
                            <?php foreach ($troops as $type => $count):
                                if ($count <= 0) continue;
                            ?>
                            <div class="troop-chip" style="border-color:#4a6a4a;">
                                <span class="troop-chip-icon">
                                    <?= $unit_icons[$type] ?? '👤' ?>
                                </span>
                                <span><?= $unit_names[$type] ?? $type ?>:</span>
                                <span class="troop-chip-count"><?= $count ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <a href="?page=messages&action=compose&to=<?= urlencode($s['from_owner'] ?? '') ?>"
                           class="btn btn-map">
                            ✉ Написать
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Суммарная мощь -->
                <?php $total_troops = sumTroops($received_supports); ?>
                <div class="power-bar">
                    <div class="power-item">
                        <span class="power-label">Суммарная поддержка:</span>
                    </div>
                    <?php foreach ($total_troops as $type => $count):
                        if ($count <= 0) continue;
                    ?>
                    <div class="power-item">
                        <span><?= $unit_icons[$type] ?? '' ?></span>
                        <span class="power-value"><?= $count ?></span>
                        <span class="power-label"><?= $unit_names[$type] ?? $type ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ТАБ: В СВОИХ ДЕРЕВНЯХ -->
    <?php elseif ($active_tab === 'own'): ?>
    <div class="card">
        <div class="card-header">
            🏘 Моя поддержка в своих деревнях
            <span style="font-size:12px; color:#aaa;">
                Всего: <?= count($own_supports) ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (empty($own_supports)): ?>
                <div class="empty">
                    <div class="empty-icon">🏘</div>
                    Нет своей поддержки в деревнях
                </div>
            <?php else: ?>
                <?php foreach ($own_supports as $s):
                    $troops = json_decode($s['troops'], true);
                    $total  = array_sum($troops);
                ?>
                <div class="support-item">
                    <div class="support-icon">🏘</div>
                    <div class="support-info">
                        <div class="support-title">
                            <?= htmlspecialchars($s['from_name'] ?? '?') ?>
                            →
                            <?= htmlspecialchars($s['to_name'] ?? '?') ?>
                        </div>
                        <div class="support-meta">
                            <?= $s['fx'] ?>|<?= $s['fy'] ?> →
                            <?= $s['tx'] ?>|<?= $s['ty'] ?>
                            &nbsp;·&nbsp; 👥 <?= $total ?> войск
                        </div>
                        <div class="troops-mini">
                            <?php foreach ($troops as $type => $count):
                                if ($count <= 0) continue;
                            ?>
                            <div class="troop-chip" style="border-color:#4a4a6a;">
                                <span class="troop-chip-icon">
                                    <?= $unit_icons[$type] ?? '👤' ?>
                                </span>
                                <span><?= $unit_names[$type] ?? $type ?>:</span>
                                <span class="troop-chip-count"><?= $count ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <a href="?page=village&id=<?= $s['to_village_id'] ?>"
                           class="btn btn-map">
                            🏘 В деревню
                        </a>
                        <a href="?page=support&action=recall&id=<?= $s['id'] ?>"
                           class="btn btn-recall"
                           onclick="return confirm('Отозвать поддержку?')">
                            ↩ Отозвать
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Кнопки -->
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
        <a href="?page=profile" style="color:#d4a843; text-decoration:none;
           padding:8px 16px; background:#3a2c10; border:1px solid #8b6914;
           border-radius:5px; font-size:13px;">
            ← Профиль
        </a>
        <a href="?page=map" style="color:#d4a843; text-decoration:none;
           padding:8px 16px; background:#3a2c10; border:1px solid #8b6914;
           border-radius:5px; font-size:13px;">
            🗺 Карта
        </a>
    </div>

</div>

</body>
</html>