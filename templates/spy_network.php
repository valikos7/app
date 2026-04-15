<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Шпионская сеть — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#0f0f1a; color:#ddd; }
        .container { max-width:1000px; margin:20px auto; padding:0 15px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
        .page-title  { font-size:24px; font-weight:bold; color:#88f; }

        .card { background:#1a1a2a; border:2px solid #3a3a6a; border-radius:10px; overflow:hidden; margin-bottom:15px; }
        .card-header { background:#252540; padding:12px 16px; font-weight:bold; color:#88f; font-size:15px; display:flex; justify-content:space-between; align-items:center; }
        .card-body   { padding:20px; }

        /* Герой */
        .spy-hero {
            background:linear-gradient(135deg,#0f0f2a,#1a0a2a);
            border:2px solid #6a2a8a; border-radius:12px;
            padding:20px; text-align:center; margin-bottom:20px;
        }
        .spy-icon  { font-size:64px; margin-bottom:10px; }
        .spy-title { font-size:20px; font-weight:bold; color:#c8a; margin-bottom:8px; }
        .spy-desc  { font-size:13px; color:#888; line-height:1.6; }
        .spy-cost  { display:flex; gap:10px; justify-content:center; margin-top:12px; flex-wrap:wrap; }
        .cost-chip { background:rgba(0,0,0,0.3); border:1px solid #6a2a8a; border-radius:6px; padding:6px 14px; font-size:13px; color:#c8a; }

        /* Мои шпионы */
        .spy-item {
            display:flex; align-items:center; gap:12px;
            padding:12px; background:#0f0f1a; border:2px solid #3a3a5a;
            border-radius:8px; margin-bottom:10px; transition:0.2s;
        }
        .spy-item:hover { border-color:#8a2a8a; }
        .spy-target { flex:1; }
        .spy-name   { font-weight:bold; color:#c8a; font-size:14px; }
        .spy-meta   { font-size:11px; color:#666; margin-top:3px; }
        .spy-timer  { font-family:monospace; color:#88f; font-size:14px; font-weight:bold; }

        /* Форма */
        .plant-form { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
        .plant-form select { padding:8px; background:#0f0f1a; color:#ddd; border:1px solid #3a3a6a; border-radius:4px; font-size:13px; min-width:200px; }

        .btn { display:inline-flex; align-items:center; gap:5px; padding:8px 18px; border-radius:5px; font-size:13px; border:none; cursor:pointer; transition:0.2s; text-decoration:none; }
        .btn-spy { background:#4a1a5a; color:#c8a; border:1px solid #8a2a8a; }
        .btn-spy:hover { background:#6a2a8a; color:#fff; }
        .btn-info { background:#1a1a5a; color:#88f; border:1px solid #2a2a8a; }
        .btn-info:hover { background:#2a2a7a; }

        /* Обнаруженные */
        .burned-item {
            display:flex; gap:10px; padding:8px 0;
            border-bottom:1px solid #333; font-size:12px;
        }
        .burned-item:last-child { border-bottom:none; }

        .alert-success { background:#1a3a1a; border:1px solid #4a4; border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0; }
        .alert-error   { background:#3a1a1a; border:1px solid #a44; border-radius:6px; padding:12px; margin-bottom:15px; color:#f66; }

        @media(max-width:600px) { .plant-form{flex-direction:column;} .spy-cost{flex-direction:column;align-items:center;} }
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
        <div class="page-title">🕵 Шпионская сеть</div>
    </div>

    <!-- Герой -->
    <div class="spy-hero">
        <div class="spy-icon">🕵</div>
        <div class="spy-title">Шпионская сеть альянса</div>
        <div class="spy-desc">
            Внедряйте шпионов в вражеские альянсы.<br>
            Получайте данные об участниках, их движениях войск и силе.
        </div>
        <div class="spy-cost">
            <div class="cost-chip">🪵 <?= number_format($costs['wood']) ?></div>
            <div class="cost-chip">🪨 <?= number_format($costs['stone']) ?></div>
            <div class="cost-chip">⛏ <?= number_format($costs['iron']) ?></div>
            <div class="cost-chip">⏱ 7 дней</div>
            <div class="cost-chip">⚠ Риск провала: 15%/день</div>
        </div>
    </div>

    <!-- Мои активные шпионы -->
    <div class="card">
        <div class="card-header">
            🕵 Мои шпионы
            <span style="font-size:12px;color:#888;"><?= count($my_spies) ?> активных</span>
        </div>
        <div class="card-body">
            <?php if (empty($my_spies)): ?>
            <div style="text-align:center;padding:20px;color:#555;">
                У вас нет активных шпионов
            </div>
            <?php else: ?>
            <?php foreach ($my_spies as $spy):
                $rem = max(0, $spy['expires_at'] - time());
            ?>
            <div class="spy-item">
                <div style="font-size:28px;">🕵</div>
                <div class="spy-target">
                    <div class="spy-name">
                        [<?= htmlspecialchars($spy['target_tag']) ?>]
                        <?= htmlspecialchars($spy['target_name']) ?>
                    </div>
                    <div class="spy-meta">
                        Внедрён: <?= date('d.m.Y H:i', $spy['planted_at']) ?>
                    </div>
                </div>
                <div class="spy-timer" data-end="<?= $spy['expires_at'] ?>">
                    ⏱ <?= floor($rem/86400) ?>д <?= gmdate('H:i:s',$rem%86400) ?>
                </div>
                <a href="?page=spy_network&action=report&spy_id=<?= $spy['id'] ?>"
                   class="btn btn-info">
                    📊 Отчёт
                </a>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Внедрить шпиона -->
    <div class="card">
        <div class="card-header">🕵 Внедрить шпиона</div>
        <div class="card-body">
            <form method="POST" action="?page=spy_network&action=plant" class="plant-form">

                <div>
                    <label style="color:#888;font-size:12px;display:block;margin-bottom:4px;">Целевой альянс:</label>
                    <select name="target_alliance_id" required>
                        <option value="">Выберите альянс...</option>
                        <?php foreach ($all_alliances as $a): ?>
                        <option value="<?= $a['id'] ?>">
                            [<?= htmlspecialchars($a['tag']) ?>] <?= htmlspecialchars($a['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="color:#888;font-size:12px;display:block;margin-bottom:4px;">Оплатить из:</label>
                    <select name="village_id" required>
                        <?php foreach ($my_villages as $v): ?>
                        <option value="<?= $v['id'] ?>">
                            <?= htmlspecialchars($v['name']) ?>
                            — 🪵<?= number_format($v['r_wood']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-spy"
                        onclick="return confirm('Внедрить шпиона? Стоимость: 🪵'.+SpyCost+' 🪨...')">
                    🕵 Внедрить
                </button>
            </form>
        </div>
    </div>

    <!-- Обнаруженные шпионы -->
    <?php if (!empty($burned_spies)): ?>
    <div class="card">
        <div class="card-header">
            🔍 Обнаруженные шпионы
            <span style="font-size:12px;color:#888;"><?= count($burned_spies) ?></span>
        </div>
        <div class="card-body">
            <?php foreach ($burned_spies as $b): ?>
            <div class="burned-item">
                <span style="font-size:18px;">🔍</span>
                <div style="flex:1;">
                    <strong style="color:#f44;"><?= htmlspecialchars($b['spy_name']??'?') ?></strong>
                    из альянса <?= htmlspecialchars($b['spy_alliance']??'?') ?>
                </div>
                <span style="color:#555;"><?= date('d.m.Y', $b['planted_at']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
document.querySelectorAll('[data-end]').forEach(el => {
    const end=parseInt(el.dataset.end);
    function tick() {
        const rem=Math.max(0,end-Math.floor(Date.now()/1000));
        if (rem<=0) { el.textContent='Истёк'; return; }
        const d=Math.floor(rem/86400);
        const h=Math.floor((rem%86400)/3600);
        const m=Math.floor((rem%3600)/60);
        const s=rem%60;
        el.textContent=`⏱ ${d}д ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    }
    setInterval(tick,1000); tick();
});
</script>

</body>
</html>