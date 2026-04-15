<?php
// controllers/TechController.php

class TechController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА ТЕХНОЛОГИЙ
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Проверяем завершённые исследования
        $this->processCompleted($user_id);

        // Все технологии с прогрессом
        $stmt = $this->db->prepare("
            SELECT t.*,
                   COALESCE(pt.level, 0)      as current_level,
                   COALESCE(pt.researching, 0) as researching,
                   COALESCE(pt.end_time, 0)    as end_time
            FROM technologies t
            LEFT JOIN player_technologies pt
                ON pt.tech_code = t.code AND pt.user_id = ?
            ORDER BY t.branch, t.sort_order ASC
        ");
        $stmt->execute([$user_id]);
        $all_techs = $stmt->fetchAll();

        // Группируем по веткам
        $branches = [
            'economy'   => ['name'=>'💰 Экономика',   'color'=>'#d4a843', 'techs'=>[]],
            'military'  => ['name'=>'⚔ Военное дело', 'color'=>'#f44',    'techs'=>[]],
            'diplomacy' => ['name'=>'🤝 Дипломатия',  'color'=>'#88f',    'techs'=>[]],
        ];
        foreach ($all_techs as $t) {
            $branches[$t['branch']]['techs'][] = $t;
        }

        // Деревни игрока
        $stmt = $this->db->prepare("SELECT id, name, r_wood, r_stone, r_iron FROM villages WHERE userid=? ORDER BY id");
        $stmt->execute([$user_id]);
        $villages = $stmt->fetchAll();

        // Активное исследование
        $active_research = null;
        foreach ($all_techs as $t) {
            if ($t['researching'] && $t['end_time'] > time()) {
                $active_research = $t;
                break;
            }
        }

        // Бонусы от технологий
        $bonuses = $this->getBonuses($user_id);

        $db = $this->db;
        require_once __DIR__ . '/../templates/technologies.php';
    }

    // =========================================================
    // НАЧАТЬ ИССЛЕДОВАНИЕ
    // =========================================================
    public function startResearch() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id    = $_SESSION['user_id'];
        $tech_code  = $_POST['tech_code']  ?? '';
        $village_id = (int)($_POST['village_id'] ?? 0);

        // Проверяем технологию
        $stmt = $this->db->prepare("SELECT * FROM technologies WHERE code=?");
        $stmt->execute([$tech_code]);
        $tech = $stmt->fetch();

        if (!$tech) {
            $_SESSION['error'] = "Технология не найдена!";
            header("Location: ?page=technologies");
            exit;
        }

        // Текущий уровень
        $stmt = $this->db->prepare("SELECT * FROM player_technologies WHERE user_id=? AND tech_code=?");
        $stmt->execute([$user_id, $tech_code]);
        $pt = $stmt->fetch();

        $current_level = (int)($pt['level'] ?? 0);

        // Проверяем максимум
        if ($current_level >= $tech['max_level']) {
            $_SESSION['error'] = "Технология уже на максимальном уровне!";
            header("Location: ?page=technologies");
            exit;
        }

        // Проверяем уже идёт исследование
        if ($pt && $pt['researching'] && $pt['end_time'] > time()) {
            $_SESSION['error'] = "Это исследование уже идёт!";
            header("Location: ?page=technologies");
            exit;
        }

        // Проверяем нет ли другого активного исследования
        $stmt = $this->db->prepare("SELECT tc.code, tc.name FROM player_technologies pt
            JOIN technologies tc ON pt.tech_code=tc.code
            WHERE pt.user_id=? AND pt.researching=1 AND pt.end_time>?");
        $stmt->execute([$user_id, time()]);
        $active = $stmt->fetch();

        if ($active) {
            $_SESSION['error'] = "Уже идёт исследование: «{$active['name']}»! Дождитесь завершения.";
            header("Location: ?page=technologies");
            exit;
        }

        // Проверяем требования
        if (!empty($tech['requires'])) {
            $stmt = $this->db->prepare("SELECT level FROM player_technologies WHERE user_id=? AND tech_code=?");
            $stmt->execute([$user_id, $tech['requires']]);
            $req = $stmt->fetch();
            $req_level = (int)($req['level'] ?? 0);

            if ($req_level < $tech['requires_lvl']) {
                $stmt2 = $this->db->prepare("SELECT name FROM technologies WHERE code=?");
                $stmt2->execute([$tech['requires']]);
                $req_tech = $stmt2->fetch();
                $_SESSION['error'] = "Требуется «{$req_tech['name']}» ур. {$tech['requires_lvl']}!";
                header("Location: ?page=technologies");
                exit;
            }
        }

        // Стоимость следующего уровня
        $next_level  = $current_level + 1;
        $cost        = $this->getCost($tech, $next_level);
        $time_needed = $this->getTime($tech, $next_level);

        // Проверяем деревню
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE id=? AND userid=?");
        $stmt->execute([$village_id, $user_id]);
        $village = $stmt->fetch();

        if (!$village) {
            $_SESSION['error'] = "Деревня не найдена!";
            header("Location: ?page=technologies");
            exit;
        }

        if ($village['r_wood']  < $cost['wood'] ||
            $village['r_stone'] < $cost['stone'] ||
            $village['r_iron']  < $cost['iron']) {
            $_SESSION['error'] = "Недостаточно ресурсов! Нужно: 🪵{$cost['wood']} 🪨{$cost['stone']} ⛏{$cost['iron']}";
            header("Location: ?page=technologies");
            exit;
        }

        // Снимаем ресурсы
        $this->db->prepare("UPDATE villages SET
            r_wood=r_wood-?, r_stone=r_stone-?, r_iron=r_iron-?
            WHERE id=?
        ")->execute([$cost['wood'], $cost['stone'], $cost['iron'], $village_id]);

        // Создаём или обновляем запись
        if ($pt) {
            $this->db->prepare("UPDATE player_technologies SET
                researching=1, end_time=?, updated_at=?
                WHERE user_id=? AND tech_code=?
            ")->execute([time()+$time_needed, time(), $user_id, $tech_code]);
        } else {
            $this->db->prepare("INSERT INTO player_technologies
                (user_id, tech_code, level, researching, end_time, updated_at)
                VALUES (?,?,0,1,?,?)
            ")->execute([$user_id, $tech_code, time()+$time_needed, time()]);
        }

        $mins = floor($time_needed/60);
        $secs = $time_needed%60;
        $_SESSION['success'] = "🔬 Исследование «{$tech['name']}» → уровень {$next_level} начато!<br>
            Время: <strong>{$mins}м {$secs}с</strong>";
        header("Location: ?page=technologies");
        exit;
    }

    // =========================================================
    // ОБРАБОТКА ЗАВЕРШЁННЫХ
    // =========================================================
    public function processCompleted($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT pt.*, t.name, t.icon
                FROM player_technologies pt
                JOIN technologies t ON pt.tech_code = t.code
                WHERE pt.user_id=? AND pt.researching=1 AND pt.end_time<=?
            ");
            $stmt->execute([$user_id, time()]);
            $done = $stmt->fetchAll();

            foreach ($done as $d) {
                $this->db->prepare("UPDATE player_technologies SET
                    level=level+1, researching=0, end_time=0, updated_at=?
                    WHERE user_id=? AND tech_code=?
                ")->execute([time(), $user_id, $d['tech_code']]);

                // Уведомление
                $new_level = $d['level'] + 1;
                $this->db->prepare("INSERT INTO reports
                    (userid, type, title, content, time, is_read)
                    VALUES (?, 'system', ?, ?, ?, 0)
                ")->execute([
                    $user_id,
                    "🔬 Исследование завершено: {$d['icon']} {$d['name']}",
                    "Технология «{$d['name']}» достигла уровня {$new_level}!\n\n" .
                    "Бонусы применены автоматически ко всем деревням.",
                    time()
                ]);
            }
        } catch (Exception $e) {
            error_log("processCompleted: " . $e->getMessage());
        }
    }

    // =========================================================
    // ПОЛУЧИТЬ БОНУСЫ ИГРОКА
    // =========================================================
    public function getBonuses($user_id) {
        $bonuses = [
            'production'    => 0,   // % к производству
            'storage'       => 0,   // % к складу
            'trade_speed'   => 0,   // % к скорости торговцев
            'build_cost'    => 0,   // % к стоимости строительства
            'attack'        => 0,   // % к атаке
            'defense'       => 0,   // % к защите
            'march_speed'   => 0,   // % к скорости походов
            'siege'         => 0,   // % к осадным
            'spy_chance'    => 0,   // % к шансу шпионажа
            'hero_exp'      => 0,   // % к опыту героя
            'taxation'      => 0,   // Дополнительные ресурсы/ч
        ];

        try {
            $stmt = $this->db->prepare("
                SELECT pt.tech_code, pt.level FROM player_technologies pt
                WHERE pt.user_id=? AND pt.level>0
            ");
            $stmt->execute([$user_id]);
            $player_techs = $stmt->fetchAll();

            $tech_map = [];
            foreach ($player_techs as $pt) {
                $tech_map[$pt['tech_code']] = (int)$pt['level'];
            }

            $lvl = fn($code) => $tech_map[$code] ?? 0;

            $bonuses['production']  = $lvl('prod_boost')   * 10;
            $bonuses['storage']     = $lvl('storage_plus') * 15;
            $bonuses['trade_speed'] = $lvl('trade_routes') * 20;
            $bonuses['build_cost']  = $lvl('engineering')  * 10;
            $bonuses['attack']      = $lvl('att_boost')    * 5;
            $bonuses['defense']     = $lvl('def_boost')    * 5;
            $bonuses['march_speed'] = $lvl('march_speed')  * 8;
            $bonuses['siege']       = $lvl('siege_power')  * 20;
            $bonuses['spy_chance']  = $lvl('spy_skill')    * 15;
            $bonuses['hero_exp']    = $lvl('hero_mastery') * 10;
            $bonuses['taxation']    = $lvl('taxation')     * 50;

        } catch (Exception $e) {}

        return $bonuses;
    }

    // =========================================================
    // СТАТИЧЕСКИЙ МЕТОД ДЛЯ ПОЛУЧЕНИЯ БОНУСОВ
    // =========================================================
    public static function getPlayerBonuses($db, $user_id) {
        try {
            $tc = new self($db);
            return $tc->getBonuses($user_id);
        } catch (Exception $e) {
            return [];
        }
    }

    // =========================================================
    // СТОИМОСТЬ УРОВНЯ
    // =========================================================
    public function getCost($tech, $level) {
        $multiplier = pow(1.5, $level - 1);
        return [
            'wood'  => (int)ceil($tech['wood_base']  * $multiplier),
            'stone' => (int)ceil($tech['stone_base'] * $multiplier),
            'iron'  => (int)ceil($tech['iron_base']  * $multiplier),
        ];
    }

    // =========================================================
    // ВРЕМЯ ИССЛЕДОВАНИЯ
    // =========================================================
    public function getTime($tech, $level) {
        $multiplier = pow(1.4, $level - 1);
        return (int)ceil($tech['time_base'] * $multiplier);
    }
}