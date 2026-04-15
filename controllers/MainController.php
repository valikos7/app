<?php
// controllers/MainController.php

class MainController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        // Кол-во игроков
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM users");
        $players = $stmt->fetch()['total'] ?? 0;

        // Онлайн
        $stmt = $this->db->prepare("SELECT COUNT(*) as online FROM users WHERE last_activity >= ?");
        $stmt->execute([time() - 300]);
        $online = $stmt->fetch()['online'] ?? 0;

        // Деревень всего
        $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM villages WHERE userid > 0");
        $total_villages = $stmt->fetch()['cnt'] ?? 0;

        // Альянсов
        $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM alliances");
        $total_alliances = $stmt->fetch()['cnt'] ?? 0;

        // Объявления
        try {
            $stmt = $this->db->query("SELECT * FROM announcement ORDER BY time DESC LIMIT 5");
            $announcements = $stmt->fetchAll();
        } catch (Exception $e) {
            $announcements = [];
        }

        // Топ игроков
        $stmt = $this->db->query("
            SELECT u.id, u.username, u.points, u.villages,
                   a.tag as alliance_tag
            FROM users u
            LEFT JOIN alliances a ON u.alliance_id = a.id
            ORDER BY u.points DESC LIMIT 10
        ");
        $top_players = $stmt->fetchAll();

        // Топ альянсов
        $stmt = $this->db->query("
            SELECT id, name, tag, points, members_count
            FROM alliances ORDER BY points DESC LIMIT 5
        ");
        $top_alliances = $stmt->fetchAll();

        $logged_in = isset($_SESSION['user_id']);
        $user      = null;
        $unread_reports  = 0;
        $unread_messages = 0;

        if ($logged_in) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            // Непрочитанные отчёты
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM reports 
                    WHERE userid = ? AND is_read = 0");
                $stmt->execute([$_SESSION['user_id']]);
                $unread_reports = $stmt->fetch()['cnt'] ?? 0;
            } catch (Exception $e) {}

            // Непрочитанные сообщения
            $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM messages 
                WHERE to_id = ? AND is_read = 0 AND deleted_by_receiver = 0");
            $stmt->execute([$_SESSION['user_id']]);
            $unread_messages = $stmt->fetch()['cnt'] ?? 0;
        }

        $db = $this->db;
        require_once __DIR__ . '/../templates/index.php';
    }
}