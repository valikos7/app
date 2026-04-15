<?php
// controllers/SeasonController.php

class SeasonController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА СЕЗОНА
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Текущий сезон
        $season = $this->getActiveSeason();

        // Рейтинг в сезоне
        $stmt = $this->db->query("
            SELECT u.id, u.username, u.points, u.villages,
                   pr.rank_id,
                   (SELECT COUNT(*) FROM village_captures WHERE to_user_id=u.id) as captures,
                   a.tag as alliance_tag
            FROM users u
            LEFT JOIN player_ranks pr ON pr.user_id=u.id
            LEFT JOIN alliance_members am ON am.user_id=u.id
            LEFT JOIN alliances a ON a.id=am.alliance_id
            WHERE u.points > 0
            ORDER BY u.points DESC
            LIMIT 50
        ");
        $season_leaders = $stmt->fetchAll();

        // Моя позиция
        $stmt = $this->db->prepare("
            SELECT COUNT(*)+1 as user_rank
            FROM users
            WHERE points > (SELECT points FROM users WHERE id=?)
        ");
        $stmt->execute([$user_id]);
        $my_position = $stmt->fetch()['user_rank'] ?? '?';

        // Статистика сезона
        $season_stats = [
            'total_players' => $this->db->query("SELECT COUNT(*) as c FROM users WHERE points>0")->fetch()['c']??0,
            'total_battles' => $this->db->query("SELECT COUNT(*) as c FROM reports WHERE type='attack'")->fetch()['c']??0,
            'total_villages'=> $this->db->query("SELECT COUNT(*) as c FROM villages WHERE userid>0")->fetch()['c']??0,
        ];

        // История сезонов
        $stmt = $this->db->query("SELECT * FROM seasons ORDER BY number DESC LIMIT 10");
        $season_history = $stmt->fetchAll();

        $all_ranks = RankController::getRanks();
        $db        = $this->db;
        require_once __DIR__ . '/../templates/season.php';
    }

    // =========================================================
    // ПОЛУЧИТЬ АКТИВНЫЙ СЕЗОН
    // =========================================================
    public function getActiveSeason() {
        try {
            $stmt = $this->db->query("SELECT * FROM seasons WHERE status='active' LIMIT 1");
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    // =========================================================
    // ЗАВЕРШИТЬ СЕЗОН (из крона)
    // =========================================================
    public function endSeasonIfNeeded() {
        try {
            $season = $this->getActiveSeason();
            if (!$season || $season['ends_at'] > time()) return;

            // Определяем победителя
            $stmt = $this->db->query("SELECT id, username, points FROM users ORDER BY points DESC LIMIT 1");
            $winner = $stmt->fetch();

            if ($winner) {
                // Помечаем сезон завершённым
                $this->db->prepare("UPDATE seasons SET status='ended', winner_id=?, winner_name=? WHERE id=?")
                    ->execute([$winner['id'], $winner['username'], $season['id']]);

                // Награда победителю
                $stmt = $this->db->prepare("SELECT id FROM villages WHERE userid=? LIMIT 1");
                $stmt->execute([$winner['id']]);
                $village = $stmt->fetch();

                if ($village) {
                    $this->db->prepare("UPDATE villages SET r_wood=r_wood+100000, r_stone=r_stone+100000, r_iron=r_iron+100000 WHERE id=?")
                        ->execute([$village['id']]);
                }

                // Уведомляем всех
                $stmt = $this->db->query("SELECT id FROM users WHERE last_activity>=" . (time()-30*86400));
                foreach ($stmt->fetchAll() as $u) {
                    $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
                        ->execute([
                            $u['id'],
                            "🏆 Сезон {$season['number']} завершён!",
                            "Сезон «{$season['name']}» завершился!\n\n" .
                            "🥇 Победитель: {$winner['username']} ({$winner['points']} очков)\n\n" .
                            "Начинается новый сезон. Мир сбрасывается!",
                            time()
                        ]);
                }

                // Запускаем новый сезон
                $this->startNewSeason($season['number'] + 1);
            }
        } catch (Exception $e) {
            error_log("endSeasonIfNeeded: " . $e->getMessage());
        }
    }

    // =========================================================
    // НАЧАТЬ НОВЫЙ СЕЗОН
    // =========================================================
    private function startNewSeason($number) {
        try {
            $season_duration = 90 * 86400; // 3 месяца

            $this->db->prepare("INSERT INTO seasons (number, name, started_at, ends_at, status)
                VALUES (?, ?, ?, ?, 'active')")
                ->execute([
                    $number,
                    "Сезон {$number}",
                    time(),
                    time() + $season_duration
                ]);

            // ОПЦИОНАЛЬНО: сброс очков (можно закомментировать)
            // $this->db->query("UPDATE users SET points=0");
            // $this->db->query("UPDATE villages SET points=0");

        } catch (Exception $e) {
            error_log("startNewSeason: " . $e->getMessage());
        }
    }
}