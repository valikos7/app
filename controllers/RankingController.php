<?php
// controllers/RankingController.php

class RankingController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $tab = $_GET['tab'] ?? 'players';

        $players = [];
        if ($tab === 'players') {
            $stmt = $this->db->query("
                SELECT u.id, u.username, u.points, u.villages,
                       u.last_activity,
                       a.name as alliance_name, a.tag as alliance_tag
                FROM users u
                LEFT JOIN alliances a ON u.alliance_id = a.id
                ORDER BY u.points DESC
                LIMIT 100
            ");
            $players = $stmt->fetchAll();
        }

        $alliances_rank = [];
        if ($tab === 'alliances') {
            $stmt = $this->db->query("
                SELECT a.*, u.username as leader_name
                FROM alliances a
                LEFT JOIN users u ON a.leader_id = u.id
                ORDER BY a.points DESC
                LIMIT 50
            ");
            $alliances_rank = $stmt->fetchAll();
        }

        $my_rank = '?';
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) + 1 as user_rank
                FROM users
                WHERE points > (SELECT points FROM users WHERE id = ?)
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $my_rank = $stmt->fetch()['user_rank'] ?? '?';
        } catch (Exception $e) {
            $my_rank = '?';
        }

        $db = $this->db;

        require_once __DIR__ . '/../templates/ranking.php';
    }
}