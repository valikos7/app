<?php
// controllers/MessageController.php

class MessageController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // === Входящие ===
    public function inbox() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        $stmt = $this->db->prepare("
            SELECT m.*, u.username as from_name
            FROM messages m
            LEFT JOIN users u ON m.from_id = u.id
            WHERE m.to_id = ? AND m.deleted_by_receiver = 0
            ORDER BY m.time DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $messages = $stmt->fetchAll();

        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM messages 
            WHERE to_id = ? AND is_read = 0 AND deleted_by_receiver = 0");
        $stmt->execute([$user_id]);
        $unread = $stmt->fetch()['cnt'] ?? 0;

        $folder = 'inbox';
        $db     = $this->db;

        require_once __DIR__ . '/../templates/messages.php';
    }

    // === Отправленные ===
    public function sent() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];

        $stmt = $this->db->prepare("
            SELECT m.*, u.username as to_name
            FROM messages m
            LEFT JOIN users u ON m.to_id = u.id
            WHERE m.from_id = ? AND m.deleted_by_sender = 0
            ORDER BY m.time DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        $messages = $stmt->fetchAll();

        $unread = 0;
        $folder = 'sent';
        $db     = $this->db;

        require_once __DIR__ . '/../templates/messages.php';
    }

    // === Просмотр сообщения ===
    public function view() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $msg_id  = (int)($_GET['msg_id'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT m.*,
                   uf.username as from_name,
                   ut.username as to_name
            FROM messages m
            LEFT JOIN users uf ON m.from_id = uf.id
            LEFT JOIN users ut ON m.to_id   = ut.id
            WHERE m.id = ? AND (m.to_id = ? OR m.from_id = ?)
        ");
        $stmt->execute([$msg_id, $user_id, $user_id]);
        $message = $stmt->fetch();

        if (!$message) {
            $_SESSION['error'] = "Сообщение не найдено.";
            header("Location: ?page=messages");
            exit;
        }

        // Помечаем как прочитанное
        if ($message['to_id'] == $user_id && !$message['is_read']) {
            $this->db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")
                ->execute([$msg_id]);
        }

        // Получаем цепочку переписки (все сообщения с этим пользователем по теме)
        $other_user_id = $message['from_id'] == $user_id
            ? $message['to_id']
            : $message['from_id'];

        // Базовая тема (убираем Re: Re: Re:)
        $base_subject = preg_replace('/^(Re:\s*)+/i', '', $message['subject']);

        $stmt = $this->db->prepare("
            SELECT m.*,
                   uf.username as from_name,
                   ut.username as to_name
            FROM messages m
            LEFT JOIN users uf ON m.from_id = uf.id
            LEFT JOIN users ut ON m.to_id   = ut.id
            WHERE (
                (m.from_id = ? AND m.to_id = ?) OR
                (m.from_id = ? AND m.to_id = ?)
            )
            AND (
                m.subject = ? OR
                m.subject = ? OR
                m.subject LIKE ?
            )
            ORDER BY m.time ASC
            LIMIT 20
        ");
        $stmt->execute([
            $user_id, $other_user_id,
            $other_user_id, $user_id,
            $message['subject'],
            'Re: ' . $base_subject,
            'Re: %' . $base_subject . '%'
        ]);
        $thread = $stmt->fetchAll();

        // Помечаем все в цепочке прочитанными
        foreach ($thread as $t) {
            if ($t['to_id'] == $user_id && !$t['is_read']) {
                $this->db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")
                    ->execute([$t['id']]);
            }
        }

        $db = $this->db;
        require_once __DIR__ . '/../templates/message_view.php';
    }

    // === Написать сообщение ===
    public function compose() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id     = $_SESSION['user_id'];
        $to_username = trim($_GET['to'] ?? '');
        $reply_to    = (int)($_GET['reply_to'] ?? 0);
        $db          = $this->db;

        // Если это ответ на сообщение
        $reply_data = null;
        if ($reply_to > 0) {
            $stmt = $this->db->prepare("
                SELECT m.*, u.username as from_name
                FROM messages m
                LEFT JOIN users u ON m.from_id = u.id
                WHERE m.id = ? AND (m.to_id = ? OR m.from_id = ?)
            ");
            $stmt->execute([$reply_to, $user_id, $user_id]);
            $reply_data = $stmt->fetch();

            if ($reply_data) {
                // Автозаполняем получателя
                if (empty($to_username)) {
                    $to_username = $reply_data['from_id'] == $user_id
                        ? $reply_data['to_name']
                        : $reply_data['from_name'];
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once __DIR__ . '/../templates/message_compose.php';
            return;
        }

        $to_username = trim($_POST['to_username'] ?? '');
        $subject     = trim($_POST['subject'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $reply_to    = (int)($_POST['reply_to'] ?? 0);

        if (empty($to_username) || empty($subject) || empty($content)) {
            $_SESSION['error'] = "Все поля обязательны!";
            require_once __DIR__ . '/../templates/message_compose.php';
            return;
        }

        // Находим получателя
        $stmt = $this->db->prepare("SELECT id, username FROM users WHERE username = ?");
        $stmt->execute([$to_username]);
        $recipient = $stmt->fetch();

        if (!$recipient) {
            $_SESSION['error'] = "Игрок '{$to_username}' не найден!";
            require_once __DIR__ . '/../templates/message_compose.php';
            return;
        }

        if ($recipient['id'] == $user_id) {
            $_SESSION['error'] = "Нельзя написать самому себе!";
            require_once __DIR__ . '/../templates/message_compose.php';
            return;
        }

        $this->db->prepare("INSERT INTO messages 
            (from_id, to_id, subject, content, time, is_read)
            VALUES (?, ?, ?, ?, ?, 0)")
            ->execute([
                $user_id,
                $recipient['id'],
                $subject,
                $content,
                time()
            ]);

        $_SESSION['success'] = "Сообщение отправлено игроку {$to_username}!";
        header("Location: ?page=messages&folder=sent");
        exit;
    }

    // === Удалить сообщение ===
    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $msg_id  = (int)($_GET['msg_id'] ?? 0);
        $folder  = $_GET['folder'] ?? 'inbox';

        $stmt = $this->db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$msg_id]);
        $msg = $stmt->fetch();

        if ($msg) {
            if ($msg['to_id'] == $user_id) {
                $this->db->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?")
                    ->execute([$msg_id]);
            } elseif ($msg['from_id'] == $user_id) {
                $this->db->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?")
                    ->execute([$msg_id]);
            }
        }

        header("Location: ?page=messages&folder=" . $folder);
        exit;
    }

    public static function getUnreadCount($db, $user_id) {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM messages 
            WHERE to_id = ? AND is_read = 0 AND deleted_by_receiver = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetch()['cnt'] ?? 0;
    }
}