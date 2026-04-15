<?php
// controllers/NoblemenController.php

class NoblemenController {
    private $db;

    // Урон лояльности за 1 атаку с дворянином
    private const LOYALTY_DAMAGE_BASE = 25;
    private const LOYALTY_DAMAGE_MIN  = 20;
    private const LOYALTY_DAMAGE_MAX  = 35;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // СТРАНИЦА ДВОРЯНИНА
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Деревни игрока с дворянами
        $stmt = $this->db->prepare("
            SELECT v.*, up.nobleman
            FROM villages v
            LEFT JOIN unit_place up ON up.villages_to_id = v.id
            WHERE v.userid = ?
            ORDER BY v.points DESC
        ");
        $stmt->execute([$user_id]);
        $my_villages = $stmt->fetchAll();

        // Деревни которые можно захватить (низкая лояльность)
        $stmt = $this->db->prepare("
            SELECT v.*, u.username as owner_name
            FROM villages v
            LEFT JOIN users u ON v.userid = u.id
            WHERE v.loyalty < 100 AND v.userid != ? AND v.userid > 0
            ORDER BY v.loyalty ASC LIMIT 20
        ");
        $stmt->execute([$user_id]);
        $capturable_villages = $stmt->fetchAll();

        // История захватов
        $stmt = $this->db->prepare("
            SELECT vc.*, v.name as village_name, v.x, v.y,
                   uf.username as from_name,
                   ut.username as to_name
            FROM village_captures vc
            LEFT JOIN villages v ON vc.village_id = v.id
            LEFT JOIN users uf ON vc.from_user_id = uf.id
            LEFT JOIN users ut ON vc.to_user_id   = ut.id
            WHERE vc.to_user_id = ? OR vc.from_user_id = ?
            ORDER BY vc.captured_at DESC LIMIT 10
        ");
        $stmt->execute([$user_id, $user_id]);
        $capture_history = $stmt->fetchAll();

        // Стоимость дворянина
        $stmt = $this->db->prepare("SELECT * FROM unit_config WHERE type='nobleman'");
        $stmt->execute();
        $nobleman_config = $stmt->fetch();

        $db = $this->db;
        require_once __DIR__ . '/../templates/nobleman.php';
    }

    // =========================================================
    // ПРИМЕНИТЬ АТАКУ ДВОРЯНИНОМ (вызывается из BattleController)
    // =========================================================
    public function applyNoblemenAttack($attacker_id, $village_id, $noblemen_count, $won) {
        if (!$won || $noblemen_count <= 0) return false;

        try {
            $stmt = $this->db->prepare("SELECT * FROM villages WHERE id=?");
            $stmt->execute([$village_id]);
            $village = $stmt->fetch();
            if (!$village) return false;

            $defender_id  = (int)$village['userid'];
            $is_barbarian = ($defender_id === -1);

            if ($is_barbarian) return false;
            if ($defender_id === $attacker_id) return false;

            // Урон лояльности
            $damage = rand(self::LOYALTY_DAMAGE_MIN, self::LOYALTY_DAMAGE_MAX);
            $damage *= $noblemen_count;
            $damage  = min($damage, 100);

            $new_loyalty = max(0, (int)$village['loyalty'] - $damage);

            // Обновляем лояльность
            $this->db->prepare("UPDATE villages SET loyalty=? WHERE id=?")
                ->execute([$new_loyalty, $village_id]);

            // Уведомляем защитника
            $stmt = $this->db->prepare("SELECT username FROM users WHERE id=?");
            $stmt->execute([$attacker_id]);
            $attacker = $stmt->fetch();

            $this->db->prepare("INSERT INTO reports
                (userid, type, title, content, time, is_read)
                VALUES (?, 'system', ?, ?, ?, 0)")
                ->execute([
                    $defender_id,
                    "⚠ Лояльность деревни снижена!",
                    "Дворянин игрока «{$attacker['username']}» снизил лояльность деревни\n" .
                    "«{$village['name']}» на {$damage} пунктов!\n\n" .
                    "Текущая лояльность: {$new_loyalty}/100\n\n" .
                    ($new_loyalty <= 0
                        ? "⚠ ДЕРЕВНЯ ЗАХВАЧЕНА!"
                        : "Ещё " . ceil($new_loyalty / self::LOYALTY_DAMAGE_BASE) . " атак для захвата"),
                    time()
                ]);

            // Деревня захвачена!
            if ($new_loyalty <= 0) {
                $this->captureVillage($attacker_id, $defender_id, $village_id, $village);
                return 'captured';
            }

            return $new_loyalty;

        } catch (Exception $e) {
            error_log("applyNoblemenAttack: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================
    // ЗАХВАТ ДЕРЕВНИ
    // =========================================================
    private function captureVillage($attacker_id, $defender_id, $village_id, $village) {
        // Получаем данные атакующего
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id=?");
        $stmt->execute([$attacker_id]);
        $attacker = $stmt->fetch();

        // Переводим деревню
        $new_name = $village['name'] . " (захвачена)";
        $this->db->prepare("UPDATE villages SET
            userid   = ?,
            loyalty  = 100,
            name     = ?
            WHERE id = ?
        ")->execute([$attacker_id, $new_name, $village_id]);

        // Убираем войска защитника из деревни
        $this->db->prepare("DELETE FROM unit_place WHERE villages_to_id=?")
            ->execute([$village_id]);

        // Создаём пустой unit_place для нового владельца
        $this->db->prepare("INSERT INTO unit_place (villages_to_id) VALUES (?)")
            ->execute([$village_id]);

        // Обновляем счётчики деревень
        $this->db->prepare("UPDATE users SET villages=villages-1 WHERE id=? AND villages>0")
            ->execute([$defender_id]);
        $this->db->prepare("UPDATE users SET villages=villages+1 WHERE id=?")
            ->execute([$attacker_id]);

        // Лог захвата
        try {
            $this->db->prepare("INSERT INTO village_captures
                (village_id, from_user_id, to_user_id, captured_at, old_name)
                VALUES (?, ?, ?, ?, ?)")
                ->execute([$village_id, $defender_id, $attacker_id, time(), $village['name']]);
        } catch (Exception $e) {}

        // Уведомляем захватчика
        $this->db->prepare("INSERT INTO reports
            (userid, type, title, content, time, is_read)
            VALUES (?, 'system', ?, ?, ?, 0)")
            ->execute([
                $attacker_id,
                "🏆 Деревня захвачена!",
                "Вы успешно захватили деревню «{$village['name']}»!\n\n" .
                "Координаты: {$village['x']}|{$village['y']}\n" .
                "Деревня теперь ваша! Управляйте ею как обычно.",
                time()
            ]);

        // Уведомляем защитника
        $this->db->prepare("INSERT INTO reports
            (userid, type, title, content, time, is_read)
            VALUES (?, 'system', ?, ?, ?, 0)")
            ->execute([
                $defender_id,
                "💀 Деревня потеряна!",
                "Ваша деревня «{$village['name']}» была захвачена игроком «{$attacker['username']}»!\n\n" .
                "Координаты: {$village['x']}|{$village['y']}\n" .
                "Деревня перешла во владение противника.",
                time()
            ]);
    }

    // =========================================================
    // ВОССТАНОВЛЕНИЕ ЛОЯЛЬНОСТИ (из крона)
    // =========================================================
    public function restoreLoyalty() {
        try {
            // Лояльность восстанавливается на 1 в час для каждой деревни
            $this->db->query("
                UPDATE villages SET loyalty = LEAST(100, loyalty + 1)
                WHERE loyalty < 100 AND userid > 0
            ");
        } catch (Exception $e) {
            error_log("restoreLoyalty: " . $e->getMessage());
        }
    }
}