<?php
// controllers/VillagesController.php

class VillagesController {
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

        $stmt = $this->db->prepare("SELECT * FROM villages WHERE userid = ? ORDER BY id");
        $stmt->execute([$user_id]);
        $villages = $stmt->fetchAll();

        $db = $this->db;

        require_once __DIR__ . '/../templates/villages.php';
    }
}