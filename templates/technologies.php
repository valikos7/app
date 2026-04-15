<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Технологии — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:1100px; margin:20px auto; padding:0 15px; }

        .page-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:20px;
        }
        .page-title { font-size:24px; font-weight:bold; color:#d4a843; }

        /* Активное исследование */
        .active-research {
            background:#1a1a2a; border:2px solid #2a2a8a;
            border-radius:10px; padding:18px; margin-bottom:20px;
            display:flex; align-items:center; gap:15px; flex-wrap:wrap;
        }
        .ar-icon  { font-size:40px; flex-shrink:0; }
        .ar-info  { flex:1; }
        .ar-title { font-size:16px; font-weight:bold; color:#88f; }
        .ar-sub   { font-size:12px; color:#888; margin-top:4px; }
        .ar-timer {
            font-size:28px; font-weight:bold;
            color:#88f; font-family:monospace;
        }
        .ar-bar { height:6px; background:#333; border-radius:3px; overflow:hidden; margin-top:8px; }
        .ar-bar-fill { height:100%; background:linear-gradient(90deg,#2a2a8a,#88f); border-radius:3px; }

        /* Выбор деревни */
        .village-bar {
            background:#2a2a1a; border:1px solid #5a4a20;
            border-radius:6px; padding:12px 16px;
            display:flex; align-items:center; gap:12px;
            margin-bottom:20px; flex-wrap:wrap;
        }
        .village-bar label { color:#888; font-size:13px; }
        .village-bar select {
            padding:7px 10px; background:#1a1a0a; color:#ddd;
            border:1px solid #444; border-radius:4px; font-size:13px;
        }

        /* Ветки технологий */
        .branch-card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:10px; overflow:hidden; margin-bottom:20px;
        }
        .branch-header {
            padding:14px 18px; font-size:16px; font-weight:bold;
            display:flex; justify-content:space-between; align-items:center;
        }
        .branch-body { padding:15px; }

        /* Дерево технологий */
        .tech-tree {
            display:flex; flex-direction:column; gap:10px;
        }
        .tech-row {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
            gap:10px;
        }

        /* Карточка технологии */
        .tech-card {
            background:#1a1a0a; border:2px solid #333;
            border-radius:8px; padding:14px; transition:0.2s;
            position:relative;
        }
        .tech-card:hover { border-color:#8b6914; }
        .tech-card.maxed { border-color:#d4a843; background:#2a2010; }
        .tech-card.researching { border-color:#2a2a8a; background:#1a1a2a; }
        .tech-card.locked { opacity:0.5; }
        .tech-card.available { border-color:#2a6a2a; }

        .tech-header {
            display:flex; align-items:center; gap:10px; margin-bottom:8px;
        }
        .tech-icon { font-size:28px; }
        .tech-name { font-size:14px; font-weight:bold; color:#d4a843; }
        .tech-level { font-size:12px; color:#888; margin-top:2px; }

        .tech-desc { font-size:12px; color:#888; margin-bottom:10px; line-height:1.5; }

        /* Прогресс уровней */
        .tech-levels {
            display:flex; gap:3px; margin-bottom:10px;
        }
        .tech-level-dot {
            flex:1; height:6px; border-radius:3px; background:#333;
        }
        .tech-level-dot.filled { background:linear-gradient(90deg,#8b6914,#d4a843); }
        .tech-level-dot.researching { background:linear-gradient(90deg,#2a2a8a,#88f); animation:pulse 1.5s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.5;} }

        /* Стоимость */
        .tech-cost {
            display:flex; gap:8px; flex-wrap:wrap; margin-bottom:10px; font-size:11px;
        }
        .cost-item { display:flex; align-items:center; gap:3px; }
        .cost-ok  { color:#4f4; }
        .cost-nok { color:#f44; }

        /* Бонус */
        .tech-bonus {
            font-size:12px; color:#4f4; margin-bottom:10px;
            background:#1a3a1a; border:1px solid #2a6a2a;
            border-radius:4px; padding:5px 8px;
        }

        /* Кнопки */
        .btn-research {
            width:100%; padding:8px; background:#1a5a1a;
            color:#fff; border:none; border-radius:5px;
            cursor:pointer; font-size:13px; transition:0.2s;
        }
        .btn-research:hover { background:#2a7a2a; }
        .btn-research:disabled {
            background:#333; color:#666; cursor:not-allowed;
        }
        .btn-research.researching-btn {
            background:#1a1a5a; color:#88f; cursor:default;
        }
        .btn-research.maxed-btn {
            background:#3a2c10; color:#d4a843; cursor:default;
        }
        .btn-research.locked-btn {
            background:#2a1a1a; color:#666; cursor:not-allowed;
        }

        /* Таймер в карточке */
        .tech-timer {
            text-align:center; font-size:14px;
            color:#88f; font-family:monospace; font-weight:bold;
        }

        /* Требование */
        .tech-requires {
            font-size:10px; color:#888; margin-bottom:6px;
        }
        .tech-requires.not-met { color:#f44; }

        /* Бонусы справа */
        .bonuses-panel {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:10px; padding:15px; margin-bottom:20px;
        }
        .bonuses-title { font-size:15px; font-weight:bold; color:#d4a843; margin-bottom:12px; }
        .bonus-grid {
            display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
            gap:8px;
        }
        .bonus-item {
            background:#1a1a0a; border:1px solid #333;
            border-radius:6px; padding:10px;
            display:flex; align-items:center; gap:8px; font-size:13px;
        }
        .bonus-icon { font-size:18px; }
        .bonus-label { color:#888; font-size:11px; }
        .bonus-val   { font-weight:bold; color:#4f4; }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        @media(max-width:600px) {
            .tech-row { grid-template-columns:1fr; }
            .bonus-grid { grid-template-columns:1fr 1fr; }
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
        <div class="page-title">🔬 Дерево технологий</div>
    </div>

    <!-- Активное исследование -->
    <?php if ($active_research): ?>
    <div class="active-research">
        <div class="ar-icon"><?= $active_research['icon'] ?></div>
        <div class="ar-info">
            <div class="ar-title">
                🔬 Исследуется: <?= htmlspecialchars($active_research['name']) ?>
                → уровень <?= $active_research['current_level']+1 ?>
            </div>
            <div class="ar-sub">
                Ветка: <?= $branches[$active_research['branch']]['name'] ?>
            </div>
            <?php
            $rem = max(0, $active_research['end_time'] - time());
            $total = $active_research['end_time'] - ($active_research['end_time'] - $rem - 3600);
            ?>
            <div class="ar-bar">
                <div class="ar-bar-fill" style="width:<?= min(100,round((1-$rem/max(1,$active_research['time_base']))*100)) ?>%;"></div>
            </div>
        </div>
        <div>
            <div style="font-size:11px;color:#888;margin-bottom:4px;">Осталось:</div>
            <div class="ar-timer" id="activeResTimer" data-end="<?= $active_research['end_time'] ?>">
                <?= gmdate('H:i:s', $rem) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Текущие бонусы -->
    <?php
    $bonus_display = [
        ['⛏','Производство',  $bonuses['production']  > 0 ? "+{$bonuses['production']}%" : '—'],
        ['🏚','Склад',        $bonuses['storage']     > 0 ? "+{$bonuses['storage']}%"    : '—'],
        ['⚔','Атака',         $bonuses['attack']      > 0 ? "+{$bonuses['attack']}%"     : '—'],
        ['🛡','Защита',        $bonuses['defense']     > 0 ? "+{$bonuses['defense']}%"    : '—'],
        ['🏃','Скорость',     $bonuses['march_speed'] > 0 ? "-{$bonuses['march_speed']}%" : '—'],
        ['💣','Осадные',      $bonuses['siege']       > 0 ? "+{$bonuses['siege']}%"       : '—'],
        ['🔍','Шпионаж',     $bonuses['spy_chance']  > 0 ? "+{$bonuses['spy_chance']}%"  : '—'],
        ['🦸','Опыт героя',  $bonuses['hero_exp']    > 0 ? "+{$bonuses['hero_exp']}%"    : '—'],
        ['🔧','Стр-во',      $bonuses['build_cost']  > 0 ? "-{$bonuses['build_cost']}%"  : '—'],
        ['💰','Налоги',       $bonuses['taxation']    > 0 ? "+{$bonuses['taxation']}/ч"   : '—'],
    ];
    ?>
    <div class="bonuses-panel">
        <div class="bonuses-title">📊 Ваши текущие бонусы от технологий</div>
        <div class="bonus-grid">
            <?php foreach ($bonus_display as $b): ?>
            <div class="bonus-item">
                <span class="bonus-icon"><?= $b[0] ?></span>
                <div>
                    <div class="bonus-label"><?= $b[1] ?></div>
                    <div class="bonus-val" style="color:<?= $b[2]==='—'?'#555':'#4f4' ?>;">
                        <?= $b[2] ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Выбор деревни -->
    <div class="village-bar">
        <label>🏘 Списать ресурсы из:</label>
        <select id="villageSelect" onchange="updateVillageInfo()">
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

    <!-- Ветки технологий -->
    <?php
    $tc_obj = new TechController($db);

    // Индексируем технологии игрока
    $player_tech_map = [];
    foreach ($branches as $branch_key => $branch) {
        foreach ($branch['techs'] as $t) {
            $player_tech_map[$t['code']] = (int)$t['current_level'];
        }
    }

    foreach ($branches as $branch_key => $branch):
        if (empty($branch['techs'])) continue;
    ?>
    <div class="branch-card">
        <div class="branch-header" style="background:linear-gradient(135deg,#2a2010,#1a1a0a);border-bottom:2px solid <?= $branch['color'] ?>20;">
            <span style="color:<?= $branch['color'] ?>;"><?= $branch['name'] ?></span>
            <span style="font-size:12px;color:#888;">
                <?php
                $done = count(array_filter($branch['techs'], fn($t)=>$t['current_level']>=$t['max_level']));
                echo "{$done}/".count($branch['techs'])." макс.";
                ?>
            </span>
        </div>
        <div class="branch-body">
            <div class="tech-tree">
                <div class="tech-row">
                    <?php foreach ($branch['techs'] as $t):
                        $current_lvl = (int)$t['current_level'];
                        $is_max      = ($current_lvl >= $t['max_level']);
                        $is_res      = ($t['researching'] && $t['end_time'] > time());
                        $next_level  = $current_lvl + 1;

                        // Проверяем требования
                        $req_met = true;
                        $req_text = '';
                        if (!empty($t['requires'])) {
                            $req_level = $player_tech_map[$t['requires']] ?? 0;
                            $req_met   = ($req_level >= $t['requires_lvl']);
                            if (!$req_met) {
                                $stmt3=$db->prepare("SELECT name FROM technologies WHERE code=?");
                                $stmt3->execute([$t['requires']]);
                                $req_tech=$stmt3->fetch();
                                $req_text="Требует: «".($req_tech['name']??$t['requires'])."» ур.{$t['requires_lvl']}";
                            }
                        }

                        // Определяем класс карточки
                        $card_class = 'tech-card';
                        if ($is_max)       $card_class .= ' maxed';
                        elseif ($is_res)   $card_class .= ' researching';
                        elseif (!$req_met) $card_class .= ' locked';
                        elseif ($current_lvl > 0 || $req_met) $card_class .= ' available';

                        // Стоимость следующего уровня
                        $cost = !$is_max ? $tc_obj->getCost($t, $next_level) : ['wood'=>0,'stone'=>0,'iron'=>0];
                        $time_needed = !$is_max ? $tc_obj->getTime($t, $next_level) : 0;

                        // Текущий бонус
                        $bonus_map = [
                            'prod_boost'   => fn($l) => "+{$l}0% производство",
                            'storage_plus' => fn($l) => "+{$l}5% склад",
                            'trade_routes' => fn($l) => "+{$l}0% скорость торговли",
                            'taxation'     => fn($l) => "+".($l*50)." рес./ч",
                            'engineering'  => fn($l) => "-{$l}0% стоимость стройки",
                            'att_boost'    => fn($l) => "+{$l}% атака",
                            'def_boost'    => fn($l) => "+{$l}% защита",
                            'march_speed'  => fn($l) => "-{$l}% время походов",
                            'siege_power'  => fn($l) => "+{$l}0% осадные",
                            'spy_skill'    => fn($l) => "+{$l}5% шанс разведки",
                            'ally_sight'   => fn($l) => "Уровень {$l}",
                            'shared_res'   => fn($l) => "Уровень {$l}",
                            'spy_net'      => fn($l) => "Уровень {$l}",
                            'hero_mastery' => fn($l) => "+{$l}0% опыт героя",
                        ];
                        $current_bonus = $current_lvl > 0 && isset($bonus_map[$t['code']])
                            ? ($bonus_map[$t['code']])($current_lvl)
                            : null;
                    ?>
                    <div class="<?= $card_class ?>">

                        <div class="tech-header">
                            <span class="tech-icon"><?= $t['icon'] ?></span>
                            <div>
                                <div class="tech-name"><?= htmlspecialchars($t['name']) ?></div>
                                <div class="tech-level">
                                    Ур. <?= $current_lvl ?>/<?= $t['max_level'] ?>
                                    <?= $is_max ? '✅' : '' ?>
                                    <?= $is_res ? '🔬' : '' ?>
                                </div>
                            </div>
                        </div>

                        <div class="tech-desc"><?= htmlspecialchars($t['description']) ?></div>

                        <!-- Уровни точками -->
                        <div class="tech-levels">
                            <?php for ($i=1;$i<=$t['max_level'];$i++): ?>
                            <div class="tech-level-dot <?=
                                ($i<=$current_lvl ? 'filled' :
                                ($is_res&&$i==$next_level ? 'researching' : ''))
                            ?>"></div>
                            <?php endfor; ?>
                        </div>

                        <!-- Текущий бонус -->
                        <?php if ($current_bonus): ?>
                        <div class="tech-bonus">✅ Сейчас: <?= $current_bonus ?></div>
                        <?php endif; ?>

                        <!-- Требования -->
                        <?php if (!empty($req_text)): ?>
                        <div class="tech-requires <?= !$req_met?'not-met':'' ?>">
                            🔒 <?= htmlspecialchars($req_text) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Стоимость -->
                        <?php if (!$is_max && !$is_res): ?>
                        <div class="tech-cost" id="cost_<?= $t['code'] ?>">
                            <span class="cost-item">
                                🪵<span id="cw_<?= $t['code'] ?>"><?= number_format($cost['wood']) ?></span>
                            </span>
                            <span class="cost-item">
                                🪨<span id="cs_<?= $t['code'] ?>"><?= number_format($cost['stone']) ?></span>
                            </span>
                            <span class="cost-item">
                                ⛏<span id="ci_<?= $t['code'] ?>"><?= number_format($cost['iron']) ?></span>
                            </span>
                            <span style="color:#888;font-size:10px;">
                                ⏱<?= floor($time_needed/60) ?>м <?= $time_needed%60 ?>с
                            </span>
                        </div>
                        <?php endif; ?>

                        <!-- Кнопка / Статус -->
                        <?php if ($is_max): ?>
                            <button class="btn-research maxed-btn" disabled>
                                ✅ Максимальный уровень
                            </button>
                        <?php elseif ($is_res): ?>
                            <div class="tech-timer" id="timer_<?= $t['code'] ?>"
                                 data-end="<?= $t['end_time'] ?>">
                                🔬 <?= gmdate('H:i:s',max(0,$t['end_time']-time())) ?>
                            </div>
                        <?php elseif (!$req_met): ?>
                            <button class="btn-research locked-btn" disabled>
                                🔒 Заблокировано
                            </button>
                        <?php elseif ($active_research): ?>
                            <button class="btn-research" disabled>
                                ⏳ Другое исследование
                            </button>
                        <?php else: ?>
                            <form method="POST" action="?page=technologies&action=research">
                                <input type="hidden" name="tech_code" value="<?= $t['code'] ?>">
                                <input type="hidden" name="village_id" id="vid_<?= $t['code'] ?>">
                                <button type="submit" class="btn-research"
                                        onclick="document.getElementById('vid_<?= $t['code'] ?>').value=document.getElementById('villageSelect').value">
                                    🔬 Исследовать (ур.<?= $next_level ?>)
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<script>
// Таймер активного исследования
const activeTimer = document.getElementById('activeResTimer');
if (activeTimer) {
    const end = parseInt(activeTimer.dataset.end);
    function updateActive() {
        const rem = Math.max(0, end - Math.floor(Date.now()/1000));
        const h=Math.floor(rem/3600), m=Math.floor((rem%3600)/60), s=rem%60;
        activeTimer.textContent =
            `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (rem<=0) location.reload();
    }
    setInterval(updateActive,1000); updateActive();
}

// Таймеры карточек
document.querySelectorAll('[id^="timer_"]').forEach(el => {
    const end = parseInt(el.dataset.end);
    function tick() {
        const rem = Math.max(0, end - Math.floor(Date.now()/1000));
        const h=Math.floor(rem/3600), m=Math.floor((rem%3600)/60), s=rem%60;
        el.textContent = `🔬 ${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        if (rem<=0) location.reload();
    }
    setInterval(tick,1000); tick();
});

// Проверка ресурсов при смене деревни
function updateVillageInfo() {
    const sel = document.getElementById('villageSelect');
    const opt = sel.options[sel.selectedIndex];
    const avail = {
        wood:  parseInt(opt.dataset.wood),
        stone: parseInt(opt.dataset.stone),
        iron:  parseInt(opt.dataset.iron)
    };

    // Обновляем классы стоимости
    document.querySelectorAll('[id^="cw_"]').forEach(el => {
        const code = el.id.replace('cw_','');
        const wood  = parseInt(el.textContent.replace(/\s/g,'')) || 0;
        const stone = parseInt(document.getElementById('cs_'+code)?.textContent.replace(/\s/g,'')) || 0;
        const iron  = parseInt(document.getElementById('ci_'+code)?.textContent.replace(/\s/g,'')) || 0;

        const costEl = document.getElementById('cost_'+code);
        if (!costEl) return;

        const items = costEl.querySelectorAll('.cost-item');
        if (items[0]) items[0].className = 'cost-item '+(avail.wood>=wood?'cost-ok':'cost-nok');
        if (items[1]) items[1].className = 'cost-item '+(avail.stone>=stone?'cost-ok':'cost-nok');
        if (items[2]) items[2].className = 'cost-item '+(avail.iron>=iron?'cost-ok':'cost-nok');
    });
}

// Инициализация
window.addEventListener('DOMContentLoaded', updateVillageInfo);
</script>

</body>
</html>