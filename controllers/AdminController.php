<?php
// controllers/AdminController.php

class AdminController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    private function checkAdmin() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }
        $stmt = $this->db->prepare("SELECT is_admin FROM users WHERE id=?");
        $stmt->execute([$_SESSION['user_id']]);
        $u = $stmt->fetch();
        if (!$u || empty($u['is_admin'])) {
            header("Location: ?page=home");
            exit;
        }
    }

    // =========================================================
    // ДАШБОРД
    // =========================================================
    public function index() {
        $this->checkAdmin();

        $stats = [];
        $stats['total_users']      = $this->db->query("SELECT COUNT(*) as c FROM users")->fetch()['c'] ?? 0;
        $stats['total_villages']   = $this->db->query("SELECT COUNT(*) as c FROM villages")->fetch()['c'] ?? 0;
        $stats['barbarian_villages']=$this->db->query("SELECT COUNT(*) as c FROM villages WHERE userid=-1")->fetch()['c'] ?? 0;
        $stats['total_alliances']  = $this->db->query("SELECT COUNT(*) as c FROM alliances")->fetch()['c'] ?? 0;
        $stats['total_reports']    = $this->db->query("SELECT COUNT(*) as c FROM reports")->fetch()['c'] ?? 0;
        $stats['total_messages']   = $this->db->query("SELECT COUNT(*) as c FROM messages")->fetch()['c'] ?? 0;
        $stats['active_movements'] = $this->db->query("SELECT COUNT(*) as c FROM troop_movements WHERE status='moving'")->fetch()['c'] ?? 0;
        $stats['alive_heroes']     = $this->db->query("SELECT COUNT(*) as c FROM heroes WHERE status='alive'")->fetch()['c'] ?? 0;

        $stmt = $this->db->prepare("SELECT COUNT(*) as c FROM users WHERE last_activity>=?");
        $stmt->execute([time()-300]);
        $stats['online'] = $stmt->fetch()['c'] ?? 0;

        $today = strtotime('today');
        $stmt  = $this->db->prepare("SELECT COUNT(*) as c FROM activity_log WHERE time>=?");
        $stmt->execute([$today]);
        $stats['today_actions'] = $stmt->fetch()['c'] ?? 0;

        // Активное событие
        try {
            $ec = new EventController($this->db);
            $stats['active_event'] = $ec->getActiveEvent();
        } catch (Exception $e) { $stats['active_event'] = null; }

        $stmt = $this->db->query("SELECT id,username,points,villages,join_date,last_activity FROM users ORDER BY join_date DESC LIMIT 10");
        $recent_users = $stmt->fetchAll();

        $stmt = $this->db->query("SELECT id,username,points,villages FROM users ORDER BY points DESC LIMIT 10");
        $top_users = $stmt->fetchAll();

        $db = $this->db;
        require_once __DIR__ . '/../templates/admin/index.php';
    }

    // =========================================================
    // ИГРОКИ
    // =========================================================
    public function players() {
        $this->checkAdmin();

        $search   = trim($_GET['search'] ?? '');
        $page_num = max(1, (int)($_GET['p'] ?? 1));
        $per_page = 20;
        $offset   = ($page_num-1)*$per_page;

        if ($search) {
            $stmt = $this->db->prepare("SELECT u.*,a.name as alliance_name FROM users u
                LEFT JOIN alliances a ON u.alliance_id=a.id
                WHERE u.username LIKE ? OR u.email LIKE ?
                ORDER BY u.points DESC LIMIT ? OFFSET ?");
            $stmt->execute(['%'.$search.'%','%'.$search.'%',$per_page,$offset]);
        } else {
            $stmt = $this->db->prepare("SELECT u.*,a.name as alliance_name FROM users u
                LEFT JOIN alliances a ON u.alliance_id=a.id
                ORDER BY u.points DESC LIMIT ? OFFSET ?");
            $stmt->execute([$per_page,$offset]);
        }
        $players = $stmt->fetchAll();

        if ($search) {
            $stmt=$this->db->prepare("SELECT COUNT(*) as c FROM users WHERE username LIKE ? OR email LIKE ?");
            $stmt->execute(['%'.$search.'%','%'.$search.'%']);
        } else {
            $stmt=$this->db->query("SELECT COUNT(*) as c FROM users");
        }
        $total       = $stmt->fetch()['c'] ?? 0;
        $total_pages = max(1, ceil($total/$per_page));

        $db = $this->db;
        require_once __DIR__ . '/../templates/admin/players.php';
    }

    // =========================================================
    // ДЕЙСТВИЯ С ИГРОКОМ
    // =========================================================
    public function playerAction() {
        $this->checkAdmin();

        $action    = $_POST['action']    ?? '';
        $player_id = (int)($_POST['player_id'] ?? 0);

        if ($player_id <= 0 || $player_id === (int)$_SESSION['user_id']) {
            $_SESSION['error'] = "Неверный ID или нельзя применять к себе!";
            header("Location: ?page=admin&section=players");
            exit;
        }

        switch ($action) {
            case 'ban':
                $this->db->prepare("UPDATE users SET is_banned=1 WHERE id=?")->execute([$player_id]);
                $_SESSION['success'] = "Игрок забанен.";
                break;
            case 'unban':
                $this->db->prepare("UPDATE users SET is_banned=0 WHERE id=?")->execute([$player_id]);
                $_SESSION['success'] = "Игрок разбанен.";
                break;
            case 'make_admin':
                $this->db->prepare("UPDATE users SET is_admin=1 WHERE id=?")->execute([$player_id]);
                $_SESSION['success'] = "Назначен администратором.";
                break;
            case 'remove_admin':
                $this->db->prepare("UPDATE users SET is_admin=0 WHERE id=?")->execute([$player_id]);
                $_SESSION['success'] = "Права администратора сняты.";
                break;
            case 'add_resources':
                $wood =(int)($_POST['wood'] ??0);
                $stone=(int)($_POST['stone']??0);
                $iron =(int)($_POST['iron'] ??0);
                $this->db->prepare("UPDATE villages SET r_wood=r_wood+?,r_stone=r_stone+?,r_iron=r_iron+? WHERE userid=?")
                    ->execute([$wood,$stone,$iron,$player_id]);
                $_SESSION['success'] = "Ресурсы добавлены: 🪵{$wood} 🪨{$stone} ⛏{$iron}";
                break;
            case 'reset_password':
                $new_pass = $_POST['new_password'] ?? '';
                if (strlen($new_pass) < 6) { $_SESSION['error']="Пароль мин. 6 символов!"; break; }
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $this->db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed,$player_id]);
                $_SESSION['success'] = "Пароль изменён.";
                break;
            case 'delete':
                $this->db->prepare("DELETE FROM villages WHERE userid=?")->execute([$player_id]);
                $this->db->prepare("DELETE FROM alliance_members WHERE user_id=?")->execute([$player_id]);
                $this->db->prepare("DELETE FROM messages WHERE from_id=? OR to_id=?")->execute([$player_id,$player_id]);
                $this->db->prepare("DELETE FROM reports WHERE userid=?")->execute([$player_id]);
                $this->db->prepare("DELETE FROM heroes WHERE user_id=?")->execute([$player_id]);
                $this->db->prepare("DELETE FROM users WHERE id=?")->execute([$player_id]);
                $_SESSION['success'] = "Игрок удалён.";
                break;
            default:
                $_SESSION['error'] = "Неизвестное действие.";
        }

        header("Location: ?page=admin&section=players");
        exit;
    }

    // =========================================================
    // ОБЪЯВЛЕНИЯ
    // =========================================================
    public function announcements() {
        $this->checkAdmin();

        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $title   = trim($_POST['title']   ?? '');
            $content = trim($_POST['content'] ?? '');
            $author  = trim($_POST['author']  ?? 'Администрация');
            if ($title && $content) {
                $this->db->prepare("INSERT INTO announcement (title,content,time,author) VALUES (?,?,?,?)")
                    ->execute([$title,$content,time(),$author]);
                $_SESSION['success'] = "Объявление опубликовано!";
            } else { $_SESSION['error'] = "Заполните все поля!"; }
            header("Location: ?page=admin&section=announcements");
            exit;
        }

        if (isset($_GET['delete_ann'])) {
            $ann_id=(int)$_GET['delete_ann'];
            $this->db->prepare("DELETE FROM announcement WHERE id=?")->execute([$ann_id]);
            $_SESSION['success'] = "Объявление удалено.";
            header("Location: ?page=admin&section=announcements");
            exit;
        }

        $stmt = $this->db->query("SELECT * FROM announcement ORDER BY time DESC");
        $announcements = $stmt->fetchAll();
        $db = $this->db;
        require_once __DIR__ . '/../templates/admin/announcements.php';
    }

    // =========================================================
    // НАСТРОЙКИ
    // =========================================================
    public function settings() {
        $this->checkAdmin();

        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $settings = $_POST['settings'] ?? [];
            foreach ($settings as $key=>$value) {
                $this->db->prepare("UPDATE game_config SET value=? WHERE `key`=?")
                    ->execute([trim($value),$key]);
            }
            $_SESSION['success'] = "Настройки сохранены!";
            header("Location: ?page=admin&section=settings");
            exit;
        }

        $stmt = $this->db->query("SELECT * FROM game_config ORDER BY `key`");
        $game_settings = $stmt->fetchAll();
        $db = $this->db;
        require_once __DIR__ . '/../templates/admin/settings.php';
    }

    // =========================================================
    // ЮНИТЫ
    // =========================================================
    public function units() {
        $this->checkAdmin();
        $stmt  = $this->db->query("SELECT * FROM unit_config ORDER BY id");
        $units = $stmt->fetchAll();
        $db    = $this->db;
        require_once __DIR__ . '/../templates/admin/units.php';
    }

    public function saveUnits() {
        $this->checkAdmin();

        $units_data     = $_POST['units'] ?? [];
        $allowed_fields = ['name','attack','def_inf','def_cav','speed','carry','pop','unit_type','wood','stone','iron','train_time'];
        $allowed_types  = ['spear','sword','axe','scout','light','heavy','ram','catapult'];
        $updated = 0;

        foreach ($units_data as $type=>$fields) {
            if (!in_array($type,$allowed_types)) continue;
            $sets=[]; $params=[];
            foreach ($allowed_fields as $field) {
                if (!isset($fields[$field])) continue;
                $value = $fields[$field];
                if ($field!=='name'&&$field!=='unit_type') {
                    $value=max(0,(int)$value);
                    if ($field==='speed')      $value=max(1,$value);
                    if ($field==='pop')        $value=max(1,$value);
                    if ($field==='train_time') $value=max(10,$value);
                } else {
                    $value=trim($value);
                    if ($field==='unit_type') $value=in_array($value,['infantry','cavalry'])?$value:'infantry';
                    if (empty($value)) continue;
                }
                $sets[]="`{$field}`=?"; $params[]=$value;
            }
            if (!empty($sets)) {
                $params[]=$type;
                $this->db->prepare("UPDATE unit_config SET ".implode(',',$sets)." WHERE `type`=?")->execute($params);
                $updated++;
            }
        }

        BattleEngine::clearCache();
        $_SESSION['success'] = "Настройки {$updated} юнитов сохранены!";
        header("Location: ?page=admin&section=units");
        exit;
    }

    public function resetUnits() {
        $this->checkAdmin();

        $defaults = [
            ['spear',   'Копейщик',          10, 15, 45,18,25,1,'infantry', 50, 30, 10, 60],
            ['sword',   'Мечник',            25, 50, 15,22,15,1,'infantry', 30, 50, 20, 90],
            ['axe',     'Топорщик',          40, 10,  5,18,10,1,'infantry', 60, 30, 40, 75],
            ['scout',   'Разведчик',          0,  2,  1, 9, 0,2,'cavalry',  80, 40, 30, 50],
            ['light',   'Лёгкая кавалерия', 130, 30, 40,10,80,4,'cavalry', 100,130,160,120],
            ['heavy',   'Тяжёлая кавалерия',150,200, 80,11,50,6,'cavalry', 150,200,250,180],
            ['ram',     'Таран',              2, 20, 50,30, 0,5,'infantry',300,200,200,240],
            ['catapult','Катапульта',        100,100, 50,30, 0,8,'infantry',320,400,100,300],
        ];

        foreach ($defaults as $d) {
            $this->db->prepare("UPDATE unit_config SET
                name=?,attack=?,def_inf=?,def_cav=?,
                speed=?,carry=?,pop=?,unit_type=?,
                wood=?,stone=?,iron=?,train_time=?
                WHERE `type`=?")
                ->execute([$d[1],$d[2],$d[3],$d[4],$d[5],$d[6],$d[7],$d[8],$d[9],$d[10],$d[11],$d[12],$d[0]]);
        }

        BattleEngine::clearCache();
        $_SESSION['success'] = "Юниты сброшены к значениям по умолчанию!";
        header("Location: ?page=admin&section=units");
        exit;
    }

    // =========================================================
    // ГЕНЕРАЦИЯ ВАРВАРОВ
    // =========================================================
    public function generateBarbarians() {
        $this->checkAdmin();

        $count   = max(1, min(500, (int)($_POST['count'] ?? 50)));
        $created = 0;
        $names   = [
            'Заброшенный хутор','Дикий лагерь','Разбойничий стан',
            'Варварский посёлок','Лесной лагерь','Горный форт',
            'Тёмное убежище','Волчье логово','Каменный лагерь',
            'Речной пост','Туманная застава','Орлиное гнездо','Древние руины'
        ];

        for ($i=0;$i<$count;$i++) {
            $x=rand(-200,200); $y=rand(-200,200);
            $chk=$this->db->prepare("SELECT id FROM villages WHERE x=? AND y=?");
            $chk->execute([$x,$y]);
            if ($chk->fetch()) continue;

            $name=$names[array_rand($names)];
            $continent=floor(($y+500)/100)*10+floor(($x+500)/100);

            $this->db->prepare("INSERT INTO villages
                (userid,name,x,y,continent,main,wood_level,stone_level,iron_level,
                 farm,storage,wall,r_wood,r_stone,r_iron,last_prod_aktu,points)
                VALUES (-1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$name,$x,$y,$continent,
                    rand(1,5),rand(1,5),rand(1,5),rand(1,5),rand(1,3),rand(1,3),rand(0,3),
                    rand(500,5000),rand(500,5000),rand(500,5000),time(),rand(20,300)]);
            $created++;
        }

        $_SESSION['success'] = "Создано варварских деревень: {$created}";
        header("Location: ?page=admin");
        exit;
    }

    // =========================================================
    // РАССЫЛКА
    // =========================================================
    public function broadcastMessage() {
        $this->checkAdmin();

        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (empty($subject)||empty($content)) {
            $_SESSION['error'] = "Заполните все поля!";
            header("Location: ?page=admin");
            exit;
        }

        $stmt  = $this->db->query("SELECT id FROM users WHERE is_admin=0");
        $users = $stmt->fetchAll();
        $count = 0;

        foreach ($users as $u) {
            $this->db->prepare("INSERT INTO messages (from_id,to_id,subject,content,time,is_read) VALUES (?,?,?,?,?,0)")
                ->execute([$_SESSION['user_id'],$u['id'],$subject,$content,time()]);
            $count++;
        }

        $_SESSION['success'] = "Сообщение отправлено {$count} игрокам!";
        header("Location: ?page=admin");
        exit;
    }

    // =========================================================
    // СОБЫТИЯ — УПРАВЛЕНИЕ
    // =========================================================
    public function events() {
        $this->checkAdmin();

        $stmt = $this->db->query("SELECT * FROM world_events ORDER BY started_at DESC LIMIT 20");
        $events = $stmt->fetchAll();

        $active = null;
        foreach ($events as $e) {
            if ($e['status']==='active' && $e['ends_at']>time()) { $active=$e; break; }
        }

        $db = $this->db;
        require_once __DIR__ . '/../templates/admin/events.php';
    }

    public function startEventAdmin() {
        $this->checkAdmin();

        $type = $_POST['type'] ?? null;
        if (empty($type)) $type = null;

        try {
            $ec = new EventController($this->db);

            // Завершаем текущее если есть
            $current = $ec->getActiveEvent();
            if ($current) $ec->endEvent($current['id']);

            $result = $ec->startEvent($type);
            if ($result) {
                $_SESSION['success'] = "✅ Событие успешно запущено!";
            } else {
                $_SESSION['error'] = "Ошибка запуска события.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Ошибка: " . $e->getMessage();
        }

        header("Location: ?page=admin&section=events");
        exit;
    }

    public function stopEventAdmin() {
        $this->checkAdmin();

        $event_id = (int)($_GET['event_id'] ?? 0);
        if ($event_id > 0) {
            try {
                $ec = new EventController($this->db);
                $ec->endEvent($event_id);
                $_SESSION['success'] = "Событие завершено досрочно.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Ошибка: " . $e->getMessage();
            }
        }

        header("Location: ?page=admin&section=events");
        exit;
    }
}