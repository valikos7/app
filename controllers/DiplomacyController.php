<?php
// controllers/DiplomacyController.php

class DiplomacyController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        // Получаем альянс пользователя
        $stmt = $this->db->prepare("
            SELECT am.alliance_id, am.role, a.*
            FROM alliance_members am
            JOIN alliances a ON am.alliance_id = a.id
            WHERE am.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();

        if (!$row) {
            $_SESSION['error'] = "Вы не состоите в альянсе!";
            header("Location: ?page=alliances");
            exit;
        }

        $alliance = $row;
        $my_role  = $row['role'];
        $alliance_id = $row['alliance_id'];

        // Все отношения
        $stmt = $this->db->prepare("
            SELECT ad.*,
                   a1.name as from_name, a1.tag as from_tag,
                   a2.name as to_name,   a2.tag as to_tag
            FROM alliance_diplomacy ad
            JOIN alliances a1 ON ad.alliance_id = a1.id
            JOIN alliances a2 ON ad.target_id   = a2.id
            WHERE ad.alliance_id = ? OR ad.target_id = ?
            ORDER BY ad.created_at DESC
        ");
        $stmt->execute([$alliance_id, $alliance_id]);
        $relations = $stmt->fetchAll();

        // Все альянсы для формы
        $stmt = $this->db->query("SELECT id, name, tag FROM alliances ORDER BY name");
        $all_alliances = $stmt->fetchAll();

        $db = $this->db;
        require_once __DIR__ . '/../templates/alliance_diplomacy.php';
    }

    public function propose() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id   = $_SESSION['user_id'];
        $target_id = (int)($_POST['target_id'] ?? 0);
        $type      = $_POST['type'] ?? '';

        if (!in_array($type, ['ally','nap','war'])) {
            $_SESSION['error'] = "Неверный тип отношений!";
            header("Location: ?page=diplomacy");
            exit;
        }

        // Получаем альянс
        $stmt = $this->db->prepare("
            SELECT am.alliance_id, am.role
            FROM alliance_members am WHERE am.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();

        if (!$row || !in_array($row['role'], ['leader','officer'])) {
            $_SESSION['error'] = "Недостаточно прав!";
            header("Location: ?page=diplomacy");
            exit;
        }

        $alliance_id = $row['alliance_id'];

        if ($target_id == $alliance_id) {
            $_SESSION['error'] = "Нельзя предложить отношения самому себе!";
            header("Location: ?page=diplomacy");
            exit;
        }

        // Проверяем существующие отношения
        $stmt = $this->db->prepare("
            SELECT id FROM alliance_diplomacy
            WHERE (alliance_id = ? AND target_id = ?)
               OR (alliance_id = ? AND target_id = ?)
            AND status != 'rejected'
        ");
        $stmt->execute([$alliance_id, $target_id, $target_id, $alliance_id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Отношения с этим альянсом уже существуют!";
            header("Location: ?page=diplomacy");
            exit;
        }

        $this->db->prepare("INSERT INTO alliance_diplomacy
            (alliance_id, target_id, type, status, created_at, created_by)
            VALUES (?, ?, ?, 'pending', ?, ?)")
            ->execute([$alliance_id, $target_id, $type, time(), $user_id]);

        // Уведомление лидеру целевого альянса
        $stmt = $this->db->prepare("
            SELECT u.id FROM users u
            JOIN alliance_members am ON u.id = am.user_id
            WHERE am.alliance_id = ? AND am.role = 'leader'
            LIMIT 1
        ");
        $stmt->execute([$target_id]);
        $leader = $stmt->fetch();

        if ($leader) {
            $type_names = ['ally'=>'Союз','nap'=>'Ненападение','war'=>'Война'];
            $this->db->prepare("INSERT INTO messages
                (from_id, to_id, subject, content, time, is_read)
                VALUES (?, ?, ?, ?, ?, 0)")
                ->execute([
                    $user_id,
                    $leader['id'],
                    '🤝 Дипломатическое предложение',
                    "Альянс предлагает вам отношения: {$type_names[$type]}\n" .
                    "Перейдите в раздел Дипломатия для ответа.",
                    time()
                ]);
        }

        $type_names = ['ally'=>'Союз 🤝','nap'=>'Ненападение 🕊','war'=>'Войну ⚔'];
        $_SESSION['success'] = "Предложение отправлено: {$type_names[$type]}";
        header("Location: ?page=diplomacy");
        exit;
    }

    public function accept() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $diplo_id = (int)($_GET['id'] ?? 0);
        $this->db->prepare("UPDATE alliance_diplomacy
            SET status = 'active' WHERE id = ?")
            ->execute([$diplo_id]);

        $_SESSION['success'] = "Отношения приняты!";
        header("Location: ?page=diplomacy");
        exit;
    }

    public function reject() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $diplo_id = (int)($_GET['id'] ?? 0);
        $this->db->prepare("UPDATE alliance_diplomacy
            SET status = 'rejected' WHERE id = ?")
            ->execute([$diplo_id]);

        $_SESSION['success'] = "Отношения отклонены.";
        header("Location: ?page=diplomacy");
        exit;
    }

    public function cancel() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $diplo_id = (int)($_GET['id'] ?? 0);
        $this->db->prepare("DELETE FROM alliance_diplomacy WHERE id = ?")
            ->execute([$diplo_id]);

        $_SESSION['success'] = "Отношения отменены.";
        header("Location: ?page=diplomacy");
        exit;
    }
}