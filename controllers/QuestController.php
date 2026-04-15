<?php
// controllers/QuestController.php

class QuestController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА КВЕСТОВ
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $today   = date('Y-m-d');
        $week    = date('Y-W');

        // Инициализируем ежедневные квесты
        $this->initDailyQuests($user_id, $today);

        // Получаем все квесты с прогрессом
        $daily    = $this->getQuestsWithProgress($user_id, 'daily',    $today);
        $weekly   = $this->getQuestsWithProgress($user_id, 'weekly',   $this->getWeekStart());
        $tutorial = $this->getQuestsWithProgress($user_id, 'tutorial', '2000-01-01');

        // Считаем статистику
        $stats = [
            'daily_done'    => count(array_filter($daily,    fn($q) => $q['completed'])),
            'daily_total'   => count($daily),
            'weekly_done'   => count(array_filter($weekly,   fn($q) => $q['completed'])),
            'weekly_total'  => count($weekly),
            'tutorial_done' => count(array_filter($tutorial, fn($q) => $q['completed'])),
            'tutorial_total'=> count($tutorial),
        ];

        // Следующий сброс
        $next_reset = strtotime('tomorrow');
        $next_weekly = strtotime('next monday');

        $db = $this->db;
        require_once __DIR__ . '/../templates/quests.php';
    }

    // =========================================================
    // ПОЛУЧИТЬ НАГРАДУ
    // =========================================================
    public function claimReward() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id    = $_SESSION['user_id'];
        $quest_id   = (int)($_GET['quest_id']   ?? 0);
        $village_id = (int)($_GET['village_id'] ?? 0);

        // Получаем прогресс квеста
        $stmt = $this->db->prepare("
            SELECT qp.*, q.title, q.reward_wood, q.reward_stone,
                   q.reward_iron, q.reward_exp, q.type, q.icon
            FROM quest_progress qp
            JOIN quests q ON qp.quest_id = q.id
            WHERE qp.user_id = ? AND qp.quest_id = ?
            AND qp.completed = 1 AND qp.rewarded = 0
            LIMIT 1
        ");
        $stmt->execute([$user_id, $quest_id]);
        $progress = $stmt->fetch();

        if (!$progress) {
            $_SESSION['error'] = "Квест не завершён или награда уже получена!";
            header("Location: ?page=quests");
            exit;
        }

        // Проверяем деревню
        if ($village_id <= 0) {
            $stmt = $this->db->prepare("SELECT id FROM villages WHERE userid=? LIMIT 1");
            $stmt->execute([$user_id]);
            $v = $stmt->fetch();
            $village_id = $v ? $v['id'] : 0;
        }

        if ($village_id <= 0) {
            $_SESSION['error'] = "Нет деревни для получения награды!";
            header("Location: ?page=quests");
            exit;
        }

        // Проверяем деревню принадлежит игроку
        $stmt = $this->db->prepare("SELECT id FROM villages WHERE id=? AND userid=?");
        $stmt->execute([$village_id, $user_id]);
        if (!$stmt->fetch()) {
            $_SESSION['error'] = "Деревня не принадлежит вам!";
            header("Location: ?page=quests");
            exit;
        }

        // Начисляем ресурсы
        if ($progress['reward_wood'] + $progress['reward_stone'] + $progress['reward_iron'] > 0) {
            $this->db->prepare("UPDATE villages SET
                r_wood  = r_wood  + ?,
                r_stone = r_stone + ?,
                r_iron  = r_iron  + ?
                WHERE id = ?
            ")->execute([
                $progress['reward_wood'],
                $progress['reward_stone'],
                $progress['reward_iron'],
                $village_id
            ]);
        }

        // Опыт герою
        if ($progress['reward_exp'] > 0) {
            try {
                $hc = new HeroController($this->db);
                $hc->addExperience($user_id, $progress['reward_exp'], 'quest');
            } catch (Exception $e) {}
        }

        // Помечаем как выданный
        $this->db->prepare("UPDATE quest_progress SET rewarded=1 WHERE user_id=? AND quest_id=?")
            ->execute([$user_id, $quest_id]);

        $parts = [];
        if ($progress['reward_wood']  > 0) $parts[] = "🪵" . number_format($progress['reward_wood']);
        if ($progress['reward_stone'] > 0) $parts[] = "🪨" . number_format($progress['reward_stone']);
        if ($progress['reward_iron']  > 0) $parts[] = "⛏" . number_format($progress['reward_iron']);
        if ($progress['reward_exp']   > 0) $parts[] = "✨" . $progress['reward_exp'] . " опыта";

        $_SESSION['success'] = "🎁 Награда за «{$progress['icon']} {$progress['title']}»: " .
                               implode(' ', $parts);
        header("Location: ?page=quests");
        exit;
    }

    // =========================================================
    // ОБНОВИТЬ ПРОГРЕСС КВЕСТА (вызывается из других контроллеров)
    // =========================================================
    public function updateProgress($user_id, $code, $increment = 1) {
        try {
            $today     = date('Y-m-d');
            $week_start= $this->getWeekStart();

            // Ищем квесты с таким кодом
            $stmt = $this->db->prepare("
                SELECT * FROM quests WHERE code = ? AND is_active = 1
            ");
            $stmt->execute([$code]);
            $quests = $stmt->fetchAll();

            foreach ($quests as $quest) {
                // Определяем дату для этого типа квеста
                if ($quest['type'] === 'daily') {
                    $date = $today;
                } elseif ($quest['type'] === 'weekly') {
                    $date = $week_start;
                } else {
                    // tutorial — один раз навсегда
                    $date = '2000-01-01';
                }

                // Проверяем уже завершён
                $stmt2 = $this->db->prepare("
                    SELECT * FROM quest_progress
                    WHERE user_id=? AND quest_id=? AND date=?
                ");
                $stmt2->execute([$user_id, $quest['id'], $date]);
                $existing = $stmt2->fetch();

                if ($existing && $existing['completed']) continue;

                // Специальная логика для некоторых квестов
                $actual_increment = $increment;

                if ($existing) {
                    // Обновляем прогресс
                    $new_progress = $existing['progress'] + $actual_increment;
                    $completed    = ($new_progress >= $quest['goal']) ? 1 : 0;

                    $this->db->prepare("UPDATE quest_progress SET
                        progress=?, completed=?, updated_at=?
                        WHERE user_id=? AND quest_id=? AND date=?
                    ")->execute([$new_progress, $completed, time(), $user_id, $quest['id'], $date]);
                } else {
                    // Создаём запись
                    $new_progress = $actual_increment;
                    $completed    = ($new_progress >= $quest['goal']) ? 1 : 0;

                    try {
                        $this->db->prepare("INSERT INTO quest_progress
                            (user_id, quest_id, progress, completed, rewarded, date, updated_at)
                            VALUES (?,?,?,?,0,?,?)
                        ")->execute([$user_id, $quest['id'], $new_progress, $completed, $date, time()]);
                    } catch (Exception $e) {}
                }

                // Уведомление о завершении
                if ($completed) {
                    $this->sendQuestComplete($user_id, $quest);
                }
            }
        } catch (Exception $e) {
            error_log("QuestController::updateProgress: " . $e->getMessage());
        }
    }

    // =========================================================
    // ПРОВЕРКА КВЕСТОВ ОЧКОВ (для tutorial)
    // =========================================================
    public function checkPointsQuest($user_id, $points) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM quests WHERE code='reach_1000' AND is_active=1
            ");
            $stmt->execute();
            $quest = $stmt->fetch();
            if (!$quest) return;

            if ($points >= $quest['goal']) {
                $this->updateProgress($user_id, 'reach_1000', $points);
            }
        } catch (Exception $e) {}
    }

    // =========================================================
    // ВСПОМОГАТЕЛЬНЫЕ
    // =========================================================
    private function initDailyQuests($user_id, $today) {
        try {
            // Проверяем инициализированы ли ежедневные квесты
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt FROM quest_progress
                WHERE user_id=? AND date=?
            ");
            $stmt->execute([$user_id, $today]);
            if ($stmt->fetch()['cnt'] > 0) return;

            // Создаём записи для ежедневных квестов
            $stmt = $this->db->prepare("SELECT id FROM quests WHERE type='daily' AND is_active=1");
            $stmt->execute();
            $quests = $stmt->fetchAll();

            foreach ($quests as $q) {
                try {
                    $this->db->prepare("INSERT IGNORE INTO quest_progress
                        (user_id, quest_id, progress, completed, rewarded, date, updated_at)
                        VALUES (?,?,0,0,0,?,?)
                    ")->execute([$user_id, $q['id'], $today, time()]);
                } catch (Exception $e) {}
            }
        } catch (Exception $e) {}
    }

    private function getQuestsWithProgress($user_id, $type, $date) {
        try {
            $stmt = $this->db->prepare("
                SELECT q.*,
                       COALESCE(qp.progress, 0)   as progress,
                       COALESCE(qp.completed, 0)  as completed,
                       COALESCE(qp.rewarded, 0)   as rewarded
                FROM quests q
                LEFT JOIN quest_progress qp
                    ON qp.quest_id = q.id
                    AND qp.user_id = ?
                    AND qp.date   = ?
                WHERE q.type = ? AND q.is_active = 1
                ORDER BY q.sort_order ASC, q.id ASC
            ");
            $stmt->execute([$user_id, $date, $type]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    private function getWeekStart() {
        $day = date('N'); // 1=Mon, 7=Sun
        return date('Y-m-d', strtotime('-' . ($day-1) . ' days'));
    }

    private function sendQuestComplete($user_id, $quest) {
        try {
            $this->db->prepare("INSERT INTO reports
                (userid, type, title, content, time, is_read)
                VALUES (?, 'system', ?, ?, ?, 0)")
                ->execute([
                    $user_id,
                    "✅ Квест выполнен: {$quest['icon']} {$quest['title']}",
                    "Квест «{$quest['title']}» выполнен!\n\n" .
                    "Награда:\n" .
                    ($quest['reward_wood']  > 0 ? "🪵 Дерево: " . number_format($quest['reward_wood'])  . "\n" : '') .
                    ($quest['reward_stone'] > 0 ? "🪨 Камень: " . number_format($quest['reward_stone']) . "\n" : '') .
                    ($quest['reward_iron']  > 0 ? "⛏ Железо: " . number_format($quest['reward_iron'])  . "\n" : '') .
                    ($quest['reward_exp']   > 0 ? "✨ Опыт героя: " . $quest['reward_exp'] . "\n" : '') .
                    "\nЗаберите награду в разделе «Квесты»!",
                    time()
                ]);
        } catch (Exception $e) {}
    }

    // =========================================================
    // СТАТИЧЕСКИЕ МЕТОДЫ ДЛЯ ВЫЗОВА ИЗ ДРУГИХ МЕСТ
    // =========================================================
    public static function trigger($db, $user_id, $code, $increment = 1) {
        try {
            $qc = new self($db);
            $qc->updateProgress($user_id, $code, $increment);
        } catch (Exception $e) {}
    }
}