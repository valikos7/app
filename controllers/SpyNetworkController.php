<?php
// controllers/SpyNetworkController.php

class SpyNetworkController {
    private $db;

    // Стоимость шпиона
    private const SPY_COST_WOOD  = 20000;
    private const SPY_COST_STONE = 15000;
    private const SPY_COST_IRON  = 15000;

    // Длительность
    private const SPY_DURATION   = 7 * 86400; // 7 дней

    // Шанс провала
    private const BURN_CHANCE    = 0.15; // 15% в день

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА ШПИОНСКОЙ СЕТИ
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Мой альянс
        $stmt = $this->db->prepare("SELECT alliance_id, role FROM alliance_members WHERE user_id=?");
        $stmt->execute([$user_id]);
        $membership = $stmt->fetch();
        if (!$membership) {
            $_SESSION['error'] = "Вы не состоите в альянсе!";
            header("Location: ?page=alliances");
            exit;
        }
        $my_alliance_id = $membership['alliance_id'];

        // Мои шпионы
        $stmt = $this->db->prepare("
            SELECT sn.*, a.name as target_name, a.tag as target_tag
            FROM spy_network sn
            JOIN alliances a ON sn.target_alliance_id=a.id
            WHERE sn.spy_user_id=? AND sn.status='active'
            ORDER BY sn.planted_at DESC
        ");
        $stmt->execute([$user_id]);
        $my_spies = $stmt->fetchAll();

        // Шпионы в моём альянсе (обнаруженные)
        $stmt = $this->db->prepare("
            SELECT sn.*, u.username as spy_name, a.name as spy_alliance
            FROM spy_network sn
            JOIN users u ON sn.spy_user_id=u.id
            LEFT JOIN alliance_members am ON am.user_id=u.id
            LEFT JOIN alliances a ON a.id=am.alliance_id
            WHERE sn.target_alliance_id=? AND sn.status='burned'
            ORDER BY sn.planted_at DESC LIMIT 10
        ");
        $stmt->execute([$my_alliance_id]);
        $burned_spies = $stmt->fetchAll();

        // Все альянсы для внедрения
        $stmt = $this->db->query("SELECT id,name,tag FROM alliances WHERE id!=0 ORDER BY name LIMIT 20");
        $all_alliances = $stmt->fetchAll();
        // Убираем свой
        $all_alliances = array_filter($all_alliances, fn($a)=>$a['id']!==$my_alliance_id);

        // Деревни для оплаты
        $stmt = $this->db->prepare("SELECT id,name,r_wood,r_stone,r_iron FROM villages WHERE userid=? LIMIT 5");
        $stmt->execute([$user_id]);
        $my_villages = $stmt->fetchAll();

        $costs = [
            'wood'  => self::SPY_COST_WOOD,
            'stone' => self::SPY_COST_STONE,
            'iron'  => self::SPY_COST_IRON,
        ];

        $db = $this->db;
        require_once __DIR__ . '/../templates/spy_network.php';
    }

    // =========================================================
    // ВНЕДРИТЬ ШПИОНА
    // =========================================================
    public function plantSpy() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id          = $_SESSION['user_id'];
        $target_alliance  = (int)($_POST['target_alliance_id'] ?? 0);
        $village_id       = (int)($_POST['village_id'] ?? 0);

        // Проверяем членство
        $stmt = $this->db->prepare("SELECT alliance_id FROM alliance_members WHERE user_id=?");
        $stmt->execute([$user_id]);
        $mem = $stmt->fetch();
        if (!$mem) {
            $_SESSION['error'] = "Вы не состоите в альянсе!";
            header("Location: ?page=spy_network");
            exit;
        }

        if ($mem['alliance_id'] === $target_alliance) {
            $_SESSION['error'] = "Нельзя шпионить за своим альянсом!";
            header("Location: ?page=spy_network");
            exit;
        }

        // Проверяем нет ли уже активного шпиона в этом альянсе
        $stmt = $this->db->prepare("SELECT id FROM spy_network WHERE spy_user_id=? AND target_alliance_id=? AND status='active'");
        $stmt->execute([$user_id, $target_alliance]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "У вас уже есть шпион в этом альянсе!";
            header("Location: ?page=spy_network");
            exit;
        }

        // Проверяем деревню
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE id=? AND userid=?");
        $stmt->execute([$village_id, $user_id]);
        $village = $stmt->fetch();
        if (!$village) {
            $_SESSION['error'] = "Деревня не найдена!";
            header("Location: ?page=spy_network");
            exit;
        }

        // Проверяем ресурсы
        if ($village['r_wood']  < self::SPY_COST_WOOD  ||
            $village['r_stone'] < self::SPY_COST_STONE ||
            $village['r_iron']  < self::SPY_COST_IRON) {
            $_SESSION['error'] = "Недостаточно ресурсов! Нужно: 🪵".self::SPY_COST_WOOD.
                " 🪨".self::SPY_COST_STONE." ⛏".self::SPY_COST_IRON;
            header("Location: ?page=spy_network");
            exit;
        }

        // Снимаем ресурсы
        $this->db->prepare("UPDATE villages SET
            r_wood=r_wood-?, r_stone=r_stone-?, r_iron=r_iron-? WHERE id=?")
            ->execute([self::SPY_COST_WOOD, self::SPY_COST_STONE, self::SPY_COST_IRON, $village_id]);

        // Внедряем шпиона
        $this->db->prepare("INSERT INTO spy_network
            (spy_user_id, target_alliance_id, planted_at, expires_at, status)
            VALUES (?,?,?,?,'active')")
            ->execute([$user_id, $target_alliance, time(), time() + self::SPY_DURATION]);

        // Получаем имя альянса
        $stmt = $this->db->prepare("SELECT name,tag FROM alliances WHERE id=?");
        $stmt->execute([$target_alliance]);
        $target = $stmt->fetch();

        $_SESSION['success'] = "🕵 Шпион успешно внедрён в альянс [{$target['tag']}] {$target['name']}!<br>Активен 7 дней.";
        header("Location: ?page=spy_network");
        exit;
    }

    // =========================================================
    // ПОЛУЧИТЬ ДАННЫЕ ШПИОНАЖА
    // =========================================================
    public function getSpyReport($spy_id) {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        $stmt = $this->db->prepare("SELECT * FROM spy_network WHERE id=? AND spy_user_id=? AND status='active'");
        $stmt->execute([$spy_id, $user_id]);
        $spy = $stmt->fetch();

        if (!$spy) {
            $_SESSION['error'] = "Шпион не найден или уже раскрыт!";
            header("Location: ?page=spy_network");
            exit;
        }

        $target_id = $spy['target_alliance_id'];

        // Данные альянса
        $stmt = $this->db->prepare("SELECT * FROM alliances WHERE id=?");
        $stmt->execute([$target_id]);
        $target_alliance = $stmt->fetch();

        // Члены альянса
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.points, u.villages, u.last_activity,
                   am.role
            FROM alliance_members am
            JOIN users u ON am.user_id=u.id
            WHERE am.alliance_id=?
            ORDER BY u.points DESC
        ");
        $stmt->execute([$target_id]);
        $members = $stmt->fetchAll();

        // Движение войск (если есть технология ally_sight)
        $movements = [];
        try {
            $stmt2 = $this->db->prepare("SELECT level FROM player_technologies WHERE user_id=? AND tech_code='spy_net' AND level>0");
            $stmt2->execute([$user_id]);
            if ($stmt2->fetch()) {
                $member_ids = array_column($members, 'id');
                if (!empty($member_ids)) {
                    $placeholders = implode(',', array_fill(0, count($member_ids), '?'));
                    $stmt3 = $this->db->prepare("
                        SELECT tm.*, v.name as from_name, v2.name as to_name
                        FROM troop_movements tm
                        LEFT JOIN villages v  ON tm.from_village_id=v.id
                        LEFT JOIN villages v2 ON tm.to_village_id=v2.id
                        WHERE tm.attacker_id IN ({$placeholders}) AND tm.status='moving'
                        ORDER BY tm.arrival_time ASC LIMIT 20
                    ");
                    $stmt3->execute($member_ids);
                    $movements = $stmt3->fetchAll();
                }
            }
        } catch (Exception $e) {}

        // Проверяем не раскрыт ли шпион (случайно)
        $days_active = (time() - $spy['planted_at']) / 86400;
        $burn_chance = $days_active * self::BURN_CHANCE;
        if (mt_rand(1, 100) / 100 < $burn_chance) {
            $this->burnSpy($spy['id'], $user_id, $target_id);
        }

        $db = $this->db;
        require_once __DIR__ . '/../templates/spy_report.php';
        exit;
    }

    // =========================================================
    // ПРОВАЛ ШПИОНА
    // =========================================================
    private function burnSpy($spy_id, $spy_user_id, $target_alliance_id) {
        $this->db->prepare("UPDATE spy_network SET status='burned' WHERE id=?")
            ->execute([$spy_id]);

        // Уведомляем шпиона
        $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
            ->execute([
                $spy_user_id,
                "🔍 Ваш шпион раскрыт!",
                "Ваш шпион был обнаружен противником!",
                time()
            ]);

        // Уведомляем лидера целевого альянса
        $stmt = $this->db->prepare("SELECT user_id FROM alliance_members WHERE alliance_id=? AND role='leader' LIMIT 1");
        $stmt->execute([$target_alliance_id]);
        $leader = $stmt->fetch();

        if ($leader) {
            $stmt2 = $this->db->prepare("SELECT username FROM users WHERE id=?");
            $stmt2->execute([$spy_user_id]);
            $spy_user = $stmt2->fetch();

            $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
                ->execute([
                    $leader['user_id'],
                    "🔍 Обнаружен шпион!",
                    "В вашем альянсе обнаружен шпион игрока «{$spy_user['username']}»!\nОн был нейтрализован.",
                    time()
                ]);
        }
    }

    // =========================================================
    // ЕЖЕДНЕВНАЯ ПРОВЕРКА ШПИОНОВ (из крона)
    // =========================================================
    public function dailyCheck() {
        try {
            // Удаляем истёкших шпионов
            $stmt = $this->db->query("SELECT * FROM spy_network WHERE status='active' AND expires_at<=".time());
            $expired = $stmt->fetchAll();

            foreach ($expired as $spy) {
                $this->db->prepare("UPDATE spy_network SET status='expired' WHERE id=?")->execute([$spy['id']]);
                $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
                    ->execute([
                        $spy['spy_user_id'],
                        "🕵 Срок шпиона истёк",
                        "Срок работы вашего шпиона в альянсе истёк.",
                        time()
                    ]);
            }

            // Случайная проверка на провал активных шпионов
            $stmt = $this->db->query("SELECT * FROM spy_network WHERE status='active'");
            $active = $stmt->fetchAll();

            foreach ($active as $spy) {
                $days = (time() - $spy['planted_at']) / 86400;
                $chance = $days * self::BURN_CHANCE / 7; // Уменьшаем шанс для крона
                if (mt_rand(1, 1000) / 1000 < $chance) {
                    $this->burnSpy($spy['id'], $spy['spy_user_id'], $spy['target_alliance_id']);
                }
            }
        } catch (Exception $e) {
            error_log("dailyCheck spies: " . $e->getMessage());
        }
    }
}