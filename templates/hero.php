<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Герой — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1100px; margin:20px auto; padding:0 15px; }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:10px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 16px;
            font-weight:bold; color:#d4a843; font-size:15px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .card-body { padding:20px; }

        /* Табы */
        .hero-tabs { display:flex; gap:5px; margin-bottom:20px; flex-wrap:wrap; }
        .hero-tab {
            padding:9px 20px; border-radius:6px;
            border:2px solid #5a4a20; color:#aaa;
            text-decoration:none; font-size:13px; transition:0.2s;
        }
        .hero-tab.active, .hero-tab:hover {
            background:#3a2c10; color:#d4a843; border-color:#8b6914;
        }

        /* Герой главная */
        .hero-main { display:grid; grid-template-columns:200px 1fr; gap:25px; }
        .hero-avatar { text-align:center; }
        .hero-icon {
            font-size:72px; display:block; margin-bottom:10px; line-height:1;
            filter:drop-shadow(0 0 15px rgba(212,168,67,0.5));
        }
        .hero-name  { font-size:17px; font-weight:bold; color:#d4a843; }
        .hero-level { font-size:12px; color:#888; margin-top:4px; }
        .hero-status {
            margin-top:8px; padding:5px 12px; border-radius:12px;
            font-size:12px; font-weight:bold; display:inline-block;
        }
        .status-alive { background:#1a3a1a; color:#4f4; border:1px solid #2a8a2a; }
        .status-regen { background:#1a1a3a; color:#88f; border:1px solid #2a2a8a; }
        .status-dead  { background:#3a1a1a; color:#f44; border:1px solid #8a1a1a; }

        /* Бары */
        .bar-label { display:flex; justify-content:space-between; font-size:12px; color:#888; margin-bottom:4px; }
        .bar { height:14px; background:#333; border-radius:7px; overflow:hidden; margin-bottom:5px; }
        .bar-fill { height:100%; border-radius:7px; transition:0.5s; }
        .bar-hp    { background:linear-gradient(90deg,#2a8a2a,#4f4); }
        .bar-hp-low   { background:linear-gradient(90deg,#8a2a2a,#f44); }
        .bar-hp-medium{ background:linear-gradient(90deg,#8a6a1a,#fa4); }
        .bar-exp   { background:linear-gradient(90deg,#1a1a8a,#88f); height:8px; border-radius:4px; }

        /* Бонусы */
        .bonus-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-top:15px; }
        .bonus-box  { background:#1a1a0a; border:1px solid #444; border-radius:6px; padding:10px; text-align:center; }
        .bonus-icon { font-size:20px; margin-bottom:4px; }
        .bonus-val  { font-size:18px; font-weight:bold; color:#4f4; }
        .bonus-lbl  { font-size:10px; color:#888; }

        /* Навыки */
        .skills-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .skill-item  { background:#1a1a0a; border:1px solid #444; border-radius:8px; padding:12px; }
        .skill-hdr   { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
        .skill-name  { font-size:13px; color:#d4a843; font-weight:bold; }
        .skill-level { font-size:18px; font-weight:bold; }
        .skill-desc  { font-size:11px; color:#888; margin-bottom:8px; }
        .skill-bar   { height:6px; background:#333; border-radius:3px; overflow:hidden; margin-bottom:8px; }
        .skill-bar-fill { height:100%; border-radius:3px; }
        .bar-attack  { background:linear-gradient(90deg,#8a2a2a,#f44); }
        .bar-defense { background:linear-gradient(90deg,#2a2a8a,#44f); }
        .bar-res     { background:linear-gradient(90deg,#6a6a1a,#dd0); }
        .bar-speed   { background:linear-gradient(90deg,#1a6a6a,#0dd); }

        .btn-skill {
            width:100%; padding:7px; background:#3a2c10;
            color:#d4a843; border:1px solid #8b6914;
            border-radius:4px; cursor:pointer; font-size:12px; transition:0.2s;
        }
        .btn-skill:hover    { background:#5a4a20; }
        .btn-skill:disabled { background:#222; color:#555; cursor:not-allowed; border-color:#333; }

        /* Снаряжение */
        .equipment-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
        .equip-slot {
            background:#1a1a0a; border:2px solid #333; border-radius:8px;
            padding:12px; text-align:center; min-height:110px;
            display:flex; flex-direction:column; align-items:center; justify-content:center;
        }
        .equip-slot.filled   { border-color:#5a4a20; }
        .equip-slot.slot-weapon { border-color:#8a2a2a; }
        .equip-slot.slot-shield { border-color:#2a2a8a; }
        .equip-slot.slot-armor  { border-color:#2a6a2a; }
        .equip-slot.slot-potion { border-color:#6a2a6a; }
        .equip-icon  { font-size:30px; margin-bottom:5px; }
        .equip-name  { font-size:11px; font-weight:bold; color:#d4a843; }
        .equip-bonus { font-size:10px; color:#888; margin-top:2px; }
        .equip-empty { color:#555; font-size:12px; }

        /* Редкости */
        .rarity-common    { color:#aaa; }
        .rarity-rare      { color:#44f; }
        .rarity-epic      { color:#c4a; }
        .rarity-legendary { color:#fa4; text-shadow:0 0 6px rgba(255,170,0,0.5); }

        /* Инвентарь */
        .inventory-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(155px,1fr));
            gap:10px;
        }
        .inventory-item {
            background:#1a1a0a; border:2px solid #333;
            border-radius:8px; padding:12px; text-align:center; transition:0.2s;
        }
        .inventory-item:hover { border-color:#8b6914; }
        .item-icon   { font-size:28px; margin-bottom:5px; }
        .item-name   { font-size:11px; font-weight:bold; }
        .item-bonus  { font-size:10px; color:#888; margin:4px 0; }
        .item-badges { display:flex; gap:4px; justify-content:center; flex-wrap:wrap; }

        /* Магазин */
        .shop-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(195px,1fr));
            gap:12px;
        }
        .shop-item {
            background:#1a1a0a; border:2px solid #333;
            border-radius:8px; padding:14px; text-align:center; transition:0.2s;
        }
        .shop-item:hover { border-color:#8b6914; transform:translateY(-2px); }
        .shop-item.common    { border-left:3px solid #666; }
        .shop-item.rare      { border-left:3px solid #44f; }
        .shop-item.epic      { border-left:3px solid #c4a; }
        .shop-item.legendary { border-left:3px solid #fa4; box-shadow:0 0 10px rgba(255,170,0,0.1); }
        .shop-price { display:flex; gap:8px; justify-content:center; margin:8px 0; font-size:12px; flex-wrap:wrap; }
        .shop-price span { color:#d4a843; }

        /* Кнопки */
        .btn {
            display:inline-flex; align-items:center; justify-content:center;
            padding:6px 14px; border-radius:4px; font-size:12px;
            border:1px solid #8b6914; background:#5a4a1a;
            color:#d4a843; cursor:pointer; transition:0.2s; text-decoration:none;
        }
        .btn:hover     { background:#7a6a2a; }
        .btn-sm        { padding:4px 10px; font-size:11px; }
        .btn-green     { background:#1a5a1a; border-color:#2a8a2a; color:#4f4; }
        .btn-green:hover { background:#2a7a2a; }
        .btn-red       { background:#5a1a1a; border-color:#8a1a1a; color:#f66; }
        .btn-red:hover { background:#7a2a2a; }
        .btn-primary   { background:#5a8a1a; border-color:#4a7a0a; color:#fff; }
        .btn-primary:hover { background:#6a9a2a; }
        .btn-block     { width:100%; display:flex; }

        /* Возрождение */
        .revive-box {
            text-align:center; padding:25px;
            background:#1a1a2a; border:2px solid #44a; border-radius:10px;
        }
        .revive-countdown {
            font-size:36px; font-weight:bold;
            color:#88f; font-family:monospace; margin-top:8px;
        }

        /* Зелья активные */
        .potion-active-card {
            background:#1a1a0a; border:2px solid #6a2a6a;
            border-radius:8px; padding:14px; text-align:center;
            min-width:160px; flex:1;
        }
        .potion-timer {
            font-size:16px; font-weight:bold; font-family:monospace;
        }

        /* Лог опыта */
        .exp-log-item {
            display:flex; justify-content:space-between; align-items:center;
            padding:7px 0; border-bottom:1px solid #333; font-size:12px;
        }
        .exp-log-item:last-child { border-bottom:none; }

        /* Алерты */
        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        @media(max-width:768px) {
            .hero-main      { grid-template-columns:1fr; }
            .skills-grid    { grid-template-columns:1fr; }
            .equipment-grid { grid-template-columns:repeat(2,1fr); }
            .bonus-grid     { grid-template-columns:repeat(2,1fr); }
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

    <!-- Табы -->
    <?php $active_tab = $_GET['tab'] ?? 'hero'; ?>
    <div class="hero-tabs">
        <a href="?page=hero&tab=hero"
           class="hero-tab <?= $active_tab==='hero'      ?'active':'' ?>">🦸 Герой</a>
        <a href="?page=hero&tab=equipment"
           class="hero-tab <?= $active_tab==='equipment' ?'active':'' ?>">⚔ Снаряжение</a>
        <a href="?page=hero&tab=inventory"
           class="hero-tab <?= $active_tab==='inventory' ?'active':'' ?>">
            🎒 Инвентарь
            (<?= count(array_filter($items, fn($i) => !$i['equipped'])) ?>)
        </a>
        <a href="?page=hero&tab=shop"
           class="hero-tab <?= $active_tab==='shop'      ?'active':'' ?>">🏪 Магазин</a>
    </div>

    <?php
    $hp_pct    = $hero['hp_max'] > 0 ? min(100, round($hero['hp']/$hero['hp_max']*100)) : 0;
    $exp_pct   = $hero['exp_to_next'] > 0 ? min(100, round($hero['experience']/$hero['exp_to_next']*100)) : 0;
    $hp_class  = $hp_pct > 60 ? 'bar-hp' : ($hp_pct > 30 ? 'bar-hp-medium' : 'bar-hp-low');

    $icons_map = [1=>'👶',3=>'🧑',5=>'🧔',8=>'⚔️',10=>'🦸',13=>'🗡️',15=>'👑',18=>'🔱',20=>'💎'];
    $hero_icon = '👶';
    foreach ($icons_map as $min_lvl => $icon) {
        if ($hero['level'] >= $min_lvl) $hero_icon = $icon;
    }

    $rarity_labels = [
        'common'    => ['label'=>'Обычный',    'class'=>'rarity-common'],
        'rare'      => ['label'=>'Редкий',     'class'=>'rarity-rare'],
        'epic'      => ['label'=>'Эпический',  'class'=>'rarity-epic'],
        'legendary' => ['label'=>'Легендарный','class'=>'rarity-legendary'],
    ];

    $bonus_names = [
        'attack'  =>'атака','defense'=>'защита','hp'=>'HP',
        'resource'=>'ресурсы','speed'=>'скорость','heal'=>'лечение'
    ];
    ?>

    <!-- ТАБ: ГЕРОЙ -->
    <?php if ($active_tab === 'hero'): ?>

    <!-- Активные зелья -->
    <?php if (!empty($active_potions)): ?>
    <div class="card" style="border-color:#6a2a6a;">
        <div class="card-header" style="background:#2a1a2a;color:#c8a;">
            🧪 Активные зелья
        </div>
        <div class="card-body">
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <?php foreach ($active_potions as $ap): ?>
                <div class="potion-active-card">
                    <div style="font-size:32px;margin-bottom:6px;"><?= $ap['icon'] ?></div>
                    <div style="font-size:13px;font-weight:bold;color:<?= $ap['color'] ?>;">
                        <?= $ap['name'] ?>
                    </div>
                    <div style="font-size:12px;color:#888;margin:5px 0;">
                        <?= $ap['desc'] ?>
                    </div>
                    <div class="potion-timer"
                         style="color:<?= $ap['color'] ?>;"
                         data-potion-end="<?= $ap['expires'] ?>">
                        ⏱ <?= gmdate('i:s', max(0,$ap['expires']-time())) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Основная карточка -->
    <div class="card">
        <div class="card-header">
            🦸 Ваш Герой
            <span style="font-size:12px;color:#888;">
                Уровень <?= $hero['level'] ?> / <?= $max_level ?>
            </span>
        </div>
        <div class="card-body">
            <div class="hero-main">

                <!-- Аватар -->
                <div class="hero-avatar">
                    <span class="hero-icon"><?= $hero_icon ?></span>
                    <div class="hero-name"><?= htmlspecialchars($hero['name']) ?></div>
                    <div class="hero-level">Уровень <?= $hero['level'] ?></div>
                    <div class="hero-status status-<?=
                        $hero['status']==='alive'        ? 'alive' :
                        ($hero['status']==='regenerating'? 'regen' : 'dead') ?>">
                        <?php
                        if     ($hero['status']==='alive')        echo '✅ Жив';
                        elseif ($hero['status']==='regenerating') echo '🔄 Возрождение';
                        else                                      echo '💀 Мёртв';
                        ?>
                    </div>

                    <?php if (!empty($hero['village_id'])): ?>
                    <div style="font-size:11px;color:#888;margin-top:8px;">
                        📍 Деревня #<?= $hero['village_id'] ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="?page=hero&action=assign" style="margin-top:10px;">
                        <select name="village_id"
                                style="font-size:11px;padding:5px;width:100%;background:#1a1a0a;
                                       color:#ddd;border:1px solid #444;border-radius:4px;margin-bottom:5px;">
                            <?php foreach ($villages as $v): ?>
                            <option value="<?= $v['id'] ?>"
                                    <?= $hero['village_id']==$v['id']?'selected':'' ?>>
                                <?= htmlspecialchars($v['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm" style="width:100%;">
                            Назначить деревню
                        </button>
                    </form>
                </div>

                <!-- Инфо -->
                <div>
                    <?php if ($hero['status'] === 'regenerating'): ?>
                    <div class="revive-box">
                        <div style="font-size:48px;margin-bottom:10px;">💀</div>
                        <div style="font-size:16px;color:#88f;">Герой возрождается...</div>
                        <div class="revive-countdown"
                             id="reviveTimer"
                             data-end="<?= $hero['revive_time'] ?>">
                            <?= gmdate('H:i:s', max(0,$hero['revive_time']-time())) ?>
                        </div>
                    </div>
                    <?php else: ?>

                    <!-- HP -->
                    <div style="margin-bottom:12px;">
                        <div class="bar-label">
                            <span>❤ Здоровье</span>
                            <span><?= $hero['hp'] ?> / <?= $hero['hp_max'] ?></span>
                        </div>
                        <div class="bar">
                            <div class="bar-fill <?= $hp_class ?>" style="width:<?= $hp_pct ?>%;"></div>
                        </div>
                    </div>

                    <!-- Опыт -->
                    <div style="margin-bottom:15px;">
                        <div class="bar-label">
                            <span>✨ Опыт</span>
                            <span><?= number_format($hero['experience']) ?> / <?= number_format($hero['exp_to_next']) ?></span>
                        </div>
                        <div class="bar" style="height:8px;">
                            <div class="bar-fill bar-exp" style="width:<?= $exp_pct ?>%;"></div>
                        </div>
                        <div style="font-size:10px;color:#666;margin-top:3px;">
                            <?php if ($hero['level'] < $max_level): ?>
                                До ур.<?= $hero['level']+1 ?>:
                                <?= number_format($hero['exp_to_next']-$hero['experience']) ?> опыта
                            <?php else: ?>🏆 Максимальный уровень!<?php endif; ?>
                        </div>
                    </div>

                    <?php if ($hero['skill_points'] > 0): ?>
                    <div style="background:#1a3a1a;border:1px solid #2a8a2a;border-radius:6px;
                                padding:10px;margin-bottom:15px;color:#4f4;font-size:13px;">
                        🌟 Доступно <strong><?= $hero['skill_points'] ?></strong> очков навыков!
                    </div>
                    <?php endif; ?>

                    <!-- Бонусы -->
                    <div class="bonus-grid">
                        <div class="bonus-box">
                            <div class="bonus-icon">⚔</div>
                            <div class="bonus-val">+<?= round($bonuses['attack']) ?>%</div>
                            <div class="bonus-lbl">Атака</div>
                        </div>
                        <div class="bonus-box">
                            <div class="bonus-icon">🛡</div>
                            <div class="bonus-val">+<?= round($bonuses['defense']) ?>%</div>
                            <div class="bonus-lbl">Защита</div>
                        </div>
                        <div class="bonus-box">
                            <div class="bonus-icon">💰</div>
                            <div class="bonus-val">+<?= round($bonuses['resource']) ?>%</div>
                            <div class="bonus-lbl">Ресурсы</div>
                        </div>
                        <div class="bonus-box">
                            <div class="bonus-icon">⚡</div>
                            <div class="bonus-val">-<?= round($bonuses['speed']) ?>%</div>
                            <div class="bonus-lbl">Скорость</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Навыки -->
    <div class="card">
        <div class="card-header">
            🌟 Навыки
            <span style="color:<?= $hero['skill_points']>0?'#4f4':'#888' ?>;font-size:13px;">
                Очков: <strong><?= $hero['skill_points'] ?></strong>
            </span>
        </div>
        <div class="card-body">
            <div class="skills-grid">
                <?php
                $skills = [
                    'skill_attack'   => ['name'=>'Атака',    'icon'=>'⚔','color'=>'#f44','bar'=>'bar-attack',
                                         'desc'=>'+2% к атаке армии за очко. Макс +40%'],
                    'skill_defense'  => ['name'=>'Защита',   'icon'=>'🛡','color'=>'#44f','bar'=>'bar-defense',
                                         'desc'=>'+2% к защите армии за очко. Макс +40%'],
                    'skill_resource' => ['name'=>'Ресурсы',  'icon'=>'💰','color'=>'#dd0','bar'=>'bar-res',
                                         'desc'=>'+1.5% к производству за очко. Макс +30%'],
                    'skill_speed'    => ['name'=>'Скорость', 'icon'=>'⚡','color'=>'#0dd','bar'=>'bar-speed',
                                         'desc'=>'-1% к времени походов за очко. Макс -20%'],
                ];
                foreach ($skills as $field => $info):
                    $lvl     = (int)$hero[$field];
                    $pct     = ($lvl / 20) * 100;
                    $can_add = $hero['skill_points'] > 0 && $lvl < 20 && $hero['status']==='alive';
                ?>
                <div class="skill-item">
                    <div class="skill-hdr">
                        <span class="skill-name"><?= $info['icon'] ?> <?= $info['name'] ?></span>
                        <span class="skill-level" style="color:<?= $info['color'] ?>;">
                            <?= $lvl ?>/20
                        </span>
                    </div>
                    <div class="skill-desc"><?= $info['desc'] ?></div>
                    <div class="skill-bar">
                        <div class="skill-bar-fill <?= $info['bar'] ?>"
                             style="width:<?= $pct ?>%;"></div>
                    </div>
                    <form method="POST" action="?page=hero&action=skill">
                        <input type="hidden" name="skill" value="<?= $field ?>">
                        <button type="submit" class="btn-skill"
                                <?= !$can_add?'disabled':'' ?>>
                            <?= $lvl>=20?'✅ Максимум':($hero['skill_points']>0?'+ Улучшить':'Нет очков') ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Лог опыта -->
    <?php if (!empty($exp_log)): ?>
    <div class="card">
        <div class="card-header">✨ Последний опыт</div>
        <div class="card-body">
            <?php
            $reason_labels = [
                'battle'   => '⚔ Бой',
                'defense'  => '🛡 Защита',
                'building' => '🔨 Строительство',
                'quest'    => '📋 Квест',
                'event'    => '🌍 Событие',
            ];
            foreach ($exp_log as $log):
                $diff = time() - $log['time'];
                if ($diff < 3600) $ts = floor($diff/60).' мин. назад';
                else $ts = date('d.m H:i', $log['time']);
            ?>
            <div class="exp-log-item">
                <span><?= $reason_labels[$log['reason']] ?? $log['reason'] ?></span>
                <span style="color:#88f;font-weight:bold;">+<?= $log['exp'] ?> опыта</span>
                <span style="color:#555;font-size:11px;"><?= $ts ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ТАБ: СНАРЯЖЕНИЕ -->
    <?php elseif ($active_tab === 'equipment'): ?>

    <div class="card">
        <div class="card-header">⚔ Снаряжение героя</div>
        <div class="card-body">
            <div class="equipment-grid">
                <?php
                $slots = [
                    'weapon' => ['icon'=>'⚔', 'name'=>'Оружие', 'class'=>'slot-weapon'],
                    'shield' => ['icon'=>'🛡', 'name'=>'Щит',    'class'=>'slot-shield'],
                    'armor'  => ['icon'=>'🧥', 'name'=>'Броня',  'class'=>'slot-armor'],
                    'potion' => ['icon'=>'🧪', 'name'=>'Зелье',  'class'=>'slot-potion'],
                ];
                foreach ($slots as $slot_key => $slot_info):
                    $e  = $equipped[$slot_key] ?? null;
                    $ic = $e ? (self::$items_config[$e['item_type']] ?? []) : [];
                    $r  = $e ? ($rarity_labels[$e['rarity']] ?? ['label'=>'?','class'=>'']) : null;
                ?>
                <div class="equip-slot <?= $e ? 'filled '.$slot_info['class'] : '' ?>">
                    <?php if ($e): ?>
                        <div class="equip-icon"><?= $ic['icon'] ?? $slot_info['icon'] ?></div>
                        <div class="equip-name <?= $r['class']??'' ?>">
                            <?= htmlspecialchars($e['name']) ?>
                        </div>
                        <div class="equip-bonus">
                            +<?= $e['bonus_value'] ?> <?= $bonus_names[$e['bonus_type']]??'' ?>
                        </div>
                        <div style="font-size:10px;margin-top:2px;" class="<?= $r['class']??'' ?>">
                            <?= $r['label']??'' ?>
                        </div>
                        <div style="margin-top:6px;">
                            <a href="?page=hero&action=equip_item&item_id=<?= $e['id'] ?>&equip_action=unequip&tab=equipment"
                               class="btn btn-sm btn-red">Снять</a>
                        </div>
                    <?php else: ?>
                        <div class="equip-icon" style="opacity:0.25;"><?= $slot_info['icon'] ?></div>
                        <div class="equip-empty"><?= $slot_info['name'] ?><br>пуст</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Суммарные бонусы -->
            <div style="margin-top:20px;padding:15px;background:#1a1a0a;
                        border:1px solid #444;border-radius:8px;">
                <div style="color:#d4a843;font-weight:bold;margin-bottom:10px;">
                    📊 Итоговые бонусы:
                </div>
                <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:13px;">
                    <span>⚔ +<?= round($bonuses['attack']) ?>%</span>
                    <span>🛡 +<?= round($bonuses['defense']) ?>%</span>
                    <span>💰 +<?= round($bonuses['resource']) ?>%</span>
                    <span>⚡ -<?= round($bonuses['speed']) ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ТАБ: ИНВЕНТАРЬ -->
    <?php elseif ($active_tab === 'inventory'): ?>

    <div class="card">
        <div class="card-header">
            🎒 Инвентарь
            <span style="font-size:12px;color:#888;"><?= count($items) ?> предметов</span>
        </div>
        <div class="card-body">
            <?php if (empty($items)): ?>
                <div style="text-align:center;padding:30px;color:#666;">
                    <div style="font-size:40px;margin-bottom:10px;">🎒</div>
                    Пусто. Купите предметы в магазине!
                </div>
            <?php else: ?>
            <div class="inventory-grid">
                <?php foreach ($items as $it):
                    $ic = self::$items_config[$it['item_type']] ?? [];
                    $r  = $rarity_labels[$it['rarity']] ?? ['label'=>'?','class'=>''];
                    $border = $it['equipped']             ? '#d4a843' :
                             ($it['rarity']==='legendary'? '#fa4' :
                             ($it['rarity']==='epic'     ? '#c4a' :
                             ($it['rarity']==='rare'     ? '#44f' : '#333')));
                ?>
                <div class="inventory-item" style="border-color:<?= $border ?>;">
                    <div class="item-icon"><?= $ic['icon'] ?? '📦' ?></div>
                    <div class="item-name <?= $r['class'] ?>">
                        <?= htmlspecialchars($it['name']) ?>
                    </div>
                    <div class="item-bonus">
                        +<?= $it['bonus_value'] ?> <?= $bonus_names[$it['bonus_type']]??'' ?>
                    </div>
                    <div style="font-size:10px;margin-bottom:6px;" class="<?= $r['class'] ?>">
                        <?= $r['label'] ?>
                        <?= $it['equipped'] ? ' · 📌' : '' ?>
                    </div>
                    <div class="item-badges">
                        <?php if ($it['item_slot'] !== 'potion'): ?>
                            <?php if (!$it['equipped']): ?>
                                <a href="?page=hero&action=equip_item&item_id=<?= $it['id'] ?>&equip_action=equip&tab=inventory"
                                   class="btn btn-sm btn-green">Надеть</a>
                            <?php else: ?>
                                <a href="?page=hero&action=equip_item&item_id=<?= $it['id'] ?>&equip_action=unequip&tab=inventory"
                                   class="btn btn-sm btn-red">Снять</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#888;font-size:10px;">Применено</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ТАБ: МАГАЗИН -->
    <?php elseif ($active_tab === 'shop'): ?>

    <div class="card">
        <div class="card-header">🏪 Магазин предметов</div>
        <div class="card-body">

            <p style="color:#888;font-size:13px;margin-bottom:20px;">
                Покупайте предметы за ресурсы. Выберите деревню для оплаты.
            </p>

            <div style="margin-bottom:20px;">
                <label style="color:#888;font-size:13px;">Оплатить из:</label>
                <select id="shopVillage"
                        style="padding:8px;background:#1a1a0a;color:#ddd;border:1px solid #444;
                               border-radius:4px;margin-left:10px;font-size:13px;">
                    <?php foreach ($villages as $v): ?>
                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php
            $shop_cats = [
                'weapon' => ['name'=>'⚔ Оружие', 'keys'=>['sword_iron','sword_steel','sword_legend']],
                'shield' => ['name'=>'🛡 Щиты',   'keys'=>['shield_wood','shield_iron','shield_legend']],
                'armor'  => ['name'=>'🧥 Броня',  'keys'=>['armor_leather','armor_chain','armor_legend']],
                'potion' => ['name'=>'🧪 Зелья',  'keys'=>['potion_hp','potion_speed','potion_res']],
            ];
            foreach ($shop_cats as $cat):
            ?>
            <h3 style="color:#d4a843;margin:20px 0 12px;"><?= $cat['name'] ?></h3>
            <div class="shop-grid" style="margin-bottom:10px;">
                <?php foreach ($cat['keys'] as $item_key):
                    $it = self::$items_config[$item_key];
                    $r  = $rarity_labels[$it['rarity']] ?? ['label'=>'?','class'=>''];
                ?>
                <div class="shop-item <?= $it['rarity'] ?>">
                    <div style="font-size:34px;margin-bottom:8px;"><?= $it['icon'] ?></div>
                    <div style="font-weight:bold;font-size:13px;" class="<?= $r['class'] ?>">
                        <?= htmlspecialchars($it['name']) ?>
                    </div>
                    <div style="font-size:11px;color:#888;margin:4px 0;"><?= $r['label'] ?></div>
                    <div style="font-size:12px;color:#4f4;margin:6px 0;">
                        +<?= $it['bonus_value'] ?> <?= $bonus_names[$it['bonus_type']]??'' ?>
                        <?php if ($it['slot']==='potion'): ?>
                            <br><span style="color:#888;font-size:10px;">Одноразовое</span>
                        <?php endif; ?>
                    </div>
                    <div class="shop-price">
                        <span>🪵<?= number_format($it['wood']) ?></span>
                        <span>🪨<?= number_format($it['stone']) ?></span>
                        <span>⛏<?= number_format($it['iron']) ?></span>
                    </div>
                    <form method="POST" action="?page=hero&action=buy">
                        <input type="hidden" name="item_type" value="<?= $item_key ?>">
                        <input type="hidden" name="village_id" id="vid_<?= $item_key ?>">
                        <button type="submit" class="btn btn-primary btn-block"
                                onclick="document.getElementById('vid_<?= $item_key ?>').value=
                                         document.getElementById('shopVillage').value">
                            Купить
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Таймер возрождения
const reviveEl = document.getElementById('reviveTimer');
if (reviveEl) {
    const end = parseInt(reviveEl.dataset.end);
    function updateRevive() {
        const rem = Math.max(0, end - Math.floor(Date.now()/1000));
        const h   = Math.floor(rem/3600);
        const m   = Math.floor((rem%3600)/60);
        const s   = rem%60;
        reviveEl.textContent =
            `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (rem <= 0) location.reload();
    }
    setInterval(updateRevive, 1000);
    updateRevive();
}

// Таймеры зелий
function updatePotionTimers() {
    const now = Math.floor(Date.now() / 1000);
    document.querySelectorAll('[data-potion-end]').forEach(el => {
        const end = parseInt(el.dataset.potionEnd);
        const rem = Math.max(0, end - now);
        const m   = Math.floor(rem / 60);
        const s   = rem % 60;
        el.textContent = `⏱ ${m}м ${String(s).padStart(2,'0')}с`;
        if (rem <= 0) location.reload();
    });
}
setInterval(updatePotionTimers, 1000);
updatePotionTimers();
</script>

</body>
</html>