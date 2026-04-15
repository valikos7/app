<?php
// controllers/AllianceChatController.php

class AllianceChatController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // === Получить сообщения (AJAX) ===
    public function getMessages() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Не авторизован']);
            exit;
        }

        $user_id     = $_SESSION['user_id'];
        $alliance_id = $this->getUserAllianceId($user_id);

        if (!$alliance_id) {
            echo json_encode(['error' => 'Не в альянсе']);
            exit;
        }

        $since = (int)($_GET['since'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT ac.id, ac.message, ac.time,
                   u.username, u.id as uid
            FROM alliance_chat ac
            JOIN users u ON ac.user_id = u.id
            WHERE ac.alliance_id = ?
            AND ac.id > ?
            ORDER BY ac.time ASC
            LIMIT 50
        ");
        $stmt->execute([$alliance_id, $since]);
        $messages = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode([
            'messages' => $messages,
            'my_id'    => $user_id
        ]);
        exit;
    }

    // === Отправить сообщение (AJAX) ===
    public function sendMessage() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['error' => 'Не авторизован']);
            exit;
        }

        $user_id     = $_SESSION['user_id'];
        $alliance_id = $this->getUserAllianceId($user_id);

        if (!$alliance_id) {
            echo json_encode(['error' => 'Не в альянсе']);
            exit;
        }

        $message = trim($_POST['message'] ?? '');

        if (empty($message)) {
            echo json_encode(['error' => 'Пустое сообщение']);
            exit;
        }

        if (mb_strlen($message) > 500) {
            echo json_encode(['error' => 'Сообщение слишком длинное (макс. 500 символов)']);
            exit;
        }

        // Спам-защита: не чаще раза в 2 секунды
        $stmt = $this->db->prepare("
            SELECT id FROM alliance_chat
            WHERE user_id = ? AND time > ?
            LIMIT 1
        ");
        $stmt->execute([$user_id, time() - 2]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'Слишком быстро! Подождите секунду.']);
            exit;
        }

        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        $this->db->prepare("INSERT INTO alliance_chat
            (alliance_id, user_id, message, time)
            VALUES (?, ?, ?, ?)")
            ->execute([$alliance_id, $user_id, $message, time()]);

        $new_id = $this->db->lastInsertId();

        // Чистим старые сообщения (оставляем последние 200)
        $this->db->prepare("DELETE FROM alliance_chat
            WHERE alliance_id = ? AND id < (
                SELECT min_id FROM (
                    SELECT MIN(id) as min_id FROM (
                        SELECT id FROM alliance_chat
                        WHERE alliance_id = ?
                        ORDER BY id DESC
                        LIMIT 200
                    ) t
                ) t2
            )")
            ->execute([$alliance_id, $alliance_id]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id'      => $new_id
        ]);
        exit;
    }

    // === Страница чата ===
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id     = $_SESSION['user_id'];
        $alliance_id = $this->getUserAllianceId($user_id);

        if (!$alliance_id) {
            $_SESSION['error'] = "Вы не состоите в альянсе!";
            header("Location: ?page=alliances");
            exit;
        }

        // Данные альянса
        $stmt = $this->db->prepare("SELECT * FROM alliances WHERE id = ?");
        $stmt->execute([$alliance_id]);
        $alliance = $stmt->fetch();

        // Последние 50 сообщений
        $stmt = $this->db->prepare("
            SELECT ac.*, u.username
            FROM alliance_chat ac
            JOIN users u ON ac.user_id = u.id
            WHERE ac.alliance_id = ?
            ORDER BY ac.time DESC
            LIMIT 50
        ");
        $stmt->execute([$alliance_id]);
        $messages = array_reverse($stmt->fetchAll());

        // Онлайн участники
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.last_activity
            FROM alliance_members am
            JOIN users u ON am.user_id = u.id
            WHERE am.alliance_id = ?
            ORDER BY u.last_activity DESC
        ");
        $stmt->execute([$alliance_id]);
        $members = $stmt->fetchAll();

        $db = $this->db;
        require_once __DIR__ . '/../templates/alliance_chat.php';
    }

    private function getUserAllianceId($user_id) {
        $stmt = $this->db->prepare("
            SELECT alliance_id FROM alliance_members WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        return $row ? $row['alliance_id'] : null;
    }
}