<?php
// templates/navbar.php
if (!isset($db)||$db===null) { global $db; }

$counters    = ['messages'=>0,'reports'=>0,'attacks'=>0];
$notifs      = [];
$in_alliance = false;
$is_admin    = false;
$hero_status = null;
$has_event   = false;
$quest_ready = 0;

if (isset($_SESSION['user_id'])&&$db!==null) {
    try { $nm=new NotificationManager($db); $counters=$nm->getCounters($_SESSION['user_id']); $notifs=$nm->getNotifications($_SESSION['user_id']); } catch (Exception $e) {}
    try { $stmt=$db->prepare("SELECT alliance_id FROM alliance_members WHERE user_id=?"); $stmt->execute([$_SESSION['user_id']]); $al=$stmt->fetch(); $in_alliance=!empty($al['alliance_id']); } catch (Exception $e) {}
    try { $stmt=$db->prepare("SELECT is_admin FROM users WHERE id=?"); $stmt->execute([$_SESSION['user_id']]); $adm=$stmt->fetch(); $is_admin=!empty($adm['is_admin']); } catch (Exception $e) {}
    try { $stmt=$db->prepare("SELECT status,hp,hp_max,level FROM heroes WHERE user_id=?"); $stmt->execute([$_SESSION['user_id']]); $hero_status=$stmt->fetch(); } catch (Exception $e) {}
    try { $stmt=$db->prepare("SELECT id FROM world_events WHERE status='active' AND ends_at>? LIMIT 1"); $stmt->execute([time()]); $has_event=(bool)$stmt->fetch(); } catch (Exception $e) {}
    try {
        $stmt=$db->prepare("SELECT COUNT(*) as cnt FROM quest_progress qp JOIN quests q ON qp.quest_id=q.id WHERE qp.user_id=? AND qp.completed=1 AND qp.rewarded=0 AND ((q.type='daily' AND qp.date=CURDATE()) OR (q.type='weekly' AND qp.date>=DATE_SUB(CURDATE(),INTERVAL WEEKDAY(CURDATE()) DAY)) OR (q.type='tutorial' AND qp.date='2000-01-01'))");
        $stmt->execute([$_SESSION['user_id']]); $quest_ready=$stmt->fetch()['cnt']??0;
    } catch (Exception $e) {}
}
?>
<style>
.navbar{background:#2c1f0e;border-bottom:3px solid #8b6914;padding:0 15px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:1000;box-shadow:0 2px 15px rgba(0,0,0,0.5);flex-wrap:wrap;min-height:48px;}
.navbar-brand{font-size:17px;font-weight:bold;color:#d4a843;text-decoration:none;padding:12px 0;white-space:nowrap;flex-shrink:0;}
.navbar-brand:hover{color:#f0c050;}
.navbar-menu{display:flex;align-items:center;gap:1px;flex:1;margin:0 8px;flex-wrap:wrap;}
.nav-link{padding:10px 9px;color:#aaa;text-decoration:none;font-size:12px;border-radius:4px;transition:0.2s;position:relative;white-space:nowrap;}
.nav-link:hover{background:#3a2c10;color:#d4a843;}
.nav-link.active{color:#d4a843;background:#3a2c10;}
.nav-badge{position:absolute;top:5px;right:2px;background:#c00;color:#fff;border-radius:10px;padding:1px 4px;font-size:9px;font-weight:bold;min-width:14px;text-align:center;line-height:1.4;}
.nav-divider{width:1px;height:16px;background:#3a3a2a;margin:0 3px;flex-shrink:0;}
.navbar-right{display:flex;align-items:center;gap:5px;flex-shrink:0;}
.nav-user{color:#888;font-size:12px;padding:5px 8px;background:#1a1a0a;border-radius:4px;border:1px solid #333;white-space:nowrap;max-width:120px;overflow:hidden;text-overflow:ellipsis;}
.hero-nav{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:4px;font-size:11px;background:#1a1a0a;border:1px solid #444;text-decoration:none;transition:0.2s;}
.hero-nav:hover{border-color:#8b6914;}
.hero-nav.alive{border-color:#2a8a2a;color:#4f4;}
.hero-nav.regen{border-color:#2a2a8a;color:#88f;}
.hero-nav.dead{border-color:#8a2a2a;color:#f44;}
.notif-wrap{position:relative;}
.notif-btn{padding:5px 9px;background:#1a1a0a;border:1px solid #333;border-radius:4px;color:#aaa;cursor:pointer;font-size:13px;transition:0.2s;white-space:nowrap;user-select:none;position:relative;}
.notif-btn:hover{background:#2a2010;border-color:#8b6914;color:#d4a843;}
.notif-dropdown{display:none;position:absolute;top:calc(100%+5px);right:0;background:#2a2a1a;border:2px solid #5a4a20;border-radius:8px;min-width:280px;max-width:320px;box-shadow:0 10px 40px rgba(0,0,0,0.8);z-index:9999;}
.notif-wrap.open .notif-dropdown{display:block;}
.notif-header{padding:10px 14px;font-weight:bold;color:#d4a843;font-size:13px;border-bottom:1px solid #444;display:flex;justify-content:space-between;}
.notif-item{display:flex;align-items:flex-start;gap:8px;padding:10px 14px;border-bottom:1px solid #333;text-decoration:none;transition:0.2s;}
.notif-item:hover{background:#3a2c10;}
.notif-item:last-child{border-bottom:none;}
.notif-icon{font-size:15px;min-width:18px;text-align:center;}
.notif-text{font-size:11px;color:#ccc;line-height:1.4;}
.notif-empty{padding:16px;text-align:center;color:#666;font-size:12px;}
.attack-warning{animation:blink 1s infinite;color:#f44 !important;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:0.3;}}
.event-pulse{animation:epulse 2s infinite;}
@keyframes epulse{0%,100%{opacity:1;}50%{opacity:0.6;}}
.nav-toggle{display:none;background:none;border:none;color:#d4a843;font-size:22px;cursor:pointer;padding:8px;flex-shrink:0;}
@media(max-width:1100px){.nav-link{padding:8px 8px;font-size:11px;}}
@media(max-width:768px){
    .nav-toggle{display:block;}
    .navbar-menu{display:none;width:100%;flex-direction:column;padding:5px 0;gap:1px;margin:0;border-top:1px solid #444;order:3;}
    .navbar-menu.open{display:flex;}
    .nav-link{width:100%;padding:10px 15px;border-radius:0;}
    .nav-divider{display:none;}
    .notif-dropdown{right:-5px;min-width:260px;}
    .hero-nav{display:none;}
}
</style>

<nav class="navbar">
    <a href="?page=home" class="navbar-brand">⚔ <?= APP_NAME ?></a>
    <button class="nav-toggle" onclick="toggleNavMenu()">☰</button>

    <div class="navbar-menu" id="navMenu">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="?page=home"     class="nav-link <?= ($page??'')==='home'    ?'active':'' ?>">🏠 Главная</a>
            <a href="?page=profile"  class="nav-link <?= ($page??'')==='profile' ?'active':'' ?>">👤 Профиль</a>
            <a href="?page=villages" class="nav-link <?= ($page??'')==='villages'?'active':'' ?>">🏘 Деревни</a>
            <a href="?page=map"      class="nav-link <?= ($page??'')==='map'     ?'active':'' ?>">🗺 Карта</a>

            <div class="nav-divider"></div>

            <a href="?page=hero"          class="nav-link <?= ($page??'')==='hero'        ?'active':'' ?>">🦸 Герой</a>
            <a href="?page=quests"        class="nav-link <?= ($page??'')==='quests'      ?'active':'' ?>">
                📋 Квесты
                <?php if ($quest_ready>0): ?><span class="nav-badge" style="background:#2a8a2a;"><?= $quest_ready ?></span><?php endif; ?>
            </a>
            <a href="?page=technologies"  class="nav-link <?= ($page??'')==='technologies'?'active':'' ?>">🔬 Технологии</a>
            <a href="?page=nobleman"      class="nav-link <?= ($page??'')==='nobleman'    ?'active':'' ?>">👑 Захват</a>
            <a href="?page=events"        class="nav-link <?= ($page??'')==='events'      ?'active':'' ?><?= $has_event?' event-pulse':'' ?>">
                🌍 События
                <?php if ($has_event): ?><span class="nav-badge" style="background:#d4a843;color:#1a1a0e;">!</span><?php endif; ?>
            </a>
            <a href="?page=ranking"       class="nav-link <?= ($page??'')==='ranking'     ?'active':'' ?>">🏆 Рейтинг</a>
            <a href="?page=alliances"     class="nav-link <?= ($page??'')==='alliances'   ?'active':'' ?>">🏰 Альянсы</a>
            <a href="?page=market"        class="nav-link <?= ($page??'')==='market'      ?'active':'' ?>">💰 Рынок</a>
            <a href="?page=black_market"  class="nav-link <?= ($page??'')==='black_market'?'active':'' ?>" style="color:#c8a;">🌑 Тёмн.рынок</a>

            <?php if ($in_alliance): ?>
            <a href="?page=alliance_chat" class="nav-link <?= ($page??'')==='alliance_chat'?'active':'' ?>">💬 Чат</a>
            <a href="?page=diplomacy"     class="nav-link <?= ($page??'')==='diplomacy'   ?'active':'' ?>">🤝 Диплом.</a>
            <a href="?page=alliance_wars" class="nav-link <?= ($page??'')==='alliance_wars'?'active':'' ?>">⚔ Войны</a>
            <a href="?page=spy_network"   class="nav-link <?= ($page??'')==='spy_network' ?'active':'' ?>">🕵 Шпионы</a>
            <?php endif; ?>

            <div class="nav-divider"></div>

            <a href="?page=ranks"         class="nav-link <?= ($page??'')==='ranks'       ?'active':'' ?>">🥇 Ранги</a>
            <a href="?page=achievements"  class="nav-link <?= ($page??'')==='achievements'?'active':'' ?>">🏅 Достижения</a>
            <a href="?page=season"        class="nav-link <?= ($page??'')==='season'       ?'active':'' ?>">🏆 Сезон</a>
            <a href="?page=support"       class="nav-link <?= ($page??'')==='support'      ?'active':'' ?>">🛡 Поддержка</a>
            <a href="?page=reports"       class="nav-link <?= ($page??'')==='reports'      ?'active':'' ?>">
                📋 Отчёты
                <?php if ($counters['reports']>0): ?><span class="nav-badge"><?= $counters['reports'] ?></span><?php endif; ?>
            </a>
            <a href="?page=messages"      class="nav-link <?= ($page??'')==='messages'     ?'active':'' ?>">
                ✉ Почта
                <?php if ($counters['messages']>0): ?><span class="nav-badge"><?= $counters['messages'] ?></span><?php endif; ?>
            </a>

            <?php if ($counters['attacks']>0): ?>
            <a href="?page=map" class="nav-link attack-warning">⚠ Атака!(<?= $counters['attacks'] ?>)</a>
            <?php endif; ?>

            <?php if ($is_admin): ?>
            <div class="nav-divider"></div>
            <a href="?page=admin" class="nav-link <?= ($page??'')==='admin'?'active':'' ?>" style="color:#d4a843;">⚙ Админ</a>
            <?php endif; ?>

        <?php else: ?>
            <a href="?page=home"    class="nav-link">🏠 Главная</a>
            <a href="?page=ranking" class="nav-link">🏆 Рейтинг</a>
        <?php endif; ?>
    </div>

    <div class="navbar-right">
        <?php if (isset($_SESSION['user_id'])): ?>

            <?php if ($hero_status): ?>
            <a href="?page=hero" class="hero-nav <?= $hero_status['status']==='alive'?'alive':($hero_status['status']==='regenerating'?'regen':'dead') ?>"
               title="Герой ур.<?= $hero_status['level'] ?>">
                🦸 Ур.<?= $hero_status['level'] ?>
                <?php if ($hero_status['status']==='alive'): ?> · <?= $hero_status['hp'] ?>/<?= $hero_status['hp_max'] ?>❤
                <?php elseif ($hero_status['status']==='regenerating'): ?> · 🔄
                <?php else: ?> · 💀<?php endif; ?>
            </a>
            <?php endif; ?>

            <div class="notif-wrap" id="notifWrap">
                <div class="notif-btn" onclick="toggleNotif()">
                    🔔
                    <?php if (count($notifs)>0): ?><span class="nav-badge"><?= count($notifs) ?></span><?php endif; ?>
                </div>
                <div class="notif-dropdown">
                    <div class="notif-header">
                        🔔 Уведомления
                        <?php if (count($notifs)>0): ?><span style="color:#888;font-size:11px;"><?= count($notifs) ?></span><?php endif; ?>
                    </div>
                    <?php if (empty($notifs)): ?>
                        <div class="notif-empty">Нет новых уведомлений</div>
                    <?php else: ?>
                        <?php foreach ($notifs as $n): ?>
                        <a href="<?= htmlspecialchars($n['link']) ?>" class="notif-item" style="border-left:3px solid <?= $n['color'] ?>;">
                            <span class="notif-icon"><?= $n['icon'] ?></span>
                            <span class="notif-text"><?= htmlspecialchars($n['text']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="nav-user" title="<?= htmlspecialchars($_SESSION['username']??'') ?>">
                👤 <?= htmlspecialchars($_SESSION['username']??'') ?>
            </div>

            <a href="?page=logout"
               style="padding:5px 10px;background:#3a1a1a;color:#f66;text-decoration:none;border-radius:4px;font-size:12px;border:1px solid #6a1a1a;transition:0.2s;"
               onmouseover="this.style.background='#5a2a2a'" onmouseout="this.style.background='#3a1a1a'">Выйти</a>

        <?php else: ?>
            <a href="?page=login"
               style="padding:6px 12px;background:#5a4a1a;color:#d4a843;text-decoration:none;border-radius:4px;font-size:12px;border:1px solid #8b6914;margin-right:4px;"
               onmouseover="this.style.background='#7a6a2a'" onmouseout="this.style.background='#5a4a1a'">🔑 Войти</a>
            <a href="?page=register"
               style="padding:6px 12px;background:#1a4a1a;color:#4f4;text-decoration:none;border-radius:4px;font-size:12px;border:1px solid #2a8a2a;"
               onmouseover="this.style.background='#2a6a2a'" onmouseout="this.style.background='#1a4a1a'">🏰 Регистрация</a>
        <?php endif; ?>
    </div>
</nav>

<script>
function toggleNavMenu(){document.getElementById('navMenu').classList.toggle('open');}
function toggleNotif(){document.getElementById('notifWrap').classList.toggle('open');}
document.addEventListener('click',function(e){
    const wrap=document.getElementById('notifWrap');
    const menu=document.getElementById('navMenu');
    const toggle=document.querySelector('.nav-toggle');
    if(wrap&&!wrap.contains(e.target))wrap.classList.remove('open');
    if(menu&&toggle&&!menu.contains(e.target)&&!toggle.contains(e.target))menu.classList.remove('open');
});
document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){document.getElementById('notifWrap')?.classList.remove('open');document.getElementById('navMenu')?.classList.remove('open');}
});
</script>