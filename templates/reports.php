<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчёты — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:950px; margin:20px auto; padding:0 15px; }

        .page-header {
            display:flex; justify-content:space-between;
            align-items:center; margin-bottom:20px;
        }
        .page-title { font-size:24px; font-weight:bold; color:#d4a843; }

        .filter-tabs {
            display:flex; gap:5px; margin-bottom:20px; flex-wrap:wrap;
        }
        .filter-tab {
            padding:8px 18px; background:#2a2a1a;
            border:2px solid #5a4a20; border-radius:6px;
            color:#aaa; text-decoration:none; font-size:13px; transition:0.2s;
        }
        .filter-tab.active, .filter-tab:hover {
            background:#3a2c10; color:#d4a843; border-color:#8b6914;
        }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
            display:flex; justify-content:space-between; align-items:center;
        }

        .report-item {
            display:flex; align-items:center; gap:12px;
            padding:12px 15px; border-bottom:1px solid #333;
            cursor:pointer; transition:0.2s;
        }
        .report-item:hover { background:#2a2010; }
        .report-item:last-child { border-bottom:none; }
        .report-item.unread {
            background:#1a2a1a; border-left:3px solid #4a8a4a;
        }

        .report-icon { font-size:24px; min-width:30px; text-align:center; }
        .report-info { flex:1; }
        .report-title { font-weight:bold; font-size:14px; }
        .report-title.win   { color:#4f4; }
        .report-title.loss  { color:#f44; }
        .report-time { font-size:11px; color:#666; margin-top:3px; }
        .report-arrow { color:#555; font-size:16px; }

        /* Модальное окно */
        .modal {
            display:none; position:fixed; top:0; left:0; right:0; bottom:0;
            background:rgba(0,0,0,0.9); z-index:2000;
            align-items:center; justify-content:center; padding:15px;
        }
        .modal.active { display:flex; }
        .modal-content {
            background:#2a2a1a; border:2px solid #8b6914;
            border-radius:10px; width:100%; max-width:780px;
            max-height:92vh; overflow-y:auto; position:relative;
        }

        .report-header-modal {
            background:#3a2c10; padding:14px 20px;
            display:flex; justify-content:space-between; align-items:center;
            position:sticky; top:0; z-index:1;
        }
        .report-modal-title { font-size:15px; font-weight:bold; color:#d4a843; flex:1; }
        .modal-close {
            background:#5a1a1a; color:#fff; border:none;
            border-radius:50%; width:30px; height:30px;
            cursor:pointer; font-size:16px; flex-shrink:0; margin-left:10px;
        }

        .report-body-modal { padding:20px; }

        /* Боевой отчёт */
        .br-result {
            text-align:center; padding:20px;
            border-radius:8px; margin-bottom:20px;
        }
        .br-result.victory { background:#1a3a1a; border:2px solid #4a8a4a; }
        .br-result.defeat  { background:#3a1a1a; border:2px solid #8a4a4a; }
        .br-result-icon    { font-size:48px; margin-bottom:8px; }
        .br-result-text    { font-size:22px; font-weight:bold; }
        .br-result.victory .br-result-text { color:#4f4; }
        .br-result.defeat  .br-result-text { color:#f44; }

        .br-meta {
            display:flex; gap:20px; justify-content:center;
            flex-wrap:wrap; margin-top:10px; font-size:13px;
        }
        .br-meta-item { text-align:center; }
        .br-meta-label { color:#888; font-size:11px; }
        .br-meta-val   { font-weight:bold; color:#d4a843; }

        .luck-badge {
            display:inline-block; padding:3px 10px;
            border-radius:10px; font-size:12px; font-weight:bold;
        }
        .luck-good { background:#1a3a1a; color:#4f4; }
        .luck-bad  { background:#3a1a1a; color:#f44; }

        /* Таблица войск */
        .troops-report-table {
            width:100%; border-collapse:collapse; margin-bottom:15px;
        }
        .troops-report-table th {
            background:#1a1a0a; color:#888; padding:8px 12px;
            text-align:center; font-size:12px; font-weight:normal;
        }
        .troops-report-table th:first-child { text-align:left; }
        .troops-report-table td {
            padding:8px 12px; border-bottom:1px solid #222;
            text-align:center; font-size:13px;
        }
        .troops-report-table td:first-child { text-align:left; }
        .troops-report-table .losses   { color:#f44; }
        .troops-report-table .survived { color:#4f4; }
        .troops-report-table tr:hover td { background:#222; }

        .br-section { margin-bottom:20px; }
        .br-title {
            font-size:14px; font-weight:bold; margin-bottom:10px;
            padding-bottom:8px; border-bottom:1px solid #444;
        }

        /* Лут */
        .loot-section {
            background:#1a2a1a; border:1px solid #4a6a4a;
            border-radius:6px; padding:15px; margin-top:15px;
        }
        .loot-title { color:#4f4; font-weight:bold; margin-bottom:10px; }
        .loot-items { display:flex; gap:25px; flex-wrap:wrap; }
        .loot-item  { text-align:center; }
        .loot-icon  { font-size:26px; }
        .loot-amount{ font-size:18px; font-weight:bold; color:#d4a843; }
        .loot-label { font-size:11px; color:#888; }

        /* Шпионаж */
        .spy-section {
            background:#1a1a2a; border:1px solid #44a;
            border-radius:6px; padding:15px; margin-bottom:12px;
        }
        .spy-title {
            font-size:13px; font-weight:bold; color:#88f;
            margin-bottom:10px; padding-bottom:6px; border-bottom:1px solid #44a;
        }
        .spy-row {
            display:flex; justify-content:space-between;
            padding:5px 0; font-size:13px; border-bottom:1px solid #222;
        }
        .spy-row:last-child { border-bottom:none; }
        .spy-label { color:#888; }
        .spy-val   { color:#ddd; font-weight:bold; }

        /* Текстовый отчёт */
        .text-report {
            white-space:pre-wrap; font-family:'Courier New',monospace;
            font-size:12px; background:#1a1a0a; padding:15px;
            border-radius:6px; border:1px solid #444; line-height:1.7;
            color:#ccc;
        }

        .empty { padding:40px; text-align:center; color:#666; }
        .empty-icon { font-size:48px; margin-bottom:10px; }

        .btn {
            display:inline-block; padding:6px 14px; border-radius:4px;
            font-size:12px; text-decoration:none; border:1px solid #8b6914;
            background:#5a4a1a; color:#d4a843; transition:0.2s; cursor:pointer;
        }
        .btn:hover { background:#7a6a2a; }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
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

    <div class="page-header">
        <div class="page-title">📋 Боевые отчёты</div>
        <a href="?page=reports&action=read_all" class="btn">
            Прочитать все
        </a>
    </div>

    <!-- Фильтры -->
    <?php $current_type = $_GET['type'] ?? ''; ?>
    <div class="filter-tabs">
        <a href="?page=reports"
           class="filter-tab <?= $current_type===''        ?'active':'' ?>">Все</a>
        <a href="?page=reports&type=attack"
           class="filter-tab <?= $current_type==='attack'  ?'active':'' ?>">⚔ Атаки</a>
        <a href="?page=reports&type=defense"
           class="filter-tab <?= $current_type==='defense' ?'active':'' ?>">🛡 Защита</a>
        <a href="?page=reports&type=scout"
           class="filter-tab <?= $current_type==='scout'   ?'active':'' ?>">🔍 Разведка</a>
        <a href="?page=reports&type=support"
           class="filter-tab <?= $current_type==='support' ?'active':'' ?>">🛡 Поддержка</a>
    </div>

    <div class="card">
        <div class="card-header">
            📋 Отчёты
            <span style="font-size:12px;color:#aaa;"><?= count($reports) ?> шт.</span>
        </div>

        <?php if (empty($reports)): ?>
        <div class="empty">
            <div class="empty-icon">📭</div>
            Отчётов нет
        </div>
        <?php else: ?>
        <?php foreach ($reports as $r):
            $is_win  = strpos($r['title'],'✅') !== false;
            $is_loss = strpos($r['title'],'❌') !== false;
            $is_unread = !$r['is_read'];

            $type_data = [
                'attack'  => ['⚔', $is_win?'win':'loss'],
                'defense' => ['🛡', $is_win?'win':'loss'],
                'scout'   => ['🔍', $is_win?'win':'loss'],
                'support' => ['🛡', 'win'],
                'market'  => ['💰', 'win'],
                'system'  => ['📢', 'win'],
            ];
            $td = $type_data[$r['type']] ?? ['📋',''];

            $diff = time() - $r['time'];
            if ($diff < 60)        $time_str = "только что";
            elseif ($diff < 3600)  $time_str = floor($diff/60)." мин. назад";
            elseif ($diff < 86400) $time_str = floor($diff/3600)." ч. назад";
            else                   $time_str = date('d.m.Y H:i', $r['time']);
        ?>
        <div class="report-item <?= $is_unread?'unread':'' ?>"
             onclick="openReport(<?= $r['id'] ?>)"
             data-type="<?= $r['type'] ?>"
             data-title="<?= htmlspecialchars($r['title'],ENT_QUOTES) ?>"
             data-content="<?= htmlspecialchars($r['content'],ENT_QUOTES) ?>"
             data-time="<?= $time_str ?>">
            <div class="report-icon"><?= $td[0] ?></div>
            <div class="report-info">
                <div class="report-title <?= $td[1] ?>">
                    <?= htmlspecialchars($r['title']) ?>
                </div>
                <div class="report-time">🕐 <?= $time_str ?></div>
            </div>
            <div class="report-arrow">›</div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно -->
<div class="modal" id="reportModal">
    <div class="modal-content">
        <div class="report-header-modal">
            <span class="report-modal-title" id="modalTitle"></span>
            <button class="modal-close" onclick="closeReport()">✕</button>
        </div>
        <div class="report-body-modal" id="modalBody"></div>
    </div>
</div>

<script>
// Иконки юнитов
const UNIT_ICONS = {
    'Копейщик':      '🔱',
    'Мечник':        '⚔️',
    'Топорщик':      '🪓',
    'Разведчик':     '🔍',
    'Лёгкая кав.':   '🐎',
    'Тяжёлая кав.':  '🦄',
    'Таран':         '🪵',
    'Катапульта':    '💣'
};

function escHtml(str) {
    return (str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openReport(id) {
    const item = document.querySelector(`[onclick="openReport(${id})"]`);
    if (!item) return;

    const type    = item.dataset.type;
    const title   = item.dataset.title;
    const content = item.dataset.content;
    const time    = item.dataset.time;

    document.getElementById('modalTitle').textContent = title;

    let html = '';

    if (type === 'attack' || type === 'defense') {
        html = renderBattleReport(title, content, type, time);
    } else if (type === 'scout') {
        html = renderScoutReport(title, content, time);
    } else {
        html = `<div class="text-report">${escHtml(content)}</div>
                <div style="margin-top:10px;font-size:11px;color:#666;">🕐 ${time}</div>`;
    }

    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('reportModal').classList.add('active');
}

// =====================================================
// КРАСИВЫЙ БОЕВОЙ ОТЧЁТ
// =====================================================
function renderBattleReport(title, content, type, time) {
    const isWin = title.includes('✅');

    // Парсим удачу
    const luckMatch = content.match(/Удача.*?([+-]?\d+)%|Неудача.*?([+-]?\d+)%/);
    const luck      = luckMatch ? parseInt(luckMatch[1] || luckMatch[2]) : 0;
    const luckSign  = content.includes('Удача') ? '+' : '';

    // Парсим силу
    const attPowMatch = content.match(/Сила атаки:\s*(\d+)/);
    const defPowMatch = content.match(/Сила защиты:\s*(\d+)/);
    const attPow = attPowMatch ? parseInt(attPowMatch[1]) : 0;
    const defPow = defPowMatch ? parseInt(defPowMatch[1]) : 0;

    // Парсим лут
    const lootWood  = content.match(/🪵(\d[\d\s]*)/);
    const lootStone = content.match(/🪨(\d[\d\s]*)/);
    const lootIron  = content.match(/⛏(\d[\d\s]*)/);
    const woodVal   = lootWood  ? parseInt(lootWood[1].replace(/\s/g,''))  : 0;
    const stoneVal  = lootStone ? parseInt(lootStone[1].replace(/\s/g,'')) : 0;
    const ironVal   = lootIron  ? parseInt(lootIron[1].replace(/\s/g,''))  : 0;
    const hasLoot   = woodVal + stoneVal + ironVal > 0;

    // Парсим урон стене
    const wallDmg = content.match(/Стена повреждена на (\d+)/);

    // Парсим секции войск
    const lines = content.split('\n');
    let attLines = [];
    let defLines = [];
    let section  = '';

    lines.forEach(line => {
        const t = line.trim();
        if (t === 'АТАКУЮЩИЕ') { section = 'att'; return; }
        if (t === 'ЗАЩИТНИКИ' || t === 'ВАШИ ВОЙСКА') { section = 'def'; return; }
        if (!t || t.startsWith('━') || t.startsWith('Юнит') ||
            t.startsWith('ПОБЕДА') || t.startsWith('ПОРАЖЕНИЕ') ||
            t.startsWith('Сила') || t.startsWith('Удача') || t.startsWith('Неудача') ||
            t.startsWith('⚔') || t.startsWith('🛡') || t.startsWith('Наград') ||
            t.startsWith('Украд') || t.startsWith('Стена')) return;

        if (section === 'att') attLines.push(t);
        else if (section === 'def') defLines.push(t);
    });

    return `
        <div class="br-result ${isWin?'victory':'defeat'}">
            <div class="br-result-icon">${isWin?'🏆':'💀'}</div>
            <div class="br-result-text">${isWin?'ПОБЕДА!':'ПОРАЖЕНИЕ'}</div>
            <div class="br-meta">
                ${attPow ? `<div class="br-meta-item">
                    <div class="br-meta-label">Сила атаки</div>
                    <div class="br-meta-val">${attPow.toLocaleString('ru')}</div>
                </div>` : ''}
                ${defPow ? `<div class="br-meta-item">
                    <div class="br-meta-label">Сила защиты</div>
                    <div class="br-meta-val">${defPow.toLocaleString('ru')}</div>
                </div>` : ''}
                <div class="br-meta-item">
                    <div class="br-meta-label">Удача</div>
                    <span class="luck-badge ${luck>=0?'luck-good':'luck-bad'}">
                        ${luckSign}${luck}%
                    </span>
                </div>
            </div>
        </div>

        <div class="br-section">
            <div class="br-title" style="color:#f44;">⚔ Атакующие</div>
            ${buildTroopTable(attLines)}
        </div>

        <div class="br-section">
            <div class="br-title" style="color:#4f4;">🛡 Защитники</div>
            ${buildTroopTable(defLines)}
        </div>

        ${hasLoot ? `
        <div class="loot-section">
            <div class="loot-title">💰 Награблено</div>
            <div class="loot-items">
                <div class="loot-item">
                    <div class="loot-icon">🪵</div>
                    <div class="loot-amount">${woodVal.toLocaleString('ru')}</div>
                    <div class="loot-label">Дерево</div>
                </div>
                <div class="loot-item">
                    <div class="loot-icon">🪨</div>
                    <div class="loot-amount">${stoneVal.toLocaleString('ru')}</div>
                    <div class="loot-label">Камень</div>
                </div>
                <div class="loot-item">
                    <div class="loot-icon">⛏</div>
                    <div class="loot-amount">${ironVal.toLocaleString('ru')}</div>
                    <div class="loot-label">Железо</div>
                </div>
            </div>
        </div>` : ''}

        ${wallDmg ? `
        <div style="background:#3a1a1a;border:1px solid #8a4a4a;border-radius:6px;
                    padding:10px;margin-top:10px;color:#f66;font-size:13px;">
            🧱 Стена повреждена на ${wallDmg[1]} уровней!
        </div>` : ''}

        <div style="margin-top:12px;font-size:11px;color:#666;">🕐 ${time}</div>
    `;
}

// Строим таблицу войск из строк текста
function buildTroopTable(lines) {
    if (!lines || lines.length === 0) {
        return '<p style="color:#666;font-size:12px;padding:8px;">Нет данных</p>';
    }

    let rows = '';
    let found = false;

    lines.forEach(line => {
        const trimmed = line.trim();
        if (!trimmed) return;

        // Разбиваем по 2+ пробелам (str_pad создаёт такие строки)
        const parts = trimmed.split(/\s{2,}/);

        if (parts.length >= 3) {
            const name     = parts[0].trim();
            const sent     = Math.max(0, parseInt(parts[1]) || 0);
            const lost     = Math.max(0, parseInt(parts[2]) || 0);
            // Выжившие — берём из отчёта или считаем сами
            const survived = parts[3] !== undefined
                ? Math.max(0, parseInt(parts[3]) || 0)
                : Math.max(0, sent - lost);

            if (!name || isNaN(sent)) return;

            const icon = UNIT_ICONS[name] || '👤';
            found = true;

            rows += `
                <tr>
                    <td>${icon} ${escHtml(name)}</td>
                    <td>${sent.toLocaleString('ru')}</td>
                    <td class="losses">${lost > 0 ? lost.toLocaleString('ru') : '0'}</td>
                    <td class="survived">${survived.toLocaleString('ru')}</td>
                </tr>
            `;
        }
    });

    if (!found) {
        return '<p style="color:#666;font-size:12px;padding:8px;">Войск нет</p>';
    }

    return `
        <table class="troops-report-table">
            <tr>
                <th>Юнит</th>
                <th>Отправлено</th>
                <th>Потери</th>
                <th>Выжило</th>
            </tr>
            ${rows}
        </table>
    `;
}

// =====================================================
// ШПИОНАЖ
// =====================================================
function renderScoutReport(title, content, time) {
    const isSuccess = title.includes('✅');

    if (!isSuccess) {
        return `
            <div style="background:#3a1a1a;border:2px solid #8a4a4a;
                        border-radius:8px;padding:20px;text-align:center;">
                <div style="font-size:40px;margin-bottom:10px;">💀</div>
                <div style="font-size:18px;color:#f44;font-weight:bold;">
                    Разведчики обнаружены!
                </div>
                <div style="color:#888;margin-top:8px;font-size:13px;white-space:pre-wrap;">
                    ${escHtml(content.split('\n').slice(2,8).join('\n'))}
                </div>
            </div>
            <div style="margin-top:10px;font-size:11px;color:#666;">🕐 ${time}</div>
        `;
    }

    // Парсим ресурсы
    const lines = content.split('\n');
    let resources = {};
    let troops    = {};
    let buildings = [];
    let section   = '';

    lines.forEach(line => {
        const t = line.trim();
        if (t.includes('РЕСУРСЫ'))  { section='res';  return; }
        if (t.includes('ЗДАНИЯ'))   { section='bld';  return; }
        if (t.includes('ВОЙСКА'))   { section='trp';  return; }
        if (t.includes('ПОДДЕРЖКА')){ section='sup';  return; }

        if (section==='res') {
            const mW = t.match(/🪵\s*Дерево[:\s]+(\d[\d\s,]*)/);
            const mS = t.match(/🪨\s*Камень[:\s]+(\d[\d\s,]*)/);
            const mI = t.match(/⛏\s*Железо[:\s]+(\d[\d\s,]*)/);
            if (mW) resources.wood  = parseInt(mW[1].replace(/[\s,]/g,''));
            if (mS) resources.stone = parseInt(mS[1].replace(/[\s,]/g,''));
            if (mI) resources.iron  = parseInt(mI[1].replace(/[\s,]/g,''));
        }

        if (section==='bld' && t && !t.startsWith('━')) {
            buildings.push(t);
        }

        if (section==='trp') {
            const parts = t.split(/\s{2,}/);
            if (parts.length >= 2) {
                const name  = parts[0].trim();
                const count = parseInt(parts[1]) || 0;
                if (name && !isNaN(count) && count >= 0) {
                    troops[name] = count;
                }
            }
        }
    });

    // Ресурсы
    let resHtml = '';
    if (resources.wood !== undefined) {
        resHtml = `
        <div class="spy-section">
            <div class="spy-title">💰 Ресурсы</div>
            <div class="spy-row">
                <span class="spy-label">🪵 Дерево</span>
                <span class="spy-val">${(resources.wood||0).toLocaleString('ru')}</span>
            </div>
            <div class="spy-row">
                <span class="spy-label">🪨 Камень</span>
                <span class="spy-val">${(resources.stone||0).toLocaleString('ru')}</span>
            </div>
            <div class="spy-row">
                <span class="spy-label">⛏ Железо</span>
                <span class="spy-val">${(resources.iron||0).toLocaleString('ru')}</span>
            </div>
        </div>`;
    }

    // Здания
    let bldsHtml = '';
    if (buildings.length > 0) {
        const bldRows = buildings
            .filter(b => b && !b.startsWith('━') && !b.includes('ЗДАНИЯ'))
            .map(b => `<div class="spy-row"><span class="spy-label">${escHtml(b)}</span></div>`)
            .join('');
        if (bldRows) {
            bldsHtml = `
            <div class="spy-section">
                <div class="spy-title">🏛 Здания</div>
                ${bldRows}
            </div>`;
        }
    }

    // Войска
    let trpsHtml = '';
    const troopEntries = Object.entries(troops);
    if (troopEntries.length > 0) {
        const rows = troopEntries.map(([name, count]) => {
            const icon = UNIT_ICONS[name] || '👤';
            return `<div class="spy-row">
                <span class="spy-label">${icon} ${escHtml(name)}</span>
                <span class="spy-val">${count.toLocaleString('ru')}</span>
            </div>`;
        }).join('');
        trpsHtml = `
        <div class="spy-section">
            <div class="spy-title">⚔ Войска</div>
            ${rows}
        </div>`;
    }

    return `
        <div style="background:#1a1a2a;border:2px solid #44a;border-radius:8px;
                    padding:15px;text-align:center;margin-bottom:15px;">
            <div style="font-size:36px;margin-bottom:8px;">🔍</div>
            <div style="font-size:18px;color:#88f;font-weight:bold;">Разведка успешна!</div>
        </div>

        ${resHtml}
        ${bldsHtml}
        ${trpsHtml}

        <div style="font-size:11px;color:#666;margin-top:10px;">🕐 ${time}</div>
    `;
}

function closeReport() {
    document.getElementById('reportModal').classList.remove('active');
}

document.getElementById('reportModal').addEventListener('click', function(e) {
    if (e.target === this) closeReport();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeReport();
});
</script>

</body>
</html>