<?php
// controllers/EventController.php

class EventController {
    private $db;

    private static $event_types = [
        'gold_rush' => [
            'title'       => '💰 Золотая лихорадка!',
            'icon'        => '💰',
            'description' => 'Производство всех ресурсов удвоено на 6 часов! ' .
                             'Стройте и тренируйте войска максимально быстро!',
            'duration'    => 6 * 3600,
            'color'       => '#d4a843',
            'bg'          => '#2a2010',
            'border'      => '#8b6914',
        ],
        'barbarian_invasion' => [
            'title'       => '⚔ Нашествие варваров!',
            'icon'        => '⚔',
            'description' => 'Орды усиленных варваров атакуют ближайшие деревни! ' .
                             'Защищайтесь и получайте опыт за каждую победу!',
            'duration'    => 12 * 3600,
            'color'       => '#f44',
            'bg'          => '#2a1a1a',
            'border'      => '#8a1a1a',
        ],
        'tournament' => [
            'title'       => '🏆 Турнир воинов!',
            'icon'        => '🏆',
            'description' => 'Недельный PvP-турнир! Побеждайте других игроков ' .
                             'и зарабатывайте очки. Топ-3 получат награды!',
            'duration'    => 7 * 24 * 3600,
            'color'       => '#d4a843',
            'bg'          => '#2a2010',
            'border'      => '#d4a843',
        ],
        'caravan' => [
            'title'       => '🚚 Торговый Караван!',
            'icon'        => '🚚',
            'description' => 'Редкий торговый NPC появился на рынке с выгодными ' .
                             'предложениями! Успейте обменять ресурсы по лучшему курсу!',
            'duration'    => 4 * 3600,
            'color'       => '#0dd',
            'bg'          => '#1a2a2a',
            'border'      => '#1a8a8a',
        ],
        'plague' => [
            'title'       => '☠ Чума!',
            'icon'        => '☠',
            'description' => 'Смертельная болезнь охватила земли. ' .
                             'Производство ресурсов снижено на 30% на 3 часа. ' .
                             'Постройте лечебницу чтобы снизить эффект!',
            'duration'    => 3 * 3600,
            'color'       => '#8a4',
            'bg'          => '#1a2a1a',
            'border'      => '#4a8a2a',
        ],
        'blessing' => [
            'title'       => '✨ Благословение богов!',
            'icon'        => '✨',
            'description' => 'Боги благосклонны к вашим землям! ' .
                             'Скорость строительства и тренировки удвоена на 4 часа!',
            'duration'    => 4 * 3600,
            'color'       => '#88f',
            'bg'          => '#1a1a2a',
            'border'      => '#2a2a8a',
        ],
    ];

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА СОБЫТИЙ
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $active_event = $this->getActiveEvent();

        // История
        $stmt = $this->db->query("
            SELECT * FROM world_events
            ORDER BY started_at DESC LIMIT 10
        ");
        $history = $stmt->fetchAll();

        // Рейтинг турнира
        $tournament_rank = [];
        if ($active_event && $active_event['type'] === 'tournament') {
            try {
                $stmt = $this->db->prepare("
                    SELECT ep.*, u.username, u.points
                    FROM event_participants ep
                    JOIN users u ON ep.user_id = u.id
                    WHERE ep.event_id = ?
                    ORDER BY ep.score DESC LIMIT 20
                ");
                $stmt->execute([$active_event['id']]);
                $tournament_rank = $stmt->fetchAll();
            } catch (Exception $e) {
                $tournament_rank = [];
            }
        }

        // Моя позиция в турнире
        $my_rank = null;
        if ($active_event && $active_event['type'] === 'tournament') {
            try {
                $stmt = $this->db->prepare("
                    SELECT ep.score,
                           (SELECT COUNT(*)+1
                            FROM event_participants ep2
                            WHERE ep2.event_id = ? AND ep2.score > ep.score) as user_rank
                    FROM event_participants ep
                    WHERE ep.event_id = ? AND ep.user_id = ?
                ");
                $stmt->execute([
                    $active_event['id'],
                    $active_event['id'],
                    $_SESSION['user_id']
                ]);
                $my_rank = $stmt->fetch();
            } catch (Exception $e) {
                $my_rank = null;
            }
        }

        $event_types = self::$event_types;
        $db          = $this->db;
        require_once __DIR__ . '/../templates/events.php';
    }

    // =========================================================
    // ПОЛУЧИТЬ АКТИВНОЕ СОБЫТИЕ
    // =========================================================
    public function getActiveEvent() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM world_events
                WHERE status = 'active' AND ends_at > ?
                ORDER BY started_at DESC LIMIT 1
            ");
            $stmt->execute([time()]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    // =========================================================
    // ЗАПУСТИТЬ СОБЫТИЕ
    // =========================================================
    public function startEvent($type = null) {
        $active = $this->getActiveEvent();
        if ($active) return false;

        if (!$type || !isset(self::$event_types[$type])) {
            $types = array_keys(self::$event_types);
            $type  = $types[array_rand($types)];
        }

        $cfg = self::$event_types[$type];

        $event_id = null;
        try {
            $this->db->prepare("INSERT INTO world_events
                (type, title, description, icon, started_at, ends_at, status, config)
                VALUES (?, ?, ?, ?, ?, ?, 'active', ?)
            ")->execute([
                $type,
                $cfg['title'],
                $cfg['description'],
                $cfg['icon'],
                time(),
                time() + $cfg['duration'],
                json_encode(['color'=>$cfg['color'],'bg'=>$cfg['bg']])
            ]);
            $event_id = $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("startEvent: " . $e->getMessage());
            return false;
        }

        $this->applyEventEffect($type, $cfg, $event_id);
        $this->notifyAllPlayers($cfg['title'], $cfg['description'], $cfg['icon']);

        return $event_id;
    }

    // =========================================================
    // ПРИМЕНИТЬ ЭФФЕКТ
    // =========================================================
    private function applyEventEffect($type, $cfg, $event_id) {
        $expires = time() + $cfg['duration'];

        switch ($type) {
            case 'gold_rush':
                $this->setConfigValue('active_event_resource_bonus', '100:' . $expires);
                break;

            case 'plague':
                $this->setConfigValue('active_event_resource_bonus', '-30:' . $expires);
                break;

            case 'blessing':
                $this->setConfigValue('active_event_build_bonus', '50:' . $expires);
                break;

            case 'barbarian_invasion':
                $this->generateStrongerBarbarians(20);
                break;

            case 'tournament':
                try {
                    $stmt = $this->db->query("
                        SELECT id FROM users
                        WHERE last_activity >= " . (time() - 7*86400)
                    );
                    foreach ($stmt->fetchAll() as $u) {
                        try {
                            $this->db->prepare("INSERT IGNORE INTO event_participants
                                (event_id, user_id, score, joined_at)
                                VALUES (?, ?, 0, ?)")
                                ->execute([$event_id, $u['id'], time()]);
                        } catch (Exception $e) {}
                    }
                } catch (Exception $e) {}
                break;

            case 'caravan':
                // Уведомление — в notifyAllPlayers
                break;
        }
    }

    // =========================================================
    // ВСПОМОГАТЕЛЬНЫЙ: установить значение конфига
    // =========================================================
    private function setConfigValue($key, $value) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM game_config WHERE `key` = ?");
            $stmt->execute([$key]);
            if ($stmt->fetch()) {
                $this->db->prepare("UPDATE game_config SET `value`=? WHERE `key`=?")
                    ->execute([$value, $key]);
            } else {
                $this->db->prepare("INSERT INTO game_config (`key`, `value`) VALUES (?, ?)")
                    ->execute([$key, $value]);
            }
        } catch (Exception $e) {
            error_log("setConfigValue: " . $e->getMessage());
        }
    }

    // =========================================================
    // ЗАВЕРШИТЬ СОБЫТИЕ
    // =========================================================
    public function endEvent($event_id) {
        $stmt = $this->db->prepare("SELECT * FROM world_events WHERE id=?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        if (!$event) return;

        $this->db->prepare("UPDATE world_events SET status='ended' WHERE id=?")
            ->execute([$event_id]);

        switch ($event['type']) {
            case 'gold_rush':
            case 'plague':
                $this->deleteConfigValue('active_event_resource_bonus');
                break;
            case 'blessing':
                $this->deleteConfigValue('active_event_build_bonus');
                break;
            case 'tournament':
                $this->rewardTournamentWinners($event_id);
                break;
        }

        // Уведомляем об окончании
        try {
            $stmt = $this->db->query("SELECT id FROM users WHERE last_activity >= " . (time()-7*86400));
            foreach ($stmt->fetchAll() as $u) {
                $this->db->prepare("INSERT INTO reports
                    (userid, type, title, content, time, is_read)
                    VALUES (?, 'system', ?, ?, ?, 0)")
                    ->execute([
                        $u['id'],
                        "🌍 Событие завершено: " . $event['title'],
                        "Мировое событие «{$event['title']}» завершилось.\n" .
                        "Следите за новыми событиями в разделе «События»!",
                        time()
                    ]);
            }
        } catch (Exception $e) {}
    }

    private function deleteConfigValue($key) {
        try {
            $this->db->prepare("DELETE FROM game_config WHERE `key`=?")->execute([$key]);
        } catch (Exception $e) {}
    }

    // =========================================================
    // НАГРАДЫ ТУРНИРА
    // =========================================================
    private function rewardTournamentWinners($event_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT ep.*, u.username
                FROM event_participants ep
                JOIN users u ON ep.user_id = u.id
                WHERE ep.event_id = ? AND ep.score > 0
                ORDER BY ep.score DESC LIMIT 3
            ");
            $stmt->execute([$event_id]);
            $winners = $stmt->fetchAll();
        } catch (Exception $e) {
            return;
        }

        $rewards = [
            1 => ['wood'=>50000,'stone'=>50000,'iron'=>50000,'title'=>'🥇 1-е место'],
            2 => ['wood'=>25000,'stone'=>25000,'iron'=>25000,'title'=>'🥈 2-е место'],
            3 => ['wood'=>10000,'stone'=>10000,'iron'=>10000,'title'=>'🥉 3-е место'],
        ];

        foreach ($winners as $i => $winner) {
            $place  = $i + 1;
            $reward = $rewards[$place] ?? null;
            if (!$reward) continue;

            $stmt2 = $this->db->prepare("SELECT id FROM villages WHERE userid=? LIMIT 1");
            $stmt2->execute([$winner['user_id']]);
            $village = $stmt2->fetch();

            if ($village) {
                $this->db->prepare("UPDATE villages SET
                    r_wood=r_wood+?, r_stone=r_stone+?, r_iron=r_iron+?
                    WHERE id=?")
                    ->execute([$reward['wood'],$reward['stone'],$reward['iron'],$village['id']]);
            }

            $this->db->prepare("INSERT INTO reports
                (userid, type, title, content, time, is_read)
                VALUES (?, 'system', ?, ?, ?, 0)")
                ->execute([
                    $winner['user_id'],
                    "🏆 {$reward['title']} в Турнире!",
                    "Поздравляем! Вы заняли {$place} место в Турнире!\n\n" .
                    "Очков набрано: " . number_format($winner['score']) . "\n\n" .
                    "Награда:\n" .
                    "🪵 Дерево: " . number_format($reward['wood']) . "\n" .
                    "🪨 Камень: " . number_format($reward['stone']) . "\n" .
                    "⛏ Железо: " . number_format($reward['iron']),
                    time()
                ]);

            try {
                $this->db->prepare("UPDATE event_participants SET rewarded=1 WHERE event_id=? AND user_id=?")
                    ->execute([$event_id, $winner['user_id']]);
            } catch (Exception $e) {}
        }
    }

    // =========================================================
    // ДОБАВИТЬ ОЧКО ТУРНИРА
    // =========================================================
    public function addTournamentScore($user_id, $score, $reason = 'attack') {
        try {
            $active = $this->getActiveEvent();
            if (!$active || $active['type'] !== 'tournament') return;

            $this->db->prepare("INSERT INTO event_participants
                (event_id, user_id, score, joined_at)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE score = score + VALUES(score)
            ")->execute([$active['id'], $user_id, $score, time()]);
        } catch (Exception $e) {}
    }

    // =========================================================
    // ГЕНЕРАЦИЯ УСИЛЕННЫХ ВАРВАРОВ
    // =========================================================
    private function generateStrongerBarbarians($count = 20) {
        $names = [
            '⚔ Орда Гаршака', '🔥 Логово Тёмных', '💀 Стан Смерти',
            '⛏ Крепость Хаоса', '🗡 Лагерь Ужаса', '🏴 Форт Тьмы',
            '☠ Пепелище', '⚔ Армия Теней', '🔥 Огненный лагерь', '💣 Руины Войны'
        ];

        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $found = false;
            for ($att = 0; $att < 15; $att++) {
                $x = rand(-200, 200);
                $y = rand(-200, 200);
                $chk = $this->db->prepare("SELECT id FROM villages WHERE x=? AND y=?");
                $chk->execute([$x, $y]);
                if (!$chk->fetch()) { $found = true; break; }
            }
            if (!$found) continue;

            $name      = $names[array_rand($names)];
            $continent = floor(($y+500)/100)*10 + floor(($x+500)/100);

            $this->db->prepare("INSERT INTO villages
                (userid,name,x,y,continent,
                 main,wood_level,stone_level,iron_level,farm,storage,wall,barracks,
                 r_wood,r_stone,r_iron,last_prod_aktu,points)
                VALUES (-1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $name,$x,$y,$continent,
                rand(8,15),rand(8,15),rand(8,15),rand(8,15),
                rand(5,10),rand(5,10),rand(5,10),rand(5,10),
                rand(20000,50000),rand(20000,50000),rand(20000,50000),
                time(),rand(1000,5000)
            ]);
            $created++;
        }
        return $created;
    }

    // =========================================================
    // УВЕДОМИТЬ ВСЕХ ИГРОКОВ
    // =========================================================
    private function notifyAllPlayers($title, $description, $icon) {
        try {
            $stmt = $this->db->query("SELECT id FROM users WHERE last_activity >= " . (time()-7*86400));
            foreach ($stmt->fetchAll() as $u) {
                $this->db->prepare("INSERT INTO reports
                    (userid, type, title, content, time, is_read)
                    VALUES (?, 'system', ?, ?, ?, 0)")
                    ->execute([
                        $u['id'],
                        "{$icon} Мировое событие: {$title}",
                        $description . "\n\nПерейдите в раздел «События» для подробностей!",
                        time()
                    ]);
            }
        } catch (Exception $e) {}
    }

    // =========================================================
    // ПОЛУЧИТЬ БОНУСЫ СОБЫТИЯ (статический метод)
    // =========================================================
    public static function getEventBonuses($db) {
        $bonuses = [
            'resource' => 0,
            'build'    => 0,
            'type'     => null,
            'title'    => null,
            'ends_at'  => 0,
        ];

        try {
            $stmt = $db->prepare("
                SELECT * FROM world_events
                WHERE status='active' AND ends_at>? LIMIT 1
            ");
            $stmt->execute([time()]);
            $event = $stmt->fetch();
            if (!$event) return $bonuses;

            $bonuses['type']    = $event['type'];
            $bonuses['title']   = $event['title'];
            $bonuses['ends_at'] = $event['ends_at'];

            // Бонус ресурсов
            $stmt2 = $db->prepare("SELECT value FROM game_config WHERE `key`='active_event_resource_bonus'");
            $stmt2->execute();
            $row = $stmt2->fetch();
            if ($row && !empty($row['value'])) {
                $parts = explode(':', $row['value']);
                if ((int)($parts[1]??0) > time()) {
                    $bonuses['resource'] = (float)($parts[0]??0);
                }
            }

            // Бонус строительства
            $stmt3 = $db->prepare("SELECT value FROM game_config WHERE `key`='active_event_build_bonus'");
            $stmt3->execute();
            $row = $stmt3->fetch();
            if ($row && !empty($row['value'])) {
                $parts = explode(':', $row['value']);
                if ((int)($parts[1]??0) > time()) {
                    $bonuses['build'] = (float)($parts[0]??0);
                }
            }
        } catch (Exception $e) {}

        return $bonuses;
    }

    // =========================================================
    // АВТОЗАПУСК ИЗ КРОНА
    // =========================================================
    public function cronCheck() {
        // Завершаем истёкшие
        try {
            $stmt = $this->db->query("
                SELECT id FROM world_events
                WHERE status='active' AND ends_at <= " . time()
            );
            foreach ($stmt->fetchAll() as $e) {
                $this->endEvent($e['id']);
            }
        } catch (Exception $e) {}

        // Запускаем новое если нужно
        $active = $this->getActiveEvent();
        if (!$active) {
            try {
                $stmt = $this->db->query("
                    SELECT MAX(ends_at) as last_end FROM world_events
                    WHERE status='ended'
                ");
                $last = (int)($stmt->fetch()['last_end'] ?? 0);
            } catch (Exception $e) {
                $last = 0;
            }

            // Пауза 48-72 часа
            $pause = rand(48, 72) * 3600;
            if (time() - $last >= $pause) {
                $this->startEvent();
            }
        }
    }
}