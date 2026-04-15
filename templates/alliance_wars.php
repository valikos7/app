<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Войны альянса — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1000px; margin:20px auto; padding:0 15px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
        .page-title  { font-size:24px; font-weight:bold; color:#d4a843; }

        .card { background:#2a2a1a; border:2px solid #5a4a20; border-radius:10px; overflow:hidden; margin-bottom:15px; }
        .card-header { background:#3a2c10; padding:12px 16px; font-weight:bold; color:#d4a843; font-size:15px; display:flex; justify-content:space-between; align-items:center; }
        .card-body { padding:20px; }

        /* Активная война */
        .war-card {
            background:#2a1a1a; border:2px solid #8a1a1a;
            border-radius:10px; padding:20px; margin-bottom:15px;
        }
        .war-card.ended { background:#1a2a1a; border-color:#2a5a2a; }

        .war-teams {
            display:grid; grid-template-columns:1fr auto 1fr;
            gap:15px; align-items:center; margin-bottom:15px;
        }
        .war-team { text-align:center; }
        .war-tag  { font-size:22px; font-weight:bold; color:#d4a843; }
        .war-name { font-size:13px; color:#888; margin-top:3px; }
        .war-score-big { font-size:42px; font-weight:bold; }
        .war-score-att { color:#f44; }
        .war-score-def { color:#44f; }
        .vs-label { font-size:18px; color:#888; font-weight:bold; text-align:center; }

        /* Прогресс бар войны */
        .war-bar { height:12px; background:#333; border-radius:6px; overflow:hidden; margin:10px 0; position:relative; }
        .war-bar-att { height:100%; float:left; background:linear-gradient(90deg,#8a1a1a,#f44); transition:0.5s; }
        .war-bar-def { height:100%; float:right; background:linear-gradient(90deg,#44f,#1a1a8a); transition:0.5s; }

        /* Таймер */
        .war-timer { text-align:center; font-size:14px; color:#888; }
        .war-timer strong { color:#fa4; font-family:monospace; font-size:18px; }

        /* Победитель */
        .war-winner { text-align:center; padding:10px; background:#1a3a1a; border-radius:6px; color:#4f4; font-weight:bold; }
        .war-draw   { text-align:center; padding:10px; background:#2a2a1a; border-radius:6px; color:#888; }

        /* Объявить войну */
        .declare-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .declare-form select { padding:8px; background:#1a1a0a; color:#ddd; border:1px solid #444; border-radius:4px; font-size:13px; min-width:200px; }

        .btn { display:inline-flex; align-items:center; gap:5px; padding:8px 18px; border-radius:5px; font-size:13px; border:none; cursor:pointer; transition:0.2s; text-decoration:none; }
        .btn-red    { background:#5a1a1a; color:#f66; border:1px solid #8a1a1a; }
        .btn-red:hover    { background:#7a2a2a; }
        .btn-gold   { background:#5a4a1a; color:#d4a843; border:1px solid #8b6914; }
        .btn-gold:hover   { background:#7a6a2a; }

        /* История */
        .hist-item { display:flex; gap:12px; align-items:center; padding:10px 0; border-bottom:1px solid #333; font-size:13px; }
        .hist-item:last-child { border-bottom:none; }

        .alert-success { background:#1a3a1a; border:1px solid #4a4; border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0; }
        .alert-error   { background:#3a1a1a; border:1px solid #a44; border-radius:6px; padding:12px; margin-bottom:15px; color:#f66; }

        @media(max-width:600px) { .war-teams{grid-template-columns:1fr;} }
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
        <div class="page-title">⚔ Войны альянса</div>
        <div style="font-size:13px;color:#888;">
            [{<?= htmlspecialchars($my_membership['tag']??'?') ?>}]
            <?= htmlspecialchars($my_membership['alliance_name']??'?') ?>
        </div>
    </div>

    <!-- Активные войны -->
    <?php if (!empty($active_wars)): ?>
    <div class="card">
        <div class="card-header">
            ⚔ Активные войны
            <span style="font-size:12px;color:#888;"><?= count($active_wars) ?></span>
        </div>
        <div class="card-body">
            <?php foreach ($active_wars as $war):
                $is_attacker = ($war['attacker_id']==$alliance_id);
                $my_score    = $is_attacker ? $war['att_score'] : $war['def_score'];
                $enemy_score = $is_attacker ? $war['def_score'] : $war['att_score'];
                $total       = max(1, $war['att_score'] + $war['def_score']);
                $att_pct     = round($war['att_score'] / $total * 100);
                $def_pct     = 100 - $att_pct;
                $rem         = max(0, $war['ends_at'] - time());
            ?>
            <div class="war-card">
                <div class="war-teams">
                    <div class="war-team">
                        <div class="war-tag">[<?= htmlspecialchars($war['att_tag']) ?>]</div>
                        <div class="war-name"><?= htmlspecialchars($war['att_name']) ?></div>
                        <div class="war-score-big war-score-att"><?= $war['att_score'] ?></div>
                    </div>
                    <div>
                        <div class="vs-label">VS</div>
                    </div>
                    <div class="war-team">
                        <div class="war-tag">[<?= htmlspecialchars($war['def_tag']) ?>]</div>
                        <div class="war-name"><?= htmlspecialchars($war['def_name']) ?></div>
                        <div class="war-score-big war-score-def"><?= $war['def_score'] ?></div>
                    </div>
                </div>

                <div class="war-bar">
                    <div class="war-bar-att" style="width:<?= $att_pct ?>%;"></div>
                    <div class="war-bar-def" style="width:<?= $def_pct ?>%;"></div>
                </div>

                <div class="war-timer">
                    До конца войны:
                    <strong data-end="<?= $war['ends_at'] ?>">
                        <?= floor($rem/86400) ?>д <?= gmdate('H:i:s',$rem%86400) ?>
                    </strong>
                </div>

                <?php if (in_array($my_role,['leader','officer'])): ?>
                <div style="margin-top:12px;text-align:center;">
                    <span style="font-size:12px;color:#888;">
                        Очки за победу в атаке: +<?= AllianceWarController::SCORE_ATTACK_WIN ?>
                        · За защиту: +<?= AllianceWarController::SCORE_DEFEND_WIN ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div style="background:#2a2a1a;border:2px solid #5a4a20;border-radius:10px;padding:30px;text-align:center;margin-bottom:15px;color:#666;">
        <div style="font-size:40px;margin-bottom:10px;">⚔</div>
        Нет активных войн
    </div>
    <?php endif; ?>

    <!-- Объявить войну -->
    <?php if (in_array($my_role,['leader','officer'])): ?>
    <div class="card">
        <div class="card-header">⚔ Объявить войну</div>
        <div class="card-body">
            <p style="color:#888;font-size:13px;margin-bottom:15px;">
                Война длится 7 дней. Очки начисляются за победы в PvP атаках против членов вражеского альянса.
                Альянс с большим количеством очков побеждает.
            </p>
            <form method="POST" action="?page=alliance_wars&action=declare"
                  class="declare-form"
                  onsubmit="return confirm('Объявить войну этому альянсу? Это действие необратимо!')">
                <select name="target_alliance_id" required>
                    <option value="">Выберите альянс...</option>
                    <?php foreach ($all_alliances as $a): ?>
                    <option value="<?= $a['id'] ?>">
                        [<?= htmlspecialchars($a['tag']) ?>] <?= htmlspecialchars($a['name']) ?>
                        (<?= $a['members_count'] ?> чел., <?= number_format($a['points']) ?> очков)
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-red">⚔ Объявить войну</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- История войн -->
    <?php if (!empty($ended_wars)): ?>
    <div class="card">
        <div class="card-header">
            📋 История войн
            <span style="font-size:12px;color:#888;"><?= count($ended_wars) ?></span>
        </div>
        <div class="card-body">
            <?php foreach ($ended_wars as $war):
                $won = ($war['winner_id']==$alliance_id);
                $draw= ($war['winner_id']===null||$war['winner_id']==0);
            ?>
            <div class="hist-item">
                <div style="font-size:20px;"><?= $draw?'🤝':($won?'🏆':'💀') ?></div>
                <div style="flex:1;">
                    <div style="font-weight:bold;color:<?= $draw?'#888':($won?'#4f4':'#f44') ?>;">
                        [<?= htmlspecialchars($war['att_tag']) ?>] vs [<?= htmlspecialchars($war['def_tag']) ?>]
                    </div>
                    <div style="font-size:11px;color:#666;">
                        Счёт: <?= $war['att_score'] ?> : <?= $war['def_score'] ?>
                        · <?= date('d.m.Y', $war['ends_at']) ?>
                    </div>
                </div>
                <div style="font-size:12px;font-weight:bold;color:<?= $draw?'#888':($won?'#4f4':'#f44') ?>;">
                    <?= $draw?'Ничья':($won?'Победа':'Поражение') ?>
                </div>
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
        const d=Math.floor(rem/86400);
        const h=Math.floor((rem%86400)/3600);
        const m=Math.floor((rem%3600)/60);
        const s=rem%60;
        el.textContent=`${d}д ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (rem<=0) location.reload();
    }
    setInterval(tick,1000); tick();
});
</script>

</body>
</html>