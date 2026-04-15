<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> — Браузерная стратегия</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',Arial;
            background:#1a1a0e; color:#ddd; line-height:1.6;
        }

        /* Hero */
        .hero {
            background:linear-gradient(135deg,#2a1a0e 0%,#1a1a0e 50%,#0e1a2a 100%);
            border-bottom:3px solid #8b6914;
            padding:60px 20px; text-align:center; position:relative; overflow:hidden;
        }
        .hero::before {
            content:'⚔'; position:absolute; font-size:300px;
            opacity:0.03; top:50%; left:50%; transform:translate(-50%,-50%);
        }
        .hero-title {
            font-size:clamp(28px,5vw,52px); font-weight:bold; color:#d4a843;
            text-shadow:0 0 30px rgba(212,168,67,0.5); margin-bottom:10px;
        }
        .hero-sub { font-size:18px; color:#888; margin-bottom:30px; }
        .hero-btns { display:flex; gap:15px; justify-content:center; flex-wrap:wrap; }
        .btn-hero-main {
            padding:16px 45px; font-size:18px; font-weight:bold;
            background:linear-gradient(135deg,#8b6914,#d4a843);
            color:#1a1a0e; border:none; border-radius:8px;
            cursor:pointer; text-decoration:none; transition:0.3s;
        }
        .btn-hero-main:hover {
            transform:translateY(-2px);
            box-shadow:0 8px 25px rgba(212,168,67,0.4);
        }
        .btn-hero-sec {
            padding:16px 45px; font-size:18px; background:transparent;
            color:#d4a843; border:2px solid #8b6914; border-radius:8px;
            cursor:pointer; text-decoration:none; transition:0.3s;
        }
        .btn-hero-sec:hover { background:#3a2c10; transform:translateY(-2px); }

        .container { max-width:1200px; margin:0 auto; padding:25px 20px; }

        /* Баннер события */
        .event-banner {
            border-radius:10px; padding:15px 20px; margin-bottom:20px;
            display:flex; align-items:center; gap:15px; flex-wrap:wrap;
        }
        .event-banner-icon { font-size:32px; flex-shrink:0; }
        .event-banner-info { flex:1; min-width:200px; }
        .event-banner-title { font-size:16px; font-weight:bold; margin-bottom:3px; }
        .event-banner-desc  { font-size:12px; color:#aaa; }
        .event-banner-timer {
            font-size:20px; font-weight:bold;
            font-family:monospace; text-align:center;
        }
        .event-banner-btn {
            padding:8px 18px; border-radius:5px; text-decoration:none;
            font-size:13px; white-space:nowrap; color:#fff;
        }

        /* Статистика */
        .stats-bar {
            display:flex; justify-content:center; gap:0; flex-wrap:wrap;
            background:#2a2010; border:2px solid #5a4a20; border-radius:10px;
            overflow:hidden; margin-bottom:25px;
        }
        .stat-item {
            flex:1; min-width:130px; padding:18px;
            text-align:center; border-right:1px solid #5a4a20; transition:0.2s;
        }
        .stat-item:last-child { border-right:none; }
        .stat-item:hover { background:#3a2c10; }
        .stat-num { font-size:28px; font-weight:bold; color:#d4a843; display:block; }
        .stat-lbl { font-size:12px; color:#888; }

        /* Контент */
        .content-grid {
            display:grid; grid-template-columns:1fr 360px; gap:20px;
        }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:10px; overflow:hidden; margin-bottom:18px;
            box-shadow:0 4px 20px rgba(0,0,0,0.3);
        }
        .card-header {
            background:#3a2c10; padding:12px 18px;
            font-weight:bold; color:#d4a843; font-size:14px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .card-body { padding:18px; }

        /* Блок игрока */
        .player-block {
            background:#1a2a1a; border:2px solid #4a8a4a;
            border-radius:10px; padding:18px; margin-bottom:18px;
        }
        .player-block h2 { color:#4f4; font-size:18px; margin-bottom:14px; }
        .player-stat {
            display:flex; justify-content:space-between;
            padding:7px 0; border-bottom:1px solid #333; font-size:13px;
        }
        .player-stat:last-child { border-bottom:none; }
        .player-stat-label { color:#888; }
        .player-stat-value { font-weight:bold; color:#d4a843; }

        /* Быстрые действия */
        .quick-actions {
            display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:14px;
        }
        .quick-btn {
            padding:10px; background:#3a2c10; border:1px solid #8b6914;
            border-radius:6px; color:#d4a843; text-decoration:none;
            text-align:center; font-size:12px; transition:0.2s;
        }
        .quick-btn:hover { background:#5a4a20; }
        .quick-btn-icon { font-size:20px; display:block; margin-bottom:3px; }

        /* Объявления */
        .ann-item {
            padding:14px 0; border-bottom:1px solid #333;
        }
        .ann-item:last-child { border-bottom:none; }
        .ann-title { font-size:14px; font-weight:bold; color:#d4a843; margin-bottom:4px; }
        .ann-meta  { font-size:11px; color:#666; margin-bottom:6px; }
        .ann-text  { font-size:13px; color:#aaa; line-height:1.6; }

        /* Топ игроков */
        .top-item {
            display:flex; align-items:center; gap:10px;
            padding:9px 0; border-bottom:1px solid #333;
        }
        .top-item:last-child { border-bottom:none; }
        .top-rank { font-size:18px; font-weight:bold; min-width:32px; text-align:center; }
        .top-name {
            flex:1; color:#d4a843; text-decoration:none; font-size:13px; font-weight:bold;
        }
        .top-name:hover { text-decoration:underline; }
        .top-pts  { color:#888; font-size:12px; text-align:right; }
        .top-pts strong { color:#d4a843; display:block; font-size:13px; }

        /* Фичи */
        .features-grid {
            display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:25px;
        }
        .feature-card {
            background:#2a2a1a; border:1px solid #5a4a20; border-radius:8px;
            padding:18px; text-align:center; transition:0.2s;
        }
        .feature-card:hover { border-color:#d4a843; transform:translateY(-2px); }
        .feature-icon  { font-size:32px; margin-bottom:8px; }
        .feature-title { font-size:13px; font-weight:bold; color:#d4a843; margin-bottom:5px; }
        .feature-desc  { font-size:11px; color:#888; line-height:1.5; }

        /* Онлайн */
        .online-dot { color:#0f0; font-size:10px; }

        /* Алерты */
        .alert-success {
            background:#1a3a1a; border:1px solid #4a4; border-radius:8px;
            padding:14px; margin-bottom:18px; color:#0f0;
        }
        .alert-error {
            background:#3a1a1a; border:1px solid #a44; border-radius:8px;
            padding:14px; margin-bottom:18px; color:#f66;
        }

        /* Ссылка */
        .link-gold { color:#d4a843; text-decoration:none; font-size:12px; }
        .link-gold:hover { text-decoration:underline; }

        @media(max-width:900px) {
            .content-grid  { grid-template-columns:1fr; }
            .features-grid { grid-template-columns:repeat(2,1fr); }
        }
        @media(max-width:500px) {
            .features-grid { grid-template-columns:1fr; }
            .stats-bar { flex-direction:column; }
            .stat-item { border-right:none; border-bottom:1px solid #5a4a20; }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- Hero (только для незалогиненных) -->
<?php if (!$logged_in): ?>
<div class="hero">
    <div class="hero-title">⚔ <?= APP_NAME ?></div>
    <div class="hero-sub">Браузерная стратегия · Стройте · Воюйте · Побеждайте</div>
    <div class="hero-btns">
        <a href="?page=register" class="btn-hero-main">🏰 Начать играть</a>
        <a href="?page=login"    class="btn-hero-sec">🔑 Войти</a>
    </div>
</div>
<?php endif; ?>

<div class="container">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Баннер активного события -->
    <?php if (!empty($active_event)):
        $et_styles = [
            'gold_rush'          => ['color'=>'#d4a843','bg'=>'#2a2010','border'=>'#8b6914'],
            'barbarian_invasion' => ['color'=>'#f44',   'bg'=>'#2a1a1a','border'=>'#8a1a1a'],
            'tournament'         => ['color'=>'#d4a843','bg'=>'#2a2010','border'=>'#d4a843'],
            'caravan'            => ['color'=>'#0dd',   'bg'=>'#1a2a2a','border'=>'#1a8a8a'],
            'plague'             => ['color'=>'#8a4',   'bg'=>'#1a2a1a','border'=>'#4a8a2a'],
            'blessing'           => ['color'=>'#88f',   'bg'=>'#1a1a2a','border'=>'#2a2a8a'],
        ];
        $es = $et_styles[$active_event['type']] ?? ['color'=>'#d4a843','bg'=>'#2a2010','border'=>'#8b6914'];
        $rem_ev = max(0, $active_event['ends_at'] - time());
    ?>
    <div class="event-banner"
         style="background:<?= $es['bg'] ?>;border:2px solid <?= $es['border'] ?>;">
        <div class="event-banner-icon"><?= $active_event['icon'] ?></div>
        <div class="event-banner-info">
            <div class="event-banner-title" style="color:<?= $es['color'] ?>;">
                🌍 Мировое событие: <?= htmlspecialchars($active_event['title']) ?>
            </div>
            <div class="event-banner-desc">
                <?= mb_substr(htmlspecialchars($active_event['description']), 0, 100) ?>...
            </div>
        </div>
        <div>
            <div style="font-size:11px;color:#888;text-align:center;margin-bottom:3px;">
                Осталось:
            </div>
            <div class="event-banner-timer"
                 id="bannerTimer"
                 style="color:<?= $es['color'] ?>;"
                 data-end="<?= $active_event['ends_at'] ?>">
                <?= gmdate('H:i:s', $rem_ev) ?>
            </div>
        </div>
        <a href="?page=events"
           class="event-banner-btn"
           style="background:<?= $es['border'] ?>;">
            Подробнее →
        </a>
    </div>
    <script>
    (function(){
        const el = document.getElementById('bannerTimer');
        if (!el) return;
        const end = parseInt(el.dataset.end);
        function tick() {
            const rem = Math.max(0, end - Math.floor(Date.now()/1000));
            const h=Math.floor(rem/3600), m=Math.floor((rem%3600)/60), s=rem%60;
            el.textContent =
                `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            if (rem<=0) location.reload();
        }
        setInterval(tick, 1000); tick();
    })();
    </script>
    <?php endif; ?>

    <!-- Статистика сервера -->
    <div class="stats-bar">
        <div class="stat-item">
            <span class="stat-num"><?= number_format($players) ?></span>
            <span class="stat-lbl">👥 Игроков</span>
        </div>
        <div class="stat-item">
            <span class="stat-num" style="color:#0f0;">
                <?= number_format($online) ?>
            </span>
            <span class="stat-lbl"><span class="online-dot">●</span> Онлайн</span>
        </div>
        <div class="stat-item">
            <span class="stat-num"><?= number_format($total_villages ?? 0) ?></span>
            <span class="stat-lbl">🏘 Деревень</span>
        </div>
        <div class="stat-item">
            <span class="stat-num"><?= number_format($total_alliances ?? 0) ?></span>
            <span class="stat-lbl">🏰 Альянсов</span>
        </div>
        <div class="stat-item">
            <span class="stat-num"><?= $config['game']['speed'] ?? 1 ?>x</span>
            <span class="stat-lbl">⚡ Скорость</span>
        </div>
    </div>

    <!-- Фичи (для незалогиненных) -->
    <?php if (!$logged_in): ?>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🏘</div>
            <div class="feature-title">Развивай деревни</div>
            <div class="feature-desc">Строй здания, добывай ресурсы, расширяй королевство</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⚔</div>
            <div class="feature-title">Воюй с врагами</div>
            <div class="feature-desc">Атакуй, грабь, разрушай стены тарануми</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🦸</div>
            <div class="feature-title">Прокачивай Героя</div>
            <div class="feature-desc">Герой даёт бонусы к атаке, защите и ресурсам</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🏰</div>
            <div class="feature-title">Создавай альянсы</div>
            <div class="feature-desc">Объединяйся, защищайте друг друга, побеждайте вместе</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🌍</div>
            <div class="feature-title">Мировые события</div>
            <div class="feature-desc">Турниры, нашествия, золотые лихорадки — каждые 2-3 дня</div>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🏆</div>
            <div class="feature-title">Соревнуйся</div>
            <div class="feature-desc">Рейтинг игроков и альянсов, борись за первое место</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Основной контент -->
    <div class="content-grid">

        <!-- Левая колонка -->
        <div>

            <!-- Блок авторизованного игрока -->
            <?php if ($logged_in && $user): ?>
            <div class="player-block">
                <h2>👋 Привет, <?= htmlspecialchars($user['username']) ?>!</h2>
                <div class="player-stat">
                    <span class="player-stat-label">Очки</span>
                    <span class="player-stat-value">
                        <?= number_format($user['points'] ?? 0) ?>
                    </span>
                </div>
                <div class="player-stat">
                    <span class="player-stat-label">Деревень</span>
                    <span class="player-stat-value"><?= $user['villages'] ?? 0 ?></span>
                </div>
                <?php if (!empty($user['alliance_id'])): ?>
                <div class="player-stat">
                    <span class="player-stat-label">Альянс</span>
                    <span class="player-stat-value" style="font-size:12px;">
                        <?= htmlspecialchars($user['alliance_role'] ?? '') ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="player-stat">
                    <span class="player-stat-label">На сервере с</span>
                    <span class="player-stat-value">
                        <?= date('d.m.Y', $user['join_date'] ?? time()) ?>
                    </span>
                </div>

                <!-- Быстрые действия -->
                <div class="quick-actions">
                    <a href="?page=villages" class="quick-btn">
                        <span class="quick-btn-icon">🏘</span>Деревни
                    </a>
                    <a href="?page=map" class="quick-btn">
                        <span class="quick-btn-icon">🗺</span>Карта
                    </a>
                    <a href="?page=hero" class="quick-btn">
                        <span class="quick-btn-icon">🦸</span>Герой
                    </a>
                    <a href="?page=events" class="quick-btn">
                        <span class="quick-btn-icon">🌍</span>События
                    </a>
                    <a href="?page=ranking" class="quick-btn">
                        <span class="quick-btn-icon">🏆</span>Рейтинг
                    </a>
                    <a href="?page=alliances" class="quick-btn">
                        <span class="quick-btn-icon">🏰</span>Альянсы
                    </a>
                    <a href="?page=market" class="quick-btn">
                        <span class="quick-btn-icon">💰</span>Рынок
                    </a>
                    <a href="?page=reports" class="quick-btn">
                        <span class="quick-btn-icon">📋</span>Отчёты
                        <?php if (($unread_reports ?? 0) > 0): ?>
                            <span style="background:#c00;color:#fff;border-radius:8px;
                                         padding:1px 5px;font-size:9px;">
                                <?= $unread_reports ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Объявления / Новости -->
            <div class="card">
                <div class="card-header">
                    📢 Новости
                    <span style="font-size:12px;color:#aaa;">
                        <?= count($announcements) ?> записей
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <div style="color:#666;text-align:center;padding:20px;">
                            Объявлений пока нет
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                        <div class="ann-item">
                            <div class="ann-title">
                                <?= htmlspecialchars($ann['title']) ?>
                            </div>
                            <div class="ann-meta">
                                ✍ <?= htmlspecialchars($ann['author'] ?? 'Администрация') ?>
                                · 🕐 <?= date('d.m.Y H:i', $ann['time']) ?>
                            </div>
                            <div class="ann-text">
                                <?= nl2br(htmlspecialchars($ann['content'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Правая колонка -->
        <div>

            <!-- Топ игроков -->
            <div class="card">
                <div class="card-header">
                    🏆 Топ игроков
                    <a href="?page=ranking" class="link-gold">Все →</a>
                </div>
                <div class="card-body" style="padding:10px 15px;">
                    <?php foreach ($top_players as $i => $tp):
                        $icons  = ['🥇','🥈','🥉'];
                        $icon   = $icons[$i] ?? ($i+1);
                        $is_me  = $logged_in && $tp['id'] == ($user['id'] ?? 0);
                    ?>
                    <div class="top-item"
                         style="<?= $is_me?'background:#1a2a1a;border-radius:4px;padding:9px;':'' ?>">
                        <div class="top-rank"><?= $icon ?></div>
                        <div style="flex:1;">
                            <a href="?page=player&id=<?= $tp['id'] ?>"
                               class="top-name"
                               style="color:<?= $is_me?'#4f4':'#d4a843' ?>">
                                <?= htmlspecialchars($tp['username']) ?>
                                <?= $is_me?'<span style="color:#888;font-size:10px;">(вы)</span>':'' ?>
                            </a>
                            <?php if (!empty($tp['alliance_tag'])): ?>
                            <span style="font-size:10px;color:#888;">
                                [<?= htmlspecialchars($tp['alliance_tag']) ?>]
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="top-pts">
                            <strong><?= number_format($tp['points']) ?></strong>
                            <?= $tp['villages'] ?> дер.
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Топ альянсов -->
            <?php if (!empty($top_alliances)): ?>
            <div class="card">
                <div class="card-header">
                    🏰 Топ альянсов
                    <a href="?page=ranking&tab=alliances" class="link-gold">Все →</a>
                </div>
                <div class="card-body" style="padding:10px 15px;">
                    <?php foreach ($top_alliances as $i => $ta):
                        $icons = ['🥇','🥈','🥉'];
                        $icon  = $icons[$i] ?? ($i+1);
                    ?>
                    <div class="top-item">
                        <div class="top-rank"><?= $icon ?></div>
                        <div style="flex:1;">
                            <a href="?page=alliance&id=<?= $ta['id'] ?>"
                               class="top-name">
                                [<?= htmlspecialchars($ta['tag']) ?>]
                                <?= htmlspecialchars($ta['name']) ?>
                            </a>
                        </div>
                        <div class="top-pts">
                            <strong><?= number_format($ta['points']) ?></strong>
                            <?= $ta['members_count'] ?> чел.
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Инфо о сервере -->
            <div class="card">
                <div class="card-header">📊 Сервер</div>
                <div class="card-body">
                    <?php
                    $info_rows = [
                        ['🌍 Сервер',   $config['game']['server_name'] ?? 'Мир 1'],
                        ['⚡ Скорость',  ($config['game']['speed'] ?? 1) . 'x'],
                        ['📅 Старт',     date('d.m.Y', $config['game']['start_time'] ?? time())],
                        ['👥 Игроков',   number_format($players)],
                        ['🏘 Деревень',  number_format($total_villages ?? 0)],
                        ['🏰 Альянсов',  number_format($total_alliances ?? 0)],
                    ];
                    foreach ($info_rows as $row): ?>
                    <div style="display:flex;justify-content:space-between;
                                padding:7px 0;border-bottom:1px solid #333;font-size:13px;">
                        <span style="color:#888;"><?= $row[0] ?></span>
                        <span style="color:#d4a843;font-weight:bold;"><?= $row[1] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>