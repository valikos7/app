<?php
// controllers/ReportController.php

class ReportController {
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

        // Прочитать все
        if (($_GET['action'] ?? '') === 'read_all') {
            $this->db->prepare("UPDATE reports SET is_read = 1 WHERE userid = ?")
                ->execute([$user_id]);
            header("Location: ?page=reports");
            exit;
        }

        $type = $_GET['type'] ?? '';

        $sql    = "SELECT * FROM reports WHERE userid = ?";
        $params = [$user_id];

        $valid_types = ['attack','defense','scout','support','market','system'];
        if (in_array($type, $valid_types)) {
            $sql    .= " AND type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY time DESC LIMIT 100";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();

        // Помечаем как прочитанные
        $this->db->prepare("UPDATE reports SET is_read = 1 WHERE userid = ?")
            ->execute([$user_id]);

        $db = $this->db;
        require_once __DIR__ . '/../templates/reports.php';
    }
}