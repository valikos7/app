<?php
// core/BattleEngine.php

class BattleEngine {

    private static $units_cache = null;
    private static $db          = null;

    private static $units_default = [
        'spear' => [
            'name'=>'Копейщик', 'attack'=>10, 'def_inf'=>15, 'def_cav'=>45,
            'speed'=>18, 'carry'=>25, 'pop'=>1, 'type'=>'infantry'
        ],
        'sword' => [
            'name'=>'Мечник', 'attack'=>25, 'def_inf'=>50, 'def_cav'=>15,
            'speed'=>22, 'carry'=>15, 'pop'=>1, 'type'=>'infantry'
        ],
        'axe' => [
            'name'=>'Топорщик', 'attack'=>40, 'def_inf'=>10, 'def_cav'=>5,
            'speed'=>18, 'carry'=>10, 'pop'=>1, 'type'=>'infantry'
        ],
        'scout' => [
            'name'=>'Разведчик', 'attack'=>0, 'def_inf'=>2, 'def_cav'=>1,
            'speed'=>9, 'carry'=>0, 'pop'=>2, 'type'=>'cavalry'
        ],
        'light' => [
            'name'=>'Лёгкая кавалерия', 'attack'=>130, 'def_inf'=>30, 'def_cav'=>40,
            'speed'=>10, 'carry'=>80, 'pop'=>4, 'type'=>'cavalry'
        ],
        'heavy' => [
            'name'=>'Тяжёлая кавалерия', 'attack'=>150, 'def_inf'=>200, 'def_cav'=>80,
            'speed'=>11, 'carry'=>50, 'pop'=>6, 'type'=>'cavalry'
        ],
        'ram' => [
            'name'=>'Таран', 'attack'=>2, 'def_inf'=>20, 'def_cav'=>50,
            'speed'=>30, 'carry'=>0, 'pop'=>5, 'type'=>'infantry'
        ],
        'catapult' => [
            'name'=>'Катапульта', 'attack'=>100, 'def_inf'=>100, 'def_cav'=>50,
            'speed'=>30, 'carry'=>0, 'pop'=>8, 'type'=>'infantry'
        ]
    ];

    private static $research_map = [
        'spear'    => 'spear_att',
        'sword'    => 'sword_def',
        'axe'      => 'axe_att',
        'scout'    => 'scout_speed',
        'light'    => 'light_att',
        'heavy'    => 'heavy_def',
        'ram'      => 'ram_att',
        'catapult' => 'catapult_att'
    ];

    // =========================================================
    // ИНИЦИАЛИЗАЦИЯ
    // =========================================================
    public static function init($db) {
        self::$db = $db;
    }

    public static function clearCache() {
        self::$units_cache = null;
    }

    // =========================================================
    // ЗАГРУЗКА ЮНИТОВ
    // =========================================================
    private static function loadUnits() {
        if (self::$units_cache !== null) {
            return self::$units_cache;
        }

        if (self::$db === null) {
            self::$units_cache = self::$units_default;
            return self::$units_cache;
        }

        try {
            $stmt = self::$db->query("SELECT * FROM unit_config WHERE is_active = 1");
            $rows = $stmt->fetchAll();

            if (empty($rows)) {
                self::$units_cache = self::$units_default;
                return self::$units_cache;
            }

            $units = [];
            foreach ($rows as $row) {
                $units[$row['type']] = [
                    'name'       => $row['name'],
                    'attack'     => (int)$row['attack'],
                    'def_inf'    => (int)$row['def_inf'],
                    'def_cav'    => (int)$row['def_cav'],
                    'speed'      => (int)$row['speed'],
                    'carry'      => (int)$row['carry'],
                    'pop'        => (int)$row['pop'],
                    'type'       => $row['unit_type'],
                    'train_time' => (int)$row['train_time'],
                ];
            }

            self::$units_cache = $units;
            return self::$units_cache;

        } catch (Exception $e) {
            self::$units_cache = self::$units_default;
            return self::$units_cache;
        }
    }

    public static function getUnitStats() {
        return self::loadUnits();
    }

    public static function getUnitInfo($type) {
        $units = self::loadUnits();
        return $units[$type] ?? null;
    }

    // =========================================================
    // ВРЕМЯ ПОХОДА
    // =========================================================
    public static function calculateTravelTime($from_x, $from_y, $to_x, $to_y, $troops) {
        $units    = self::loadUnits();
        $distance = sqrt(pow($to_x - $from_x, 2) + pow($to_y - $from_y, 2));

        if ($distance == 0) return 60;

        $slowest = 0;
        foreach ($troops as $type => $count) {
            if ($count > 0 && isset($units[$type])) {
                $speed = $units[$type]['speed'];
                if ($speed > $slowest) $slowest = $speed;
            }
        }

        if ($slowest == 0) return 60;

        return (int)ceil($distance * $slowest * 60);
    }

    // =========================================================
    // ГРУЗОПОДЪЁМНОСТЬ
    // =========================================================
    public static function calculateCarryCapacity($troops) {
        $units = self::loadUnits();
        $total = 0;
        foreach ($troops as $type => $count) {
            if ($count > 0 && isset($units[$type])) {
                $total += $units[$type]['carry'] * $count;
            }
        }
        return $total;
    }

    // =========================================================
    // ОСНОВНОЙ БОЙ
    // =========================================================
    public static function battle(
        $attackers,
        $defenders,
        $wall_level   = 0,
        $att_research = [],
        $def_research = []
    ) {
        $units = self::loadUnits();

        $result = [
            'won'           => false,
            'att_survivors' => [],
            'att_losses'    => [],
            'def_survivors' => [],
            'def_losses'    => [],
            'wall_damage'   => 0,
            'att_power'     => 0,
            'def_power'     => 0,
            'luck'          => 0
        ];

        // Инициализируем нулями
        foreach ($attackers as $type => $count) {
            $result['att_losses'][$type]    = 0;
            $result['att_survivors'][$type] = max(0, (int)$count);
        }
        foreach ($defenders as $type => $count) {
            $result['def_losses'][$type]    = 0;
            $result['def_survivors'][$type] = max(0, (int)$count);
        }

        // === Сила атаки ===
        $total_att     = 0;
        $att_inf_count = 0;
        $att_cav_count = 0;

        foreach ($attackers as $type => $count) {
            $count = max(0, (int)$count);
            if ($count <= 0 || !isset($units[$type])) continue;

            $unit     = $units[$type];
            $unit_att = (float)$unit['attack'];

            // Бонус исследований
            $rkey = self::$research_map[$type] ?? null;
            if ($rkey && !empty($att_research[$rkey])) {
                $lvl       = max(0, (int)$att_research[$rkey]);
                $unit_att += $unit['attack'] * 0.05 * $lvl;
            }

            $total_att += $unit_att * $count;

            if ($unit['type'] === 'infantry') $att_inf_count += $count;
            else                              $att_cav_count += $count;
        }

        $total_att_units = $att_inf_count + $att_cav_count;
        $inf_ratio = $total_att_units > 0 ? $att_inf_count / $total_att_units : 0.5;
        $cav_ratio = 1 - $inf_ratio;

        // === Сила защиты ===
        $def_vs_inf = 0;
        $def_vs_cav = 0;

        foreach ($defenders as $type => $count) {
            $count = max(0, (int)$count);
            if ($count <= 0 || !isset($units[$type])) continue;

            $unit         = $units[$type];
            $unit_def_inf = (float)$unit['def_inf'];
            $unit_def_cav = (float)$unit['def_cav'];

            $rkey = self::$research_map[$type] ?? null;
            if ($rkey && !empty($def_research[$rkey])) {
                $lvl          = max(0, (int)$def_research[$rkey]);
                $unit_def_inf += $unit['def_inf'] * 0.05 * $lvl;
                $unit_def_cav += $unit['def_cav'] * 0.05 * $lvl;
            }

            $def_vs_inf += $unit_def_inf * $count;
            $def_vs_cav += $unit_def_cav * $count;
        }

        // Взвешенная защита
        $total_def  = ($def_vs_inf * $inf_ratio) + ($def_vs_cav * $cav_ratio);
        $wall_bonus = 1 + ($wall_level * 0.05);
        $total_def *= $wall_bonus;
        $total_def  = max($total_def, 20 + ($wall_level * 10));

        // Удача
        $luck          = rand(-25, 25);
        $luck_modifier = 1 + ($luck / 100);
        $effective_att = $total_att * $luck_modifier;

        $result['att_power'] = (int)$effective_att;
        $result['def_power'] = (int)$total_def;
        $result['luck']      = $luck;

        // === Победитель ===
        if ($effective_att > $total_def) {
            $result['won']  = true;
            $loss_ratio_att = pow($total_def / max(1.0, $effective_att), 1.5);
            $loss_ratio_def = 1.0;

            // Урон стене
            if (!empty($attackers['ram']) && (int)$attackers['ram'] > 0) {
                $ram_unit = $units['ram'] ?? null;
                $ram_att  = $ram_unit ? (float)$ram_unit['attack'] : 2.0;

                $rkey = self::$research_map['ram'] ?? null;
                if ($rkey && !empty($att_research[$rkey])) {
                    $lvl     = max(0, (int)$att_research[$rkey]);
                    $ram_att += $ram_att * 0.05 * $lvl;
                }

                $result['wall_damage'] = min(
                    $wall_level,
                    (int)floor(((int)$attackers['ram'] * $ram_att) / 20)
                );
            }

        } else {
            $result['won']  = false;
            $loss_ratio_att = 1.0;
            $loss_ratio_def = pow($effective_att / max(1.0, $total_def), 1.5);
        }

        // Ограничиваем ratio
        $loss_ratio_att = min(1.0, max(0.0, $loss_ratio_att));
        $loss_ratio_def = min(1.0, max(0.0, $loss_ratio_def));

        // === Потери атакующего ===
        foreach ($attackers as $type => $count) {
            $count   = max(0, (int)$count);
            $losses  = (int)round($count * $loss_ratio_att);
            $losses  = max(0, min($losses, $count));
            $result['att_losses'][$type]    = $losses;
            $result['att_survivors'][$type] = $count - $losses;
        }

        // === Потери защитника ===
        foreach ($defenders as $type => $count) {
            $count   = max(0, (int)$count);
            $losses  = (int)round($count * $loss_ratio_def);
            $losses  = max(0, min($losses, $count));
            $result['def_losses'][$type]    = $losses;
            $result['def_survivors'][$type] = $count - $losses;
        }

        return $result;
    }

    // =========================================================
    // ГРАБЁЖ (с учётом тайника)
    // =========================================================
    public static function calculateLoot($carry_capacity, $target_village) {
        $hide_level = (int)($target_village['hide'] ?? 0);
        $protected  = $hide_level * 200;

        $available_wood  = max(0, (int)($target_village['r_wood']  ?? 0) - $protected);
        $available_stone = max(0, (int)($target_village['r_stone'] ?? 0) - $protected);
        $available_iron  = max(0, (int)($target_village['r_iron']  ?? 0) - $protected);

        $total_available = $available_wood + $available_stone + $available_iron;

        if ($total_available <= 0 || $carry_capacity <= 0) {
            return ['wood'=>0,'stone'=>0,'iron'=>0];
        }

        $loot_total = min((int)$carry_capacity, $total_available);

        return [
            'wood'  => (int)floor($loot_total * ($available_wood  / $total_available)),
            'stone' => (int)floor($loot_total * ($available_stone / $total_available)),
            'iron'  => (int)floor($loot_total * ($available_iron  / $total_available))
        ];
    }
}
