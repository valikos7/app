<?php
// controllers/SupportController.php

class SupportController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СПИСОК ВСЕЙ ПОДДЕРЖКИ ИГРОКА
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Поддержка которую я отправил другим
        $stmt = $this->db->prepare("
            SELECT
                tm.*,
                v1.name as from_name, v1.x as fx, v1.y as fy,
                v2.name as to_name,   v2.x as tx, v2.y as ty,
                u2.username as to_owner
            FROM troop_movements tm
            LEFT JOIN villages v1 ON tm.from_village_id = v1.id
            LEFT JOIN villages v2 ON tm.to_village_id   = v2.id
            LEFT JOIN users    u2 ON v2.userid = u2.id
            WHERE tm.attacker_id = ?
            AND tm.type = 'support'
            AND tm.status = 'completed'
            ORDER BY tm.arrival_time DESC
        ");
        $stmt->execute([$user_id]);
        $sent_supports = $stmt->fetchAll();

        // Поддержка которую я получил от других
        $stmt = $this->db->prepare("
            SELECT
                tm.*,
                v1.name as from_name, v1.x as fx, v1.y as fy,
                v2.name as to_name,   v2.x as tx, v2.y as ty,
                u1.username as from_owner
            FROM troop_movements tm
            LEFT JOIN villages v1 ON tm.from_village_id = v1.id
            LEFT JOIN villages v2 ON tm.to_village_id   = v2.id
            LEFT JOIN users    u1 ON tm.attacker_id = u1.id
            WHERE v2.userid = ?
            AND tm.attacker_id != ?
            AND tm.type = 'support'
            AND tm.status = 'completed'
            ORDER BY tm.arrival_time DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        $received_supports = $stmt->fetchAll();

        // Моя поддержка в своих деревнях
        $stmt = $this->db->prepare("
            SELECT
                tm.*,
                v1.name as from_name, v1.x as fx, v1.y as fy,
                v2.name as to_name,   v2.x as tx, v2.y as ty
            FROM troop_movements tm
            LEFT JOIN villages v1 ON tm.from_village_id = v1.id
            LEFT JOIN villages v2 ON tm.to_village_id   = v2.id
            WHERE tm.attacker_id = ?
            AND v2.userid = ?
            AND tm.type = 'support'
            AND tm.status = 'completed'
            ORDER BY tm.arrival_time DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        $own_supports = $stmt->fetchAll();

        $db = $this->db;
        require_once __DIR__ . '/../templates/support.php';
    }

    // =========================================================
    // ОТЗЫВ ПОДДЕРЖКИ
    // =========================================================
    public function recall() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id   = $_SESSION['user_id'];
        $support_id = (int)($_GET['id'] ?? 0);

        if ($support_id <= 0) {
            $_SESSION['error'] = "Неверный ID поддержки.";
            header("Location: ?page=support");
            exit;
        }

        // Проверяем что поддержка наша
        $stmt = $this->db->prepare("
            SELECT tm.*,
                   v1.x as fx, v1.y as fy,
                   v2.x as tx, v2.y as ty
            FROM troop_movements tm
            LEFT JOIN villages v1 ON tm.from_village_id = v1.id
            LEFT JOIN villages v2 ON tm.to_village_id   = v2.id
            WHERE tm.id = ?
            AND tm.attacker_id = ?
            AND tm.type = 'support'
            AND tm.status = 'completed'
        ");
        $stmt->execute([$support_id, $user_id]);
        $support = $stmt->fetch();

        if (!$support) {
            $_SESSION['error'] = "Поддержка не найдена или не принадлежит вам!";
            header("Location: ?page=support");
            exit;
        }

        $troops = json_decode($support['troops'], true);

        // Проверяем что войска ещё есть
        $total = array_sum($troops);
        if ($total <= 0) {
            // Удаляем пустую поддержку
            $this->db->prepare("DELETE FROM troop_movements WHERE id = ?")
                ->execute([$support_id]);
            $_SESSION['error'] = "В этой поддержке нет войск.";
            header("Location: ?page=support");
            exit;
        }

        // Время возврата (от деревни цели до деревни отправки)
        $travel_time = BattleEngine::calculateTravelTime(
            (float)$support['tx'], (float)$support['ty'],
            (float)$support['fx'], (float)$support['fy'],
            $troops
        );

        // Помечаем как отозванную (меняем тип на return)
        $extra = ['loot' => ['wood' => 0, 'stone' => 0, 'iron' => 0]];

        $this->db->prepare("
            UPDATE troop_movements SET
                type           = 'return',
                status         = 'moving',
                departure_time = ?,
                arrival_time   = ?,
                from_village_id = to_village_id,
                to_village_id   = from_village_id,
                extra_data      = ?
            WHERE id = ?
        ")->execute([
            time(),
            time() + $travel_time,
            json_encode($extra),
            $support_id
        ]);

        $mins = floor($travel_time / 60);
        $secs = $travel_time % 60;

        // Уведомляем владельца деревни куда шла поддержка
        $stmt = $this->db->prepare("SELECT userid, name FROM villages WHERE id = ?");
        $stmt->execute([$support['to_village_id']]);
        $to_village = $stmt->fetch();

        if ($to_village && (int)$to_village['userid'] !== $user_id) {
            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $sender = $stmt->fetch();

            $this->db->prepare("
                INSERT INTO reports
                (userid, type, title, content, time, is_read)
                VALUES (?, 'support', ?, ?, ?, 0)
            ")->execute([
                $to_village['userid'],
                "⚠ Поддержка отозвана!",
                "Игрок «{$sender['username']}» отозвал поддержку из деревни «{$to_village['name']}»!\n" .
                "Войска возвращаются домой.",
                time()
            ]);
        }

        $_SESSION['success'] = "✅ Поддержка отозвана! Войска вернутся через {$mins}м {$secs}с.";
        header("Location: ?page=support");
        exit;
    }

    // =========================================================
    // ПОДДЕРЖКА В КОНКРЕТНОЙ ДЕРЕВНЕ
    // =========================================================
    public function inVillage($village_id) {
        if (!isset($_SESSION['user_id'])) {
            return [];
        }

        // Входящая поддержка
        $stmt = $this->db->prepare("
            SELECT
                tm.id, tm.troops, tm.attacker_id,
                u.username as from_user,
                v.name as from_village_name
            FROM troop_movements tm
            LEFT JOIN users    u ON tm.attacker_id    = u.id
            LEFT JOIN villages v ON tm.from_village_id = v.id
            WHERE tm.to_village_id = ?
            AND tm.type   = 'support'
            AND tm.status = 'completed'
            ORDER BY tm.arrival_time ASC
        ");
        $stmt->execute([$village_id]);
        return $stmt->fetchAll();
    }
}