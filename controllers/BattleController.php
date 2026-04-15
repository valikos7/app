<?php
// controllers/BattleController.php

class BattleController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================
    // ФОРМА АТАКИ
    // =========================================================
    public function showAttackForm() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $target_id=(int)($_GET['target']??0);
        if ($target_id<=0) { $_SESSION['error']="Неверный ID цели."; header("Location: ?page=map"); exit; }

        $stmt=$this->db->prepare("SELECT id,name,x,y FROM villages WHERE userid=?");
        $stmt->execute([$_SESSION['user_id']]);
        $my_villages=$stmt->fetchAll();
        if (empty($my_villages)) { $_SESSION['error']="У вас нет деревень!"; header("Location: ?page=map"); exit; }

        $stmt=$this->db->prepare("SELECT v.*,IF(v.userid=-1,'Варвары',u.username) as owner_name
            FROM villages v LEFT JOIN users u ON v.userid=u.id WHERE v.id=?");
        $stmt->execute([$target_id]);
        $target=$stmt->fetch();
        if (!$target) { $_SESSION['error']="Целевая деревня не найдена."; header("Location: ?page=map"); exit; }

        $village_troops=[];
        foreach ($my_villages as $v) {
            $stmt=$this->db->prepare("SELECT * FROM unit_place WHERE villages_to_id=?");
            $stmt->execute([$v['id']]);
            $village_troops[$v['id']]=$stmt->fetch()?:[
                'spear'=>0,'sword'=>0,'axe'=>0,'scout'=>0,
                'light'=>0,'heavy'=>0,'ram'=>0,'catapult'=>0,'nobleman'=>0
            ];
        }

        $mode=$_GET['mode']??'attack';
        if (!in_array($mode,['attack','spy','support'])) $mode='attack';
        $is_own=((int)$target['userid']===(int)$_SESSION['user_id']);
        if ($is_own&&$mode!=='support') $mode='support';

        $unit_stats=BattleEngine::getUnitStats();
        $db=$this->db;
        require_once __DIR__.'/../templates/attack_form.php';
    }

    // =========================================================
    // БОНУС СКОРОСТИ
    // =========================================================
    private function getSpeedBonus($user_id) {
        $total=0;

        try { $hc=new HeroController($this->db); $bon=$hc->getHeroBonuses($user_id); $total+=(float)($bon['speed']??0); } catch (Exception $e) {}

        try {
            $key="potion_speed_{$user_id}";
            $stmt=$this->db->prepare("SELECT value FROM game_config WHERE `key`=?");
            $stmt->execute([$key]); $row=$stmt->fetch();
            if ($row&&!empty($row['value'])) {
                $parts=explode(':',$row['value']);
                $bonus=(float)($parts[0]??0); $expires=(int)($parts[1]??0);
                if ($expires>time()) $total+=$bonus;
                else $this->db->prepare("DELETE FROM game_config WHERE `key`=?")->execute([$key]);
            }
        } catch (Exception $e) {}

        try {
            $stmt=$this->db->prepare("SELECT level FROM player_technologies WHERE user_id=? AND tech_code='march_speed' AND level>0");
            $stmt->execute([$user_id]); $row=$stmt->fetch();
            if ($row) $total+=(int)$row['level']*8;
        } catch (Exception $e) {}

        try { $rb=RankController::getRankBonuses($this->db,$user_id); $total+=(float)($rb['speed']??0); } catch (Exception $e) {}

        return $total;
    }

    private function applySpeedBonus($travel_time,$speed_bonus) {
        if ($speed_bonus>0) $travel_time=(int)ceil($travel_time*(1-$speed_bonus/100));
        return max(60,$travel_time);
    }

    // =========================================================
    // ЗАПУСК АТАКИ
    // =========================================================
    public function launchAttack() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $attacker_id=$_SESSION['user_id'];
        $from_village_id=(int)($_POST['from_village']??0);
        $target_village_id=(int)($_POST['target_village']??0);

        $unit_types=['spear','sword','axe','scout','light','heavy','ram','catapult','nobleman'];
        $troops=[]; $total=0;
        foreach ($unit_types as $type) { $troops[$type]=max(0,(int)($_POST[$type]??0)); $total+=$troops[$type]; }

        if ($total<=0) { $_SESSION['error']="Выберите хотя бы одного воина!"; header("Location: ?page=map"); exit; }

        $stmt=$this->db->prepare("SELECT * FROM villages WHERE id=? AND userid=?");
        $stmt->execute([$from_village_id,$attacker_id]);
        $from_village=$stmt->fetch();
        if (!$from_village) { $_SESSION['error']="Деревня не принадлежит вам!"; header("Location: ?page=map"); exit; }

        $stmt=$this->db->prepare("SELECT userid FROM villages WHERE id=?");
        $stmt->execute([$target_village_id]);
        $tc=$stmt->fetch();
        if ($tc&&(int)$tc['userid']===$attacker_id) { $_SESSION['error']="Нельзя атаковать свою деревню!"; header("Location: ?page=map"); exit; }

        // Щит мира
        if ($tc&&$tc['userid']>0) {
            try { if (BlackMarketController::hasPeaceShield($this->db,$tc['userid'])) { $_SESSION['error']="На деревне противника активен Щит мира!"; header("Location: ?page=map"); exit; } } catch (Exception $e) {}
        }

        $stmt=$this->db->prepare("SELECT * FROM unit_place WHERE villages_to_id=?");
        $stmt->execute([$from_village_id]);
        $current=$stmt->fetch()?:[];
        foreach ($troops as $type=>$count) {
            if ($count>(int)($current[$type]??0)) {
                $_SESSION['error']="Недостаточно войск {$type}! Есть:".($current[$type]??0);
                header("Location: ?page=attack&target={$target_village_id}"); exit;
            }
        }

        $stmt=$this->db->prepare("SELECT * FROM villages WHERE id=?");
        $stmt->execute([$target_village_id]);
        $target_village=$stmt->fetch();
        if (!$target_village) { $_SESSION['error']="Целевая деревня не найдена."; header("Location: ?page=map"); exit; }

        if ($troops['nobleman']>0&&(int)$target_village['userid']===-1) {
            $_SESSION['error']="Нельзя отправить Дворянина против варваров!";
            header("Location: ?page=attack&target={$target_village_id}"); exit;
        }

        $this->subtractTroops($from_village_id,$troops);

        $travel_time=BattleEngine::calculateTravelTime(
            $from_village['x'],$from_village['y'],
            $target_village['x'],$target_village['y'],$troops
        );
        $speed_bonus=$this->getSpeedBonus($attacker_id);
        $travel_time=$this->applySpeedBonus($travel_time,$speed_bonus);

        $this->db->prepare("INSERT INTO troop_movements
            (attacker_id,from_village_id,to_village_id,troops,departure_time,arrival_time,type,status)
            VALUES (?,?,?,?,?,?,'attack','moving')")
            ->execute([$attacker_id,$from_village_id,$target_village_id,
                json_encode($troops),time(),time()+$travel_time]);

        $mins=floor($travel_time/60); $secs=$travel_time%60;
        $_SESSION['success']="⚔ Войска отправлены!<br>Время: <strong>{$mins}м {$secs}с</strong>".
            ($speed_bonus>0?" (бонус:-{$speed_bonus}%)":'').
            ($troops['nobleman']>0?"<br>👑 Дворянин отправлен!":'');
        header("Location: ?page=profile"); exit;
    }

    // =========================================================
    // ЗАПУСК ШПИОНАЖА
    // =========================================================
    public function launchSpy() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $attacker_id=$_SESSION['user_id'];
        $from_village_id=(int)($_POST['from_village']??0);
        $target_village_id=(int)($_POST['target_village']??0);
        $scout_count=max(1,(int)($_POST['scout']??0));

        $stmt=$this->db->prepare("SELECT * FROM villages WHERE id=? AND userid=?");
        $stmt->execute([$from_village_id,$attacker_id]);
        $from_village=$stmt->fetch();
        if (!$from_village) { $_SESSION['error']="Деревня не принадлежит вам!"; header("Location: ?page=map"); exit; }

        $stmt=$this->db->prepare("SELECT userid FROM villages WHERE id=?");
        $stmt->execute([$target_village_id]);
        $tc=$stmt->fetch();
        if ($tc&&(int)$tc['userid']===$attacker_id) { $_SESSION['error']="Нельзя шпионить за собой!"; header("Location: ?page=map"); exit; }

        $stmt=$this->db->prepare("SELECT scout FROM unit_place WHERE villages_to_id=?");
        $stmt->execute([$from_village_id]);
        $units=$stmt->fetch(); $avail=(int)($units['scout']??0);
        if ($avail<$scout_count) { $_SESSION['error']="Недостаточно разведчиков! Есть:{$avail}"; header("Location: ?page=attack&target={$target_village_id}&mode=spy"); exit; }

        $stmt=$this->db->prepare("SELECT * FROM villages WHERE id=?");
        $stmt->execute([$target_village_id]);
        $target_village=$stmt->fetch();
        if (!$target_village) { $_SESSION['error']="Целевая деревня не найдена."; header("Location: ?page=map"); exit; }

        $troops=['spear'=>0,'sword'=>0,'axe'=>0,'scout'=>$scout_count,'light'=>0,'heavy'=>0,'ram'=>0,'catapult'=>0,'nobleman'=>0];
        $this->subtractTroops($from_village_id,$troops);

        $travel_time=BattleEngine::calculateTravelTime(
            $from_village['x'],$from_village['y'],
            $target_village['x'],$target_village['y'],$troops
        );
        $speed_bonus=$this->getSpeedBonus($attacker_id);
        $travel_time=$this->applySpeedBonus($travel_time,$speed_bonus);

        $this->db->prepare("INSERT INTO troop_movements
            (attacker_id,from_village_id,to_village_id,troops,departure_time,arrival_time,type,status)
            VALUES (?,?,?,?,?,?,'scout','moving')")
            ->execute([$attacker_id,$from_village_id,$target_village_id,
                json_encode(['scout'=>$scout_count]),time(),time()+$travel_time]);

        $mins=floor($travel_time/60); $secs=$travel_time%60;
        $_SESSION['success']="🔍 Разведчики отправлены! ({$scout_count})<br>Время: <strong>{$mins}м {$secs}с</strong>";
        header("Location: ?page=profile"); exit;
    }

    // =========================================================
    // ЗАПУСК ПОДДЕРЖКИ
    // =========================================================
    public function launchSupport() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $user_id=$_SESSION['user_id'];
        $from_village_id=(int)($_POST['from_village']??0);
        $target_village_id=(int)($_POST['target_village']??0);

        $unit_types=['spear','sword','axe','scout','light','heavy','ram','catapult'];
        $troops=[]; $total=0;
        foreach ($unit_types as $type) { $troops[$type]=max(0,(int)($_POST[$type]??0)); $total+=$troops[$type]; }
        if ($total<=0) { $_SESSION['error']="Выберите хотя бы одного воина!"; header("Location: ?page=map"); exit; }

        $stmt=$this->db->prepare("SELECT * FROM villages WHERE id=? AND userid=?");
        $stmt->execute([$from_village_id,$user_id]);
        $from_village=$stmt->fetch();
        if (!$from_village) { $_SESSION['error']="Деревня не принадлежит вам!"; header("Location: ?page=map"); exit; }

        $stmt=$this->db->prepare("SELECT v.*,u.username as owner_name FROM villages v LEFT JOIN users u ON v.userid=u.id WHERE v.id=?");
        $stmt->execute([$target_village_id]);
        $target_village=$stmt->fetch();
        if (!$target_village) { $_SESSION['error']="Деревня не найдена."; header("Location: ?page=map"); exit; }
        if ((int)$target_village['userid']===-1) { $_SESSION['error']="Нельзя поддерживать варваров!"; header("Location: ?page=map"); exit; }

        $stmt=$this->db->prepare("SELECT * FROM unit_place WHERE villages_to_id=?");
        $stmt->execute([$from_village_id]);
        $current=$stmt->fetch()?:[];
        foreach ($troops as $type=>$count) {
            if ($count>(int)($current[$type]??0)) {
                $_SESSION['error']="Недостаточно войск {$type}!";
                header("Location: ?page=attack&target={$target_village_id}&mode=support"); exit;
            }
        }

        $this->subtractTroops($from_village_id,$troops);

        $travel_time=BattleEngine::calculateTravelTime(
            $from_village['x'],$from_village['y'],
            $target_village['x'],$target_village['y'],$troops
        );
        $speed_bonus=$this->getSpeedBonus($user_id);
        $travel_time=$this->applySpeedBonus($travel_time,$speed_bonus);

        $this->db->prepare("INSERT INTO troop_movements
            (attacker_id,from_village_id,to_village_id,troops,departure_time,arrival_time,type,status,extra_data)
            VALUES (?,?,?,?,?,?,'support','moving',?)")
            ->execute([$user_id,$from_village_id,$target_village_id,
                json_encode($troops),time(),time()+$travel_time,
                json_encode(['owner_village_id'=>$from_village_id,'owner_user_id'=>$user_id])]);

        $mins=floor($travel_time/60); $secs=$travel_time%60;
        $_SESSION['success']="🛡 Поддержка отправлена в «".htmlspecialchars($target_village['name'])."»!<br>Время: <strong>{$mins}м {$secs}с</strong>";
        header("Location: ?page=profile"); exit;
    }

    // =========================================================
    // ОБРАБОТКА ПРИБЫТИЙ
    // =========================================================
    public function processArrivals() {
        try {
            $stmt=$this->db->query("SELECT * FROM troop_movements
                WHERE status='moving' AND arrival_time<=".time()." ORDER BY arrival_time ASC LIMIT 50");
            $arrivals=$stmt->fetchAll();
            foreach ($arrivals as $movement) {
                try {
                    switch ($movement['type']) {
                        case 'attack':  $this->resolveAttack($movement);  break;
                        case 'scout':   $this->resolveSpy($movement);     break;
                        case 'support': $this->resolveSupport($movement); break;
                        case 'return':  $this->resolveReturn($movement);  break;
                    }
                } catch (Exception $e) { error_log("resolveMovement #{$movement['id']}: ".$e->getMessage()); }
                $this->db->prepare("UPDATE troop_movements SET status='completed' WHERE id=?")->execute([$movement['id']]);
            }
        } catch (Exception $e) { error_log("processArrivals: ".$e->getMessage()); }
    }

    // =========================================================
    // РАЗРЕШЕНИЕ АТАКИ
    // =========================================================
    private function resolveAttack($movement) {
        $attackers=json_decode($movement['troops'],true);
        $from_village_id=(int)$movement['from_village_id'];
        $to_village_id=(int)$movement['to_village_id'];
        $attacker_id=(int)$movement['attacker_id'];

        foreach ($attackers as $type=>$count) $attackers[$type]=max(0,(int)$count);

        $stmt=$this->db->prepare("SELECT * FROM villages WHERE id=?");
        $stmt->execute([$to_village_id]);
        $target_village=$stmt->fetch();
        if (!$target_village) return;

        $defender_id=(int)$target_village['userid'];
        $is_barbarian=($defender_id===-1);
        $wall_level=(int)($target_village['wall']??0);

        $defenders=$this->getVillageTroops($to_village_id);
        if ($is_barbarian) {
            $pts=max(10,(int)($target_village['points']??50)); $k=max(1,(int)($pts/20));
            $defenders=['spear'=>rand($k,$k*3),'sword'=>rand(0,$k*2),'axe'=>rand(0,$k),
                'scout'=>0,'light'=>0,'heavy'=>0,'ram'=>0,'catapult'=>0,'nobleman'=>0];
        }

        // Поддержка союзников
        if (!$is_barbarian) {
            $stmt=$this->db->prepare("SELECT troops FROM troop_movements WHERE to_village_id=? AND type='support' AND status='completed'");
            $stmt->execute([$to_village_id]);
            foreach ($stmt->fetchAll() as $sup) {
                $st=json_decode($sup['troops'],true);
                foreach ($st as $type=>$count) $defenders[$type]=($defenders[$type]??0)+max(0,(int)$count);
            }
        }

        $att_research=$this->getResearch($from_village_id);
        $def_research=$is_barbarian?[]:$this->getResearch($to_village_id);

        // Бонусы героев
        $att_hero_bonus=0; $def_hero_bonus=0;
        try {
            $hc=new HeroController($this->db);
            $att_bon=$hc->getHeroBonuses($attacker_id); $att_hero_bonus=(float)($att_bon['attack']??0);
            if (!$is_barbarian&&$defender_id>0) { $def_bon=$hc->getHeroBonuses($defender_id); $def_hero_bonus=(float)($def_bon['defense']??0); }
        } catch (Exception $e) {}

        $result=BattleEngine::battle($attackers,$defenders,$wall_level,$att_research,$def_research);

        if ($att_hero_bonus>0) $result['att_power']=(int)($result['att_power']*(1+$att_hero_bonus/100));
        if ($def_hero_bonus>0) $result['def_power']=(int)($result['def_power']*(1+$def_hero_bonus/100));

        // Бонусы технологий
        try {
            $tc=new TechController($this->db);
            $att_tech=$tc->getBonuses($attacker_id);
            if (!empty($att_tech['attack'])) $result['att_power']=(int)($result['att_power']*(1+(float)$att_tech['attack']/100));
            if (!empty($att_tech['siege'])&&(($attackers['ram']??0)>0||($attackers['catapult']??0)>0)) {
                $result['wall_damage']=(int)($result['wall_damage']*(1+(float)$att_tech['siege']/100));
            }
            if (!$is_barbarian&&$defender_id>0) {
                $def_tech=$tc->getBonuses($defender_id);
                if (!empty($def_tech['defense'])) $result['def_power']=(int)($result['def_power']*(1+(float)$def_tech['defense']/100));
            }
        } catch (Exception $e) {}

        // Бонусы рангов
        try {
            $att_rank=RankController::getRankBonuses($this->db,$attacker_id);
            if (!empty($att_rank['attack'])) $result['att_power']=(int)($result['att_power']*(1+(float)$att_rank['attack']/100));
            if (!$is_barbarian&&$defender_id>0) {
                $def_rank=RankController::getRankBonuses($this->db,$defender_id);
                if (!empty($def_rank['defense'])) $result['def_power']=(int)($result['def_power']*(1+(float)$def_rank['defense']/100));
            }
        } catch (Exception $e) {}

        // Урон стене
        if ($result['wall_damage']>0) {
            $this->db->prepare("UPDATE villages SET wall=GREATEST(0,wall-?) WHERE id=?")->execute([$result['wall_damage'],$to_village_id]);
        }

        if (!$is_barbarian) {
            $this->setVillageTroops($to_village_id,$result['def_survivors']);
            $this->resolveSupportsLoss($to_village_id,$result);
        }

        // Грабёж
        $loot=['wood'=>0,'stone'=>0,'iron'=>0];
        if ($result['won']) {
            $carry=BattleEngine::calculateCarryCapacity($result['att_survivors']);
            $loot=BattleEngine::calculateLoot($carry,$target_village);
            if ($loot['wood']+$loot['stone']+$loot['iron']>0) {
                $this->db->prepare("UPDATE villages SET
                    r_wood=GREATEST(0,r_wood-?),r_stone=GREATEST(0,r_stone-?),r_iron=GREATEST(0,r_iron-?)
                    WHERE id=?")->execute([$loot['wood'],$loot['stone'],$loot['iron'],$to_village_id]);
            }
        }

        // === ДВОРЯНИН ===
        $nobleman_result=null;
        $noblemen_sent=(int)($attackers['nobleman']??0);
        if ($noblemen_sent>0&&$result['won']&&!$is_barbarian) {
            $noblemen_survived=(int)($result['att_survivors']['nobleman']??0);
            if ($noblemen_survived>0) {
                try { $nc=new NoblemenController($this->db); $nobleman_result=$nc->applyNoblemenAttack($attacker_id,$to_village_id,$noblemen_survived,true); }
                catch (Exception $e) { error_log("nobleman: ".$e->getMessage()); }
            }
        }

        $this->createBattleReports($attacker_id,$defender_id,$is_barbarian,
            $from_village_id,$to_village_id,$attackers,$defenders,$result,$loot,$nobleman_result);
        $this->updateBattleStats($attacker_id,$defender_id,$result,$loot,$is_barbarian);

        // Опыт героям
        try {
            $hc=new HeroController($this->db);
            $hero_exp_mult=1.0;
            try { $tc2=new TechController($this->db); $hbon=$tc2->getBonuses($attacker_id); if (!empty($hbon['hero_exp'])) $hero_exp_mult=1+(float)$hbon['hero_exp']/100; } catch (Exception $e) {}
            $hc->addExperience($attacker_id,(int)round(($result['won']?rand(20,50):rand(5,15))*$hero_exp_mult),'battle');
            $hc->heroTakeDamage($attacker_id,$result['won']?rand(5,15):rand(20,40));
            if (!$is_barbarian&&$defender_id>0) {
                $def_exp_mult=1.0;
                try { $tc3=new TechController($this->db); $dbon=$tc3->getBonuses($defender_id); if (!empty($dbon['hero_exp'])) $def_exp_mult=1+(float)$dbon['hero_exp']/100; } catch (Exception $e) {}
                $hc->addExperience($defender_id,(int)round(rand(5,20)*$def_exp_mult),'defense');
                if (!$result['won']) $hc->heroTakeDamage($defender_id,rand(5,15));
            }
        } catch (Exception $e) {}

        // Квесты
        try {
            QuestController::trigger($this->db,$attacker_id,'attack_barb',1);
            if ($result['won']) {
                QuestController::trigger($this->db,$attacker_id,'attack_win_3',1);
                QuestController::trigger($this->db,$attacker_id,'attack_win_10',1);
                QuestController::trigger($this->db,$attacker_id,'first_attack',1);
            }
            $loot_total=($loot['wood']??0)+($loot['stone']??0)+($loot['iron']??0);
            if ($loot_total>0) { QuestController::trigger($this->db,$attacker_id,'collect_5000',$loot_total); QuestController::trigger($this->db,$attacker_id,'collect_50k',$loot_total); }
            if ($result['won']&&!$is_barbarian) { try { (new EventController($this->db))->addTournamentScore($attacker_id,10,'attack'); } catch (Exception $e) {} }
        } catch (Exception $e) {}

        // Ранг и достижения
        try {
            $stmt_pts=$this->db->prepare("SELECT points FROM users WHERE id=?");
            $stmt_pts->execute([$attacker_id]); $pts_row=$stmt_pts->fetch();
            if ($pts_row) { $rc=new RankController($this->db); $rc->updateRank($attacker_id,(int)$pts_row['points']); }
            AchievementController::check($this->db,$attacker_id);
            if (!$is_barbarian&&$defender_id>0) AchievementController::check($this->db,$defender_id);
        } catch (Exception $e) {}

        // Очки альянсовых войн
        try {
            if (!$is_barbarian&&$result['won']) {
                $stmt_am=$this->db->prepare("SELECT alliance_id FROM alliance_members WHERE user_id=?");
                $stmt_am->execute([$attacker_id]); $am=$stmt_am->fetch();
                if ($am&&!empty($am['alliance_id'])) {
                    $wc=new AllianceWarController($this->db);
                    $wc->addWarScore($attacker_id,$am['alliance_id'],AllianceWarController::SCORE_ATTACK_WIN,'attack');
                }
            }
        } catch (Exception $e) {}

        // Возврат
        if ($nobleman_result!=='captured'&&array_sum($result['att_survivors'])>0) {
            $this->createReturnMovement($attacker_id,$to_village_id,$from_village_id,$result['att_survivors'],$loot);
        } elseif ($nobleman_result==='captured') {
            $surv_no_noble=$result['att_survivors']; $surv_no_noble['nobleman']=0;
            if (array_sum($surv_no_noble)>0) $this->createReturnMovement($attacker_id,$to_village_id,$from_village_id,$surv_no_noble,$loot);
        }
    }

    // =========================================================
    // РАЗРЕШЕНИЕ ШПИОНАЖА
    // =========================================================
    private function resolveSpy($movement) {
        $attacker_id=(int)$movement['attacker_id'];
        $from_village_id=(int)$movement['from_village_id'];
        $to_village_id=(int)$movement['to_village_id'];
        $scouts_data=json_decode($movement['troops'],true);
        $scout_count=max(0,(int)($scouts_data['scout']??0));

        $stmt=$this->db->prepare("SELECT v.*,u.username as owner_name FROM villages v LEFT JOIN users u ON v.userid=u.id WHERE v.id=?");
        $stmt->execute([$to_village_id]); $target=$stmt->fetch();
        if (!$target) return;

        $defender_id=(int)$target['userid']; $is_barbarian=($defender_id===-1);
        $def_scouts=0;
        if (!$is_barbarian) { $stmt=$this->db->prepare("SELECT scout FROM unit_place WHERE villages_to_id=?"); $stmt->execute([$to_village_id]); $du=$stmt->fetch(); $def_scouts=max(0,(int)($du['scout']??0)); }

        if ($def_scouts===0) $sc=0.95;
        elseif ($scout_count/$def_scouts>=3) $sc=0.95;
        elseif ($scout_count/$def_scouts>=2) $sc=0.85;
        elseif ($scout_count/$def_scouts>=1) $sc=0.70;
        elseif ($scout_count/$def_scouts>=0.5) $sc=0.50;
        else $sc=0.25;

        try { $stmt2=$this->db->prepare("SELECT level FROM player_technologies WHERE user_id=? AND tech_code='spy_skill' AND level>0"); $stmt2->execute([$attacker_id]); $row=$stmt2->fetch(); if ($row) $sc=min(0.99,$sc+(int)$row['level']*0.15); } catch (Exception $e) {}

        $success=mt_rand(1,100)/100<=$sc;
        $scouts_lost=0; $def_scouts_lost=0;
        if (!$success) { $scouts_lost=$scout_count; if ($def_scouts>0) $def_scouts_lost=min($def_scouts,max(1,(int)($def_scouts*0.1))); }
        else { if ($def_scouts>$scout_count) $scouts_lost=(int)ceil($scout_count*0.1); }
        $scouts_survived=max(0,$scout_count-$scouts_lost);

        if ($def_scouts_lost>0&&!$is_barbarian) { $this->db->prepare("UPDATE unit_place SET scout=GREATEST(0,scout-?) WHERE villages_to_id=?")->execute([$def_scouts_lost,$to_village_id]); }

        try { $this->db->prepare("INSERT INTO player_stats (user_id,spies_sent,spies_success) VALUES (?,1,?) ON DUPLICATE KEY UPDATE spies_sent=spies_sent+1,spies_success=spies_success+VALUES(spies_success)")->execute([$attacker_id,$success?1:0]); } catch (Exception $e) {}
        try { $hc=new HeroController($this->db); $hc->addExperience($attacker_id,$success?rand(10,25):rand(2,8),'battle'); } catch (Exception $e) {}
        try { QuestController::trigger($this->db,$attacker_id,'spy_1',1); if ($success) QuestController::trigger($this->db,$attacker_id,'spy_10',1); AchievementController::check($this->db,$attacker_id); } catch (Exception $e) {}

        $unit_names=['spear'=>'Копейщики','sword'=>'Мечники','axe'=>'Топорщики','scout'=>'Разведчики','light'=>'Лёгкая кав.','heavy'=>'Тяжёлая кав.','ram'=>'Тараны','catapult'=>'Катапульты','nobleman'=>'Дворяне'];

        if ($success) {
            $def_troops=$this->getVillageTroops($to_village_id);
            $stmt=$this->db->prepare("SELECT tm.troops,u.username as owner FROM troop_movements tm JOIN users u ON tm.attacker_id=u.id WHERE tm.to_village_id=? AND tm.type='support' AND tm.status='completed'");
            $stmt->execute([$to_village_id]); $supports=$stmt->fetchAll();
            $loyalty=(int)($target['loyalty']??100);
            $rc="✅ Разведка «{$target['name']}» успешна";
            $content="🔍 Разведка: {$target['name']} ({$target['x']}|{$target['y']})\nВладелец: ".($target['owner_name']??'Варвары')."\n❤ Лояльность: {$loyalty}/100\n\n💰 РЕСУРСЫ:\n━━━━━━━━━━━━━━━━━━━━━━━\n🪵 ".number_format($target['r_wood'])."\n🪨 ".number_format($target['r_stone'])."\n⛏ ".number_format($target['r_iron'])."\n\n🏛 ЗДАНИЯ:\n━━━━━━━━━━━━━━━━━━━━━━━\n";
            $blds=['main'=>'Гл. здание','barracks'=>'Казармы','stable'=>'Конюшня','smith'=>'Кузница','wall'=>'Стена','farm'=>'Ферма','storage'=>'Склад','garage'=>'Мастерская'];
            foreach ($blds as $key=>$name) { $lvl=(int)($target[$key]??0); if ($lvl>0) $content.="{$name}: {$lvl} ур.\n"; }
            $content.="\n⚔ ВОЙСКА:\n━━━━━━━━━━━━━━━━━━━━━━━\n"; $has=false;
            foreach ($def_troops as $type=>$count) { if ($count>0&&isset($unit_names[$type])) { $content.=str_pad($unit_names[$type],22).": {$count}\n"; $has=true; } }
            if (!$has) $content.="Войск нет\n";
            if (!empty($supports)) { $content.="\n🛡 ПОДДЕРЖКА:\n━━━━━━━━━━━━━━━━━━━━━━━\n"; foreach ($supports as $sup) { $st=json_decode($sup['troops'],true); $content.="От: {$sup['owner']}\n"; foreach ($st as $type=>$count) { if ($count>0&&isset($unit_names[$type])) $content.="  ".str_pad($unit_names[$type],20).": {$count}\n"; } } }
            $content.="\n🔍 Разведчиков: {$scout_count}".($scouts_lost>0?" (потеряно: {$scouts_lost})":"")."\n✅ Выжило: {$scouts_survived}";
        } else {
            $rc="❌ Разведка «{$target['name']}» провалилась";
            $content="🔍 Попытка: {$target['name']} ({$target['x']}|{$target['y']})\n\n❌ Разведчики обнаружены!\n📊 Шанс: ".round($sc*100)."%\n🔍 Отправлено: {$scout_count}\n💀 Погибло: {$scouts_lost}";
            if (!$is_barbarian&&$defender_id>0) { $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'defense',?,?,?,0)")->execute([$defender_id,"🔍 Попытка шпионажа отражена!","Разведчики уничтожены ({$scouts_lost}) у «{$target['name']}»!",time()]); }
        }

        $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'scout',?,?,?,0)")->execute([$attacker_id,$rc,$content,time()]);
        if ($scouts_survived>0) $this->createReturnMovement($attacker_id,$to_village_id,$from_village_id,['scout'=>$scouts_survived],['wood'=>0,'stone'=>0,'iron'=>0]);
    }

    // =========================================================
    // РАЗРЕШЕНИЕ ПОДДЕРЖКИ
    // =========================================================
    private function resolveSupport($movement) {
        $troops=json_decode($movement['troops'],true);
        $to_village_id=(int)$movement['to_village_id'];
        $from_id=(int)$movement['from_village_id'];
        $attacker_id=(int)$movement['attacker_id'];

        $stmt=$this->db->prepare("SELECT v.name,v.userid,u.username as owner_name FROM villages v LEFT JOIN users u ON v.userid=u.id WHERE v.id=?");
        $stmt->execute([$to_village_id]); $to_v=$stmt->fetch();
        if (!$to_v) { $this->createReturnMovement($attacker_id,$to_village_id,$from_id,$troops,['wood'=>0,'stone'=>0,'iron'=>0]); return; }

        $un=['spear'=>'Копейщики','sword'=>'Мечники','axe'=>'Топорщики','scout'=>'Разведчики','light'=>'Лёгкая кав.','heavy'=>'Тяжёлая кав.','ram'=>'Тараны','catapult'=>'Катапульты'];
        $ts=''; foreach ($troops as $t=>$c) { if ($c>0&&isset($un[$t])) $ts.="{$un[$t]}: {$c}\n"; }

        $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'support',?,?,?,0)")->execute([$attacker_id,"🛡 Поддержка прибыла в «{$to_v['name']}»","Ваши войска защищают «{$to_v['name']}».\n\nВойска:\n{$ts}",time()]);

        if ((int)$to_v['userid']!==$attacker_id) {
            $stmt=$this->db->prepare("SELECT username FROM users WHERE id=?"); $stmt->execute([$attacker_id]); $sender=$stmt->fetch();
            $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'support',?,?,?,0)")->execute([$to_v['userid'],"🛡 Союзник «".($sender['username']??'?')."» прислал поддержку!","Войска:\n{$ts}",time()]);
        }
    }

    // =========================================================
    // РАЗРЕШЕНИЕ ВОЗВРАТА
    // =========================================================
    private function resolveReturn($movement) {
        $troops=json_decode($movement['troops'],true);
        $to_village_id=(int)$movement['to_village_id'];
        $extra=json_decode($movement['extra_data']??'{}',true);
        $loot=$extra['loot']??['wood'=>0,'stone'=>0,'iron'=>0];

        if (!empty($troops)) {
            $check=$this->db->prepare("SELECT id FROM unit_place WHERE villages_to_id=?"); $check->execute([$to_village_id]);
            if ($check->fetch()) {
                $sets=[]; $params=[];
                foreach ($troops as $type=>$count) { if ($count>0) { $sets[]="`{$type}`=`{$type}`+?"; $params[]=(int)$count; } }
                if (!empty($sets)) { $params[]=$to_village_id; $this->db->prepare("UPDATE unit_place SET ".implode(',',$sets)." WHERE villages_to_id=?")->execute($params); }
            } else {
                $fields=['villages_to_id'=>$to_village_id];
                foreach ($troops as $type=>$count) $fields[$type]=max(0,(int)$count);
                $cols=implode(',',array_map(fn($k)=>"`{$k}`",array_keys($fields)));
                $vals=implode(',',array_fill(0,count($fields),'?'));
                $this->db->prepare("INSERT INTO unit_place ({$cols}) VALUES ({$vals})")->execute(array_values($fields));
            }
        }

        if (($loot['wood']+$loot['stone']+$loot['iron'])>0) {
            $this->db->prepare("UPDATE villages SET r_wood=r_wood+?,r_stone=r_stone+?,r_iron=r_iron+? WHERE id=?")->execute([(int)$loot['wood'],(int)$loot['stone'],(int)$loot['iron'],$to_village_id]);
        }
    }

    // =========================================================
    // ПОТЕРИ ПОДДЕРЖКИ
    // =========================================================
    private function resolveSupportsLoss($village_id,$result) {
        $stmt=$this->db->prepare("SELECT * FROM troop_movements WHERE to_village_id=? AND type='support' AND status='completed'");
        $stmt->execute([$village_id]);
        foreach ($stmt->fetchAll() as $sup) {
            $sup_troops=json_decode($sup['troops'],true); $new_troops=[]; $has_troops=false;
            foreach ($sup_troops as $type=>$count) {
                $count=max(0,(int)$count); $loss_pct=$result['won']?1.0:0.5;
                $lost=max(0,min((int)round($count*$loss_pct),$count)); $survived=$count-$lost;
                $new_troops[$type]=$survived; if ($survived>0) $has_troops=true;
            }
            if (!$has_troops) {
                $this->db->prepare("DELETE FROM troop_movements WHERE id=?")->execute([$sup['id']]);
                $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'support',?,?,?,0)")->execute([$sup['attacker_id'],"💀 Ваша поддержка уничтожена!","Войска поддержки в деревне #{$village_id} уничтожены в бою!",time()]);
            } else {
                $this->db->prepare("UPDATE troop_movements SET troops=? WHERE id=?")->execute([json_encode($new_troops),$sup['id']]);
            }
        }
    }

    // =========================================================
    // СОЗДАТЬ ВОЗВРАТ
    // =========================================================
    private function createReturnMovement($attacker_id,$from_id,$to_id,$troops,$loot) {
        $stmt=$this->db->prepare("SELECT x,y FROM villages WHERE id=?");
        $stmt->execute([$from_id]); $from_v=$stmt->fetch();
        $stmt=$this->db->prepare("SELECT x,y FROM villages WHERE id=?");
        $stmt->execute([$to_id]); $to_v=$stmt->fetch();
        if (!$from_v||!$to_v) return;
        $rt=max(60,BattleEngine::calculateTravelTime($from_v['x'],$from_v['y'],$to_v['x'],$to_v['y'],$troops));
        $this->db->prepare("INSERT INTO troop_movements (attacker_id,from_village_id,to_village_id,troops,departure_time,arrival_time,type,status,extra_data) VALUES (?,?,?,?,?,?,'return','moving',?)")
            ->execute([$attacker_id,$from_id,$to_id,json_encode($troops),time(),time()+$rt,json_encode(['loot'=>$loot])]);
    }

    // =========================================================
    // ОТЧЁТЫ
    // =========================================================
    private function createBattleReports($attacker_id,$defender_id,$is_barbarian,$from_id,$to_id,$attackers,$defenders,$result,$loot,$nobleman_result=null) {
        $un=['spear'=>'Копейщик','sword'=>'Мечник','axe'=>'Топорщик','scout'=>'Разведчик','light'=>'Лёгкая кав.','heavy'=>'Тяжёлая кав.','ram'=>'Таран','catapult'=>'Катапульта','nobleman'=>'Дворянин'];
        $sep="━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $luck=$result['luck']>=0?"✅ Удача: +{$result['luck']}%":"❌ Неудача: {$result['luck']}%";

        $att="⚔ Битва: #{$from_id} → #{$to_id}\n\n{$luck}\nСила атаки:  {$result['att_power']}\nСила защиты: {$result['def_power']}\n\n";
        $att.="АТАКУЮЩИЕ\n{$sep}".str_pad('Юнит',22).str_pad('Отпр.',10).str_pad('Потери',10)."Выжило\n";
        foreach ($attackers as $type=>$count) {
            if ($count<=0) continue;
            $name=$un[$type]??$type; $lost=max(0,(int)($result['att_losses'][$type]??0)); $surv=max(0,(int)($result['att_survivors'][$type]??($count-$lost)));
            $att.=str_pad($name,22).str_pad($count,10).str_pad($lost,10)."{$surv}\n";
        }
        $att.="\nЗАЩИТНИКИ\n{$sep}".str_pad('Юнит',22).str_pad('Было',10).str_pad('Потери',10)."Выжило\n";
        foreach ($defenders as $type=>$count) {
            if ($count<=0) continue;
            $name=$un[$type]??$type; $lost=max(0,(int)($result['def_losses'][$type]??0)); $surv=max(0,(int)($result['def_survivors'][$type]??($count-$lost)));
            $att.=str_pad($name,22).str_pad($count,10).str_pad($lost,10)."{$surv}\n";
        }
        $att.="\n";
        if ($result['won']) { $att.="🏆 ПОБЕДА!\nНаграблено: 🪵{$loot['wood']} 🪨{$loot['stone']} ⛏{$loot['iron']}\n"; if ($result['wall_damage']>0) $att.="Стена повреждена на {$result['wall_damage']} ур.\n"; }
        else $att.="💀 ПОРАЖЕНИЕ\n";

        if ($nobleman_result!==null&&$nobleman_result!==false) {
            $att.="\n👑 ДВОРЯНИН:\n";
            if ($nobleman_result==='captured') { $att.="🎉 ДЕРЕВНЯ ЗАХВАЧЕНА!\nДеревня #{$to_id} теперь ваша!\n"; }
            elseif (is_numeric($nobleman_result)) { $att.="Лояльность: {$nobleman_result}/100\nОсталось: ~".ceil($nobleman_result/30)." атак\n"; }
        }

        $att_title=($result['won']?"✅ ":"❌ ")."Атака на деревню #{$to_id}";
        if ($nobleman_result==='captured') $att_title="🏆 Деревня #{$to_id} ЗАХВАЧЕНА!";
        $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'attack',?,?,?,0)")->execute([$attacker_id,$att_title,$att,time()]);

        if (!$is_barbarian&&$defender_id>0) {
            $def="🛡 Деревня #{$to_id} атакована!\n\n{$luck}\nСила атаки:  {$result['att_power']}\nСила защиты: {$result['def_power']}\n\n";
            $def.="АТАКУЮЩИЕ\n{$sep}".str_pad('Юнит',22).str_pad('Отпр.',10).str_pad('Потери',10)."Выжило\n";
            foreach ($attackers as $type=>$count) {
                if ($count<=0) continue;
                $name=$un[$type]??$type; $lost=max(0,(int)($result['att_losses'][$type]??0)); $surv=max(0,(int)($result['att_survivors'][$type]??($count-$lost)));
                $def.=str_pad($name,22).str_pad($count,10).str_pad($lost,10)."{$surv}\n";
            }
            $def.="\nВАШИ ВОЙСКА\n{$sep}".str_pad('Юнит',22).str_pad('Было',10).str_pad('Потери',10)."Выжило\n";
            foreach ($defenders as $type=>$count) {
                if ($count<=0) continue;
                $name=$un[$type]??$type; $lost=max(0,(int)($result['def_losses'][$type]??0)); $surv=max(0,(int)($result['def_survivors'][$type]??($count-$lost)));
                $def.=str_pad($name,22).str_pad($count,10).str_pad($lost,10)."{$surv}\n";
            }
            $def.="\n".($result['won']?"💀 Разграблена!\nУкрадено: 🪵{$loot['wood']} 🪨{$loot['stone']} ⛏{$loot['iron']}\n":"🏆 Атака отбита!\n");
            if ($nobleman_result!==null&&$nobleman_result!==false) {
                $def.="\n👑 ДВОРЯНИН:\n";
                if ($nobleman_result==='captured') $def.="💀 Деревня захвачена противником!\n";
                elseif (is_numeric($nobleman_result)) $def.="⚠ Лояльность снижена до {$nobleman_result}/100!\n";
            }
            $def_title=($result['won']?"❌ ":"✅ ")."Защита деревни #{$to_id}";
            if ($nobleman_result==='captured') $def_title="💀 Деревня #{$to_id} захвачена!";
            $this->db->prepare("INSERT INTO reports (userid,type,title,content,time,is_read) VALUES (?,'defense',?,?,?,0)")->execute([$defender_id,$def_title,$def,time()]);
        }
    }

    // =========================================================
    // СТАТИСТИКА
    // =========================================================
    private function updateBattleStats($attacker_id,$defender_id,$result,$loot,$is_barbarian) {
        try {
            $loot_total=($loot['wood']??0)+($loot['stone']??0)+($loot['iron']??0);
            $att_lost=array_sum(array_map(fn($v)=>max(0,(int)$v),$result['att_losses']));
            $this->db->prepare("INSERT INTO player_stats (user_id,attacks_sent,attacks_won,attacks_lost,resources_looted,units_lost) VALUES (?,1,?,?,?,?) ON DUPLICATE KEY UPDATE attacks_sent=attacks_sent+1,attacks_won=attacks_won+VALUES(attacks_won),attacks_lost=attacks_lost+VALUES(attacks_lost),resources_looted=resources_looted+VALUES(resources_looted),units_lost=units_lost+VALUES(units_lost)")
                ->execute([$attacker_id,$result['won']?1:0,$result['won']?0:1,$loot_total,$att_lost]);
            if (!$is_barbarian&&$defender_id>0) {
                $def_lost=array_sum(array_map(fn($v)=>max(0,(int)$v),$result['def_losses']));
                $this->db->prepare("INSERT INTO player_stats (user_id,defenses_total,defenses_won,defenses_lost,resources_lost,units_lost) VALUES (?,1,?,?,?,?) ON DUPLICATE KEY UPDATE defenses_total=defenses_total+1,defenses_won=defenses_won+VALUES(defenses_won),defenses_lost=defenses_lost+VALUES(defenses_lost),resources_lost=resources_lost+VALUES(resources_lost),units_lost=units_lost+VALUES(units_lost)")
                    ->execute([$defender_id,$result['won']?0:1,$result['won']?1:0,$loot_total,$def_lost]);
            }
        } catch (Exception $e) { error_log("updateBattleStats: ".$e->getMessage()); }
    }

    // =========================================================
    // ВСПОМОГАТЕЛЬНЫЕ
    // =========================================================
    private function subtractTroops($village_id,$troops) {
        $sets=[]; $params=[];
        foreach ($troops as $type=>$count) { if ($count>0) { $sets[]="`{$type}`=GREATEST(0,`{$type}`-?)"; $params[]=(int)$count; } }
        if (!empty($sets)) { $params[]=$village_id; $this->db->prepare("UPDATE unit_place SET ".implode(',',$sets)." WHERE villages_to_id=?")->execute($params); }
    }

    private function getVillageTroops($village_id) {
        $stmt=$this->db->prepare("SELECT * FROM unit_place WHERE villages_to_id=?"); $stmt->execute([$village_id]);
        $row=$stmt->fetch()?:[]; $types=['spear','sword','axe','scout','light','heavy','ram','catapult','nobleman'];
        $result=[]; foreach ($types as $type) $result[$type]=max(0,(int)($row[$type]??0));
        return $result;
    }

    private function setVillageTroops($village_id,$troops) {
        $sets=[]; $params=[];
        foreach ($troops as $type=>$count) { $sets[]="`{$type}`=?"; $params[]=max(0,(int)$count); }
        if (!empty($sets)) { $params[]=$village_id; $this->db->prepare("UPDATE unit_place SET ".implode(',',$sets)." WHERE villages_to_id=?")->execute($params); }
    }

    private function getResearch($village_id) {
        try { $stmt=$this->db->prepare("SELECT * FROM smith_research WHERE village_id=?"); $stmt->execute([$village_id]); return $stmt->fetch()?:[]; }
        catch (Exception $e) { return []; }
    }

    public function renderTroopTable($unit_stats,$mode='attack') {
        $icons=['spear'=>'🔱','sword'=>'⚔️','axe'=>'🪓','scout'=>'🔍','light'=>'🐎','heavy'=>'🦄','ram'=>'🪵','catapult'=>'💣','nobleman'=>'👑'];
        echo '<table><tr><th>Юнит</th><th>⚔</th><th>🛡П</th><th>🛡К</th>';
        if ($mode==='attack') echo '<th>📦</th>';
        echo '<th>Есть</th><th>Отпр.</th></tr>';
        foreach ($unit_stats as $type=>$stats) {
            if ($mode==='support'&&in_array($type,['ram','catapult','nobleman'])) continue;
            echo '<tr>';
            echo '<td><span style="font-size:16px;">'.($icons[$type]??'👤').'</span><br><span style="font-size:10px;">'.htmlspecialchars($stats['name']).'</span></td>';
            echo '<td>'.$stats['attack'].'</td><td>'.$stats['def_inf'].'</td><td>'.$stats['def_cav'].'</td>';
            if ($mode==='attack') echo '<td>'.$stats['carry'].'</td>';
            echo '<td><span style="color:#0f0;font-size:12px;cursor:pointer;" id="avail_'.$type.'" onclick="sendAll(\''.$type.'\')">0</span>';
            echo '<br><button type="button" style="padding:2px 5px;background:#3a2c10;color:#d4a843;border:1px solid #8b6914;border-radius:3px;cursor:pointer;font-size:9px;" onclick="sendAll(\''.$type.'\')">Все</button></td>';
            echo '<td><input type="number" name="'.$type.'" id="input_'.$type.'" value="0" min="0" style="width:60px;padding:5px;background:#1a1a0a;color:#ddd;border:1px solid #555;border-radius:3px;text-align:center;"></td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}