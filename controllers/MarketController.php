<?php
// controllers/MarketController.php

class MarketController {
    private $db;

    // Скорость торговца (мин/клетку)
    private const MERCHANT_SPEED = 20;

    // Максимум ресурсов за одну сделку
    private const MAX_TRADE = 50000;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // ГЛАВНАЯ СТРАНИЦА РЫНКА
    // =========================================================
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id = $_SESSION['user_id'];
        $tab     = $_GET['tab'] ?? 'market';

        // Мои деревни
        $stmt = $this->db->prepare("
            SELECT id, name, x, y, r_wood, r_stone, r_iron
            FROM villages WHERE userid = ? ORDER BY id
        ");
        $stmt->execute([$user_id]);
        $my_villages = $stmt->fetchAll();

        // Активные предложения на рынке
        $stmt = $this->db->prepare("
            SELECT mo.*,
                   u.username,
                   v.name as village_name,
                   v.x, v.y
            FROM market_offers mo
            JOIN users    u ON mo.user_id    = u.id
            JOIN villages v ON mo.village_id = v.id
            WHERE mo.status = 'active'
            ORDER BY mo.created_at DESC
            LIMIT 50
        ");
        $stmt->execute();
        $market_offers = $stmt->fetchAll();

        // Мои предложения
        $stmt = $this->db->prepare("
            SELECT mo.*,
                   v.name as village_name
            FROM market_offers mo
            JOIN villages v ON mo.village_id = v.id
            WHERE mo.user_id = ?
            ORDER BY mo.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
        $my_offers = $stmt->fetchAll();

        // Мои торговые пути (внутренняя торговля)
        $stmt = $this->db->prepare("
            SELECT it.*,
                   v1.name as from_name,
                   v2.name as to_name
            FROM internal_trades it
            JOIN villages v1 ON it.from_village = v1.id
            JOIN villages v2 ON it.to_village   = v2.id
            WHERE it.user_id = ? AND it.status = 'moving'
            ORDER BY it.arrival_time ASC
        ");
        $stmt->execute([$user_id]);
        $my_trades = $stmt->fetchAll();

        // Обрабатываем прибывшие торговые пути
        $this->processArrivals();

        $db = $this->db;
        require_once __DIR__ . '/../templates/market.php';
    }

    // =========================================================
    // СОЗДАТЬ ПРЕДЛОЖЕНИЕ НА РЫНКЕ
    // =========================================================
    public function createOffer() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id     = $_SESSION['user_id'];
        $village_id  = (int)($_POST['village_id']   ?? 0);
        $offer_type  = $_POST['offer_type']          ?? '';
        $offer_amount= max(1, (int)($_POST['offer_amount'] ?? 0));
        $want_type   = $_POST['want_type']           ?? '';
        $want_amount = max(1, (int)($_POST['want_amount']  ?? 0));

        // Валидация типов ресурсов
        $valid_resources = ['wood', 'stone', 'iron'];
        if (!in_array($offer_type, $valid_resources) ||
            !in_array($want_type,  $valid_resources)) {
            $_SESSION['error'] = "Неверный тип ресурса!";
            header("Location: ?page=market");
            exit;
        }

        if ($offer_type === $want_type) {
            $_SESSION['error'] = "Нельзя торговать одинаковыми ресурсами!";
            header("Location: ?page=market");
            exit;
        }

        if ($offer_amount > self::MAX_TRADE || $want_amount > self::MAX_TRADE) {
            $_SESSION['error'] = "Максимум " . number_format(self::MAX_TRADE) . " ресурсов за сделку!";
            header("Location: ?page=market");
            exit;
        }

        // Проверяем деревню
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE id = ? AND userid = ?");
        $stmt->execute([$village_id, $user_id]);
        $village = $stmt->fetch();

        if (!$village) {
            $_SESSION['error'] = "Деревня не найдена!";
            header("Location: ?page=market");
            exit;
        }

        // Проверяем наличие ресурсов
        $res_field = 'r_' . $offer_type;
        if ((int)($village[$res_field] ?? 0) < $offer_amount) {
            $_SESSION['error'] = "Недостаточно ресурсов для предложения!";
            header("Location: ?page=market");
            exit;
        }

        // Проверяем количество активных предложений (макс 5)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as cnt FROM market_offers
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$user_id]);
        $active_count = $stmt->fetch()['cnt'] ?? 0;

        if ($active_count >= 5) {
            $_SESSION['error'] = "Максимум 5 активных предложений!";
            header("Location: ?page=market");
            exit;
        }

        // Резервируем ресурсы (снимаем из деревни)
        $this->db->prepare("
            UPDATE villages SET {$res_field} = {$res_field} - ?
            WHERE id = ?
        ")->execute([$offer_amount, $village_id]);

        // Создаём предложение
        $this->db->prepare("
            INSERT INTO market_offers
            (user_id, village_id, offer_type, offer_amount,
             want_type, want_amount, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'active', ?)
        ")->execute([
            $user_id, $village_id,
            $offer_type, $offer_amount,
            $want_type,  $want_amount,
            time()
        ]);

        $res_names = ['wood' => 'дерево', 'stone' => 'камень', 'iron' => 'железо'];

        $_SESSION['success'] = "✅ Предложение создано! " .
            number_format($offer_amount) . " " . $res_names[$offer_type] .
            " → " . number_format($want_amount) . " " . $res_names[$want_type];

        header("Location: ?page=market&tab=my_offers");
        exit;
    }

    // =========================================================
    // ПРИНЯТЬ ПРЕДЛОЖЕНИЕ
    // =========================================================
    public function acceptOffer() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id   = $_SESSION['user_id'];
        $offer_id  = (int)($_GET['id']         ?? 0);
        $village_id= (int)($_GET['village_id'] ?? 0);

        // Получаем предложение
        $stmt = $this->db->prepare("
            SELECT mo.*, v.x as seller_x, v.y as seller_y
            FROM market_offers mo
            JOIN villages v ON mo.village_id = v.id
            WHERE mo.id = ? AND mo.status = 'active'
        ");
        $stmt->execute([$offer_id]);
        $offer = $stmt->fetch();

        if (!$offer) {
            $_SESSION['error'] = "Предложение не найдено или уже завершено!";
            header("Location: ?page=market");
            exit;
        }

        // Нельзя принять своё предложение
        if ((int)$offer['user_id'] === $user_id) {
            $_SESSION['error'] = "Нельзя принять своё предложение!";
            header("Location: ?page=market");
            exit;
        }

        // Проверяем деревню покупателя
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE id = ? AND userid = ?");
        $stmt->execute([$village_id, $user_id]);
        $buyer_village = $stmt->fetch();

        if (!$buyer_village) {
            $_SESSION['error'] = "Деревня не найдена!";
            header("Location: ?page=market");
            exit;
        }

        // Проверяем наличие нужных ресурсов у покупателя
        $want_field = 'r_' . $offer['want_type'];
        if ((int)($buyer_village[$want_field] ?? 0) < $offer['want_amount']) {
            $_SESSION['error'] = "Недостаточно ресурсов для обмена!";
            header("Location: ?page=market");
            exit;
        }

        // Снимаем ресурсы у покупателя
        $this->db->prepare("
            UPDATE villages SET {$want_field} = {$want_field} - ?
            WHERE id = ?
        ")->execute([$offer['want_amount'], $village_id]);

        // Начисляем покупателю то что он купил
        $offer_field = 'r_' . $offer['offer_type'];
        $this->db->prepare("
            UPDATE villages SET {$offer_field} = {$offer_field} + ?
            WHERE id = ?
        ")->execute([$offer['offer_amount'], $village_id]);

        // Начисляем продавцу то что он хотел
        $this->db->prepare("
            UPDATE villages SET {$want_field} = {$want_field} + ?
            WHERE id = ?
        ")->execute([$offer['want_amount'], $offer['village_id']]);

        // Помечаем предложение как завершённое
        $this->db->prepare("
            UPDATE market_offers SET
                status       = 'completed',
                completed_at = ?,
                buyer_id     = ?
            WHERE id = ?
        ")->execute([time(), $user_id, $offer_id]);

        // Уведомляем продавца
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $buyer = $stmt->fetch();

        $res_names = ['wood'=>'🪵 дерево','stone'=>'🪨 камень','iron'=>'⛏ железо'];

        $this->db->prepare("
            INSERT INTO reports
            (userid, type, title, content, time, is_read)
            VALUES (?, 'market', ?, ?, ?, 0)
        ")->execute([
            $offer['user_id'],
            "💰 Ваше предложение принято!",
            "Игрок «{$buyer['username']}» принял ваше предложение!\n\n" .
            "Вы отдали: " . number_format($offer['offer_amount']) . " " .
                $res_names[$offer['offer_type']] . "\n" .
            "Вы получили: " . number_format($offer['want_amount']) . " " .
                $res_names[$offer['want_type']] . "\n\n" .
            "Ресурсы зачислены в деревню #{$offer['village_id']}",
            time()
        ]);

        $_SESSION['success'] = "✅ Обмен успешно совершён!";
        header("Location: ?page=market");
        exit;
    }

    // =========================================================
    // ОТМЕНИТЬ ПРЕДЛОЖЕНИЕ
    // =========================================================
    public function cancelOffer() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id  = $_SESSION['user_id'];
        $offer_id = (int)($_GET['id'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT * FROM market_offers
            WHERE id = ? AND user_id = ? AND status = 'active'
        ");
        $stmt->execute([$offer_id, $user_id]);
        $offer = $stmt->fetch();

        if (!$offer) {
            $_SESSION['error'] = "Предложение не найдено!";
            header("Location: ?page=market&tab=my_offers");
            exit;
        }

        // Возвращаем ресурсы в деревню
        $res_field = 'r_' . $offer['offer_type'];
        $this->db->prepare("
            UPDATE villages SET {$res_field} = {$res_field} + ?
            WHERE id = ?
        ")->execute([$offer['offer_amount'], $offer['village_id']]);

        // Отменяем предложение
        $this->db->prepare("
            UPDATE market_offers SET status = 'cancelled' WHERE id = ?
        ")->execute([$offer_id]);

        $_SESSION['success'] = "Предложение отменено. Ресурсы возвращены.";
        header("Location: ?page=market&tab=my_offers");
        exit;
    }

    // =========================================================
    // ВНУТРЕННЯЯ ТОРГОВЛЯ (между своими деревнями)
    // =========================================================
    public function internalTrade() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?page=login");
            exit;
        }

        $user_id      = $_SESSION['user_id'];
        $from_village = (int)($_POST['from_village'] ?? 0);
        $to_village   = (int)($_POST['to_village']   ?? 0);
        $wood         = max(0, (int)($_POST['wood']  ?? 0));
        $stone        = max(0, (int)($_POST['stone'] ?? 0));
        $iron         = max(0, (int)($_POST['iron']  ?? 0));

        if ($wood + $stone + $iron <= 0) {
            $_SESSION['error'] = "Укажите количество ресурсов для отправки!";
            header("Location: ?page=market&tab=internal");
            exit;
        }

        if ($from_village === $to_village) {
            $_SESSION['error'] = "Нельзя отправить ресурсы в ту же деревню!";
            header("Location: ?page=market&tab=internal");
            exit;
        }

        // Проверяем обе деревни
        $stmt = $this->db->prepare("
            SELECT * FROM villages WHERE id = ? AND userid = ?
        ");
        $stmt->execute([$from_village, $user_id]);
        $from_v = $stmt->fetch();

        if (!$from_v) {
            $_SESSION['error'] = "Деревня-отправитель не найдена!";
            header("Location: ?page=market&tab=internal");
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM villages WHERE id = ? AND userid = ?
        ");
        $stmt->execute([$to_village, $user_id]);
        $to_v = $stmt->fetch();

        if (!$to_v) {
            $_SESSION['error'] = "Деревня-получатель не найдена!";
            header("Location: ?page=market&tab=internal");
            exit;
        }

        // Проверяем ресурсы
        if ($from_v['r_wood']  < $wood ||
            $from_v['r_stone'] < $stone ||
            $from_v['r_iron']  < $iron) {
            $_SESSION['error'] = "Недостаточно ресурсов в деревне-отправителе!";
            header("Location: ?page=market&tab=internal");
            exit;
        }

        // Снимаем ресурсы
        $this->db->prepare("
            UPDATE villages SET
                r_wood  = r_wood  - ?,
                r_stone = r_stone - ?,
                r_iron  = r_iron  - ?
            WHERE id = ?
        ")->execute([$wood, $stone, $iron, $from_village]);

        // Рассчитываем время доставки
        $distance = sqrt(
            pow($to_v['x'] - $from_v['x'], 2) +
            pow($to_v['y'] - $from_v['y'], 2)
        );
        $travel_time = (int)ceil($distance * self::MERCHANT_SPEED * 60);
        $travel_time = max($travel_time, 60); // минимум 1 минута

        // Создаём торговый путь
        $this->db->prepare("
            INSERT INTO internal_trades
            (user_id, from_village, to_village, wood, stone, iron,
             departure_time, arrival_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'moving')
        ")->execute([
            $user_id, $from_village, $to_village,
            $wood, $stone, $iron,
            time(), time() + $travel_time
        ]);

        $mins = floor($travel_time / 60);
        $secs = $travel_time % 60;

        $_SESSION['success'] = "🚚 Ресурсы отправлены!<br>" .
            "🪵{$wood} 🪨{$stone} ⛏{$iron}<br>" .
            "Прибудут через: <strong>{$mins}м {$secs}с</strong>";

        header("Location: ?page=market&tab=internal");
        exit;
    }

    // =========================================================
    // ОБРАБОТКА ПРИБЫТИЯ ТОРГОВЦЕВ
    // =========================================================
    public function processArrivals() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM internal_trades
                WHERE status = 'moving'
                AND arrival_time <= " . time()
            );
            $arrivals = $stmt->fetchAll();

            foreach ($arrivals as $trade) {
                // Начисляем ресурсы
                $this->db->prepare("
                    UPDATE villages SET
                        r_wood  = r_wood  + ?,
                        r_stone = r_stone + ?,
                        r_iron  = r_iron  + ?
                    WHERE id = ?
                ")->execute([
                    (int)$trade['wood'],
                    (int)$trade['stone'],
                    (int)$trade['iron'],
                    (int)$trade['to_village']
                ]);

                $this->db->prepare("
                    UPDATE internal_trades SET status = 'arrived' WHERE id = ?
                ")->execute([$trade['id']]);
            }
        } catch (Exception $e) {
            // Игнорируем
        }
    }
}