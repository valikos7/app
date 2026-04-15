<?php
// controllers/RankController.php

class RankController {
    private $db;

    private static $ranks = [
        1  => ['name'=>'Рекрут',     'icon'=>'🥉', 'min_points'=>0,     'color'=>'#888',   'bonus'=>[]],
        2  => ['name'=>'Солдат',     'icon'=>'⚔',  'min_points'=>500,   'color'=>'#aaa',   'bonus'=>['production'=>2]],
        3  => ['name'=>'Воин',       'icon'=>'🗡',  'min_points'=>2000,  'color'=>'#4f4',   'bonus'=>['production'=>3,'attack'=>2]],
        4  => ['name'=>'Ветеран',    'icon'=>'🛡',  'min_points'=>5000,  'color'=>'#4af',   'bonus'=>['production'=>5,'attack'=>3,'defense'=>2]],
        5  => ['name'=>'Сержант',    'icon'=>'🎖',  'min_points'=>10000, 'color'=>'#88f',   'bonus'=>['production'=>7,'attack'=>5,'defense'=>3]],
        6  => ['name'=>'Капитан',    'icon'=>'⭐',  'min_points'=>20000, 'color'=>'#d4a843','bonus'=>['production'=>10,'attack'=>7,'defense'=>5,'speed'=>3]],
        7  => ['name'=>'Майор',      'icon'=>'🌟',  'min_points'=>35000, 'color'=>'#fa4',   'bonus'=>['production'=>12,'attack'=>10,'defense'=>7,'speed'=>5]],
        8  => ['name'=>'Полковник',  'icon'=>'💫',  'min_points'=>60000, 'color'=>'#f84',   'bonus'=>['production'=>15,'attack'=>12,'defense'=>10,'speed'=>7]],
        9  => ['name'=>'Генерал',    'icon'=>'👑',  'min_points'=>100000,'color'=>'#f44',   'bonus'=>['production'=>20,'attack'=>15,'defense'=>12,'speed'=>10]],
        10 => ['name'=>'Маршал',     'icon'=>'💎',  'min_points'=>200000,'color'=>'#e0f',   'bonus'=>['production'=>25,'attack'=>20,'defense'=>15,'speed'=>15]],
    ];

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА РАНГОВ
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $user_id = $_SESSION['user_id'];

        $rank_data    = $this->getOrCreateRank($user_id);
        $current_rank = self::$ranks[$rank_data['rank_id']] ?? self::$ranks[1];
        $next_rank_id = $rank_data['rank_id'] + 1;
        $next_rank    = self::$ranks[$next_rank_id] ?? null;

        $stmt=$this->db->prepare("SELECT points FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        $user=$stmt->fetch();
        $points=(int)($user['points']??0);

        $this->updateRank($user_id,$points);
        $rank_data    = $this->getOrCreateRank($user_id);
        $current_rank = self::$ranks[$rank_data['rank_id']] ?? self::$ranks[1];
        $next_rank_id = $rank_data['rank_id'] + 1;
        $next_rank    = self::$ranks[$next_rank_id] ?? null;

        $progress_pct = 0;
        if ($next_rank) {
            $current_min = $current_rank['min_points'];
            $next_min    = $next_rank['min_points'];
            $range       = $next_min - $current_min;
            $progress    = $points - $current_min;
            $progress_pct= $range > 0 ? min(100, round(($progress / $range) * 100)) : 100;
        }

        $stmt=$this->db->query("
            SELECT pr.*, u.username, u.points, u.villages
            FROM player_ranks pr JOIN users u ON pr.user_id=u.id
            ORDER BY pr.rank_id DESC, u.points DESC LIMIT 20
        ");
        $rank_leaders = $stmt->fetchAll();

        $all_ranks = self::$ranks;
        $db        = $this->db;
        require_once __DIR__ . '/../templates/ranks.php';
    }

    // =========================================================
    // ПОЛУЧИТЬ ИЛИ СОЗДАТЬ РАНГ
    // =========================================================
    public function getOrCreateRank($user_id) {
        try {
            $stmt=$this->db->prepare("SELECT * FROM player_ranks WHERE user_id=?");
            $stmt->execute([$user_id]);
            $rank=$stmt->fetch();
            if (!$rank) {
                $this->db->prepare("INSERT INTO player_ranks (user_id,rank_id,rank_points,updated_at) VALUES (?,1,0,?)")
                    ->execute([$user_id,time()]);
                return ['user_id'=>$user_id,'rank_id'=>1,'rank_points'=>0];
            }
            return $rank;
        } catch (Exception $e) {
            return ['user_id'=>$user_id,'rank_id'=>1,'rank_points'=>0];
        }
    }

    // =========================================================
    // ОБНОВИТЬ РАНГ
    // =========================================================
    public function updateRank($user_id,$points) {
        try {
            $new_rank_id = 1;
            foreach (self::$ranks as $rank_id=>$rank) {
                if ($points >= $rank['min_points']) $new_rank_id=$rank_id;
            }

            $old_data    = $this->getOrCreateRank($user_id);
            $old_rank_id = (int)($old_data['rank_id']??1);

            $this->db->prepare("INSERT INTO player_ranks (user_id,rank_id,rank_points,updated_at)
                VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE rank_id=VALUES(rank_id),rank_points=VALUES(rank_points),updated_at=VALUES(updated_at)")
                ->execute([$user_id,$new_rank_id,$points,time()]);

            // Уведомление о новом ранге
            if ($new_rank_id > $old_rank_id) {
                $new_rank=self::$ranks[$new_rank_id];
                $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
                    ->execute([
                        $user_id,
                        "{$new_rank['icon']} Новый ранг: {$new_rank['name']}!",
                        "Поздравляем! Вы получили ранг «{$new_rank['name']}»!\n\n".
                        "Бонусы ранга:\n".$this->formatBonuses($new_rank['bonus']).
                        "\nПродолжайте развиваться!",
                        time()
                    ]);

                // Достижения за ранг
                try { AchievementController::check($this->db,$user_id); } catch (Exception $e) {}
            }
        } catch (Exception $e) {
            error_log("updateRank: ".$e->getMessage());
        }
    }

    // =========================================================
    // ПОЛУЧИТЬ БОНУСЫ РАНГА (статический)
    // =========================================================
    public static function getRankBonuses($db,$user_id) {
        try {
            $stmt=$db->prepare("SELECT rank_id FROM player_ranks WHERE user_id=?");
            $stmt->execute([$user_id]);
            $row=$stmt->fetch();
            if (!$row) return [];
            $rank=self::$ranks[(int)$row['rank_id']]??self::$ranks[1];
            return $rank['bonus']??[];
        } catch (Exception $e) { return []; }
    }

    public static function getRanks() {
        return self::$ranks;
    }

    private function formatBonuses($bonuses) {
        if (empty($bonuses)) return "Нет бонусов\n";
        $names=['production'=>'Производство','attack'=>'Атака','defense'=>'Защита','speed'=>'Скорость'];
        $result='';
        foreach ($bonuses as $key=>$val) {
            $result.="• ".($names[$key]??$key).": +{$val}%\n";
        }
        return $result;
    }
}