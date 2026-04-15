<?php
// controllers/AllianceWarController.php

class AllianceWarController {
    private $db;

    // Длительность войны — 7 дней
    private const WAR_DURATION = 7 * 86400;

    // Очки за действия
    private const SCORE_ATTACK_WIN  = 10;
    private const SCORE_ATTACK_BARB = 2;
    private const SCORE_DEFEND_WIN  = 5;
    private const SCORE_CAPTURE     = 50;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА ВОЙН АЛЬЯНСА
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Альянс пользователя
        $stmt = $this->db->prepare("
            SELECT am.*, a.name as alliance_name, a.tag, a.leader_id, a.points
            FROM alliance_members am
            JOIN alliances a ON am.alliance_id=a.id
            WHERE am.user_id=?
        ");
        $stmt->execute([$user_id]);
        $my_membership = $stmt->fetch();

        if (!$my_membership) {
            $_SESSION['error'] = "Вы не состоите в альянсе!";
            header("Location: ?page=alliances");
            exit;
        }

        $alliance_id = $my_membership['alliance_id'];
        $my_role     = $my_membership['role'];

        // Активные войны
        $stmt = $this->db->prepare("
            SELECT aw.*,
                   a1.name as att_name, a1.tag as att_tag,
                   a2.name as def_name, a2.tag as def_tag
            FROM alliance_wars aw
            JOIN alliances a1 ON aw.attacker_id=a1.id
            JOIN alliances a2 ON aw.defender_id=a2.id
            WHERE (aw.attacker_id=? OR aw.defender_id=?)
            AND aw.status='active'
            ORDER BY aw.started_at DESC
        ");
        $stmt->execute([$alliance_id, $alliance_id]);
        $active_wars = $stmt->fetchAll();

        // Завершённые войны
        $stmt = $this->db->prepare("
            SELECT aw.*,
                   a1.name as att_name, a1.tag as att_tag,
                   a2.name as def_name, a2.tag as def_tag
            FROM alliance_wars aw
            JOIN alliances a1 ON aw.attacker_id=a1.id
            JOIN alliances a2 ON aw.defender_id=a2.id
            WHERE (aw.attacker_id=? OR aw.defender_id=?)
            AND aw.status='ended'
            ORDER BY aw.ends_at DESC LIMIT 10
        ");
        $stmt->execute([$alliance_id, $alliance_id]);
        $ended_wars = $stmt->fetchAll();

        // Все альянсы для объявления войны
        $stmt = $this->db->query("SELECT id,name,tag,points,members_count FROM alliances ORDER BY points DESC LIMIT 30");
        $all_alliances = $stmt->fetchAll();

        $db = $this->db;
        require_once __DIR__ . '/../templates/alliance_wars.php';
    }

    // =========================================================
    // ОБЪЯВИТЬ ВОЙНУ
    // =========================================================
    public function declareWar() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id     = $_SESSION['user_id'];
        $defender_id = (int)($_POST['target_alliance_id'] ?? 0);

        // Проверяем права
        $stmt = $this->db->prepare("SELECT am.role, am.alliance_id FROM alliance_members am WHERE am.user_id=?");
        $stmt->execute([$user_id]);
        $membership = $stmt->fetch();

        if (!$membership || !in_array($membership['role'], ['leader','officer'])) {
            $_SESSION['error'] = "Только лидер или офицер может объявить войну!";
            header("Location: ?page=alliance_wars");
            exit;
        }

        $attacker_id = $membership['alliance_id'];

        if ($attacker_id === $defender_id) {
            $_SESSION['error'] = "Нельзя объявить войну своему альянсу!";
            header("Location: ?page=alliance_wars");
            exit;
        }

        // Проверяем нет ли уже активной войны между ними
        $stmt = $this->db->prepare("
            SELECT id FROM alliance_wars
            WHERE ((attacker_id=? AND defender_id=?) OR (attacker_id=? AND defender_id=?))
            AND status='active'
        ");
        $stmt->execute([$attacker_id,$defender_id,$defender_id,$attacker_id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Война с этим альянсом уже идёт!";
            header("Location: ?page=alliance_wars");
            exit;
        }

        // Проверяем существование цели
        $stmt = $this->db->prepare("SELECT * FROM alliances WHERE id=?");
        $stmt->execute([$defender_id]);
        $target_alliance = $stmt->fetch();
        if (!$target_alliance) {
            $_SESSION['error'] = "Альянс не найден!";
            header("Location: ?page=alliance_wars");
            exit;
        }

        // Объявляем войну
        $this->db->prepare("INSERT INTO alliance_wars
            (attacker_id, defender_id, started_at, ends_at, status, att_score, def_score)
            VALUES (?,?,?,?,'active',0,0)")
            ->execute([$attacker_id, $defender_id, time(), time() + self::WAR_DURATION]);

        $war_id = $this->db->lastInsertId();

        // Уведомляем оба альянса
        $this->notifyAlliance($attacker_id,
            "⚔ Война объявлена!",
            "Ваш альянс объявил войну [{$target_alliance['tag']}] {$target_alliance['name']}!\nВойна продлится 7 дней.",
            $war_id
        );
        $this->notifyAlliance($defender_id,
            "⚔ Вам объявили войну!",
            "Альянс объявил вам войну!\nВойна продлится 7 дней. Защищайтесь!",
            $war_id
        );

        // Дипломатия — если был мир, отменяем
        try {
            $this->db->prepare("UPDATE alliance_diplomacy SET status='rejected'
                WHERE ((alliance_id=? AND target_id=?) OR (alliance_id=? AND target_id=?))
                AND type IN ('ally','nap') AND status='active'")
                ->execute([$attacker_id,$defender_id,$defender_id,$attacker_id]);
        } catch (Exception $e) {}

        $_SESSION['success'] = "⚔ Война объявлена альянсу [{$target_alliance['tag']}] {$target_alliance['name']}!<br>Война продлится 7 дней.";
        header("Location: ?page=alliance_wars");
        exit;
    }

    // =========================================================
    // ДОБАВИТЬ ОЧКИ ВОЙНЫ
    // =========================================================
    public function addWarScore($user_id, $alliance_id, $score, $reason = 'attack') {
        try {
            // Ищем активную войну
            $stmt = $this->db->prepare("
                SELECT * FROM alliance_wars
                WHERE (attacker_id=? OR defender_id=?) AND status='active'
                LIMIT 1
            ");
            $stmt->execute([$alliance_id, $alliance_id]);
            $war = $stmt->fetch();
            if (!$war) return;

            // Определяем сторону
            $is_attacker = ($war['attacker_id'] === $alliance_id);
            $score_field = $is_attacker ? 'att_score' : 'def_score';

            $this->db->prepare("UPDATE alliance_wars SET {$score_field}={$score_field}+? WHERE id=?")
                ->execute([$score, $war['id']]);

            $this->db->prepare("INSERT INTO war_score_log (war_id,user_id,alliance_id,score,reason,time)
                VALUES (?,?,?,?,?,?)")
                ->execute([$war['id'],$user_id,$alliance_id,$score,$reason,time()]);

        } catch (Exception $e) {
            error_log("addWarScore: " . $e->getMessage());
        }
    }

    // =========================================================
    // ЗАВЕРШИТЬ ВОЙНЫ (из крона)
    // =========================================================
    public function endExpiredWars() {
        try {
            $stmt = $this->db->query("SELECT * FROM alliance_wars WHERE status='active' AND ends_at<=".time());
            $wars = $stmt->fetchAll();

            foreach ($wars as $war) {
                $att_score = (int)$war['att_score'];
                $def_score = (int)$war['def_score'];
                $winner_id = null;

                if ($att_score > $def_score) $winner_id = $war['attacker_id'];
                elseif ($def_score > $att_score) $winner_id = $war['defender_id'];
                // иначе ничья

                $this->db->prepare("UPDATE alliance_wars SET status='ended', winner_id=? WHERE id=?")
                    ->execute([$winner_id, $war['id']]);

                // Получаем имена
                $stmt2 = $this->db->prepare("SELECT name,tag FROM alliances WHERE id=?");
                $stmt2->execute([$war['attacker_id']]);
                $att = $stmt2->fetch();
                $stmt2->execute([$war['defender_id']]);
                $def = $stmt2->fetch();

                if ($winner_id) {
                    $stmt3 = $this->db->prepare("SELECT name,tag FROM alliances WHERE id=?");
                    $stmt3->execute([$winner_id]);
                    $winner = $stmt3->fetch();
                    $result_text = "🏆 Победитель: [{$winner['tag']}] {$winner['name']}!\n";
                } else {
                    $result_text = "🤝 Ничья!\n";
                }

                $msg = "⚔ Война между [{$att['tag']}] {$att['name']} и [{$def['tag']}] {$def['name']} завершилась!\n\n".
                       "Счёт: {$att['tag']} {$att_score} : {$def_score} {$def['tag']}\n".
                       $result_text;

                $this->notifyAlliance($war['attacker_id'], "⚔ Война завершена!", $msg, $war['id']);
                $this->notifyAlliance($war['defender_id'], "⚔ Война завершена!", $msg, $war['id']);
            }
        } catch (Exception $e) {
            error_log("endExpiredWars: " . $e->getMessage());
        }
    }

    // =========================================================
    // УВЕДОМИТЬ ВСЕХ ЧЛЕНОВ АЛЬЯНСА
    // =========================================================
    private function notifyAlliance($alliance_id, $title, $content, $war_id = null) {
        try {
            $stmt = $this->db->prepare("SELECT user_id FROM alliance_members WHERE alliance_id=?");
            $stmt->execute([$alliance_id]);
            foreach ($stmt->fetchAll() as $m) {
                $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'system',?,?,?,0)")
                    ->execute([$m['user_id'], $title, $content, time()]);
            }
        } catch (Exception $e) {}
    }

    // =========================================================
    // ПОЛУЧИТЬ ТЕКУЩУЮ ВОЙНУ ДЛЯ АЛЬЯНСА
    // =========================================================
    public function getActiveWar($alliance_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT aw.*,
                       a1.name as att_name, a1.tag as att_tag,
                       a2.name as def_name, a2.tag as def_tag
                FROM alliance_wars aw
                JOIN alliances a1 ON aw.attacker_id=a1.id
                JOIN alliances a2 ON aw.defender_id=a2.id
                WHERE (aw.attacker_id=? OR aw.defender_id=?) AND aw.status='active'
                LIMIT 1
            ");
            $stmt->execute([$alliance_id, $alliance_id]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}