<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Карта мира — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1200px; margin:20px auto; padding:0 15px; }

        .page-header {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:20px; flex-wrap:wrap; gap:10px;
        }
        .page-title { font-size:24px; font-weight:bold; color:#d4a843; }

        /* Управление картой */
        .map-controls {
            display:flex; gap:10px; align-items:center; flex-wrap:wrap;
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; padding:12px; margin-bottom:20px;
        }
        .coord-input {
            width:70px; padding:6px; background:#1a1a0a; color:#ddd;
            border:1px solid #444; border-radius:4px; text-align:center;
        }
        .btn-map {
            padding:6px 14px; background:#5a4a1a; color:#d4a843;
            border:1px solid #8b6914; border-radius:4px; cursor:pointer;
            font-size:12px; transition:0.2s; text-decoration:none;
        }
        .btn-map:hover { background:#7a6a2a; }
        .btn-center { background:#1a5a1a; border-color:#2a8a2a; color:#4f4; }
        .btn-center:hover { background:#2a7a2a; }

        /* Сетка карты */
        .map-wrapper {
            overflow:auto; background:#0f0f0f; border:2px solid #333;
            border-radius:8px; padding:20px; position:relative;
        }
        .map-grid {
            display:grid;
            grid-template-columns:repeat(<?= $size*2+1 ?>, 40px);
            grid-template-rows:repeat(<?= $size*2+1 ?>, 40px);
            gap:2px;
            margin:0 auto;
            width:max-content;
        }

        /* Клетки */
        .cell {
            width:40px; height:40px; border-radius:4px;
            display:flex; align-items:center; justify-content:center;
            font-size:18px; cursor:pointer; position:relative;
            transition:0.2s; user-select:none;
        }
        .cell:hover { transform:scale(1.1); z-index:10; box-shadow:0 0 10px rgba(0,0,0,0.8); }

        .cell-empty   { background:#1a1a1a; border:1px solid #222; }
        .cell-my      { background:#1a3a1a; border:2px solid #2a8a2a; box-shadow:inset 0 0 10px rgba(42,138,42,0.3); }
        .cell-player  { background:#1a2a3a; border:2px solid #2a4a8a; }
        .cell-barbarian { background:#3a1a1a; border:2px solid #8a2a2a; }

        /* Индикаторы */
        .loyalty-indicator {
            position:absolute; bottom:-2px; right:-2px;
            font-size:10px; line-height:1;
            text-shadow:0 0 2px #000;
        }

        /* Легенда */
        .legend {
            display:flex; gap:15px; flex-wrap:wrap; margin-top:15px;
            font-size:12px; color:#888; justify-content:center;
        }
        .legend-item { display:flex; align-items:center; gap:5px; }
        .legend-icon { font-size:16px; }

        /* Модальное окно деревни */
        .modal {
            display:none; position:fixed; top:0; left:0; right:0; bottom:0;
            background:rgba(0,0,0,0.85); z-index:1000;
            align-items:center; justify-content:center; padding:15px;
        }
        .modal.active { display:flex; }
        .modal-box {
            background:#2a2a1a; border:2px solid #8b6914;
            border-radius:10px; padding:20px; min-width:320px; max-width:450px;
            position:relative; animation:fadeIn 0.2s;
        }
        @keyframes fadeIn { from{opacity:0;transform:scale(0.9);} to{opacity:1;transform:scale(1);} }

        .modal-close {
            position:absolute; top:10px; right:12px;
            background:none; border:none; color:#888;
            font-size:20px; cursor:pointer;
        }
        .modal-title { font-size:18px; font-weight:bold; color:#d4a843; margin-bottom:12px; }
        .modal-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #333; font-size:13px; }
        .modal-row:last-child { border-bottom:none; }
        .modal-actions { display:flex; gap:8px; margin-top:15px; flex-wrap:wrap; }

        /* Лояльность бар в модалке */
        .loyalty-bar-small {
            height:8px; background:#333; border-radius:4px; overflow:hidden; margin-top:4px;
        }
        .loyalty-fill { height:100%; transition:0.5s; }
        .fill-high   { background:#4f4; }
        .fill-medium { background:#fa4; }
        .fill-low    { background:#f44; }

        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        @media(max-width:600px) {
            .map-grid { grid-template-columns:repeat(<?= $size*2+1 ?>, 32px); grid-template-rows:repeat(<?= $size*2+1 ?>, 32px); }
            .cell { width:32px; height:32px; font-size:14px; }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div class="page-title">🗺 Карта мира</div>
        <div style="font-size:13px;color:#888;">
            Сектор: <?= $center_x ?>|<?= $center_y ?>
        </div>
    </div>

    <!-- Управление -->
    <div class="map-controls">
        <form method="GET" style="display:flex;gap:5px;align-items:center;">
            <input type="hidden" name="page" value="map">
            <label style="color:#888;font-size:12px;">X:</label>
            <input type="number" name="x" value="<?= $center_x ?>" class="coord-input">
            <label style="color:#888;font-size:12px;">Y:</label>
            <input type="number" name="y" value="<?= $center_y ?>" class="coord-input">
            <button type="submit" class="btn-map">Перейти</button>
        </form>
        <div style="width:1px;height:20px;background:#444;margin:0 5px;"></div>
        <a href="?page=map&x=<?= $center_x ?>&y=<?= $center_y+1 ?>" class="btn-map">⬇ Юг</a>
        <a href="?page=map&x=<?= $center_x ?>&y=<?= $center_y-1 ?>" class="btn-map">⬆ Север</a>
        <a href="?page=map&x=<?= $center_x-1 ?>&y=<?= $center_y ?>" class="btn-map">⬅ Запад</a>
        <a href="?page=map&x=<?= $center_x+1 ?>&y=<?= $center_y ?>" class="btn-map">➡ Восток</a>
        <div style="width:1px;height:20px;background:#444;margin:0 5px;"></div>
        <a href="?page=map" class="btn-map btn-center">🏠 Центр (0|0)</a>
    </div>

    <!-- Карта -->
    <div class="map-wrapper">
        <div class="map-grid">
            <?php for ($y = $center_y + $size; $y >= $center_y - $size; $y--): ?>
                <?php for ($x = $center_x - $size; $x <= $center_x + $size; $x++): ?>
                    <?php
                    $v = $villages["{$x}_{$y}"] ?? null;
                    $is_mine = ($v && isset($_SESSION['user_id']) && $v['userid'] == $_SESSION['user_id']);

                    // Определяем класс и иконку
                    if ($v) {
                        $is_barb = ($v['userid'] == -1);
                        if ($is_mine) {
                            $cell_class = 'cell-my';
                            $icon = '🏰';
                        } elseif ($is_barb) {
                            $cell_class = 'cell-barbarian';
                            $icon = '🏚️';
                        } else {
                            $cell_class = 'cell-player';
                            $icon = '🏘️';
                        }

                        // Лояльность
                        $loyalty = (int)($v['loyalty'] ?? 100);
                        $loyalty_indicator = '';
                        if ($loyalty < 100 && !$is_mine && !$is_barb) {
                            if ($loyalty <= 0)       $loyalty_indicator = '<span class="loyalty-indicator">🔴</span>';
                            elseif ($loyalty <= 30)  $loyalty_indicator = '<span class="loyalty-indicator">🟠</span>';
                            elseif ($loyalty <= 60)  $loyalty_indicator = '<span class="loyalty-indicator">🟡</span>';
                        }
                    } else {
                        // Пустая клетка — определяем ландшафт детерминированно
                        $terrain_seed = abs($x * 7 + $y * 13) % 20;
                        if ($terrain_seed < 10)      { $icon = ''; $cell_style = ''; }
                        elseif ($terrain_seed < 13)  { $icon = '🌲'; $cell_style = 'background:#1a2a1a;'; }
                        elseif ($terrain_seed < 16)  { $icon = '⛰'; $cell_style = 'background:#2a2a2a;'; }
                        elseif ($terrain_seed < 18)  { $icon = '🌊'; $cell_style = 'background:#1a1a2a;'; }
                        else                         { $icon = '🏔'; $cell_style = 'background:#2a1a1a;'; }
                        $cell_class = 'cell-empty';
                        $loyalty_indicator = '';
                    }
                    ?>
                    <div class="cell <?= $cell_class ?>"
                         style="<?= $cell_style ?? '' ?>"
                         onclick="openVillage(<?= $x ?>, <?= $y ?>, <?= $v ? "'".htmlspecialchars($v['name'],ENT_QUOTES)."'":"'null'" ?>, <?= $v ? $v['id']:0 ?>, <?= $v ? $v['userid']:0 ?>, <?= $v ? $loyalty:100 ?>)">
                        <?= $icon ?>
                        <?= $loyalty_indicator ?>
                    </div>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Легенда -->
    <div class="legend">
        <div class="legend-item"><span class="legend-icon">🏰</span> Ваша</div>
        <div class="legend-item"><span class="legend-icon">🏘️</span> Игрок</div>
        <div class="legend-item"><span class="legend-icon">🏚️</span> Варвар</div>
        <div class="legend-item"><span class="legend-icon">🟡</span> Лояльность &lt;60%</div>
        <div class="legend-item"><span class="legend-icon">🟠</span> Лояльность &lt;30%</div>
        <div class="legend-item"><span class="legend-icon">🔴</span> Готов к захвату</div>
        <div class="legend-item"><span class="legend-icon">🌲</span> Лес</div>
        <div class="legend-item"><span class="legend-icon">⛰</span> Горы</div>
        <div class="legend-item"><span class="legend-icon">🌊</span> Река</div>
    </div>

</div>

<!-- Модальное окно -->
<div class="modal" id="villageModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <div class="modal-title" id="modalTitle"></div>

        <div class="modal-row">
            <span>Координаты:</span>
            <span id="modalCoords" style="color:#d4a843;"></span>
        </div>
        <div class="modal-row">
            <span>Владелец:</span>
            <span id="modalOwner"></span>
        </div>
        <div class="modal-row">
            <span>Очки:</span>
            <span id="modalPoints"></span>
        </div>

        <!-- Блок лояльности -->
        <div id="loyaltyBlock" style="margin-top:10px;display:none;">
            <div class="modal-row" style="border-bottom:none;">
                <span>❤ Лояльность:</span>
                <span id="modalLoyaltyVal"></span>
            </div>
            <div class="loyalty-bar-small">
                <div class="loyalty-fill" id="modalLoyaltyBar"></div>
            </div>
            <div style="font-size:11px;color:#888;margin-top:4px;" id="loyaltyHint"></div>
        </div>

        <div class="modal-actions" id="modalActions">
            <!-- Кнопки будут добавлены JS -->
        </div>
    </div>
</div>

<script>
const myUserId = <?= json_encode($_SESSION['user_id'] ?? 0) ?>;

function openVillage(x, y, name, id, owner, loyalty) {
    const modal = document.getElementById('villageModal');
    const title = document.getElementById('modalTitle');
    const coords = document.getElementById('modalCoords');
    const ownerEl = document.getElementById('modalOwner');
    const pointsEl = document.getElementById('modalPoints');
    const actions = document.getElementById('modalActions');
    const loyaltyBlock = document.getElementById('loyaltyBlock');
    const loyaltyVal = document.getElementById('modalLoyaltyVal');
    const loyaltyBar = document.getElementById('modalLoyaltyBar');
    const loyaltyHint = document.getElementById('loyaltyHint');

    coords.textContent = `${x}|${y}`;

    // Если пустая клетка
    if (id === 0) {
        title.textContent = name === 'null' ? 'Пустая местность' : name;
        ownerEl.textContent = '—';
        pointsEl.textContent = '—';
        actions.innerHTML = '';
        loyaltyBlock.style.display = 'none';
        modal.classList.add('active');
        return;
    }

    // Данные деревни (загружаем через AJAX или используем переданные, здесь упрощено)
    // Для полноценной работы нужен AJAX запрос к API для получения очков и имени владельца
    // Но мы можем сформировать ссылки сразу
    title.textContent = name === 'null' ? 'Деревня' : name;

    let isMine = (owner == myUserId);
    let isBarb = (owner == -1);

    ownerEl.textContent = isBarb ? 'Варвары' : (isMine ? 'Вы' : 'Игрок #' + owner);
    pointsEl.textContent = '...'; // Можно подгрузить AJAX

    // Лояльность
    if (!isBarb && !isMine) {
        loyaltyBlock.style.display = 'block';
        loyaltyVal.textContent = loyalty + '/100';

        let barClass = 'fill-high';
        let hint = 'Лояльность высокая';
        if (loyalty <= 0) { barClass='fill-low'; hint='🏆 ГОТОВА К ЗАХВАТУ!'; }
        else if (loyalty <= 30) { barClass='fill-low'; hint='Критически низкая лояльность'; }
        else if (loyalty <= 60) { barClass='fill-medium'; hint='Средняя лояльность'; }

        loyaltyBar.className = 'loyalty-fill ' + barClass;
        loyaltyBar.style.width = loyalty + '%';
        loyaltyHint.textContent = hint;
    } else {
        loyaltyBlock.style.display = 'none';
    }

    // Кнопки действий
    let buttons = '';
    buttons += `<a href="?page=attack&target=${id}" class="btn-map" style="background:#5a1a1a;border-color:#8a1a1a;color:#f66;">⚔ Атаковать</a>`;
    if (!isBarb) {
        buttons += `<a href="?page=player&id=${owner}" class="btn-map">👤 Профиль</a>`;
    }
    if (isMine) {
        buttons = `<a href="?page=village&id=${id}" class="btn-map btn-center">🏰 Открыть</a>`;
    }

    actions.innerHTML = buttons;
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('villageModal').classList.remove('active');
}

document.getElementById('villageModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

</body>
</html>