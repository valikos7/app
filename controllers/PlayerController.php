<?php
// controllers/PlayerController.php

class PlayerController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function view() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $player_id = (int)($_GET['id'] ?? 0);

        if ($player_id <= 0) {
            header("Location: ?page=ranking");
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$player_id]);
        $player = $stmt->fetch();

        if (!$player) {
            $_SESSION['error'] = "Игрок не найден.";
            header("Location: ?page=ranking");
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT id, name, x, y, continent, points
            FROM villages WHERE userid = ?
            ORDER BY points DESC
        ");
        $stmt->execute([$player_id]);
        $villages = $stmt->fetchAll();

        $first_village = $villages[0] ?? null;

        $alliance = null;
        if ($player['alliance_id']) {
            $stmt = $this->db->prepare("SELECT * FROM alliances WHERE id = ?");
            $stmt->execute([$player['alliance_id']]);
            $alliance = $stmt->fetch();
        }

        $my_rank = '?';
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) + 1 as user_rank
                FROM users
                WHERE points > (SELECT points FROM users WHERE id = ?)
            ");
            $stmt->execute([$player_id]);
            $my_rank = $stmt->fetch()['user_rank'] ?? '?';
        } catch (Exception $e) {
            $my_rank = '?';
        }

        $player_rank = $my_rank;
        $db          = $this->db;

        require_once __DIR__ . '/../templates/player_profile.php';
    }
}