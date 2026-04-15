<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container {
            max-width:1100px; margin:20px auto; padding:0 15px;
            display:grid; grid-template-columns:310px 1fr; gap:20px;
        }
        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:10px 15px;
            font-weight:bold; color:#d4a843; font-size:14px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .card-body { padding:15px; }
        .avatar {
            width:80px; height:80px; background:#3a2c10;
            border:3px solid #d4a843; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:36px; margin:0 auto 15px;
        }
        .player-name { text-align:center; font-size:22px; font-weight:bold; color:#d4a843; }
        .player-rank { text-align:center; color:#888; font-size:12px; margin-top:4px; }
        .stat-row {
            display:flex; justify-content:space-between;
            padding:8px 0; border-bottom:1px solid #333; font-size:13px;
        }
        .stat-row:last-child { border-bottom:none; }
        .stat-label { color:#888; }
        .stat-value { font-weight:bold; color:#d4a843; }
        .btn {
            display:block; padding:9px 14px; background:#3a2c10;
            color:#d4a843; text-decoration:none; border-radius:5px;
            font-size:13px; border:1px solid #8b6914; transition:0.2s;
            text-align:center; margin-bottom:6px;
        }
        .btn:hover { background:#5a4a20; }
        .btn-hero   { background:#2a1a3a; border-color:#8a2a8a; color:#c8a; }
        .btn-hero:hover  { background:#3a2a5a; }
        .btn-red    { background:#3a1a1a; border-color:#8a1a1a; color:#f66; }
        .btn-red:hover    { background:#5a2a2a; }
        .btn-green  { background:#1a3a1a; border-color:#2a8a2a; color:#4f4; }
        .btn-green:hover  { background:#2a5a2a; }
        .btn-blue   { background:#1a1a3a; border-color:#2a2a8a; color:#88f; }
        .btn-blue:hover   { background:#2a2a5a; }
        .btn-gold   { background:#3a2c10; border-color:#d4a843; color:#d4a843; }
        .btn-gold:hover   { background:#5a4a20; }
        .btn-purple { background:#2a1a3a; border-color:#6a2a8a; color:#c8a; }
        .btn-purple:hover { background:#3a2a5a; }
        .villages-table { width:100%; border-collapse:collapse; }
        .villages-table th {
            background:#3a2c10; color:#d4a843;
            padding:10px 12px; text-align:left; font-size:13px;
        }
        .villages-table td {
            padding:9px 12px; border-bottom:1px solid #333; font-size:13px;
        }
        .villages-table tr:hover td { background:#2a2010; }
        .movement-row {
            padding:10px 12px; border-bottom:1px solid #333;
            font-size:13px; display:flex; gap:10px; align-items:center;
        }
        .movement-row:last-child { border-bottom:none; }
        .mov-type { font-size:18px; min-width:24px; text-align:center; }
        .mov-info { flex:1; min-width:0; }
        .mov-timer { color:#0f0; font-weight:bold; font-size:13px; white-space:nowrap; }

        /* Мини-карточка героя */
        .hero-mini-card {
            background:#1a1a2a; border:2px solid #6a2a8a;
            border-radius:8px; padding:14px; margin-bottom:10px;
            text-decoration:none; display:block; transition:0.2s;
        }
        .hero-mini-card:hover { border-color:#c8a; }
        .hero-mini-header {
            display:flex; align-items:center; gap:10px; margin-bottom:10px;
        }
        .hero-mini-icon { font-size:32px; }
        .hero-mini-name { font-size:14px; font-weight:bold; color:#c8a; }
        .hero-mini-level{ font-size:11px; color:#888; }
        .hero-hp-bar {
            height:6px; background:#333; border-radius:3px; overflow:hidden; margin:5px 0;
        }
        .hero-hp-fill {
            height:100%; border-radius:3px;
            background:linear-gradient(90deg,#2a8a2a,#4f4); transition:0.3s;
        }
        .hero-hp-fill.low    { background:linear-gradient(90deg,#8a2a2a,#f44); }
        .hero-hp-fill.medium { background:linear-gradient(90deg,#8a6a1a,#fa4); }
        .hero-exp-bar {
            height:4px; background:#333; border-radius:2px; overflow:hidden;
        }
        .hero-exp-fill { height:100%; background:linear-gradient(90deg,#1a1a8a,#88f); border-radius:2px; }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4; border-radius:6px;
            padding:12px; margin-bottom:15px; color:#0f0; font-size:13px;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44; border-radius:6px;
            padding:12px; margin-bottom:15px; color:#f66; font-size:13px;
        }
        @media(max-width:800px) { .container{grid-template-columns:1fr;} }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <!-- Левая колонка -->
    <div>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Профиль -->
        <div class="card">
            <div class="card-header">👤 Профиль</div>
            <div class="card-body">
                <div class="avatar">👑</div>
                <div class="player-name"><?= htmlspecialchars($user['username']) ?></div>
                <div class="player-rank">
                    <?= !empty($user['is_admin']) ? '⚙ Администратор' : 'Игрок' ?>
                    · <?= APP_NAME ?>
                </div>
                <div style="margin-top:15px;">
                    <div class="stat-row">
                        <span class="stat-label">Очки</span>
                        <span class="stat-value"><?= number_format($user['points']??0) ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Деревень</span>
                        <span class="stat-value"><?= $user['villages']??0 ?></span>
                    </div>
                    <?php if (!empty($user['alliance_id'])): ?>
                    <div class="stat-row">
                        <span class="stat-label">Альянс</span>
                        <span class="stat-value" style="font-size:12px;"><?= htmlspecialchars($user['alliance_role']??'') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="stat-row">
                        <span class="stat-label">На сервере с</span>
                        <span class="stat-value"><?= date('d.m.Y', $user['join_date']??time()) ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Последний вход</span>
                        <span class="stat-value" style="font-size:12px;"><?= date('d.m.Y H:i', $user['last_activity']??time()) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Мини-карточка героя -->
        <?php
        try {
            $hc   = new HeroController($db);
            $hero = $hc->getOrCreateHero($user['id']);
            if ($hero && $hero['status'] === 'regenerating' && $hero['revive_time'] <= time()) {
                $hc->reviveHero($user['id']);
                $hero = $hc->getHero($user['id']);
            }
        } catch (Exception $e) { $hero = null; }

        if ($hero):
            $hp_pct   = $hero['hp_max'] > 0 ? min(100, round($hero['hp']/$hero['hp_max']*100)) : 0;
            $exp_pct  = $hero['exp_to_next'] > 0 ? min(100, round($hero['experience']/$hero['exp_to_next']*100)) : 0;
            $hp_class = $hp_pct > 60 ? '' : ($hp_pct > 30 ? 'medium' : 'low');
            $icons_by_level = [1=>'👶',3=>'🧑',5=>'🧔',8=>'⚔️',10=>'🦸',13=>'🗡️',15=>'👑',18=>'🔱',20=>'💎'];
            $hero_icon = '👶';
            foreach ($icons_by_level as $min_lvl => $icon) {
                if ($hero['level'] >= $min_lvl) $hero_icon = $icon;
            }
        ?>
        <a href="?page=hero" class="hero-mini-card">
            <div class="hero-mini-header">
                <span class="hero-mini-icon"><?= $hero_icon ?></span>
                <div>
                    <div class="hero-mini-name">
                        <?= htmlspecialchars($hero['name']) ?>
                        <?php
                        $st_str = $hero['status']==='alive' ? '<span style="color:#4f4;">✅ Жив</span>' :
                                 ($hero['status']==='dead'  ? '<span style="color:#f44;">💀 Мёртв</span>' :
                                                              '<span style="color:#88f;">🔄 Возрождение</span>');
                        echo $st_str;
                        ?>
                    </div>
                    <div class="hero-mini-level">Уровень <?= $hero['level'] ?> / 20</div>
                </div>
            </div>
            <div style="font-size:11px; color:#888; margin-bottom:4px;">
                ❤ <?= $hero['hp'] ?> / <?= $hero['hp_max'] ?>
            </div>
            <div class="hero-hp-bar">
                <div class="hero-hp-fill <?= $hp_class ?>" style="width:<?= $hp_pct ?>%;"></div>
            </div>
            <div style="font-size:11px; color:#888; margin:5px 0 4px;">
                ✨ Опыт: <?= number_format($hero['experience']) ?> / <?= number_format($hero['exp_to_next']) ?>
            </div>
            <div class="hero-exp-bar">
                <div class="hero-exp-fill" style="width:<?= $exp_pct ?>%;"></div>
            </div>
            <?php if ($hero['skill_points'] > 0): ?>
            <div style="margin-top:6px; font-size:11px; color:#4f4;">
                🌟 Доступно <?= $hero['skill_points'] ?> очков навыков!
            </div>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <!-- Быстрые действия -->
        <div class="card">
            <div class="card-header">⚡ Действия</div>
            <div class="card-body">
                <a href="?page=villages"      class="btn">🏘 Мои деревни</a>
                <a href="?page=map"           class="btn">🗺 Карта мира</a>
                <a href="?page=hero"          class="btn btn-hero">🦸 Мой герой</a>
                <a href="?page=market"        class="btn btn-gold">💰 Рынок</a>
                <a href="?page=support"       class="btn btn-blue">🛡 Поддержка</a>
                <a href="?page=reports"       class="btn">
                    📋 Отчёты
                    <?php if ($has_reports > 0): ?>
                        <span style="background:#c00;color:#fff;border-radius:10px;
                                     padding:1px 6px;font-size:10px;margin-left:4px;">
                            <?= $has_reports ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="?page=messages"      class="btn">✉ Сообщения</a>
                <a href="?page=stats"         class="btn">📊 Статистика</a>
                <a href="?page=ranking"       class="btn">🏆 Рейтинг</a>
				<a href="?page=quests" class="btn btn-green">📋 Квесты</a>
				<a href="?page=technologies" class="btn">🔬 Технологии</a>
                <a href="?page=alliances"     class="btn">🏰 Альянсы</a>
                <?php if (!empty($user['alliance_id'])): ?>
                <a href="?page=alliance_chat" class="btn btn-purple">💬 Чат альянса</a>
                <a href="?page=diplomacy"     class="btn btn-purple">🤝 Дипломатия</a>
                <?php endif; ?>
                <?php if (!empty($user['is_admin'])): ?>
                <a href="?page=admin"         class="btn btn-gold">⚙ Админ панель</a>
                <?php endif; ?>
                <a href="?page=logout"        class="btn btn-red">🚪 Выйти</a>
            </div>
        </div>
    </div>

    <!-- Правая колонка -->
    <div>

        <!-- Деревни -->
        <div class="card">
            <div class="card-header">
                🏘 Мои деревни
                <span style="font-size:12px;color:#aaa;"><?= count($villages) ?> шт.</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($villages)): ?>
                    <div style="padding:20px;text-align:center;color:#666;">Деревень нет</div>
                <?php else: ?>
                <table class="villages-table">
                    <tr>
                        <th>Деревня</th>
                        <th>Координаты</th>
                        <th>Очки</th>
                        <th>Ресурсы</th>
                        <th>Действия</th>
                    </tr>
                    <?php foreach ($villages as $v): ?>
                    <tr>
                        <td>
                            <a href="?page=village&id=<?= $v['id'] ?>"
                               style="color:#d4a843;text-decoration:none;font-weight:bold;">
                                <?= htmlspecialchars($v['name']) ?>
                            </a>
                            <br><span style="color:#666;font-size:11px;">K<?= $v['continent']??'?' ?></span>
                        </td>
                        <td style="color:#888;font-size:12px;"><?= $v['x'] ?>|<?= $v['y'] ?></td>
                        <td style="color:#d4a843;font-weight:bold;"><?= number_format($v['points']) ?></td>
                        <td style="font-size:11px;line-height:1.7;">
                            🪵<?= number_format($v['r_wood']) ?><br>
                            🪨<?= number_format($v['r_stone']) ?><br>
                            ⛏<?= number_format($v['r_iron']) ?>
                        </td>
                        <td>
                            <a href="?page=village&id=<?= $v['id'] ?>"
                               class="btn" style="margin-bottom:4px;padding:6px 10px;font-size:12px;">
                                Управлять
                            </a>
                            <a href="?page=map&x=<?= $v['x'] ?>&y=<?= $v['y'] ?>"
                               class="btn btn-blue" style="padding:6px 10px;font-size:12px;">
                                На карте
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Активные походы -->
        <?php
        $stmt = $db->prepare("
            SELECT tm.*,
                   v1.name as from_name, v1.x as fx, v1.y as fy,
                   v2.name as to_name,   v2.x as tx, v2.y as ty
            FROM troop_movements tm
            LEFT JOIN villages v1 ON tm.from_village_id=v1.id
            LEFT JOIN villages v2 ON tm.to_village_id=v2.id
            WHERE tm.attacker_id=? AND tm.status='moving'
            ORDER BY tm.arrival_time ASC LIMIT 20
        ");
        $stmt->execute([$user['id']]);
        $movements = $stmt->fetchAll();

        if (!empty($movements)):
        $type_info = [
            'attack'  => ['⚔','#f44','Атака'],
            'return'  => ['🔙','#4f4','Возврат'],
            'support' => ['🛡','#88f','Поддержка'],
            'scout'   => ['🔍','#fa4','Разведка'],
        ];
        ?>
        <div class="card">
            <div class="card-header">
                🚶 Активные походы
                <span style="font-size:12px;color:#aaa;"><?= count($movements) ?></span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php foreach ($movements as $mov):
                    $remaining = max(0,$mov['arrival_time']-time());
                    $mins = floor($remaining/60); $secs = $remaining%60;
                    $ti   = $type_info[$mov['type']] ?? ['?','#888','?'];
                ?>
                <div class="movement-row">
                    <div class="mov-type"><?= $ti[0] ?></div>
                    <div class="mov-info">
                        <div style="font-size:13px;color:<?= $ti[1] ?>;"><?= $ti[2] ?></div>
                        <div style="font-size:11px;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= htmlspecialchars(mb_substr($mov['from_name']??'?',0,12)) ?>
                            (<?= $mov['fx'] ?>|<?= $mov['fy'] ?>) →
                            <?= htmlspecialchars(mb_substr($mov['to_name']??'?',0,12)) ?>
                            (<?= $mov['tx'] ?>|<?= $mov['ty'] ?>)
                        </div>
                    </div>
                    <div class="mov-timer" data-end="<?= $mov['arrival_time'] ?>">
                        <?= $mins ?>м <?= str_pad($secs,2,'0',STR_PAD_LEFT) ?>с
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Входящие атаки -->
        <?php
        $stmt = $db->prepare("
            SELECT tm.*,
                   v2.name as to_name, v2.x as tx, v2.y as ty
            FROM troop_movements tm
            LEFT JOIN villages v2 ON tm.to_village_id=v2.id
            WHERE v2.userid=? AND tm.attacker_id!=?
            AND tm.type='attack' AND tm.status='moving'
            ORDER BY tm.arrival_time ASC
        ");
        $stmt->execute([$user['id'],$user['id']]);
        $incoming = $stmt->fetchAll();

        if (!empty($incoming)):
        ?>
        <div class="card" style="border-color:#a44;">
            <div class="card-header" style="background:#3a1a1a;color:#f44;">
                ⚠ Входящие атаки
                <span style="font-size:12px;"><?= count($incoming) ?> атак!</span>
            </div>
            <div class="card-body" style="padding:0;">
                <?php foreach ($incoming as $att):
                    $remaining = max(0,$att['arrival_time']-time());
                    $mins = floor($remaining/60); $secs = $remaining%60;
                ?>
                <div class="movement-row" style="background:#2a1a1a;">
                    <div class="mov-type">⚔</div>
                    <div class="mov-info">
                        <div style="font-size:13px;color:#f44;">
                            Атака на <?= htmlspecialchars($att['to_name']??'?') ?>
                        </div>
                        <div style="font-size:11px;color:#666;">(<?= $att['tx'] ?>|<?= $att['ty'] ?>)</div>
                    </div>
                    <div style="color:#f44;font-weight:bold;font-size:13px;white-space:nowrap;"
                         data-end="<?= $att['arrival_time'] ?>">
                        <?= $mins ?>м <?= str_pad($secs,2,'0',STR_PAD_LEFT) ?>с
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function updateTimers() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('[data-end]').forEach(el => {
        const end = parseInt(el.dataset.end);
        const rem = Math.max(0, end - now);
        const m   = Math.floor(rem / 60);
        const s   = rem % 60;
        el.textContent = `${m}м ${String(s).padStart(2,'0')}с`;
    });
}
setInterval(updateTimers, 1000);
updateTimers();
</script>

</body>
</html>