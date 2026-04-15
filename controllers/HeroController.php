<?php
// controllers/HeroController.php

class HeroController {
    private $db;

    private static $items_config = [
        'sword_iron'    => ['name'=>'Железный меч',      'slot'=>'weapon','bonus_type'=>'attack',  'bonus_value'=>5,  'rarity'=>'common',   'icon'=>'⚔️', 'wood'=>500,  'stone'=>200, 'iron'=>800],
        'sword_steel'   => ['name'=>'Стальной меч',      'slot'=>'weapon','bonus_type'=>'attack',  'bonus_value'=>12, 'rarity'=>'rare',     'icon'=>'🗡️', 'wood'=>1000, 'stone'=>500, 'iron'=>2000],
        'sword_legend'  => ['name'=>'Легендарный клинок','slot'=>'weapon','bonus_type'=>'attack',  'bonus_value'=>25, 'rarity'=>'legendary','icon'=>'✨', 'wood'=>3000, 'stone'=>2000,'iron'=>5000],
        'shield_wood'   => ['name'=>'Деревянный щит',    'slot'=>'shield','bonus_type'=>'defense', 'bonus_value'=>5,  'rarity'=>'common',   'icon'=>'🛡️', 'wood'=>300,  'stone'=>100, 'iron'=>200],
        'shield_iron'   => ['name'=>'Железный щит',      'slot'=>'shield','bonus_type'=>'defense', 'bonus_value'=>12, 'rarity'=>'rare',     'icon'=>'🔰', 'wood'=>500,  'stone'=>300, 'iron'=>1500],
        'shield_legend' => ['name'=>'Щит легенды',       'slot'=>'shield','bonus_type'=>'defense', 'bonus_value'=>25, 'rarity'=>'legendary','icon'=>'💠', 'wood'=>2000, 'stone'=>1500,'iron'=>4000],
        'armor_leather' => ['name'=>'Кожаная броня',     'slot'=>'armor', 'bonus_type'=>'hp',      'bonus_value'=>20, 'rarity'=>'common',   'icon'=>'🧥', 'wood'=>400,  'stone'=>200, 'iron'=>300],
        'armor_chain'   => ['name'=>'Кольчуга',          'slot'=>'armor', 'bonus_type'=>'hp',      'bonus_value'=>50, 'rarity'=>'rare',     'icon'=>'⚙️', 'wood'=>800,  'stone'=>600, 'iron'=>1200],
        'armor_legend'  => ['name'=>'Мифриловый доспех', 'slot'=>'armor', 'bonus_type'=>'hp',      'bonus_value'=>100,'rarity'=>'legendary','icon'=>'🏆', 'wood'=>2500, 'stone'=>2000,'iron'=>3500],
        'potion_hp'     => ['name'=>'Зелье здоровья',   'slot'=>'potion','bonus_type'=>'heal',    'bonus_value'=>50, 'rarity'=>'common',   'icon'=>'🧪', 'wood'=>100,  'stone'=>50,  'iron'=>150],
        'potion_speed'  => ['name'=>'Зелье скорости',   'slot'=>'potion','bonus_type'=>'speed',   'bonus_value'=>20, 'rarity'=>'rare',     'icon'=>'⚡', 'wood'=>200,  'stone'=>100, 'iron'=>300],
        'potion_res'    => ['name'=>'Зелье богатства',  'slot'=>'potion','bonus_type'=>'resource','bonus_value'=>30, 'rarity'=>'rare',     'icon'=>'💰', 'wood'=>300,  'stone'=>200, 'iron'=>400],
    ];

    private static $level_config = [
        1  => ['exp'=>100,  'hp'=>100,  'skill_points'=>2],
        2  => ['exp'=>200,  'hp'=>110,  'skill_points'=>2],
        3  => ['exp'=>400,  'hp'=>125,  'skill_points'=>2],
        4  => ['exp'=>700,  'hp'=>145,  'skill_points'=>3],
        5  => ['exp'=>1100, 'hp'=>170,  'skill_points'=>3],
        6  => ['exp'=>1600, 'hp'=>200,  'skill_points'=>3],
        7  => ['exp'=>2200, 'hp'=>235,  'skill_points'=>4],
        8  => ['exp'=>3000, 'hp'=>275,  'skill_points'=>4],
        9  => ['exp'=>4000, 'hp'=>320,  'skill_points'=>4],
        10 => ['exp'=>5000, 'hp'=>370,  'skill_points'=>5],
        11 => ['exp'=>6500, 'hp'=>425,  'skill_points'=>5],
        12 => ['exp'=>8000, 'hp'=>485,  'skill_points'=>5],
        13 => ['exp'=>10000,'hp'=>550,  'skill_points'=>6],
        14 => ['exp'=>12000,'hp'=>620,  'skill_points'=>6],
        15 => ['exp'=>15000,'hp'=>700,  'skill_points'=>6],
        16 => ['exp'=>18000,'hp'=>785,  'skill_points'=>7],
        17 => ['exp'=>22000,'hp'=>875,  'skill_points'=>7],
        18 => ['exp'=>27000,'hp'=>970,  'skill_points'=>7],
        19 => ['exp'=>33000,'hp'=>1070, 'skill_points'=>8],
        20 => ['exp'=>40000,'hp'=>1200, 'skill_points'=>8],
    ];

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА ГЕРОЯ
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $hero    = $this->getOrCreateHero($user_id);

        if ($hero['status'] === 'regenerating' && $hero['revive_time'] <= time()) {
            $this->reviveHero($user_id);
            $hero = $this->getHero($user_id);
        }

        $items        = $this->getItems($user_id);
        $equipped     = $this->getEquipped($user_id);
        $bonuses      = $this->getHeroBonuses($user_id);
        $items_config = self::$items_config;
        $level_config = self::$level_config;
        $max_level    = max(array_keys(self::$level_config));

        $stmt = $this->db->prepare("SELECT id, name FROM villages WHERE userid = ?");
        $stmt->execute([$user_id]);
        $villages = $stmt->fetchAll();

        $stmt = $this->db->prepare("
            SELECT * FROM hero_exp_log
            WHERE user_id = ? ORDER BY time DESC LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $exp_log = $stmt->fetchAll();

        // Активные зелья
        $active_potions = $this->getActivePotions($user_id);

        $db = $this->db;
        require_once __DIR__ . '/../templates/hero.php';
    }

    // =========================================================
    // ПОЛУЧИТЬ АКТИВНЫЕ ЗЕЛЬЯ
    // =========================================================
    public function getActivePotions($user_id) {
        $potions = [];

        $potion_keys = [
            "potion_res_{$user_id}"   => ['icon'=>'💰','name'=>'Зелье богатства', 'desc_tpl'=>'+{bonus}% к производству ресурсов','color'=>'#dd0'],
            "potion_speed_{$user_id}" => ['icon'=>'⚡','name'=>'Зелье скорости',  'desc_tpl'=>'-{bonus}% к времени походов',       'color'=>'#0dd'],
        ];

        foreach ($potion_keys as $key => $info) {
            try {
                $stmt = $this->db->prepare("SELECT value FROM game_config WHERE `key` = ?");
                $stmt->execute([$key]);
                $row = $stmt->fetch();
                if ($row && !empty($row['value'])) {
                    $parts   = explode(':', $row['value']);
                    $bonus   = (float)($parts[0] ?? 0);
                    $expires = (int)($parts[1]   ?? 0);
                    if ($expires > time()) {
                        $desc = str_replace('{bonus}', $bonus, $info['desc_tpl']);
                        $potions[] = [
                            'icon'    => $info['icon'],
                            'name'    => $info['name'],
                            'desc'    => $desc,
                            'expires' => $expires,
                            'color'   => $info['color']
                        ];
                    } else {
                        // Удаляем истёкшее
                        $this->db->prepare("DELETE FROM game_config WHERE `key` = ?")
                            ->execute([$key]);
                    }
                }
            } catch (Exception $e) {}
        }

        return $potions;
    }

    // =========================================================
    // ДОБАВИТЬ НАВЫК
    // =========================================================
    public function addSkill() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $skill   = $_POST['skill'] ?? '';
        $allowed = ['skill_attack','skill_defense','skill_resource','skill_speed'];

        if (!in_array($skill, $allowed)) {
            $_SESSION['error'] = "Неверный навык!";
            header("Location: ?page=hero");
            exit;
        }

        $hero = $this->getHero($user_id);
        if (!$hero || $hero['skill_points'] <= 0) {
            $_SESSION['error'] = "Нет очков навыков!";
            header("Location: ?page=hero");
            exit;
        }

        if ((int)$hero[$skill] >= 20) {
            $_SESSION['error'] = "Навык уже на максимуме (20)!";
            header("Location: ?page=hero");
            exit;
        }

        $this->db->prepare("UPDATE heroes SET
            `{$skill}` = `{$skill}` + 1,
            skill_points = skill_points - 1
            WHERE user_id = ?
        ")->execute([$user_id]);

        $names = [
            'skill_attack'   => 'Атака',
            'skill_defense'  => 'Защита',
            'skill_resource' => 'Ресурсы',
            'skill_speed'    => 'Скорость'
        ];
        $_SESSION['success'] = "✅ Навык «{$names[$skill]}» улучшен!";
        header("Location: ?page=hero");
        exit;
    }

    // =========================================================
    // КУПИТЬ ПРЕДМЕТ
    // =========================================================
    public function buyItem() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id    = $_SESSION['user_id'];
        $item_type  = $_POST['item_type']  ?? '';
        $village_id = (int)($_POST['village_id'] ?? 0);

        if (!isset(self::$items_config[$item_type])) {
            $_SESSION['error'] = "Неизвестный предмет!";
            header("Location: ?page=hero&tab=shop");
            exit;
        }

        $item = self::$items_config[$item_type];

        $stmt = $this->db->prepare("SELECT * FROM villages WHERE id = ? AND userid = ?");
        $stmt->execute([$village_id, $user_id]);
        $village = $stmt->fetch();

        if (!$village) {
            $_SESSION['error'] = "Деревня не найдена!";
            header("Location: ?page=hero&tab=shop");
            exit;
        }

        if ($village['r_wood']  < $item['wood']  ||
            $village['r_stone'] < $item['stone'] ||
            $village['r_iron']  < $item['iron']) {
            $_SESSION['error'] = "Недостаточно ресурсов! " .
                "Нужно: 🪵{$item['wood']} 🪨{$item['stone']} ⛏{$item['iron']}";
            header("Location: ?page=hero&tab=shop");
            exit;
        }

        $this->db->prepare("UPDATE villages SET
            r_wood  = r_wood  - ?,
            r_stone = r_stone - ?,
            r_iron  = r_iron  - ?
            WHERE id = ?
        ")->execute([$item['wood'], $item['stone'], $item['iron'], $village_id]);

        $this->db->prepare("INSERT INTO hero_items
            (user_id, item_type, item_slot, name, bonus_type, bonus_value,
             rarity, equipped, obtained_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
        ")->execute([
            $user_id, $item_type, $item['slot'],
            $item['name'], $item['bonus_type'], $item['bonus_value'],
            $item['rarity'], time()
        ]);

        if ($item['slot'] === 'potion') {
            $this->applyPotion($user_id, $item_type, $item);
        }

        $_SESSION['success'] = "✅ Получен: {$item['icon']} {$item['name']}!";
        header("Location: ?page=hero&tab=inventory");
        exit;
    }

    // =========================================================
    // НАДЕТЬ / СНЯТЬ ПРЕДМЕТ
    // =========================================================
    public function equipItem() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id      = $_SESSION['user_id'];
        $item_id      = (int)($_GET['item_id']      ?? 0);
        $equip_action = $_GET['equip_action'] ?? 'equip';
        $tab          = $_GET['tab']          ?? 'inventory';

        if ($item_id <= 0) {
            $_SESSION['error'] = "Неверный ID предмета!";
            header("Location: ?page=hero&tab={$tab}");
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM hero_items WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
        $item = $stmt->fetch();

        if (!$item) {
            $_SESSION['error'] = "Предмет не найден!";
            header("Location: ?page=hero&tab={$tab}");
            exit;
        }

        if ($item['item_slot'] === 'potion') {
            $_SESSION['error'] = "Зелья нельзя надевать!";
            header("Location: ?page=hero&tab={$tab}");
            exit;
        }

        $hero = $this->getHero($user_id);
        if (!$hero) {
            $_SESSION['error'] = "Герой не найден!";
            header("Location: ?page=hero&tab={$tab}");
            exit;
        }

        if ($equip_action === 'equip') {
            // Снимаем предмет в этом слоте
            $this->db->prepare("UPDATE hero_items SET equipped = 0
                WHERE user_id = ? AND item_slot = ? AND equipped = 1
            ")->execute([$user_id, $item['item_slot']]);

            // Надеваем
            $this->db->prepare("UPDATE hero_items SET equipped = 1 WHERE id = ?")
                ->execute([$item_id]);

            // Броня — обновляем HP
            if ($item['bonus_type'] === 'hp') {
                $new_hp_max = $hero['hp_max'] + $item['bonus_value'];
                $new_hp     = min($new_hp_max, $hero['hp'] + $item['bonus_value']);
                $this->db->prepare("UPDATE heroes SET hp_max = ?, hp = ? WHERE user_id = ?")
                    ->execute([$new_hp_max, $new_hp, $user_id]);
            }

            $_SESSION['success'] = "✅ «{$item['name']}» надет!";

        } else {
            // Снимаем
            $this->db->prepare("UPDATE hero_items SET equipped = 0 WHERE id = ?")
                ->execute([$item_id]);

            if ($item['bonus_type'] === 'hp') {
                $new_hp_max = max(100, $hero['hp_max'] - $item['bonus_value']);
                $new_hp     = min($new_hp_max, $hero['hp']);
                $this->db->prepare("UPDATE heroes SET hp_max = ?, hp = ? WHERE user_id = ?")
                    ->execute([$new_hp_max, $new_hp, $user_id]);
            }

            $_SESSION['success'] = "Предмет «{$item['name']}» снят.";
        }

        header("Location: ?page=hero&tab={$tab}");
        exit;
    }

    // =========================================================
    // НАЗНАЧИТЬ ДЕРЕВНЮ
    // =========================================================
    public function assignVillage() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id    = $_SESSION['user_id'];
        $village_id = (int)($_POST['village_id'] ?? 0);

        $stmt = $this->db->prepare("SELECT id FROM villages WHERE id = ? AND userid = ?");
        $stmt->execute([$village_id, $user_id]);
        if (!$stmt->fetch()) {
            $_SESSION['error'] = "Деревня не найдена!";
            header("Location: ?page=hero");
            exit;
        }

        $this->db->prepare("UPDATE heroes SET village_id = ? WHERE user_id = ?")
            ->execute([$village_id, $user_id]);

        $_SESSION['success'] = "✅ Герой назначен в деревню!";
        header("Location: ?page=hero");
        exit;
    }

    // =========================================================
    // СОЗДАТЬ / ПОЛУЧИТЬ ГЕРОЯ
    // =========================================================
    public function getOrCreateHero($user_id) {
        $hero = $this->getHero($user_id);
        if ($hero) return $hero;

        $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $name = ($user['username'] ?? 'Герой') . "'s Hero";

        $stmt = $this->db->prepare("SELECT id FROM villages WHERE userid = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $v = $stmt->fetch();
        $village_id = $v ? $v['id'] : null;

        $this->db->prepare("INSERT INTO heroes
            (user_id, name, level, experience, exp_to_next,
             skill_attack, skill_defense, skill_resource, skill_speed,
             skill_points, hp, hp_max, status, village_id, created_at)
            VALUES (?, ?, 1, 0, 100, 0, 0, 0, 0, 2, 100, 100, 'alive', ?, ?)
        ")->execute([$user_id, $name, $village_id, time()]);

        return $this->getHero($user_id);
    }

    public function getHero($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM heroes WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    // =========================================================
    // ДОБАВИТЬ ОПЫТ
    // =========================================================
    public function addExperience($user_id, $exp, $reason = 'battle') {
        try {
            $hero = $this->getHero($user_id);
            if (!$hero || $hero['status'] === 'dead') return;

            $new_exp   = $hero['experience'] + $exp;
            $level     = $hero['level'];
            $max_level = max(array_keys(self::$level_config));

            $leveled_up   = false;
            $skill_gained = 0;
            $new_hp_max   = $hero['hp_max'];

            while ($level < $max_level) {
                $cfg = self::$level_config[$level] ?? null;
                if (!$cfg) break;
                if ($new_exp >= $cfg['exp']) {
                    $new_exp     -= $cfg['exp'];
                    $level++;
                    $leveled_up   = true;
                    $skill_gained += self::$level_config[$level]['skill_points'] ?? 2;
                    $new_hp_max   = self::$level_config[$level]['hp'];
                } else {
                    break;
                }
            }

            $next_exp = self::$level_config[$level]['exp'] ?? 99999;

            $this->db->prepare("UPDATE heroes SET
                experience   = ?,
                level        = ?,
                exp_to_next  = ?,
                skill_points = skill_points + ?,
                hp_max       = ?
                WHERE user_id = ?
            ")->execute([$new_exp, $level, $next_exp, $skill_gained, $new_hp_max, $user_id]);

            $this->db->prepare("INSERT INTO hero_exp_log (user_id, exp, reason, time) VALUES (?,?,?,?)")
                ->execute([$user_id, $exp, $reason, time()]);

            if ($leveled_up) {
                $this->db->prepare("INSERT INTO reports
                    (userid, type, title, content, time, is_read)
                    VALUES (?, 'system', ?, ?, ?, 0)
                ")->execute([
                    $user_id,
                    "🦸 Герой достиг уровня {$level}!",
                    "Ваш герой повысил уровень до {$level}!\n" .
                    "Получено очков навыков: {$skill_gained}\n" .
                    "HP увеличен до {$new_hp_max}\n\n" .
                    "Распределите очки навыков в разделе «Герой»!",
                    time()
                ]);
            }

            return $leveled_up;
        } catch (Exception $e) {
            error_log("addExperience: " . $e->getMessage());
        }
    }

    // =========================================================
    // УРОН ГЕРОЮ
    // =========================================================
    public function heroTakeDamage($user_id, $damage_percent) {
        try {
            $hero = $this->getHero($user_id);
            if (!$hero || $hero['status'] !== 'alive') return;

            $damage = (int)ceil($hero['hp_max'] * $damage_percent / 100);
            $new_hp = max(0, $hero['hp'] - $damage);

            if ($new_hp <= 0) {
                $revive_hours = max(1, 24 - $hero['level']);
                $revive_time  = time() + ($revive_hours * 3600);

                $this->db->prepare("UPDATE heroes SET
                    hp = 0, status = 'regenerating', revive_time = ?
                    WHERE user_id = ?
                ")->execute([$revive_time, $user_id]);

                $this->db->prepare("INSERT INTO reports
                    (userid, type, title, content, time, is_read)
                    VALUES (?, 'system', ?, ?, ?, 0)
                ")->execute([
                    $user_id,
                    "💀 Ваш герой погиб в бою!",
                    "Герой погиб!\n" .
                    "Возрождение через: {$revive_hours} ч.\n" .
                    "До возрождения герой не даёт бонусов.",
                    time()
                ]);
            } else {
                $this->db->prepare("UPDATE heroes SET hp = ? WHERE user_id = ?")
                    ->execute([$new_hp, $user_id]);
            }
        } catch (Exception $e) {
            error_log("heroTakeDamage: " . $e->getMessage());
        }
    }

    // =========================================================
    // ВОЗРОДИТЬ ГЕРОЯ
    // =========================================================
    public function reviveHero($user_id) {
        $this->db->prepare("UPDATE heroes SET
            status = 'alive', hp = hp_max, revive_time = 0
            WHERE user_id = ?
        ")->execute([$user_id]);
    }

    // =========================================================
    // БОНУСЫ ГЕРОЯ
    // =========================================================
    public function getHeroBonuses($user_id) {
        try {
            $hero = $this->getHero($user_id);
            if (!$hero || $hero['status'] !== 'alive') {
                return ['attack'=>0,'defense'=>0,'resource'=>0,'speed'=>0];
            }

            $bonuses = [
                'attack'   => $hero['skill_attack']   * 2,
                'defense'  => $hero['skill_defense']  * 2,
                'resource' => $hero['skill_resource'] * 1.5,
                'speed'    => $hero['skill_speed']    * 1,
            ];

            $stmt = $this->db->prepare("
                SELECT * FROM hero_items WHERE user_id = ? AND equipped = 1
            ");
            $stmt->execute([$user_id]);
            foreach ($stmt->fetchAll() as $item) {
                switch ($item['bonus_type']) {
                    case 'attack':   $bonuses['attack']   += $item['bonus_value']; break;
                    case 'defense':  $bonuses['defense']  += $item['bonus_value']; break;
                    case 'resource': $bonuses['resource'] += $item['bonus_value']; break;
                    case 'speed':    $bonuses['speed']    += $item['bonus_value']; break;
                }
            }

            return $bonuses;
        } catch (Exception $e) {
            return ['attack'=>0,'defense'=>0,'resource'=>0,'speed'=>0];
        }
    }

    // =========================================================
    // ПРИМЕНИТЬ ЗЕЛЬЕ
    // =========================================================
    private function applyPotion($user_id, $item_type, $item) {
        switch ($item['bonus_type']) {

            case 'heal':
                $this->db->prepare("UPDATE heroes SET hp = LEAST(hp_max, hp + ?) WHERE user_id = ?")
                    ->execute([$item['bonus_value'], $user_id]);
                $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
                    ->execute([
                        $user_id,
                        "🧪 Зелье здоровья применено!",
                        "Герой восстановил {$item['bonus_value']} HP.",
                        time()
                    ]);
                break;

            case 'resource':
                $expires = time() + 3600;
                $key     = "potion_res_{$user_id}";
                try {
                    $this->db->prepare("DELETE FROM game_config WHERE `key` = ?")->execute([$key]);
                    $this->db->prepare("INSERT INTO game_config (`key`, `value`) VALUES (?, ?)")
                        ->execute([$key, $item['bonus_value'] . ':' . $expires]);
                } catch (Exception $e) {}
                $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
                    ->execute([
                        $user_id,
                        "💰 Зелье богатства применено!",
                        "Производство ресурсов +{$item['bonus_value']}% на 1 час!\n" .
                        "Активно до: " . date('H:i:s', $expires),
                        time()
                    ]);
                break;

            case 'speed':
                $expires = time() + 1800;
                $key     = "potion_speed_{$user_id}";
                try {
                    $this->db->prepare("DELETE FROM game_config WHERE `key` = ?")->execute([$key]);
                    $this->db->prepare("INSERT INTO game_config (`key`, `value`) VALUES (?, ?)")
                        ->execute([$key, $item['bonus_value'] . ':' . $expires]);
                } catch (Exception $e) {}
                $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
                    ->execute([
                        $user_id,
                        "⚡ Зелье скорости применено!",
                        "Скорость войск -{$item['bonus_value']}% на 30 минут!\n" .
                        "Активно до: " . date('H:i:s', $expires),
                        time()
                    ]);
                break;
        }

        // Удаляем зелье из инвентаря
        $this->db->prepare("DELETE FROM hero_items WHERE user_id = ? AND item_type = ? LIMIT 1")
            ->execute([$user_id, $item_type]);
    }

    // =========================================================
    // ВСПОМОГАТЕЛЬНЫЕ
    // =========================================================
    public function getItems($user_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM hero_items
            WHERE user_id = ?
            ORDER BY equipped DESC, rarity DESC, obtained_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function getEquipped($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM hero_items WHERE user_id = ? AND equipped = 1");
        $stmt->execute([$user_id]);
        $equipped = [];
        foreach ($stmt->fetchAll() as $item) {
            $equipped[$item['item_slot']] = $item;
        }
        return $equipped;
    }

    public static function getItemsConfig() {
        return self::$items_config;
    }

    public static function getLevelConfig() {
        return self::$level_config;
    }
}