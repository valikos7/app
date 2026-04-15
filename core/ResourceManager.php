<?php
// core/ResourceManager.php

class ResourceManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function updateResources($village_id) {
        $stmt = $this->db->prepare("SELECT * FROM villages WHERE id=?");
        $stmt->execute([$village_id]);
        $village = $stmt->fetch();
        if (!$village) return;

        $now         = time();
        $last_update = (int)($village['last_prod_aktu'] ?? $now);
        $diff        = max(0, $now - $last_update);
        if ($diff < 5) return;

        $production  = $this->calculateProductionPerSecond($village);
        $max_storage = $this->getMaxStorage($village);
        $max_food    = $this->getMaxFood($village);

        $new_wood  = min($village['r_wood']  + round($production['wood']  * $diff), $max_storage);
        $new_stone = min($village['r_stone'] + round($production['stone'] * $diff), $max_storage);
        $new_iron  = min($village['r_iron']  + round($production['iron']  * $diff), $max_storage);
        $new_food  = min(($village['food']??0) + round($production['food'] * $diff), $max_food);

        $this->db->prepare("UPDATE villages SET
            r_wood=?, r_stone=?, r_iron=?, food=?, last_prod_aktu=?
            WHERE id=?
        ")->execute([$new_wood,$new_stone,$new_iron,$new_food,$now,$village_id]);
    }

    public function getProductionPerHour($village) {
        $ps = $this->calculateProductionPerSecond($village);
        return [
            'wood'  => round($ps['wood']  * 3600),
            'stone' => round($ps['stone'] * 3600),
            'iron'  => round($ps['iron']  * 3600)
        ];
    }

    private function calculateProductionPerSecond($village) {
        $config      = $this->getBaseProduction();
        $wood_level  = (int)($village['wood_level']  ?? 0);
        $stone_level = (int)($village['stone_level'] ?? 0);
        $iron_level  = (int)($village['iron_level']  ?? 0);
        $farm_level  = (int)($village['farm']        ?? 1);
        $user_id     = (int)($village['userid']      ?? 0);
        $village_id  = (int)($village['id']          ?? 0);

        // === Бонус героя ===
        $hero_bonus = 0;
        try {
            if ($user_id > 0) {
                $hc   = new HeroController($this->db);
                $hero = $hc->getHero($user_id);
                if ($hero && $hero['status']==='alive' &&
                    (int)($hero['village_id']??0)===$village_id) {
                    $bon        = $hc->getHeroBonuses($user_id);
                    $hero_bonus = ((float)($bon['resource']??0)) / 100;
                }
            }
        } catch (Exception $e) {}

        // === Бонус зелья богатства ===
        $potion_bonus = 0;
        try {
            if ($user_id > 0) {
                $key  = "potion_res_{$user_id}";
                $stmt = $this->db->prepare("SELECT value FROM game_config WHERE `key`=?");
                $stmt->execute([$key]);
                $row  = $stmt->fetch();
                if ($row && !empty($row['value'])) {
                    $parts   = explode(':', $row['value']);
                    $bonus   = (float)($parts[0]??0);
                    $expires = (int)($parts[1]??0);
                    if ($expires > time()) {
                        $potion_bonus = $bonus / 100;
                    } else {
                        $this->db->prepare("DELETE FROM game_config WHERE `key`=?")->execute([$key]);
                    }
                }
            }
        } catch (Exception $e) {}

        // === Бонус мирового события ===
        $event_bonus = 0;
        try {
            $stmt = $this->db->prepare("SELECT value FROM game_config WHERE `key`='active_event_resource_bonus'");
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && !empty($row['value'])) {
                $parts   = explode(':', $row['value']);
                $bonus   = (float)($parts[0]??0);
                $expires = (int)($parts[1]??0);
                if ($expires > time()) $event_bonus = $bonus / 100;
            }
        } catch (Exception $e) {}

        // === Бонус технологий (prod_boost) ===
        $tech_bonus = 0;
        try {
            if ($user_id > 0) {
                $stmt = $this->db->prepare("
                    SELECT level FROM player_technologies
                    WHERE user_id=? AND tech_code='prod_boost' AND level>0
                ");
                $stmt->execute([$user_id]);
                $row = $stmt->fetch();
                if ($row) $tech_bonus = (int)$row['level'] * 10 / 100;
            }
        } catch (Exception $e) {}

        // === Бонус ранга ===
        $rank_bonus = 0;
        try {
            if ($user_id > 0) {
                $rank_bonuses = RankController::getRankBonuses($this->db, $user_id);
                $rank_bonus   = ((float)($rank_bonuses['production'] ?? 0)) / 100;
            }
        } catch (Exception $e) {}

        // === Налогообложение (taxation) ===
        $taxation_per_hour = 0;
        try {
            if ($user_id > 0) {
                $stmt = $this->db->prepare("
                    SELECT level FROM player_technologies
                    WHERE user_id=? AND tech_code='taxation' AND level>0
                ");
                $stmt->execute([$user_id]);
                $row = $stmt->fetch();
                if ($row) $taxation_per_hour = (int)$row['level'] * 50;
            }
        } catch (Exception $e) {}

        $multiplier = 1 + $hero_bonus + $potion_bonus + $event_bonus + $tech_bonus + $rank_bonus;

        return [
            'wood'  => ($config['base_wood_per_hour']  * (1 + $wood_level  * 0.20) * $multiplier + $taxation_per_hour/3) / 3600,
            'stone' => ($config['base_stone_per_hour'] * (1 + $stone_level * 0.20) * $multiplier + $taxation_per_hour/3) / 3600,
            'iron'  => ($config['base_iron_per_hour']  * (1 + $iron_level  * 0.20) * $multiplier + $taxation_per_hour/3) / 3600,
            'food'  => ($farm_level * 120) / 3600
        ];
    }

    private function getBaseProduction() {
        try {
            $stmt = $this->db->query("SELECT `key`,`value` FROM game_config
                WHERE `key` IN ('base_wood_per_hour','base_stone_per_hour','base_iron_per_hour')");
            $config = [];
            while ($row = $stmt->fetch()) $config[$row['key']] = (int)$row['value'];
            return [
                'base_wood_per_hour'  => $config['base_wood_per_hour']  ?? 40,
                'base_stone_per_hour' => $config['base_stone_per_hour'] ?? 40,
                'base_iron_per_hour'  => $config['base_iron_per_hour']  ?? 40
            ];
        } catch (Exception $e) {
            return ['base_wood_per_hour'=>40,'base_stone_per_hour'=>40,'base_iron_per_hour'=>40];
        }
    }

    public function getMaxStorage($village) {
        $storage_level = (int)($village['storage'] ?? 1);
        $base          = 800 + ($storage_level * 400);

        // Бонус технологии storage_plus
        try {
            $user_id = (int)($village['userid'] ?? 0);
            if ($user_id > 0) {
                $stmt = $this->db->prepare("
                    SELECT level FROM player_technologies
                    WHERE user_id=? AND tech_code='storage_plus' AND level>0
                ");
                $stmt->execute([$user_id]);
                $row = $stmt->fetch();
                if ($row) $base = (int)($base * (1 + (int)$row['level'] * 0.15));
            }
        } catch (Exception $e) {}

        return $base;
    }

    public function getMaxFood($village) {
        $storage_level = (int)($village['storage'] ?? 1);
        return 1000 + ($storage_level * 500);
    }

    public function getMaxPopulation($village) {
        $farm_level = (int)($village['farm'] ?? 1);
        return 100 + ($farm_level * 80);
    }
}