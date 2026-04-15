<?php
// controllers/SettlerController.php

class SettlerController {
    private $db;

    // Стоимость поселенцев
    private $settler_cost = [
        'wood'  => 5000,
        'stone' => 5000,
        'iron'  => 5000
    ];

    // Количество поселенцев для основания деревни
    private $settlers_needed = 3;

    public function __construct($db) {
        $this->db = $db;
    }

    // === Форма отправки поселенцев ===
    public function showForm() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id    = $_SESSION['user_id'];
        $village_id = (int)($_GET['village_id'] ?? 0);

        // Получаем деревню
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE id = ? AND userid = ?");
        $stmt->execute([$village_id, $user_id]);
        $village = $stmt->fetch();

        if (!$village) {
            $_SESSION['error'] = "Деревня не найдена.";
            header("Location: ?page=profile");
            exit;
        }

        // Проверяем уровень главного здания
        if ((int)$village['main'] < 10) {
            $_SESSION['error'] = "Для отправки поселенцев нужно Главное здание уровня 10!";
            header("Location: ?page=village&id=" . $village_id);
            exit;
        }

        // Активные поселенцы этой деревни
        $stmt = $this->db->prepare("
            SELECT * FROM settlers 
            WHERE from_village_id = ? AND status = 'moving'
        ");
        $stmt->execute([$village_id]);
        $active_settlers = $stmt->fetchAll();

        $db = $this->db;
        $settler_cost = $this->settler_cost;
        $settlers_needed = $this->settlers_needed;

        require_once __DIR__ . '/../templates/settlers.php';
    }

    // === Отправка поселенцев ===
    public function send() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id    = $_SESSION['user_id'];
        $village_id = (int)($_POST['village_id'] ?? 0);
        $target_x   = (int)($_POST['target_x'] ?? 0);
        $target_y   = (int)($_POST['target_y'] ?? 0);

        // Проверяем деревню
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE id = ? AND userid = ?");
        $stmt->execute([$village_id, $user_id]);
        $village = $stmt->fetch();

        if (!$village) {
            $_SESSION['error'] = "Деревня не найдена.";
            header("Location: ?page=profile");
            exit;
        }

        // Проверяем уровень главного здания
        if ((int)$village['main'] < 10) {
            $_SESSION['error'] = "Нужно Главное здание уровня 10!";
            header("Location: ?page=settlers&village_id=" . $village_id);
            exit;
        }

        // Проверяем что координаты свободны
        $stmt = $this->db->prepare("SELECT id FROM villages WHERE x = ? AND y = ?");
        $stmt->execute([$target_x, $target_y]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Эти координаты уже заняты!";
            header("Location: ?page=settlers&village_id=" . $village_id);
            exit;
        }

        // Проверяем границы карты
        if ($target_x < -500 || $target_x > 500 || $target_y < -500 || $target_y > 500) {
            $_SESSION['error'] = "Координаты вне карты! (от -500 до 500)";
            header("Location: ?page=settlers&village_id=" . $village_id);
            exit;
        }

        // Проверяем активных поселенцев
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as cnt FROM settlers 
            WHERE from_village_id = ? AND status = 'moving'
        ");
        $stmt->execute([$village_id]);
        $active = $stmt->fetch()['cnt'] ?? 0;

        if ($active >= $this->settlers_needed) {
            $_SESSION['error'] = "Поселенцы уже отправлены из этой деревни!";
            header("Location: ?page=settlers&village_id=" . $village_id);
            exit;
        }

        // Проверяем ресурсы (стоимость × количество поселенцев)
        $total_wood  = $this->settler_cost['wood']  * $this->settlers_needed;
        $total_stone = $this->settler_cost['stone'] * $this->settlers_needed;
        $total_iron  = $this->settler_cost['iron']  * $this->settlers_needed;

        if ($village['r_wood']  < $total_wood  ||
            $village['r_stone'] < $total_stone ||
            $village['r_iron']  < $total_iron) {
            $_SESSION['error'] = "Недостаточно ресурсов для отправки поселенцев!";
            header("Location: ?page=settlers&village_id=" . $village_id);
            exit;
        }

        // Списываем ресурсы
        $this->db->prepare("UPDATE villages SET
            r_wood  = r_wood  - ?,
            r_stone = r_stone - ?,
            r_iron  = r_iron  - ?
            WHERE id = ?")
            ->execute([$total_wood, $total_stone, $total_iron, $village_id]);

        // Рассчитываем время похода
        $distance = sqrt(
            pow($target_x - $village['x'], 2) +
            pow($target_y - $village['y'], 2)
        );
        $travel_time = (int)ceil($distance * 30 * 60); // 30 мин/клетку
        $travel_time = max($travel_time, 300); // минимум 5 минут

        // Создаём запись поселенцев
        $this->db->prepare("INSERT INTO settlers 
            (user_id, from_village_id, target_x, target_y, 
             departure_time, arrival_time, status)
            VALUES (?, ?, ?, ?, ?, ?, 'moving')")
            ->execute([
                $user_id,
                $village_id,
                $target_x,
                $target_y,
                time(),
                time() + $travel_time
            ]);

        $mins = floor($travel_time / 60);
        $secs = $travel_time % 60;

        // Логируем
        $this->db->prepare("INSERT INTO activity_log (user_id, action, details, time)
            VALUES (?, 'settlers_sent', ?, ?)")
            ->execute([
                $user_id,
                "Поселенцы отправлены на {$target_x}|{$target_y}",
                time()
            ]);

        $_SESSION['success'] = "Поселенцы отправлены на {$target_x}|{$target_y}!<br>
            Время в пути: <strong>{$mins} мин. {$secs} сек.</strong>";
        header("Location: ?page=profile");
        exit;
    }

    // === Обработка прибытия поселенцев ===
    public function processArrivals() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM settlers 
                WHERE status = 'moving' AND arrival_time <= " . time()
            );
            $arrivals = $stmt->fetchAll();

            foreach ($arrivals as $settler) {
                $this->foundVillage($settler);
                $this->db->prepare("UPDATE settlers SET status = 'arrived' WHERE id = ?")
                    ->execute([$settler['id']]);
            }
        } catch (Exception $e) {
            // Игнорируем
        }
    }

    // === Основание новой деревни ===
    private function foundVillage($settler) {
        $user_id  = $settler['user_id'];
        $target_x = $settler['target_x'];
        $target_y = $settler['target_y'];

        // Проверяем что место ещё свободно
        $stmt = $this->db->prepare("SELECT id FROM villages WHERE x = ? AND y = ?");
        $stmt->execute([$target_x, $target_y]);
        if ($stmt->fetch()) {
            // Место занято — возвращаем ресурсы
            $this->db->prepare("UPDATE villages SET
                r_wood  = r_wood  + ?,
                r_stone = r_stone + ?,
                r_iron  = r_iron  + ?
                WHERE id = ?")
                ->execute([
                    $this->settler_cost['wood']  * $this->settlers_needed,
                    $this->settler_cost['stone'] * $this->settlers_needed,
                    $this->settler_cost['iron']  * $this->settlers_needed,
                    $settler['from_village_id']
                ]);

            // Уведомляем игрока
            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            $this->db->prepare("INSERT INTO reports 
                (userid, type, title, content, time, is_read)
                VALUES (?, 'system', ?, ?, ?, 0)")
                ->execute([
                    $user_id,
                    "❌ Поселенцы вернулись",
                    "Место {$target_x}|{$target_y} оказалось занятым.\n" .
                    "Ресурсы возвращены в исходную деревню.",
                    time()
                ]);
            return;
        }

        // Получаем данные игрока
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // Вычисляем континент
        $continent = floor(($target_y + 500) / 100) * 10 +
                     floor(($target_x + 500) / 100);

        // Создаём новую деревню
        $village_name = "Деревня " . ($user['villages'] + 1) . 
                        " (" . $user['username'] . ")";

        $stmt = $this->db->prepare("INSERT INTO villages 
            (userid, name, x, y, continent,
             main, wood_level, stone_level, iron_level, farm, storage,
             r_wood, r_stone, r_iron, last_prod_aktu, points)
            VALUES (?, ?, ?, ?, ?,
                    1, 1, 1, 1, 1, 1,
                    800, 800, 800, ?, 0)");
        $stmt->execute([
            $user_id, $village_name,
            $target_x, $target_y, $continent,
            time()
        ]);

        $new_village_id = $this->db->lastInsertId();

        // Создаём запись войск
        $this->db->prepare("INSERT INTO unit_place 
            (villages_to_id, spear, sword, axe, scout, light, heavy, ram, catapult)
            VALUES (?, 0, 0, 0, 0, 0, 0, 0, 0)")
            ->execute([$new_village_id]);

        // Создаём запись исследований
        $this->db->prepare("INSERT INTO smith_research (village_id) VALUES (?)")
            ->execute([$new_village_id]);

        // Обновляем счётчик деревень игрока
        $this->db->prepare("UPDATE users SET villages = villages + 1 WHERE id = ?")
            ->execute([$user_id]);

        // Отчёт игроку
        $this->db->prepare("INSERT INTO reports 
            (userid, type, title, content, time, is_read)
            VALUES (?, 'system', ?, ?, ?, 0)")
            ->execute([
                $user_id,
                "✅ Основана новая деревня!",
                "Поселенцы успешно добрались до {$target_x}|{$target_y}.\n" .
                "Основана новая деревня: \"{$village_name}\"!\n" .
                "Деревня #{$new_village_id} теперь принадлежит вам.",
                time()
            ]);

        // Логируем
        $this->db->prepare("INSERT INTO activity_log 
            (user_id, action, details, time) VALUES (?, 'village_founded', ?, ?)")
            ->execute([
                $user_id,
                "Основана деревня {$village_name} на {$target_x}|{$target_y}",
                time()
            ]);
    }
}