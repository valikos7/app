<?php
// controllers/AchievementController.php

class AchievementController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА ДОСТИЖЕНИЙ
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Все достижения
        $stmt = $this->db->query("SELECT * FROM achievements WHERE is_active=1 ORDER BY category, points ASC");
        $all_achievements = $stmt->fetchAll();

        // Достижения игрока
        $stmt = $this->db->prepare("SELECT achievement_code, unlocked_at FROM player_achievements WHERE user_id=?");
        $stmt->execute([$user_id]);
        $unlocked = [];
        foreach ($stmt->fetchAll() as $row) {
            $unlocked[$row['achievement_code']] = $row['unlocked_at'];
        }

        // Группируем
        $categories = [
            'general'  => ['name'=>'🌍 Общие',     'items'=>[]],
            'battle'   => ['name'=>'⚔ Боевые',     'items'=>[]],
            'economy'  => ['name'=>'💰 Экономика',  'items'=>[]],
            'hero'     => ['name'=>'🦸 Герой',      'items'=>[]],
            'rank'     => ['name'=>'🏆 Ранги',      'items'=>[]],
            'social'   => ['name'=>'🤝 Социальные', 'items'=>[]],
        ];

        foreach ($all_achievements as $ach) {
            $ach['unlocked']    = isset($unlocked[$ach['code']]);
            $ach['unlocked_at'] = $unlocked[$ach['code']] ?? null;
            $cat = $ach['category'] ?? 'general';
            if (isset($categories[$cat])) {
                $categories[$cat]['items'][] = $ach;
            }
        }

        // Статистика
        $total    = count($all_achievements);
        $done     = count($unlocked);
        $total_pts= 0; $earned_pts = 0;
        foreach ($all_achievements as $ach) {
            $total_pts += $ach['points'];
            if (isset($unlocked[$ach['code']])) $earned_pts += $ach['points'];
        }

        // Топ по достижениям
        $stmt = $this->db->query("
            SELECT u.id, u.username, COUNT(pa.id) as ach_count,
                   SUM(a.points) as ach_points
            FROM users u
            JOIN player_achievements pa ON pa.user_id=u.id
            JOIN achievements a ON a.code=pa.achievement_code
            GROUP BY u.id
            ORDER BY ach_points DESC, ach_count DESC
            LIMIT 10
        ");
        $leaders = $stmt->fetchAll();

        $db = $this->db;
        require_once __DIR__ . '/../templates/achievements.php';
    }

    // =========================================================
    // ПРОВЕРИТЬ И ВЫДАТЬ ДОСТИЖЕНИЯ
    // =========================================================
    public function checkAndGrant($user_id) {
        try {
            $stmt = $this->db->query("SELECT * FROM achievements WHERE is_active=1");
            $achievements = $stmt->fetchAll();

            // Уже полученные
            $stmt = $this->db->prepare("SELECT achievement_code FROM player_achievements WHERE user_id=?");
            $stmt->execute([$user_id]);
            $already = array_column($stmt->fetchAll(), 'achievement_code');

            // Статистика пользователя
            $stats = $this->getUserStats($user_id);

            foreach ($achievements as $ach) {
                if (in_array($ach['code'], $already)) continue;

                $value = $stats[$ach['condition']] ?? 0;
                if ($value >= $ach['threshold']) {
                    $this->grantAchievement($user_id, $ach);
                }
            }
        } catch (Exception $e) {
            error_log("checkAndGrant: " . $e->getMessage());
        }
    }

    // =========================================================
    // ВЫДАТЬ ДОСТИЖЕНИЕ
    // =========================================================
    private function grantAchievement($user_id, $ach) {
        try {
            $this->db->prepare("INSERT IGNORE INTO player_achievements
                (user_id, achievement_code, unlocked_at) VALUES (?,?,?)")
                ->execute([$user_id, $ach['code'], time()]);

            // Уведомление
            $this->db->prepare("INSERT INTO reports
                (userid, type, title, content, time, is_read)
                VALUES (?, 'system', ?, ?, ?, 0)")
                ->execute([
                    $user_id,
                    "🏅 Достижение разблокировано: {$ach['icon']} {$ach['name']}",
                    "Поздравляем! Вы разблокировали достижение:\n\n" .
                    "{$ach['icon']} **{$ach['name']}**\n" .
                    "{$ach['description']}\n\n" .
                    "+{$ach['points']} очков достижений!",
                    time()
                ]);
        } catch (Exception $e) {}
    }

    // =========================================================
    // СТАТИСТИКА ДЛЯ ПРОВЕРКИ
    // =========================================================
    private function getUserStats($user_id) {
        $stats = [];

        try {
            // Деревни
            $stmt=$this->db->prepare("SELECT COUNT(*) as c FROM villages WHERE userid=?");
            $stmt->execute([$user_id]);
            $stats['villages'] = (int)$stmt->fetch()['c'];

            // Статы боёв
            $stmt=$this->db->prepare("SELECT * FROM player_stats WHERE user_id=?");
            $stmt->execute([$user_id]);
            $ps=$stmt->fetch()?:[];
            $stats['attacks_won']       = (int)($ps['attacks_won']??0);
            $stats['resources_looted']  = (int)($ps['resources_looted']??0);
            $stats['spies_success']     = (int)($ps['spies_success']??0);
            $stats['buildings_built']   = (int)($ps['buildings_built']??0);
            $stats['units_trained']     = (int)($ps['units_trained']??0);

            // Ранг
            $stmt=$this->db->prepare("SELECT rank_id FROM player_ranks WHERE user_id=?");
            $stmt->execute([$user_id]);
            $rrow=$stmt->fetch();
            $stats['rank_id'] = (int)($rrow['rank_id']??1);

            // Герой
            $stmt=$this->db->prepare("SELECT level FROM heroes WHERE user_id=?");
            $stmt->execute([$user_id]);
            $hrow=$stmt->fetch();
            $stats['hero_level'] = (int)($hrow['level']??0);

            // Альянс
            $stmt=$this->db->prepare("SELECT id FROM alliance_members WHERE user_id=?");
            $stmt->execute([$user_id]);
            $stats['in_alliance'] = $stmt->fetch() ? 1 : 0;

            // Захваты
            $stmt=$this->db->prepare("SELECT COUNT(*) as c FROM village_captures WHERE to_user_id=?");
            $stmt->execute([$user_id]);
            $stats['captures'] = (int)($stmt->fetch()['c']??0);

            // Технологии
            $stmt=$this->db->prepare("SELECT COUNT(*) as c FROM player_technologies WHERE user_id=? AND level>0");
            $stmt->execute([$user_id]);
            $stats['technologies'] = (int)($stmt->fetch()['c']??0);

            // Квесты
            $stmt=$this->db->prepare("SELECT COUNT(*) as c FROM quest_progress WHERE user_id=? AND completed=1");
            $stmt->execute([$user_id]);
            $stats['quests_done'] = (int)($stmt->fetch()['c']??0);

            // Победы в сезоне (пока 0)
            $stats['season_wins'] = 0;

        } catch (Exception $e) {
            error_log("getUserStats: " . $e->getMessage());
        }

        return $stats;
    }

    // Статический вызов
    public static function check($db, $user_id) {
        try {
            $ac = new self($db);
            $ac->checkAndGrant($user_id);
        } catch (Exception $e) {}
    }
}
