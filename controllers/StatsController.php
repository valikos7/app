<?php
// controllers/StatsController.php

class StatsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА СТАТИСТИКИ ИГРОКА
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $player_id = (int)($_GET['id'] ?? $_SESSION['user_id']);

        // Данные игрока
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$player_id]);
        $player = $stmt->fetch();

        if (!$player) {
            $_SESSION['error'] = "Игрок не найден.";
            header("Location: ?page=ranking");
            exit;
        }

        $is_own = ($player_id == $_SESSION['user_id']);

        // Статистика боёв
        $stmt = $this->db->prepare("
            SELECT * FROM player_stats WHERE user_id = ?
        ");
        $stmt->execute([$player_id]);
        $stats = $stmt->fetch() ?: [
            'attacks_sent'     => 0, 'attacks_won'      => 0,
            'attacks_lost'     => 0, 'defenses_total'   => 0,
            'defenses_won'     => 0, 'defenses_lost'    => 0,
            'spies_sent'       => 0, 'spies_success'    => 0,
            'resources_looted' => 0, 'resources_lost'   => 0,
            'units_trained'    => 0, 'units_lost'       => 0,
            'buildings_built'  => 0
        ];

        // История очков для графика
        $stmt = $this->db->prepare("
            SELECT points, villages, recorded_at
            FROM points_history
            WHERE user_id = ?
            ORDER BY recorded_at ASC
            LIMIT 30
        ");
        $stmt->execute([$player_id]);
        $points_history = $stmt->fetchAll();

        // Если истории мало — добавляем текущую точку
        if (empty($points_history)) {
            $points_history = [[
                'points'      => $player['points'],
                'villages'    => $player['villages'],
                'recorded_at' => time()
            ]];
        }

        // Деревни игрока
        $stmt = $this->db->prepare("
            SELECT id, name, x, y, points, continent
            FROM villages WHERE userid = ?
            ORDER BY points DESC
        ");
        $stmt->execute([$player_id]);
        $villages = $stmt->fetchAll();

        // Альянс
        $alliance = null;
        if ($player['alliance_id']) {
            $stmt = $this->db->prepare("SELECT * FROM alliances WHERE id = ?");
            $stmt->execute([$player['alliance_id']]);
            $alliance = $stmt->fetch();
        }

        // Позиция в рейтинге
        $stmt = $this->db->prepare("
            SELECT COUNT(*) + 1 as user_rank FROM users
            WHERE points > (SELECT points FROM users WHERE id = ?)
        ");
        $stmt->execute([$player_id]);
        $rank = $stmt->fetch()['user_rank'] ?? '?';

        // Отчёты о боях (последние)
        $stmt = $this->db->prepare("
            SELECT * FROM reports
            WHERE userid = ?
            ORDER BY time DESC LIMIT 10
        ");
        $stmt->execute([$player_id]);
        $recent_reports = $stmt->fetchAll();

        // Считаем достижения
        $achievements = $this->getAchievements($player, $stats);

        $db = $this->db;
        require_once __DIR__ . '/../templates/stats.php';
    }

    // =========================================================
    // ЗАПИСАТЬ ИСТОРИЮ ОЧКОВ (вызывается из крона)
    // =========================================================
    public function recordHistory($db) {
        try {
            $stmt = $db->query("SELECT id, points, villages FROM users WHERE points > 0");
            $users = $stmt->fetchAll();

            foreach ($users as $u) {
                $db->prepare("INSERT INTO points_history (user_id, points, villages, recorded_at)
                    VALUES (?, ?, ?, ?)")
                    ->execute([$u['id'], $u['points'], $u['villages'], time()]);
            }

            // Чистим старую историю (оставляем 30 записей на игрока)
            $db->query("
                DELETE ph FROM points_history ph
                LEFT JOIN (
                    SELECT id FROM points_history
                    ORDER BY recorded_at DESC
                    LIMIT 30
                ) keep_ids ON ph.id = keep_ids.id
                WHERE keep_ids.id IS NULL
            ");
        } catch (Exception $e) {}
    }

    // =========================================================
    // ДОСТИЖЕНИЯ
    // =========================================================
    private function getAchievements($player, $stats) {
        $achievements = [];

        // По очкам
        $points_levels = [
            100    => ['🌱', 'Новичок',    'Набрал 100 очков'],
            500    => ['⚔',  'Воин',       'Набрал 500 очков'],
            1000   => ['🛡',  'Защитник',   'Набрал 1000 очков'],
            5000   => ['👑',  'Правитель',  'Набрал 5000 очков'],
            10000  => ['🏆',  'Король',     'Набрал 10000 очков'],
            50000  => ['💎',  'Легенда',    'Набрал 50000 очков'],
        ];
        foreach ($points_levels as $required => $ach) {
            if (($player['points'] ?? 0) >= $required) {
                $achievements[] = ['icon'=>$ach[0],'title'=>$ach[1],'desc'=>$ach[2],'done'=>true];
            }
        }

        // По деревням
        if (($player['villages'] ?? 0) >= 2)
            $achievements[] = ['icon'=>'🏘','title'=>'Колонист','desc'=>'2+ деревни','done'=>true];
        if (($player['villages'] ?? 0) >= 5)
            $achievements[] = ['icon'=>'🏰','title'=>'Правитель','desc'=>'5+ деревень','done'=>true];

        // По атакам
        if (($stats['attacks_won'] ?? 0) >= 1)
            $achievements[] = ['icon'=>'⚔','title'=>'Первая кровь','desc'=>'Первая победа в бою','done'=>true];
        if (($stats['attacks_won'] ?? 0) >= 10)
            $achievements[] = ['icon'=>'🗡','title'=>'Завоеватель','desc'=>'10 побед в атаке','done'=>true];
        if (($stats['attacks_won'] ?? 0) >= 50)
            $achievements[] = ['icon'=>'⚔','title'=>'Генерал','desc'=>'50 побед в атаке','done'=>true];

        // По защите
        if (($stats['defenses_won'] ?? 0) >= 1)
            $achievements[] = ['icon'=>'🛡','title'=>'Страж','desc'=>'Первая успешная защита','done'=>true];
        if (($stats['defenses_won'] ?? 0) >= 10)
            $achievements[] = ['icon'=>'🏰','title'=>'Крепость','desc'=>'10 успешных защит','done'=>true];

        // По шпионажу
        if (($stats['spies_success'] ?? 0) >= 1)
            $achievements[] = ['icon'=>'🔍','title'=>'Шпион','desc'=>'Первая успешная разведка','done'=>true];
        if (($stats['spies_success'] ?? 0) >= 10)
            $achievements[] = ['icon'=>'🕵','title'=>'Мастер шпионажа','desc'=>'10 успешных разведок','done'=>true];

        // По граблежу
        if (($stats['resources_looted'] ?? 0) >= 10000)
            $achievements[] = ['icon'=>'💰','title'=>'Грабитель','desc'=>'10000+ украдено ресурсов','done'=>true];
        if (($stats['resources_looted'] ?? 0) >= 100000)
            $achievements[] = ['icon'=>'💎','title'=>'Мародёр','desc'=>'100000+ украдено ресурсов','done'=>true];

        // По альянсу
        if (!empty($player['alliance_id']))
            $achievements[] = ['icon'=>'🤝','title'=>'Союзник','desc'=>'Вступил в альянс','done'=>true];

        return $achievements;
    }
}