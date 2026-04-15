<?php
// controllers/AuthController.php

class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function showLoginForm() {
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);
        require_once __DIR__ . '/../templates/login.php';
    }

    public function showRegisterForm() {
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['error']);
        require_once __DIR__ . '/../templates/register.php';
    }

    public function login() {
        // Rate limit
        $limit = Security::rateLimit('login', 10, 300);
        if (!$limit['allowed']) {
            $_SESSION['error'] = $limit['message'];
            header("Location: ?page=login");
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password']      ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = "Заполните все поля!";
            header("Location: ?page=login");
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (!empty($user['is_banned'])) {
                $_SESSION['error'] = "Ваш аккаунт заблокирован администратором.";
                header("Location: ?page=login");
                exit;
            }

            Security::resetRateLimit('login');
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];

            $this->db->prepare("UPDATE users SET last_activity=? WHERE id=?")
                ->execute([time(), $user['id']]);

            header("Location: ?page=home");
            exit;
        } else {
            $_SESSION['error'] = "Неверное имя пользователя или пароль!";
            header("Location: ?page=login");
            exit;
        }
    }

    public function register() {
        // Rate limit
        $limit = Security::rateLimit('register', 3, 3600);
        if (!$limit['allowed']) {
            $_SESSION['error'] = $limit['message'];
            header("Location: ?page=register");
            exit;
        }

        $username  = trim($_POST['username']  ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']       ?? '';
        $password2 = $_POST['password2']      ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = "Все поля обязательны!";
            header("Location: ?page=register");
            exit;
        }

        if ($password !== $password2) {
            $_SESSION['error'] = "Пароли не совпадают!";
            header("Location: ?page=register");
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['error'] = "Пароль минимум 6 символов!";
            header("Location: ?page=register");
            exit;
        }

        $val = Security::validateUsername($username);
        if ($val !== true) {
            $_SESSION['error'] = $val;
            header("Location: ?page=register");
            exit;
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Пользователь с таким именем или email уже существует!";
            header("Location: ?page=register");
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $this->db->prepare("INSERT INTO users
            (username,email,password,join_date,last_activity,points,villages)
            VALUES (?,?,?,?,?,0,0)");
        $stmt->execute([$username,$email,$hashed,time(),time()]);
        $user_id = $this->db->lastInsertId();

        if ($user_id) {
            // Первый пользователь = администратор
            $cnt = $this->db->query("SELECT COUNT(*) as c FROM users")->fetch()['c'] ?? 0;
            if ($cnt <= 1) {
                $this->db->prepare("UPDATE users SET is_admin=1 WHERE id=?")->execute([$user_id]);
            }

            // Создаём деревню
            $village_id = $this->createFirstVillage($user_id, $username);

            // Создаём героя
            try {
                $hc = new HeroController($this->db);
                $hc->getOrCreateHero($user_id);
            } catch (Exception $e) {}

            // Приветственное сообщение
            $this->sendWelcomeMessage($user_id, $username);

            $_SESSION['success'] = "Регистрация успешна! Войдите в игру.";
            header("Location: ?page=login");
            exit;
        } else {
            $_SESSION['error'] = "Ошибка при регистрации.";
            header("Location: ?page=register");
            exit;
        }
    }

    private function sendWelcomeMessage($user_id, $username) {
        try {
            $stmt_admin = $this->db->query("SELECT id FROM users WHERE is_admin=1 LIMIT 1");
            $admin      = $stmt_admin->fetch();
            $from_id    = $admin ? $admin['id'] : 1;
        } catch (Exception $e) {
            $from_id = 1;
        }

        $content  = "Привет, {$username}! 👋\n\n";
        $content .= "Добро пожаловать в «" . APP_NAME . "»!\n\n";
        $content .= "🏘 Твоя первая деревня создана.\n";
        $content .= "🦸 Твой герой ждёт прокачки в разделе «Герой».\n\n";
        $content .= "💡 СОВЕТЫ:\n";
        $content .= "1. Улучши лесопилку, каменоломню и шахту\n";
        $content .= "2. Построй казармы (нужно ГЗ ур.3)\n";
        $content .= "3. Атакуй варваров на карте\n";
        $content .= "4. Прокачивай героя — он даёт бонусы!\n";
        $content .= "5. Вступи в альянс\n\n";
        $content .= "Удачи! ⚔\n\nАдминистрация " . APP_NAME;

        try {
            $this->db->prepare("INSERT INTO messages
                (from_id,to_id,subject,content,time,is_read)
                VALUES (?,?,?,?,?,0)")
                ->execute([
                    $from_id, $user_id,
                    '🎉 Добро пожаловать в ' . APP_NAME . '!',
                    $content, time()
                ]);
        } catch (Exception $e) {}
    }

    private function createFirstVillage($user_id, $username) {
        $attempts = 0;
        do {
            $x = rand(-200, 200);
            $y = rand(-200, 200);
            $stmt = $this->db->prepare("SELECT id FROM villages WHERE x=? AND y=?");
            $stmt->execute([$x, $y]);
            $attempts++;
        } while ($stmt->fetch() && $attempts < 50);

        $villagename = "Деревня " . $username;
        $continent   = floor(($y+500)/100)*10 + floor(($x+500)/100);

        $stmt = $this->db->prepare("INSERT INTO villages
            (userid,name,x,y,continent,
             main,wood_level,stone_level,iron_level,farm,storage,
             r_wood,r_stone,r_iron,last_prod_aktu,points)
            VALUES (?,?,?,?,?,1,1,1,1,1,1,1200,1200,1200,?,0)");
        $stmt->execute([$user_id,$villagename,$x,$y,$continent,time()]);
        $village_id = $this->db->lastInsertId();

        // Войска
        $this->db->prepare("INSERT INTO unit_place
            (villages_to_id,spear,sword,axe,scout,light,heavy,ram,catapult)
            VALUES (?,0,0,0,0,0,0,0,0)")
            ->execute([$village_id]);

        // Исследования
        try {
            $this->db->prepare("INSERT INTO smith_research (village_id) VALUES (?)")
                ->execute([$village_id]);
        } catch (Exception $e) {}

        // Очки
        $this->recalculateVillagePoints($village_id);

        // Счётчик деревень
        $this->db->prepare("UPDATE users SET villages=1 WHERE id=?")->execute([$user_id]);

        return $village_id;
    }

    private function recalculateVillagePoints($village_id) {
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE id=?");
        $stmt->execute([$village_id]);
        $village = $stmt->fetch();
        if (!$village) return;

        $pts = 0;
        $ppl = ['main'=>10,'wood_level'=>4,'stone_level'=>4,'iron_level'=>4,
                'farm'=>5,'storage'=>5,'barracks'=>6,'stable'=>8,
                'smith'=>6,'garage'=>7,'wall'=>6,'hide'=>3];

        foreach ($ppl as $field => $val) {
            $pts += $val * (int)($village[$field] ?? 0);
        }

        $this->db->prepare("UPDATE villages SET points=? WHERE id=?")->execute([$pts,$village_id]);
        $this->db->prepare("UPDATE users SET points=(
            SELECT COALESCE(SUM(points),0) FROM villages WHERE userid=?
        ) WHERE id=?")->execute([$village['userid'],$village['userid']]);
    }
}