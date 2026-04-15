<?php
// controllers/BlackMarketController.php

class BlackMarketController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА ТЁМНОГО РЫНКА
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Сбрасываем запасы раз в сутки
        $this->resetStockIfNeeded();

        // Получаем товары
        $stmt = $this->db->query("
            SELECT bm.*,
                   (SELECT COUNT(*) FROM black_market_purchases bmp
                    WHERE bmp.item_id=bm.id AND bmp.user_id={$user_id}
                    AND bmp.purchased_at >= " . strtotime('today') . ") as my_purchases_today
            FROM black_market bm
            WHERE bm.is_active=1 AND bm.stock>0
            ORDER BY bm.type, bm.id
        ");
        $items = $stmt->fetchAll();

        // Мои деревни
        $stmt = $this->db->prepare("SELECT id,name,r_wood,r_stone,r_iron FROM villages WHERE userid=? ORDER BY id");
        $stmt->execute([$user_id]);
        $villages = $stmt->fetchAll();

        // История покупок
        $stmt = $this->db->prepare("
            SELECT bmp.*, bm.name, bm.icon
            FROM black_market_purchases bmp
            JOIN black_market bm ON bmp.item_id=bm.id
            WHERE bmp.user_id=?
            ORDER BY bmp.purchased_at DESC LIMIT 10
        ");
        $stmt->execute([$user_id]);
        $purchase_history = $stmt->fetchAll();

        // Активные наёмники
        $stmt = $this->db->prepare("
            SELECT m.*, v.name as village_name
            FROM mercenaries m
            JOIN villages v ON m.village_id=v.id
            WHERE m.user_id=? AND m.expires_at>?
        ");
        $stmt->execute([$user_id, time()]);
        $active_mercs = $stmt->fetchAll();

        // Следующий сброс
        $next_reset = strtotime('tomorrow');

        $db = $this->db;
        require_once __DIR__ . '/../templates/black_market.php';
    }

    // =========================================================
    // КУПИТЬ ТОВАР
    // =========================================================
    public function buy() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id    = $_SESSION['user_id'];
        $item_id    = (int)($_POST['item_id']    ?? 0);
        $village_id = (int)($_POST['village_id'] ?? 0);

        // Получаем товар
        $stmt = $this->db->prepare("SELECT * FROM black_market WHERE id=? AND is_active=1 AND stock>0");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if (!$item) {
            $_SESSION['error'] = "Товар недоступен или закончился!";
            header("Location: ?page=black_market");
            exit;
        }

        // Проверяем лимит в день
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM black_market_purchases
            WHERE user_id=? AND item_id=? AND purchased_at>=?");
        $stmt->execute([$user_id, $item_id, strtotime('today')]);
        $today_count = $stmt->fetch()['cnt'] ?? 0;

        if ($today_count >= $item['max_per_day']) {
            $_SESSION['error'] = "Достигнут дневной лимит покупок для этого товара!";
            header("Location: ?page=black_market");
            exit;
        }

        // Проверяем деревню
        if ($village_id > 0) {
            $stmt = $this->db->prepare("SELECT * FROM villages WHERE id=? AND userid=?");
            $stmt->execute([$village_id, $user_id]);
            $village = $stmt->fetch();
            if (!$village) {
                $_SESSION['error'] = "Деревня не найдена!";
                header("Location: ?page=black_market");
                exit;
            }
        } else {
            // Берём первую деревню
            $stmt = $this->db->prepare("SELECT * FROM villages WHERE userid=? LIMIT 1");
            $stmt->execute([$user_id]);
            $village = $stmt->fetch();
        }

        if (!$village) {
            $_SESSION['error'] = "Нет деревень!";
            header("Location: ?page=black_market");
            exit;
        }

        // Проверяем ресурсы
        if ($village['r_wood']  < $item['price_wood']  ||
            $village['r_stone'] < $item['price_stone'] ||
            $village['r_iron']  < $item['price_iron']) {
            $_SESSION['error'] = "Недостаточно ресурсов! Нужно: " .
                "🪵{$item['price_wood']} 🪨{$item['price_stone']} ⛏{$item['price_iron']}";
            header("Location: ?page=black_market");
            exit;
        }

        // Снимаем ресурсы
        if ($item['price_wood'] + $item['price_stone'] + $item['price_iron'] > 0) {
            $this->db->prepare("UPDATE villages SET
                r_wood=r_wood-?, r_stone=r_stone-?, r_iron=r_iron-?
                WHERE id=?")
                ->execute([$item['price_wood'], $item['price_stone'], $item['price_iron'], $village['id']]);
        }

        // Применяем эффект
        $result = $this->applyEffect($user_id, $village['id'], $item);

        // Записываем покупку
        $this->db->prepare("INSERT INTO black_market_purchases (user_id,item_id,village_id,purchased_at) VALUES (?,?,?,?)")
            ->execute([$user_id, $item_id, $village['id'], time()]);

        // Уменьшаем запас
        $this->db->prepare("UPDATE black_market SET stock=GREATEST(0,stock-1) WHERE id=?")
            ->execute([$item_id]);

        $_SESSION['success'] = "✅ Куплено: {$item['icon']} {$item['name']}!<br>{$result}";
        header("Location: ?page=black_market");
        exit;
    }

    // =========================================================
    // ПРИМЕНИТЬ ЭФФЕКТ
    // =========================================================
    private function applyEffect($user_id, $village_id, $item) {
        switch ($item['effect_type']) {

            case 'instant_build':
                // Мгновенно завершаем текущее строительство
                try {
                    $stmt = $this->db->prepare("SELECT * FROM build_queue WHERE village_id=? AND status='building' LIMIT 1");
                    $stmt->execute([$village_id]);
                    $build = $stmt->fetch();
                    if ($build) {
                        $this->db->prepare("UPDATE villages SET `{$build['building']}`=? WHERE id=?")
                            ->execute([$build['level'], $village_id]);
                        $pts_map=['main'=>10,'wood_level'=>4,'stone_level'=>4,'iron_level'=>4,'farm'=>5,'storage'=>5,
                            'barracks'=>6,'stable'=>8,'smith'=>6,'garage'=>7,'wall'=>6,'hide'=>3];
                        $pts=($pts_map[$build['building']]??4)*$build['level'];
                        $this->db->prepare("UPDATE villages SET points=points+? WHERE id=?")->execute([$pts,$village_id]);
                        $this->db->prepare("UPDATE build_queue SET status='done' WHERE id=?")->execute([$build['id']]);
                        $this->db->prepare("UPDATE build_queue SET position=position-1 WHERE village_id=? AND status!='done' AND position>?")->execute([$village_id,$build['position']]);
                        return "Строительство «{$build['building']}» завершено мгновенно!";
                    }
                    return "Нет активного строительства.";
                } catch (Exception $e) { return "Ошибка."; }

            case 'double_production':
                $expires = time() + $item['effect_hours'] * 3600;
                $this->setConfig("potion_res_{$user_id}", "100:{$expires}");
                return "Двойное производство активно на {$item['effect_hours']} ч. до ".date('H:i', $expires);

            case 'instant_train':
                try {
                    $stmt = $this->db->prepare("SELECT train_queue,train_end_time FROM villages WHERE id=?");
                    $stmt->execute([$village_id]);
                    $v = $stmt->fetch();
                    if ($v && !empty($v['train_queue']) && $v['train_end_time'] > time()) {
                        list($unit_type,$amount) = explode(':', $v['train_queue'].':0');
                        $amount = (int)$amount;
                        if ($amount > 0) {
                            $this->db->prepare("UPDATE unit_place SET `{$unit_type}`=`{$unit_type}`+? WHERE villages_to_id=?")
                                ->execute([$amount, $village_id]);
                            $this->db->prepare("UPDATE villages SET train_queue=NULL,train_end_time=0 WHERE id=?")
                                ->execute([$village_id]);
                            return "Тренировка {$amount} {$unit_type} завершена мгновенно!";
                        }
                    }
                    return "Нет активной тренировки.";
                } catch (Exception $e) { return "Ошибка."; }

            case 'peace_shield':
                $expires = time() + $item['effect_hours'] * 3600;
                $this->setConfig("peace_shield_{$user_id}", $expires);
                return "Щит мира активен на {$item['effect_hours']} ч. до ".date('H:i:s', $expires);

            case 'add_wood':
                $this->db->prepare("UPDATE villages SET r_wood=r_wood+? WHERE id=?")
                    ->execute([$item['effect_value'], $village_id]);
                return "+".number_format($item['effect_value'])." 🪵 дерева добавлено!";

            case 'add_stone':
                $this->db->prepare("UPDATE villages SET r_stone=r_stone+? WHERE id=?")
                    ->execute([$item['effect_value'], $village_id]);
                return "+".number_format($item['effect_value'])." 🪨 камня добавлено!";

            case 'add_iron':
                $this->db->prepare("UPDATE villages SET r_iron=r_iron+? WHERE id=?")
                    ->execute([$item['effect_value'], $village_id]);
                return "+".number_format($item['effect_value'])." ⛏ железа добавлено!";

            case 'merc_spear':
            case 'merc_axe':
            case 'merc_light':
                $unit_type = str_replace('merc_', '', $item['effect_type']);
                $amount    = $item['effect_value'];
                $expires   = time() + $item['effect_hours'] * 3600;

                // Добавляем в unit_place временно (помечаем как наёмников)
                try {
                    $check = $this->db->prepare("SELECT id FROM unit_place WHERE villages_to_id=?");
                    $check->execute([$village_id]);
                    if ($check->fetch()) {
                        $this->db->prepare("UPDATE unit_place SET `{$unit_type}`=`{$unit_type}`+? WHERE villages_to_id=?")
                            ->execute([$amount, $village_id]);
                    } else {
                        $this->db->prepare("INSERT INTO unit_place (villages_to_id,`{$unit_type}`) VALUES (?,?)")
                            ->execute([$village_id, $amount]);
                    }

                    // Записываем в таблицу наёмников
                    $this->db->prepare("INSERT INTO mercenaries (user_id,village_id,troops,expires_at,created_at) VALUES (?,?,?,?,?)")
                        ->execute([$user_id, $village_id, json_encode([$unit_type=>$amount]), $expires, time()]);

                    $unit_names=['spear'=>'копейщиков','axe'=>'топорщиков','light'=>'лёгкой кав.'];
                    return "+{$amount} ".($unit_names[$unit_type]??$unit_type)." на {$item['effect_hours']} ч.!";
                } catch (Exception $e) { return "Ошибка найма."; }

            default:
                return "Эффект применён.";
        }
    }

    // =========================================================
    // ИСТЕЧЕНИЕ НАЁМНИКОВ (вызывается из крона)
    // =========================================================
    public function processExpiredMercenaries() {
        try {
            $stmt = $this->db->query("SELECT * FROM mercenaries WHERE expires_at<=" . time());
            $expired = $stmt->fetchAll();

            foreach ($expired as $merc) {
                $troops = json_decode($merc['troops'], true);

                // Убираем наёмников из деревни
                $sets=[]; $params=[];
                foreach ($troops as $type=>$count) {
                    if ($count>0) {
                        $sets[]="`{$type}`=GREATEST(0,`{$type}`-?)";
                        $params[]=$count;
                    }
                }
                if (!empty($sets)) {
                    $params[]=$merc['village_id'];
                    $this->db->prepare("UPDATE unit_place SET ".implode(',',$sets)." WHERE villages_to_id=?")->execute($params);
                }

                // Удаляем запись
                $this->db->prepare("DELETE FROM mercenaries WHERE id=?")->execute([$merc['id']]);

                // Уведомляем игрока
                $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
                    ->execute([
                        $merc['user_id'],
                        "⚔ Наёмники ушли",
                        "Срок контракта наёмников истёк.\nОни покинули вашу деревню.",
                        time()
                    ]);
            }
        } catch (Exception $e) {
            error_log("processExpiredMercenaries: " . $e->getMessage());
        }
    }

    // =========================================================
    // СБРОС ЗАПАСОВ
    // =========================================================
    private function resetStockIfNeeded() {
        try {
            $stmt = $this->db->query("SELECT MIN(resets_at) as min_reset FROM black_market WHERE is_active=1");
            $min_reset = $stmt->fetch()['min_reset'] ?? 0;

            if ($min_reset <= time()) {
                $this->db->query("UPDATE black_market SET
                    stock = CASE type
                        WHEN 'boost'     THEN 3
                        WHEN 'resource'  THEN 5
                        WHEN 'mercenary' THEN 3
                        ELSE 3
                    END,
                    resets_at = " . strtotime('tomorrow') . "
                    WHERE is_active=1");
            }
        } catch (Exception $e) {}
    }

    // =========================================================
    // ВСПОМОГАТЕЛЬНЫЕ
    // =========================================================
    private function setConfig($key, $value) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM game_config WHERE `key`=?");
            $stmt->execute([$key]);
            if ($stmt->fetch()) {
                $this->db->prepare("UPDATE game_config SET value=? WHERE `key`=?")->execute([$value,$key]);
            } else {
                $this->db->prepare("INSERT INTO game_config (`key`,`value`) VALUES (?,?)")->execute([$key,$value]);
            }
        } catch (Exception $e) {}
    }

    // Проверка щита мира
    public static function hasPeaceShield($db, $user_id) {
        try {
            $stmt = $db->prepare("SELECT value FROM game_config WHERE `key`=?");
            $stmt->execute(["peace_shield_{$user_id}"]);
            $row = $stmt->fetch();
            if ($row && (int)$row['value'] > time()) return true;
        } catch (Exception $e) {}
        return false;
    }
}