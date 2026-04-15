<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Рынок — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }

        .container { max-width:1100px; margin:20px auto; padding:0 15px; }

        .page-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:20px;
        }
        .page-title { font-size:24px; font-weight:bold; color:#d4a843; }

        /* Табы */
        .tabs {
            display:flex; gap:5px; margin-bottom:20px; flex-wrap:wrap;
        }
        .tab {
            padding:10px 20px; border-radius:6px;
            text-decoration:none; font-size:13px;
            border:2px solid #5a4a20; color:#aaa; transition:0.2s;
        }
        .tab.active, .tab:hover {
            background:#3a2c10; color:#d4a843; border-color:#8b6914;
        }

        /* Карточки */
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

        /* Форма создания предложения */
        .offer-form {
            display:grid;
            grid-template-columns:1fr auto 1fr auto;
            gap:15px; align-items:end; flex-wrap:wrap;
        }
        .form-group { display:flex; flex-direction:column; gap:6px; }
        .form-group label { font-size:12px; color:#888; }

        select, input[type="number"] {
            padding:10px; background:#1a1a0a; color:#ddd;
            border:2px solid #444; border-radius:6px;
            font-size:14px; width:100%; transition:0.2s;
        }
        select:focus, input[type="number"]:focus {
            border-color:#8b6914; outline:none;
        }

        .arrow-icon {
            font-size:24px; color:#d4a843;
            text-align:center; padding-bottom:5px;
        }

        .btn {
            padding:10px 22px; border-radius:5px;
            font-size:13px; border:none; cursor:pointer;
            text-decoration:none; transition:0.2s;
            display:inline-block; text-align:center;
        }
        .btn-gold   { background:#5a4a1a; color:#d4a843; border:1px solid #8b6914; }
        .btn-gold:hover   { background:#7a6a2a; }
        .btn-green  { background:#1a5a1a; color:#4f4; border:1px solid #2a8a2a; }
        .btn-green:hover  { background:#2a7a2a; }
        .btn-red    { background:#5a1a1a; color:#f66; border:1px solid #8a1a1a; }
        .btn-red:hover    { background:#7a2a2a; }
        .btn-blue   { background:#1a1a5a; color:#88f; border:1px solid #2a2a8a; }
        .btn-blue:hover   { background:#2a2a7a; }

        /* Таблица предложений */
        table { width:100%; border-collapse:collapse; }
        th {
            background:#3a2c10; color:#d4a843;
            padding:10px 12px; text-align:left; font-size:13px;
        }
        td { padding:10px 12px; border-bottom:1px solid #333; font-size:13px; }
        tr:hover td { background:#2a2010; }

        .res-offer {
            display:flex; align-items:center; gap:6px;
            font-size:14px; font-weight:bold;
        }
        .res-icon { font-size:20px; }

        .ratio-good { color:#4f4; font-size:12px; }
        .ratio-bad  { color:#f44; font-size:12px; }
        .ratio-neutral { color:#fa4; font-size:12px; }

        /* Внутренняя торговля */
        .internal-form {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:20px;
        }
        .res-inputs {
            display:flex; gap:10px; flex-wrap:wrap;
        }
        .res-input-group {
            display:flex; flex-direction:column;
            gap:4px; flex:1; min-width:80px;
        }
        .res-input-group label {
            font-size:11px; color:#888; text-align:center;
        }
        .res-input-group input {
            text-align:center;
        }

        /* Активные торговые пути */
        .trade-item {
            display:flex; align-items:center; gap:12px;
            padding:12px; border-radius:6px; margin-bottom:8px;
            background:#1a1a2a; border:1px solid #44a;
        }
        .trade-icon { font-size:28px; }
        .trade-info { flex:1; }
        .trade-title { font-weight:bold; color:#d4a843; font-size:13px; }
        .trade-res { font-size:12px; color:#888; margin-top:3px; }
        .trade-timer {
            color:#88f; font-weight:bold; font-size:14px;
            white-space:nowrap;
        }

        /* Пустой список */
        .empty {
            padding:30px; text-align:center; color:#666;
        }
        .empty-icon { font-size:40px; margin-bottom:8px; }

        /* Алерты */
        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        /* Информация о ресурсах */
        .village-res {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:12px; margin-bottom:15px;
            display:flex; gap:20px; flex-wrap:wrap; font-size:13px;
        }
        .village-res-item { display:flex; align-items:center; gap:6px; }
        .village-res-val { color:#d4a843; font-weight:bold; }

        @media(max-width:700px) {
            .offer-form    { grid-template-columns:1fr; }
            .internal-form { grid-template-columns:1fr; }
            .arrow-icon    { transform:rotate(90deg); }
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
        <div class="page-title">💰 Рынок</div>
        <div style="font-size:13px; color:#888;">
            Обменивайте ресурсы с другими игроками
        </div>
    </div>

    <!-- Табы -->
    <?php $active_tab = $_GET['tab'] ?? 'market'; ?>
    <div class="tabs">
        <a href="?page=market&tab=market"
           class="tab <?= $active_tab==='market' ? 'active':'' ?>">
            🏪 Рынок
            <span style="color:#888; font-size:11px;">
                (<?= count($market_offers) ?>)
            </span>
        </a>
        <a href="?page=market&tab=create"
           class="tab <?= $active_tab==='create' ? 'active':'' ?>">
            ➕ Создать предложение
        </a>
        <a href="?page=market&tab=my_offers"
           class="tab <?= $active_tab==='my_offers' ? 'active':'' ?>">
            📋 Мои предложения
            <span style="color:#888; font-size:11px;">
                (<?= count($my_offers) ?>)
            </span>
        </a>
        <a href="?page=market&tab=internal"
           class="tab <?= $active_tab==='internal' ? 'active':'' ?>">
            🚚 Между деревнями
        </a>
    </div>

    <?php
    $res_names = ['wood'=>'Дерево','stone'=>'Камень','iron'=>'Железо'];
    $res_icons = ['wood'=>'🪵','stone'=>'🪨','iron'=>'⛏'];
    ?>

    <!-- ТАБ: РЫНОК -->
    <?php if ($active_tab === 'market'): ?>

    <!-- Мои ресурсы -->
    <?php if (!empty($my_villages)): ?>
    <div class="village-res">
        <span style="color:#888;">Ресурсы (1-я деревня):</span>
        <div class="village-res-item">
            🪵 <span class="village-res-val">
                <?= number_format($my_villages[0]['r_wood']) ?>
            </span>
        </div>
        <div class="village-res-item">
            🪨 <span class="village-res-val">
                <?= number_format($my_villages[0]['r_stone']) ?>
            </span>
        </div>
        <div class="village-res-item">
            ⛏ <span class="village-res-val">
                <?= number_format($my_villages[0]['r_iron']) ?>
            </span>
        </div>
        <a href="?page=market&tab=create" class="btn btn-green" style="padding:6px 14px; font-size:12px;">
            + Создать предложение
        </a>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            🏪 Активные предложения
            <span style="font-size:12px; color:#aaa;">
                <?= count($market_offers) ?> шт.
            </span>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($market_offers)): ?>
                <div class="empty">
                    <div class="empty-icon">🏪</div>
                    На рынке нет предложений.<br>
                    <a href="?page=market&tab=create"
                       style="color:#d4a843;">Создайте первое!</a>
                </div>
            <?php else: ?>
            <table>
                <tr>
                    <th>Игрок</th>
                    <th>Предлагает</th>
                    <th></th>
                    <th>Хочет</th>
                    <th>Курс</th>
                    <th>Деревня</th>
                    <th>Действие</th>
                </tr>
                <?php foreach ($market_offers as $offer):
                    $is_mine = ($offer['user_id'] == $_SESSION['user_id']);
                    $ratio   = round($offer['want_amount'] / $offer['offer_amount'], 2);
                    if ($ratio < 1)       $ratio_class = 'ratio-good';
                    elseif ($ratio > 1.5) $ratio_class = 'ratio-bad';
                    else                  $ratio_class = 'ratio-neutral';
                ?>
                <tr <?= $is_mine ? 'style="background:#2a2010;"' : '' ?>>
                    <td>
                        <a href="?page=player&id=<?= $offer['user_id'] ?>"
                           style="color:#d4a843; text-decoration:none;">
                            <?= htmlspecialchars($offer['username']) ?>
                        </a>
                        <?php if ($is_mine): ?>
                            <span style="color:#888; font-size:10px;">(вы)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="res-offer">
                            <span class="res-icon">
                                <?= $res_icons[$offer['offer_type']] ?>
                            </span>
                            <strong><?= number_format($offer['offer_amount']) ?></strong>
                            <span style="color:#888; font-size:11px;">
                                <?= $res_names[$offer['offer_type']] ?>
                            </span>
                        </div>
                    </td>
                    <td style="text-align:center; color:#d4a843;">→</td>
                    <td>
                        <div class="res-offer">
                            <span class="res-icon">
                                <?= $res_icons[$offer['want_type']] ?>
                            </span>
                            <strong><?= number_format($offer['want_amount']) ?></strong>
                            <span style="color:#888; font-size:11px;">
                                <?= $res_names[$offer['want_type']] ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="<?= $ratio_class ?>">
                            1:<?= $ratio ?>
                        </span>
                    </td>
                    <td style="font-size:12px; color:#888;">
                        <?= htmlspecialchars($offer['village_name']) ?>
                        <br>
                        <span style="font-size:10px;">
                            <?= $offer['x'] ?>|<?= $offer['y'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!$is_mine): ?>
                        <form method="GET" action="?page=market&action=accept">
                            <input type="hidden" name="id"
                                   value="<?= $offer['id'] ?>">
                            <select name="village_id"
                                    style="margin-bottom:5px; font-size:11px;">
                                <?php foreach ($my_villages as $v): ?>
                                    <option value="<?= $v['id'] ?>">
                                        <?= htmlspecialchars($v['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-green"
                                    style="padding:5px 12px; font-size:12px; width:100%;"
                                    onclick="return confirm('Принять предложение?')">
                                ✓ Принять
                            </button>
                        </form>
                        <?php else: ?>
                        <a href="?page=market&action=cancel&id=<?= $offer['id'] ?>"
                           class="btn btn-red"
                           style="padding:5px 12px; font-size:12px;"
                           onclick="return confirm('Отменить предложение?')">
                            ✕ Отменить
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ТАБ: СОЗДАТЬ ПРЕДЛОЖЕНИЕ -->
    <?php elseif ($active_tab === 'create'): ?>
    <div class="card">
        <div class="card-header">➕ Создать торговое предложение</div>
        <div class="card-body">

            <p style="color:#888; font-size:13px; margin-bottom:20px;">
                Разместите предложение на рынке. Другие игроки смогут его принять.
                Ресурсы будут заморожены до принятия или отмены.
                Максимум <strong style="color:#d4a843;">5</strong> активных предложений.
            </p>

            <form method="POST" action="?page=market&action=create">

                <!-- Деревня -->
                <div class="form-group" style="margin-bottom:20px;">
                    <label>Из деревни:</label>
                    <select name="village_id" id="villageSelect"
                            onchange="updateVillageRes()">
                        <?php foreach ($my_villages as $v): ?>
                        <option value="<?= $v['id'] ?>"
                                data-wood="<?= $v['r_wood'] ?>"
                                data-stone="<?= $v['r_stone'] ?>"
                                data-iron="<?= $v['r_iron'] ?>">
                            <?= htmlspecialchars($v['name']) ?>
                            — 🪵<?= number_format($v['r_wood']) ?>
                            🪨<?= number_format($v['r_stone']) ?>
                            ⛏<?= number_format($v['r_iron']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="offer-form">
                    <!-- Предлагаю -->
                    <div>
                        <h4 style="color:#d4a843; margin-bottom:12px;">
                            📤 Я предлагаю
                        </h4>
                        <div class="form-group">
                            <label>Ресурс:</label>
                            <select name="offer_type" id="offerType"
                                    onchange="updateRatio()">
                                <option value="wood">🪵 Дерево</option>
                                <option value="stone">🪨 Камень</option>
                                <option value="iron">⛏ Железо</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-top:10px;">
                            <label>Количество:</label>
                            <input type="number" name="offer_amount"
                                   id="offerAmount"
                                   value="1000" min="1"
                                   max="<?= self::MAX_TRADE ?>"
                                   oninput="updateRatio()">
                        </div>
                        <div id="availableInfo"
                             style="font-size:11px; color:#888; margin-top:5px;">
                        </div>
                    </div>

                    <div class="arrow-icon" style="padding:20px 0;">⇄</div>

                    <!-- Хочу получить -->
                    <div>
                        <h4 style="color:#d4a843; margin-bottom:12px;">
                            📥 Хочу получить
                        </h4>
                        <div class="form-group">
                            <label>Ресурс:</label>
                            <select name="want_type" id="wantType"
                                    onchange="updateRatio()">
                                <option value="stone">🪨 Камень</option>
                                <option value="iron">⛏ Железо</option>
                                <option value="wood">🪵 Дерево</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-top:10px;">
                            <label>Количество:</label>
                            <input type="number" name="want_amount"
                                   id="wantAmount"
                                   value="1000" min="1"
                                   max="<?= self::MAX_TRADE ?>"
                                   oninput="updateRatio()">
                        </div>
                    </div>

                    <!-- Курс -->
                    <div style="text-align:center; padding-top:20px;">
                        <div style="font-size:12px; color:#888; margin-bottom:5px;">
                            Курс обмена:
                        </div>
                        <div id="ratioDisplay"
                             style="font-size:20px; font-weight:bold; color:#d4a843;">
                            1:1
                        </div>
                        <div id="ratioHint"
                             style="font-size:11px; color:#888; margin-top:5px;">
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px; text-align:center;">
                    <button type="submit" class="btn btn-gold"
                            style="padding:12px 35px; font-size:15px;">
                        💰 Разместить на рынке
                    </button>
                    <a href="?page=market" class="btn btn-red"
                       style="padding:12px 25px; margin-left:10px;">
                        Отмена
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ТАБ: МОИ ПРЕДЛОЖЕНИЯ -->
    <?php elseif ($active_tab === 'my_offers'): ?>
    <div class="card">
        <div class="card-header">
            📋 Мои предложения
            <a href="?page=market&tab=create"
               class="btn btn-green"
               style="padding:6px 14px; font-size:12px;">
                + Создать
            </a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($my_offers)): ?>
                <div class="empty">
                    <div class="empty-icon">📋</div>
                    У вас нет предложений
                </div>
            <?php else: ?>
            <table>
                <tr>
                    <th>Статус</th>
                    <th>Предлагаю</th>
                    <th></th>
                    <th>Хочу</th>
                    <th>Деревня</th>
                    <th>Создано</th>
                    <th>Действие</th>
                </tr>
                <?php foreach ($my_offers as $o):
                    $status_labels = [
                        'active'    => ['🟡 Активно',   '#fa4'],
                        'completed' => ['✅ Завершено',  '#4f4'],
                        'cancelled' => ['❌ Отменено',   '#f44'],
                    ];
                    $sl = $status_labels[$o['status']] ?? ['?', '#888'];
                ?>
                <tr>
                    <td style="color:<?= $sl[1] ?>;"><?= $sl[0] ?></td>
                    <td>
                        <?= $res_icons[$o['offer_type']] ?>
                        <strong><?= number_format($o['offer_amount']) ?></strong>
                        <span style="color:#888; font-size:11px;">
                            <?= $res_names[$o['offer_type']] ?>
                        </span>
                    </td>
                    <td style="color:#d4a843;">→</td>
                    <td>
                        <?= $res_icons[$o['want_type']] ?>
                        <strong><?= number_format($o['want_amount']) ?></strong>
                        <span style="color:#888; font-size:11px;">
                            <?= $res_names[$o['want_type']] ?>
                        </span>
                    </td>
                    <td style="font-size:12px; color:#888;">
                        <?= htmlspecialchars($o['village_name']) ?>
                    </td>
                    <td style="font-size:11px; color:#666;">
                        <?= date('d.m H:i', $o['created_at']) ?>
                    </td>
                    <td>
                        <?php if ($o['status'] === 'active'): ?>
                        <a href="?page=market&action=cancel&id=<?= $o['id'] ?>"
                           class="btn btn-red"
                           style="padding:5px 12px; font-size:12px;"
                           onclick="return confirm('Отменить предложение?')">
                            ✕ Отменить
                        </a>
                        <?php else: ?>
                        <span style="color:#666; font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ТАБ: ВНУТРЕННЯЯ ТОРГОВЛЯ -->
    <?php elseif ($active_tab === 'internal'): ?>

    <!-- Активные торговые пути -->
    <?php if (!empty($my_trades)): ?>
    <div class="card">
        <div class="card-header">
            🚚 В пути
            <span style="font-size:12px; color:#aaa;">
                <?= count($my_trades) ?> маршрут(ов)
            </span>
        </div>
        <div class="card-body">
            <?php foreach ($my_trades as $t):
                $remaining = max(0, $t['arrival_time'] - time());
                $mins = floor($remaining / 60);
                $secs = $remaining % 60;
            ?>
            <div class="trade-item">
                <div class="trade-icon">🚚</div>
                <div class="trade-info">
                    <div class="trade-title">
                        <?= htmlspecialchars($t['from_name']) ?>
                        → <?= htmlspecialchars($t['to_name']) ?>
                    </div>
                    <div class="trade-res">
                        <?php if ($t['wood']  > 0): ?>🪵 <?= number_format($t['wood']) ?> &nbsp;<?php endif; ?>
                        <?php if ($t['stone'] > 0): ?>🪨 <?= number_format($t['stone']) ?> &nbsp;<?php endif; ?>
                        <?php if ($t['iron']  > 0): ?>⛏ <?= number_format($t['iron']) ?><?php endif; ?>
                    </div>
                </div>
                <div class="trade-timer" data-end="<?= $t['arrival_time'] ?>">
                    ⏱ <?= $mins ?>м <?= str_pad($secs, 2, '0', STR_PAD_LEFT) ?>с
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Форма отправки ресурсов -->
    <div class="card">
        <div class="card-header">🚚 Отправить ресурсы между своими деревнями</div>
        <div class="card-body">

            <p style="color:#888; font-size:13px; margin-bottom:20px;">
                Торговцы доставят ресурсы со скоростью
                <strong style="color:#d4a843;">
                    <?= MarketController::MERCHANT_SPEED ?> мин/клетку
                </strong>.
            </p>

            <form method="POST" action="?page=market&action=internal">
                <div class="internal-form">
                    <div>
                        <div class="form-group" style="margin-bottom:15px;">
                            <label>📤 Из деревни:</label>
                            <select name="from_village" id="fromVillage"
                                    onchange="updateInternalInfo()">
                                <?php foreach ($my_villages as $v): ?>
                                <option value="<?= $v['id'] ?>"
                                        data-x="<?= $v['x'] ?>"
                                        data-y="<?= $v['y'] ?>"
                                        data-wood="<?= $v['r_wood'] ?>"
                                        data-stone="<?= $v['r_stone'] ?>"
                                        data-iron="<?= $v['r_iron'] ?>">
                                    <?= htmlspecialchars($v['name']) ?>
                                    (<?= $v['x'] ?>|<?= $v['y'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Ресурсы -->
                        <div class="res-inputs">
                            <div class="res-input-group">
                                <label>🪵 Дерево</label>
                                <input type="number" name="wood" id="sendWood"
                                       value="0" min="0" oninput="updateInternalInfo()">
                                <div id="avail_wood"
                                     style="font-size:10px; color:#888; text-align:center;">
                                    Есть: 0
                                </div>
                            </div>
                            <div class="res-input-group">
                                <label>🪨 Камень</label>
                                <input type="number" name="stone" id="sendStone"
                                       value="0" min="0" oninput="updateInternalInfo()">
                                <div id="avail_stone"
                                     style="font-size:10px; color:#888; text-align:center;">
                                    Есть: 0
                                </div>
                            </div>
                            <div class="res-input-group">
                                <label>⛏ Железо</label>
                                <input type="number" name="iron" id="sendIron"
                                       value="0" min="0" oninput="updateInternalInfo()">
                                <div id="avail_iron"
                                     style="font-size:10px; color:#888; text-align:center;">
                                    Есть: 0
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="form-group" style="margin-bottom:15px;">
                            <label>📥 В деревню:</label>
                            <select name="to_village" id="toVillage"
                                    onchange="updateInternalInfo()">
                                <?php foreach ($my_villages as $v): ?>
                                <option value="<?= $v['id'] ?>"
                                        data-x="<?= $v['x'] ?>"
                                        data-y="<?= $v['y'] ?>">
                                    <?= htmlspecialchars($v['name']) ?>
                                    (<?= $v['x'] ?>|<?= $v['y'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Информация о маршруте -->
                        <div id="routeInfo"
                             style="background:#1a1a0a; border:1px solid #444;
                                    border-radius:6px; padding:12px;
                                    font-size:13px; color:#888;">
                            Выберите деревни для расчёта времени
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px; text-align:center;">
                    <button type="submit" class="btn btn-gold"
                            style="padding:12px 35px; font-size:15px;"
                            onclick="return confirmInternal()">
                        🚚 Отправить торговцев
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
const MERCHANT_SPEED = <?= MarketController::MERCHANT_SPEED ?>;

// === РЫНОК: Обновление курса ===
function updateRatio() {
    const offer = parseFloat(document.getElementById('offerAmount')?.value) || 1;
    const want  = parseFloat(document.getElementById('wantAmount')?.value)  || 1;
    const ratio = (want / offer).toFixed(2);

    const el = document.getElementById('ratioDisplay');
    if (!el) return;

    el.textContent = `1 : ${ratio}`;

    const hint = document.getElementById('ratioHint');
    if (hint) {
        if (ratio < 1)      { el.style.color = '#4f4'; hint.textContent = '✅ Выгодный курс'; }
        else if (ratio > 1.5){ el.style.color = '#f44'; hint.textContent = '❌ Невыгодный курс'; }
        else                 { el.style.color = '#fa4'; hint.textContent = '📊 Средний курс'; }
    }

    // Обновляем доступные ресурсы
    updateVillageRes();
}

function updateVillageRes() {
    const sel = document.getElementById('villageSelect');
    if (!sel) return;
    const opt    = sel.options[sel.selectedIndex];
    const ofType = document.getElementById('offerType')?.value;

    const avail = {
        wood: parseInt(opt.dataset.wood   || 0),
        stone: parseInt(opt.dataset.stone || 0),
        iron:  parseInt(opt.dataset.iron  || 0)
    };

    const availEl = document.getElementById('availableInfo');
    if (availEl && ofType) {
        const icons = {wood:'🪵', stone:'🪨', iron:'⛏'};
        availEl.textContent = `Доступно ${icons[ofType]}: ${avail[ofType].toLocaleString('ru')}`;
        availEl.style.color = avail[ofType] >= (parseInt(document.getElementById('offerAmount')?.value)||0) ? '#4f4' : '#f44';
    }
}

// === ВНУТРЕННЯЯ ТОРГОВЛЯ ===
function updateInternalInfo() {
    const fromSel = document.getElementById('fromVillage');
    const toSel   = document.getElementById('toVillage');
    if (!fromSel || !toSel) return;

    const fromOpt = fromSel.options[fromSel.selectedIndex];
    const toOpt   = toSel.options[toSel.selectedIndex];

    // Обновляем доступные ресурсы
    ['wood','stone','iron'].forEach(res => {
        const el = document.getElementById('avail_' + res);
        if (el) {
            const val = parseInt(fromOpt.dataset[res] || 0);
            el.textContent = 'Есть: ' + val.toLocaleString('ru');

            const inp = document.getElementById('send' + res.charAt(0).toUpperCase() + res.slice(1));
            if (inp) inp.max = val;
        }
    });

    // Расчёт времени
    const fromX = parseFloat(fromOpt.dataset.x);
    const fromY = parseFloat(fromOpt.dataset.y);
    const toX   = parseFloat(toOpt.dataset.x);
    const toY   = parseFloat(toOpt.dataset.y);

    const dist    = Math.sqrt(Math.pow(toX-fromX,2) + Math.pow(toY-fromY,2));
    const timeSec = Math.max(60, Math.ceil(dist * MERCHANT_SPEED * 60));
    const mins    = Math.floor(timeSec / 60);
    const secs    = timeSec % 60;

    const routeEl = document.getElementById('routeInfo');
    if (routeEl) {
        if (fromSel.value === toSel.value) {
            routeEl.innerHTML = '<span style="color:#f44;">❌ Выберите разные деревни!</span>';
        } else {
            routeEl.innerHTML = `
                📏 Расстояние: <strong style="color:#d4a843;">${dist.toFixed(1)}</strong> кл.<br>
                ⏱ Время: <strong style="color:#d4a843;">${mins}м ${secs}с</strong><br>
                🚚 Скорость: ${MERCHANT_SPEED} мин/кл
            `;
        }
    }
}

function confirmInternal() {
    const w = parseInt(document.getElementById('sendWood')?.value  || 0);
    const s = parseInt(document.getElementById('sendStone')?.value || 0);
    const i = parseInt(document.getElementById('sendIron')?.value  || 0);
    if (w + s + i <= 0) {
        alert('Укажите количество ресурсов!');
        return false;
    }
    return confirm(`Отправить: 🪵${w} 🪨${s} ⛏${i}?`);
}

// Таймеры торговых путей
function updateTradeTimers() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('.trade-timer[data-end]').forEach(el => {
        const end = parseInt(el.dataset.end);
        const rem = Math.max(0, end - now);
        const m   = Math.floor(rem / 60);
        const s   = rem % 60;
        el.textContent = `⏱ ${m}м ${String(s).padStart(2,'0')}с`;
        if (rem <= 0) location.reload();
    });
}
setInterval(updateTradeTimers, 1000);

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    updateRatio();
    updateVillageRes();
    updateInternalInfo();
});
</script>

</body>
</html>