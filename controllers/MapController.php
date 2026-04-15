<?php
// controllers/MapController.php

class MapController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        // Координаты центра
        $center_x = (int)($_GET['x'] ?? 0);
        $center_y = (int)($_GET['y'] ?? 0);
        $size     = 10; // Радиус обзора

        // Получаем деревни в радиусе
        $min_x = $center_x - $size;
        $max_x = $center_x + $size;
        $min_y = $center_y - $size;
        $max_y = $center_y + $size;

        $stmt = $this->db->prepare("
            SELECT v.id, v.name, v.x, v.y, v.userid, v.points,
                   v.r_wood, v.r_stone, v.r_iron,
                   v.loyalty, v.terrain,
                   IF(v.userid=-1,'Варвары',u.username) as username
            FROM villages v
            LEFT JOIN users u ON v.userid=u.id
            WHERE v.x BETWEEN ? AND ? AND v.y BETWEEN ? AND ?
            ORDER BY v.y, v.x
        ");
        $stmt->execute([$min_x, $max_x, $min_y, $max_y]);
        $villages_raw = $stmt->fetchAll();

        // Преобразуем в ассоциативный массив "x_y" => data
        $villages = [];
        foreach ($villages_raw as $v) {
            $villages["{$v['x']}_{$v['y']}"] = $v;
        }

        $center_x = $center_x;
        $center_y = $center_y;
        $size     = $size;

        require_once __DIR__ . '/../templates/map.php';
    }
}