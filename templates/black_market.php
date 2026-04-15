<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Тёмный рынок — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#0f0f1a; color:#ddd; }
        .container { max-width:1100px; margin:20px auto; padding:0 15px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
        .page-title  { font-size:24px; font-weight:bold; color:#c8a; }

        .card { background:#1a1a2a; border:2px solid #3a3a6a; border-radius:10px; overflow:hidden; margin-bottom:15px; }
        .card-header { background:#252540; padding:12px 16px; font-weight:bold; color:#c8a; font-size:15px; display:flex; justify-content:space-between; align-items:center; }
        .card-body   { padding:20px; }

        /* Шапка тёмного рынка */
        .bm-hero {
            background:linear-gradient(135deg,#0f0f2a,#1a0a2a);
            border:2px solid #6a2a8a; border-radius:10px;
            padding:20px; margin-bottom:20px; text-align:center;
        }
        .bm-icon  { font-size:64px; margin-bottom:10px; display:block; }
        .bm-title { font-size:22px; font-weight:bold; color:#c8a; margin-bottom:8px; }
        .bm-desc  { color:#888; font-size:13px; line-height:1.6; }

        /* Таймер сброса */
        .reset-timer {
            display:inline-block; background:rgba(0,0,0,0.4);
            border:1px solid #6a2a8a; border-radius:6px;
            padding:8px 16px; margin-top:12px; font-family:monospace;
        }

        /* Категории */
        .category-title {
            font-size:16px; font-weight:bold; margin:20px 0 12px;
            display:flex; align-items:center; gap:8px;
        }

        /* Товары */
        .items-grid {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
            gap:12px;
        }
        .item-card {
            background:#0f0f1a; border:2px solid #3a3a5a;
            border-radius:8px; padding:16px; transition:0.2s;
        }
        .item-card:hover { border-color:#8a2a8a; transform:translateY(-2px); }
        .item-card.sold-out { opacity:0.5; }
        .item-card.daily-limit { border-color:#5a3a1a; }

        .item-icon { font-size:36px; margin-bottom:8px; display:block; }
        .item-name { font-size:14px; font-weight:bold; color:#c8a; margin-bottom:4px; }
        .item-desc { font-size:12px; color:#888; margin-bottom:10px; line-height:1.5; }

        .item-price {
            display:flex; gap:8px; flex-wrap:wrap;
            font-size:12px; margin-bottom:10px;
        }
        .price-chip { color:#d4a843; }

        .item-stock {
            font-size:11px; margin-bottom:8px;
        }
        .stock-ok   { color:#4f4; }
        .stock-low  { color:#fa4; }
        .stock-none { color:#f44; }

        .btn-buy {
            width:100%; padding:8px; background:#4a1a5a;
            color:#c8a; border:1px solid #8a2a8a;
            border-radius:5px; cursor:pointer; font-size:13px;
            transition:0.2s;
        }
        .btn-buy:hover { background:#6a2a8a; color:#fff; }
        .btn-buy:disabled { background:#1a1a2a; color:#555; border-color:#333; cursor:not-allowed; }

        /* Деревня для покупки */
        .village-selector {
            background:#1a1a2a; border:1px solid #3a3a6a;
            border-radius:6px; padding:12px 16px;
            display:flex; align-items:center; gap:12px;
            margin-bottom:20px; flex-wrap:wrap;
        }
        .village-selector label { color:#888; font-size:13px; }
        .village-selector select {
            padding:7px; background:#0f0f1a; color:#ddd;
            border:1px solid #3a3a6a; border-radius:4px; font-size:13px;
        }

        /* Наёмники */
        .merc-item {
            display:flex; align-items:center; gap:12px;
            padding:10px; background:#1a1a0a; border:1px solid #5a4a20;
            border-radius:6px; margin-bottom:8px; font-size:13px;
        }
        .merc-info { flex:1; }
        .merc-timer{ font-family:monospace; color:#fa4; font-weight:bold; }

        /* История */
        .history-item {
            display:flex; align-items:center; gap:10px;
            padding:8px 0; border-bottom:1px solid #333; font-size:12px;
        }
        .history-item:last-child { border-bottom:none; }

        .alert-success { background:#1a3a1a; border:1px solid #4a4; border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0; }
        .alert-error   { background:#3a1a1a; border:1px solid #a44; border-radius:6px; padding:12px; margin-bottom:15px; color:#f66; }

        @media(max-width:600px) { .items-grid{grid-template-columns:1fr;} }
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
        <div class="page-title">🌑 Тёмный рынок</div>
    </div>

    <!-- Шапка -->
    <div class="bm-hero">
        <span class="bm-icon">🌑</span>
        <div class="bm-title">Добро пожаловать в Тёмный рынок</div>
        <div class="bm-desc">
            Здесь можно найти то, чего нет нигде больше.<br>
            Ускорения, наёмники, редкие ресурсы — всё за правильную цену.
        </div>
        <div class="reset-timer">
            🔄 Сброс ассортимента через:
            <span id="resetTimer" data-end="<?= $next_reset ?>">
                <?= gmdate('H:i:s', $next_reset - time()) ?>
            </span>
        </div>
    </div>

    <!-- Выбор деревни -->
    <div class="village-selector">
        <label>🏘 Деревня для оплаты:</label>
        <select id="villageSelect" onchange="updatePriceChecks()">
            <?php foreach ($villages as $v): ?>
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

    <!-- Активные наёмники -->
    <?php if (!empty($active_mercs)): ?>
    <div class="card">
        <div class="card-header">
            ⚔ Активные наёмники
            <span style="font-size:12px;color:#888;"><?= count($active_mercs) ?></span>
        </div>
        <div class="card-body">
            <?php foreach ($active_mercs as $m):
                $troops = json_decode($m['troops'], true);
                $remaining = max(0, $m['expires_at'] - time());
            ?>
            <div class="merc-item">
                <span style="font-size:24px;">⚔</span>
                <div class="merc-info">
                    <div style="color:#d4a843;font-weight:bold;">
                        <?= htmlspecialchars($m['village_name']) ?>
                    </div>
                    <div style="font-size:11px;color:#888;">
                        <?php foreach ($troops as $type=>$count): ?>
                            <?= $type ?>: <?= $count ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="merc-timer"
                     data-end="<?= $m['expires_at'] ?>">
                    ⏱ <?= gmdate('H:i:s', $remaining) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Группируем товары по типу
    $grouped = [];
    foreach ($items as $item) {
        $grouped[$item['type']][] = $item;
    }
    $type_names = [
        'boost'     => ['🌟 Ускорения и бусты', '#fa4'],
        'resource'  => ['💰 Ресурсы',           '#d4a843'],
        'mercenary' => ['⚔ Наёмники',           '#f44'],
    ];
    ?>

    <!-- Товары по категориям -->
    <?php foreach ($type_names as $type => [$type_label, $type_color]): ?>
    <?php if (empty($grouped[$type])) continue; ?>

    <div class="category-title" style="color:<?= $type_color ?>;">
        <?= $type_label ?>
        <div style="flex:1;height:1px;background:<?= $type_color ?>22;margin-left:10px;"></div>
    </div>

    <div class="items-grid" style="margin-bottom:20px;">
        <?php foreach ($grouped[$type] as $item):
            $can_buy   = ($item['my_purchases_today'] < $item['max_per_day']) && $item['stock'] > 0;
            $card_class= 'item-card';
            if ($item['stock']==0) $card_class.=' sold-out';
            elseif (!$can_buy) $card_class.=' daily-limit';
        ?>
        <div class="<?= $card_class ?>">
            <span class="item-icon"><?= $item['icon'] ?></span>
            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="item-desc"><?= htmlspecialchars($item['description']) ?></div>

            <!-- Цена -->
            <div class="item-price">
                <?php if ($item['price_wood']>0):  ?><span class="price-chip" id="pw_<?= $item['id'] ?>">🪵<?= number_format($item['price_wood']) ?></span><?php endif; ?>
                <?php if ($item['price_stone']>0): ?><span class="price-chip" id="ps_<?= $item['id'] ?>">🪨<?= number_format($item['price_stone']) ?></span><?php endif; ?>
                <?php if ($item['price_iron']>0):  ?><span class="price-chip" id="pi_<?= $item['id'] ?>">⛏<?= number_format($item['price_iron']) ?></span><?php endif; ?>
                <?php if ($item['price_wood']+$item['price_stone']+$item['price_iron']==0): ?>
                    <span style="color:#4f4;">Бесплатно</span>
                <?php endif; ?>
            </div>

            <!-- Запас -->
            <div class="item-stock <?= $item['stock']>3?'stock-ok':($item['stock']>0?'stock-low':'stock-none') ?>">
                <?php if ($item['stock']==0): ?>
                    ❌ Нет в наличии
                <?php elseif ($item['stock']<=3): ?>
                    ⚠ Осталось: <?= $item['stock'] ?>
                <?php else: ?>
                    ✅ В наличии: <?= $item['stock'] ?>
                <?php endif; ?>
            </div>

            <!-- Лимит -->
            <div style="font-size:10px;color:#555;margin-bottom:8px;">
                Куплено сегодня: <?= $item['my_purchases_today'] ?>/<?= $item['max_per_day'] ?>
            </div>

            <!-- Кнопка покупки -->
            <form method="POST" action="?page=black_market&action=buy">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <input type="hidden" name="village_id" id="vid_<?= $item['id'] ?>">
                <button type="submit" class="btn-buy"
                        <?= !$can_buy?'disabled':'' ?>
                        onclick="document.getElementById('vid_<?= $item['id'] ?>').value=document.getElementById('villageSelect').value;
                                 return confirm('Купить «<?= htmlspecialchars($item['name'],ENT_QUOTES) ?>»?')">
                    <?php if ($item['stock']==0): ?>
                        Нет в наличии
                    <?php elseif (!$can_buy): ?>
                        Дневной лимит
                    <?php else: ?>
                        🌑 Купить
                    <?php endif; ?>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endforeach; ?>

    <!-- История покупок -->
    <?php if (!empty($purchase_history)): ?>
    <div class="card">
        <div class="card-header">📋 История покупок</div>
        <div class="card-body">
            <?php foreach ($purchase_history as $h):
                $diff = time() - $h['purchased_at'];
                if ($diff<3600) $ts=floor($diff/60)." мин. назад";
                elseif ($diff<86400) $ts=floor($diff/3600)." ч. назад";
                else $ts=date('d.m.Y', $h['purchased_at']);
            ?>
            <div class="history-item">
                <span style="font-size:18px;"><?= $h['icon'] ?></span>
                <div style="flex:1;"><?= htmlspecialchars($h['name']) ?></div>
                <div style="color:#555;"><?= $ts ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Таймер сброса
const resetEl = document.getElementById('resetTimer');
if (resetEl) {
    const end = parseInt(resetEl.dataset.end);
    function updateReset() {
        const rem = Math.max(0, end - Math.floor(Date.now()/1000));
        const h=Math.floor(rem/3600), m=Math.floor((rem%3600)/60), s=rem%60;
        resetEl.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (rem<=0) location.reload();
    }
    setInterval(updateReset,1000); updateReset();
}

// Таймеры наёмников
document.querySelectorAll('.merc-timer[data-end]').forEach(el => {
    const end=parseInt(el.dataset.end);
    function tick() {
        const rem=Math.max(0,end-Math.floor(Date.now()/1000));
        const h=Math.floor(rem/3600), m=Math.floor((rem%3600)/60), s=rem%60;
        el.textContent=`⏱ ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (rem<=0) location.reload();
    }
    setInterval(tick,1000); tick();
});
</script>

</body>
</html>