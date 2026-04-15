<?php
// core/GameHelper.php

class GameHelper {

    /**
     * Форматирование времени
     */
    public static function formatTime($seconds) {
        $seconds = max(0, (int)$seconds);
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return sprintf('%d:%02d:%02d', $h, $m, $s);
        }
        return sprintf('%02d:%02d', $m, $s);
    }

    /**
     * Форматирование больших чисел
     */
    public static function formatNumber($num) {
        if ($num >= 1000000) {
            return round($num / 1000000, 1) . 'M';
        }
        if ($num >= 1000) {
            return round($num / 1000, 1) . 'K';
        }
        return number_format($num);
    }

    /**
     * Расстояние между координатами
     */
    public static function distance($x1, $y1, $x2, $y2) {
        return round(sqrt(pow($x2-$x1, 2) + pow($y2-$y1, 2)), 2);
    }

    /**
     * Континент по координатам
     */
    public static function getContinent($x, $y) {
        return floor(($y + 500) / 100) * 10 + floor(($x + 500) / 100);
    }

    /**
     * Название континента
     */
    public static function getContinentName($x, $y) {
        $k = self::getContinent($x, $y);
        return 'K' . $k;
    }

    /**
     * Время с момента события
     */
    public static function timeAgo($timestamp) {
        $diff = time() - $timestamp;

        if ($diff < 60)     return 'только что';
        if ($diff < 3600)   return floor($diff/60) . ' мин. назад';
        if ($diff < 86400)  return floor($diff/3600) . ' ч. назад';
        if ($diff < 604800) return floor($diff/86400) . ' дн. назад';

        return date('d.m.Y', $timestamp);
    }

    /**
     * Безопасный вывод HTML
     */
    public static function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Стоимость здания по уровню
     */
    public static function getBuildingCost($building, $level) {
        $multipliers = [
            'main'        => ['wood'=>200, 'stone'=>150, 'iron'=>100],
            'wood_level'  => ['wood'=>100, 'stone'=>80,  'iron'=>50],
            'stone_level' => ['wood'=>80,  'stone'=>100, 'iron'=>50],
            'iron_level'  => ['wood'=>80,  'stone'=>80,  'iron'=>100],
            'farm'        => ['wood'=>120, 'stone'=>100, 'iron'=>80],
            'storage'     => ['wood'=>120, 'stone'=>100, 'iron'=>80],
            'barracks'    => ['wood'=>150, 'stone'=>120, 'iron'=>100],
            'stable'      => ['wood'=>200, 'stone'=>150, 'iron'=>200],
            'smith'       => ['wood'=>150, 'stone'=>200, 'iron'=>100],
            'garage'      => ['wood'=>200, 'stone'=>150, 'iron'=>200],
            'wall'        => ['wood'=>150, 'stone'=>200, 'iron'=>100],
            'hide'        => ['wood'=>80,  'stone'=>60,  'iron'=>40],
        ];

        $base = $multipliers[$building] ?? ['wood'=>120,'stone'=>100,'iron'=>80];

        return [
            'wood'  => $base['wood']  * $level,
            'stone' => $base['stone'] * $level,
            'iron'  => $base['iron']  * $level,
        ];
    }

    /**
     * Проверка - онлайн ли игрок
     */
    public static function isOnline($last_activity) {
        return ($last_activity >= time() - 300);
    }

    /**
     * Генерация случайного цвета для аватара
     */
    public static function getAvatarColor($username) {
        $colors = [
            '#8b1a1a', '#1a3a8b', '#1a8b3a', '#8b6914',
            '#5a1a8b', '#1a7a8b', '#8b3a1a', '#2a5a1a'
        ];
        return $colors[crc32($username) % count($colors)];
    }
}