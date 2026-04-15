<?php
// controllers/ProfileController.php

class ProfileController {
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

        // Получаем данные пользователя
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // Получаем все деревни игрока
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE userid = ? ORDER BY id");
        $stmt->execute([$user_id]);
        $villages = $stmt->fetchAll();

        // Количество непрочитанных отчётов
        $has_reports = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM reports WHERE userid = ?"
        );
        $has_reports->execute([$user_id]);
        $has_reports = $has_reports->fetch()['cnt'] ?? 0;

        // Передаём $db в шаблон
        $db = $this->db;

        require_once __DIR__ . '/../templates/profile.php';
    }
}