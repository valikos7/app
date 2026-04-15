<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Дипломатия — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:900px; margin:20px auto; padding:0 15px; }
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

        /* Статусы дипломатии */
        .diplo-grid {
            display:grid; grid-template-columns:repeat(3,1fr); gap:12px;
            margin-bottom:20px;
        }
        .diplo-type {
            border-radius:8px; padding:15px; text-align:center; cursor:pointer;
            transition:0.2s; border:2px solid transparent;
        }
        .diplo-ally  { background:#1a3a1a; border-color:#4a8a4a; }
        .diplo-nap   { background:#1a1a3a; border-color:#4a4a8a; }
        .diplo-war   { background:#3a1a1a; border-color:#8a4a4a; }
        .diplo-icon  { font-size:36px; margin-bottom:8px; }
        .diplo-title { font-size:14px; font-weight:bold; }
        .diplo-desc  { font-size:11px; color:#888; margin-top:5px; }

        .diplo-ally-color  { color:#4f4; }
        .diplo-nap-color   { color:#88f; }
        .diplo-war-color   { color:#f44; }

        /* Список отношений */
        .relation-item {
            display:flex; align-items:center; gap:12px;
            padding:12px; border-radius:6px; margin-bottom:8px;
        }
        .relation-ally { background:#1a2a1a; border:1px solid #4a6a4a; }
        .relation-nap  { background:#1a1a2a; border:1px solid #4a4a6a; }
        .relation-war  { background:#2a1a1a; border:1px solid #6a4a4a; }

        .relation-badge {
            padding:3px 10px; border-radius:10px;
            font-size:11px; font-weight:bold;
        }
        .badge-ally   { background:#1a5a1a; color:#4f4; }
        .badge-nap    { background:#1a1a5a; color:#88f; }
        .badge-war    { background:#5a1a1a; color:#f44; }
        .badge-pending{ background:#5a4a1a; color:#d4a843; }

        .relation-name { flex:1; font-size:14px; color:#d4a843; font-weight:bold; }
        .relation-tag  { color:#888; font-size:12px; }

        .btn {
            display:inline-block; padding:6px 14px; border-radius:4px;
            font-size:12px; border:none; cursor:pointer; transition:0.2s;
            text-decoration:none;
        }
        .btn-green  { background:#1a5a1a; color:#fff; }
        .btn-green:hover  { background:#2a7a2a; }
        .btn-red    { background:#5a1a1a; color:#fff; }
        .btn-red:hover    { background:#7a2a2a; }
        .btn-cancel { background:#5a4a1a; color:#ddd; border:1px solid #8b6914; }
        .btn-cancel:hover { background:#7a6a2a; }

        /* Форма предложения */
        .propose-form {
            display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;
        }
        .propose-form select,
        .propose-form input {
            padding:8px 12px; background:#1a1a0a; color:#ddd;
            border:1px solid #444; border-radius:4px; font-size:13px;
        }
        .propose-form select { min-width:180px; }

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

    <!-- Типы дипломатии -->
    <div class="card">
        <div class="card-header">
            🤝 Дипломатия альянса
            [<?= htmlspecialchars($alliance['tag']) ?>]
        </div>
        <div class="card-body">
            <div class="diplo-grid">
                <div class="diplo-type diplo-ally">
                    <div class="diplo-icon">🤝</div>
                    <div class="diplo-title diplo-ally-color">Союз (Ally)</div>
                    <div class="diplo-desc">
                        Полноценный союз. Нельзя атаковать друг друга.
                    </div>
                </div>
                <div class="diplo-type diplo-nap">
                    <div class="diplo-icon">🕊</div>
                    <div class="diplo-title diplo-nap-color">Ненападение (NAP)</div>
                    <div class="diplo-desc">
                        Пакт о ненападении. Мирное сосуществование.
                    </div>
                </div>
                <div class="diplo-type diplo-war">
                    <div class="diplo-icon">⚔</div>
                    <div class="diplo-title diplo-war-color">Война (War)</div>
                    <div class="diplo-desc">
                        Официальное объявление войны альянсу.
                    </div>
                </div>
            </div>

            <!-- Форма предложения -->
            <?php if ($my_role === 'leader' || $my_role === 'officer'): ?>
            <div style="background:#1a1a0a; border:1px solid #444;
                        border-radius:6px; padding:15px; margin-bottom:20px;">
                <h4 style="color:#d4a843; margin-bottom:12px;">
                    📨 Предложить отношения
                </h4>
                <form method="POST" action="?page=diplomacy&action=propose"
                      class="propose-form">
                    <div>
                        <label style="font-size:11px;color:#888;display:block;margin-bottom:4px;">
                            Альянс:
                        </label>
                        <select name="target_id" required>
                            <option value="">Выберите альянс...</option>
                            <?php foreach ($all_alliances as $a):
                                if ($a['id'] == $alliance['id']) continue;
                            ?>
                            <option value="<?= $a['id'] ?>">
                                [<?= htmlspecialchars($a['tag']) ?>]
                                <?= htmlspecialchars($a['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px;color:#888;display:block;margin-bottom:4px;">
                            Тип:
                        </label>
                        <select name="type" required>
                            <option value="ally">🤝 Союз</option>
                            <option value="nap">🕊 Ненападение</option>
                            <option value="war">⚔ Война</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-green">
                        Предложить
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Активные отношения -->
            <h3 style="color:#d4a843; margin-bottom:12px;">
                📋 Текущие отношения
            </h3>

            <?php if (empty($relations)): ?>
                <div style="color:#666; text-align:center; padding:20px;">
                    Нет активных дипломатических отношений
                </div>
            <?php else: ?>
                <?php foreach ($relations as $r):
                    $type_labels = [
                        'ally' => ['🤝 Союз',          'relation-ally', 'badge-ally'],
                        'nap'  => ['🕊 Ненападение',    'relation-nap',  'badge-nap'],
                        'war'  => ['⚔ Война',           'relation-war',  'badge-war'],
                    ];
                    $status_labels = [
                        'pending'  => ['⏳ Ожидание', 'badge-pending'],
                        'active'   => $type_labels[$r['type']] ?? ['?', '', ''],
                        'rejected' => ['❌ Отклонено', 'badge-nap'],
                    ];
                    $tlabel = $type_labels[$r['type']] ?? ['?', 'relation-nap', 'badge-nap'];
                    $slabel = $status_labels[$r['status']] ?? ['?', ''];

                    $is_incoming = ($r['target_id'] == $alliance['id']);
                    $other_name  = $is_incoming ? $r['from_name']  : $r['to_name'];
                    $other_tag   = $is_incoming ? $r['from_tag']   : $r['to_tag'];
                    $other_id    = $is_incoming ? $r['alliance_id'] : $r['target_id'];
                ?>
                <div class="relation-item <?= $tlabel[1] ?>">
                    <div>
                        <span class="relation-badge <?= $slabel[1] ?>">
                            <?= $slabel[0] ?>
                        </span>
                    </div>
                    <div class="relation-name">
                        <a href="?page=alliance&id=<?= $other_id ?>"
                           style="color:#d4a843;text-decoration:none;">
                            [<?= htmlspecialchars($other_tag) ?>]
                            <?= htmlspecialchars($other_name) ?>
                        </a>
                    </div>
                    <div style="font-size:11px;color:#666;">
                        <?= $is_incoming ? '← Входящее' : '→ Исходящее' ?>
                    </div>
                    <div style="display:flex;gap:5px;">
                        <?php if ($r['status'] === 'pending' && $is_incoming
                              && ($my_role === 'leader' || $my_role === 'officer')): ?>
                            <a href="?page=diplomacy&action=accept&id=<?= $r['id'] ?>"
                               class="btn btn-green">✓ Принять</a>
                            <a href="?page=diplomacy&action=reject&id=<?= $r['id'] ?>"
                               class="btn btn-red">✗ Отклонить</a>
                        <?php elseif ($my_role === 'leader' || $my_role === 'officer'): ?>
                            <a href="?page=diplomacy&action=cancel&id=<?= $r['id'] ?>"
                               class="btn btn-cancel"
                               onclick="return confirm('Отменить отношения?')">
                                Отменить
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>