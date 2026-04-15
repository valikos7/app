<?php
// core/NotificationManager.php

class NotificationManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Получить все уведомления для пользователя
     */
    public function getNotifications($user_id, $limit = 10) {
        $notifs = [];

        // Непрочитанные сообщения
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as cnt FROM messages
            WHERE to_id = ? AND is_read = 0 AND deleted_by_receiver = 0
        ");
        $stmt->execute([$user_id]);
        $unread_msg = $stmt->fetch()['cnt'] ?? 0;

        if ($unread_msg > 0) {
            $notifs[] = [
                'type'  => 'message',
                'icon'  => '✉️',
                'text'  => "У вас $unread_msg непрочитанных сообщений",
                'link'  => '?page=messages',
                'color' => '#4a8a4a'
            ];
        }

        // Непрочитанные отчёты
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as cnt FROM reports
            WHERE userid = ? AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        $unread_rep = $stmt->fetch()['cnt'] ?? 0;

        if ($unread_rep > 0) {
            $notifs[] = [
                'type'  => 'report',
                'icon'  => '⚔️',
                'text'  => "Новых боевых отчётов: $unread_rep",
                'link'  => '?page=reports',
                'color' => '#8a4a4a'
            ];
        }

        // Завершённые постройки
        $stmt = $this->db->prepare("
            SELECT v.id, v.name, v.build_queue
            FROM villages v
            WHERE v.userid = ?
            AND v.build_queue IS NOT NULL
            AND v.queue_end_time <= ?
            AND v.queue_end_time > 0
        ");
        $stmt->execute([$user_id, time()]);
        $done_builds = $stmt->fetchAll();

        foreach ($done_builds as $v) {
            $building_names = [
                'main'=>'Главное здание','wood_level'=>'Лесопилка',
                'stone_level'=>'Каменоломня','iron_level'=>'Шахта',
                'farm'=>'Ферма','storage'=>'Склад','barracks'=>'Казармы',
                'stable'=>'Конюшня','smith'=>'Кузница',
                'garage'=>'Мастерская','wall'=>'Стена','hide'=>'Тайник'
            ];
            $bname = $building_names[$v['build_queue']] ?? $v['build_queue'];
            $notifs[] = [
                'type'  => 'build',
                'icon'  => '🔨',
                'text'  => "Построено: $bname в деревне \"{$v['name']}\"",
                'link'  => "?page=village&id={$v['id']}",
                'color' => '#4a6a4a'
            ];
        }

        // Завершённые тренировки
        $stmt = $this->db->prepare("
            SELECT v.id, v.name, v.train_queue
            FROM villages v
            WHERE v.userid = ?
            AND v.train_queue IS NOT NULL
            AND v.train_end_time <= ?
            AND v.train_end_time > 0
        ");
        $stmt->execute([$user_id, time()]);
        $done_trains = $stmt->fetchAll();

        foreach ($done_trains as $v) {
            $unit_names = [
                'spear'=>'Копейщики','sword'=>'Мечники','axe'=>'Топорщики',
                'scout'=>'Разведчики','light'=>'Лёгкая кав.','heavy'=>'Тяжёлая кав.'
            ];
            list($utype) = explode(':', $v['train_queue'] . ':');
            $uname = $unit_names[$utype] ?? $utype;
            $notifs[] = [
                'type'  => 'train',
                'icon'  => '⚔️',
                'text'  => "Тренировка завершена: $uname в \"{$v['name']}\"",
                'link'  => "?page=village&id={$v['id']}&screen=barracks",
                'color' => '#4a4a8a'
            ];
        }

        // Прибывающие войска (скоро)
        $stmt = $this->db->prepare("
            SELECT tm.*, v.name as to_name
            FROM troop_movements tm
            JOIN villages v ON tm.to_village_id = v.id
            WHERE v.userid = ?
            AND tm.status = 'moving'
            AND tm.type = 'attack'
            AND tm.arrival_time <= ?
        ");
        $stmt->execute([$user_id, time() + 1800]); // через 30 минут
        $incoming = $stmt->fetchAll();

        foreach ($incoming as $mov) {
            $mins = max(0, ceil(($mov['arrival_time'] - time()) / 60));
            $notifs[] = [
                'type'  => 'attack',
                'icon'  => '⚠️',
                'text'  => "Входящая атака на \"{$mov['to_name']}\" через {$mins} мин!",
                'link'  => "?page=map",
                'color' => '#8a1a1a'
            ];
        }

        return array_slice($notifs, 0, $limit);
    }

    /**
     * Получить счётчики для навбара
     */
    public function getCounters($user_id) {
        $counters = [
            'messages' => 0,
            'reports'  => 0,
            'attacks'  => 0
        ];

        // Непрочитанные сообщения
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as cnt FROM messages
            WHERE to_id = ? AND is_read = 0 AND deleted_by_receiver = 0
        ");
        $stmt->execute([$user_id]);
        $counters['messages'] = $stmt->fetch()['cnt'] ?? 0;

        // Непрочитанные отчёты
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt FROM reports
                WHERE userid = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            $counters['reports'] = $stmt->fetch()['cnt'] ?? 0;
        } catch (Exception $e) {
            $counters['reports'] = 0;
        }

        // Входящие атаки
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as cnt
            FROM troop_movements tm
            JOIN villages v ON tm.to_village_id = v.id
            WHERE v.userid = ? AND tm.status = 'moving' AND tm.type = 'attack'
        ");
        $stmt->execute([$user_id]);
        $counters['attacks'] = $stmt->fetch()['cnt'] ?? 0;

        return $counters;
    }
}