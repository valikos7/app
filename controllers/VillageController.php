<?php
// controllers/VillageController.php

class VillageController {
    private $db;
    private const MAX_QUEUE = 2;

    public function __construct($db) {
        $this->db = $db;
    }

    public function view() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $user_id    = $_SESSION['user_id'];
        $village_id = (int)($_GET['id'] ?? 0);
        $screen     = $_GET['screen'] ?? 'overview';

        if ($village_id <= 0) {
            echo "<h2>Неверный ID деревни.</h2><a href='?page=profile'>Вернуться</a>"; exit;
        }

        $this->processBuildQueue($village_id);
        $this->processTrainingQueue($village_id);

        if (isset($_GET['build'])) {
            $this->startUpgrade($village_id, $_GET['build']);
            header("Location: ?page=village&id=".$village_id); exit;
        }
        if (isset($_GET['cancel_build'])) {
            $this->cancelBuild($village_id, (int)$_GET['cancel_build']);
            header("Location: ?page=village&id=".$village_id); exit;
        }
        if (isset($_POST['train'])) {
            $this->trainUnits($village_id, $_POST);
            $unit_type=$_POST['unit_type']??'';
            if (in_array($unit_type,['ram','catapult','nobleman'])) $rs='garage';
            elseif (in_array($unit_type,['scout','light','heavy']))  $rs='stable';
            else                                                       $rs='barracks';
            header("Location: ?page=village&id={$village_id}&screen={$rs}"); exit;
        }

        if (isset($_GET['ajax'])&&$_GET['ajax']==1) {
            $rm=new ResourceManager($this->db); $rm->updateResources($village_id);
            $stmt=$this->db->prepare("SELECT r_wood,r_stone,r_iron FROM villages WHERE id=?");
            $stmt->execute([$village_id]); $res=$stmt->fetch();
            header('Content-Type: application/json');
            echo json_encode(['wood'=>$res['r_wood']??0,'stone'=>$res['r_stone']??0,'iron'=>$res['r_iron']??0]);
            exit;
        }

        $rm=new ResourceManager($this->db); $rm->updateResources($village_id);

        $stmt=$this->db->prepare("SELECT * FROM villages WHERE id=? AND userid=?");
        $stmt->execute([$village_id,$user_id]);
        $village=$stmt->fetch();
        if (!$village) { echo "<h2>Деревня не найдена или не принадлежит вам.</h2><a href='?page=profile'>Вернуться</a>"; exit; }

        $build_queue=$this->getBuildQueue($village_id);

        if ($screen!=='overview') {
            $redirects=['settlers'=>"?page=settlers&village_id={$village_id}"];
            if (isset($redirects[$screen])) { header("Location: ".$redirects[$screen]); exit; }

            $current_level=(int)($village[$screen]??0);
            $restricted=['barracks','stable','smith','garage'];
            if (in_array($screen,$restricted)&&$current_level==0) {
                $_SESSION['error']="Сначала постройте это здание!";
                header("Location: ?page=village&id={$village_id}"); exit;
            }

            $building_file=__DIR__.'/../templates/building_'.$screen.'.php';
            if (file_exists($building_file)) {
                $db=$this->db;
                if (in_array($screen,['barracks','stable','garage'])) {
                    $stmt=$this->db->prepare("SELECT spear,sword,axe,scout,light,heavy,ram,catapult,nobleman
                        FROM unit_place WHERE villages_to_id=?");
                    $stmt->execute([$village_id]);
                    $GLOBALS['units']=$stmt->fetch()?:[
                        'spear'=>0,'sword'=>0,'axe'=>0,'scout'=>0,'light'=>0,
                        'heavy'=>0,'ram'=>0,'catapult'=>0,'nobleman'=>0
                    ];
                }
                require_once $building_file;
            } else {
                if (in_array($screen,['wood_level','stone_level','iron_level'])) {
                    $db=$this->db; require_once __DIR__.'/../templates/building_resource.php';
                } else {
                    $_SESSION['error']="Здание '{$screen}' не найдено.";
                    header("Location: ?page=village&id={$village_id}"); exit;
                }
            }
            exit;
        }

        $db=$this->db;
        require_once __DIR__.'/../templates/village.php';
    }

    // =========================================================
    // ОЧЕРЕДЬ СТРОИТЕЛЬСТВА
    // =========================================================
    public function getBuildQueue($village_id) {
        try {
            $stmt=$this->db->prepare("SELECT * FROM build_queue WHERE village_id=? AND status!='done' ORDER BY position ASC");
            $stmt->execute([$village_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) { return []; }
    }

    private function processBuildQueue($village_id) {
        try {
            $stmt=$this->db->prepare("SELECT * FROM build_queue WHERE village_id=? AND status!='done' ORDER BY position ASC");
            $stmt->execute([$village_id]);
            $queue=$stmt->fetchAll();
            if (empty($queue)) return;

            $now=time(); $changed=false;

            foreach ($queue as $item) {
                if ($item['status']==='pending') {
                    $check=$this->db->prepare("SELECT id FROM build_queue WHERE village_id=? AND status='building'");
                    $check->execute([$village_id]);
                    if (!$check->fetch()) {
                        $dur=$item['end_time']-$item['start_time'];
                        $this->db->prepare("UPDATE build_queue SET status='building',start_time=?,end_time=? WHERE id=?")
                            ->execute([time(),time()+$dur,$item['id']]);
                        $item['status']='building'; $item['end_time']=time()+$dur;
                    }
                }

                if ($item['status']==='building'&&$item['end_time']<=$now) {
                    $building=$item['building']; $level=$item['level'];
                    $this->db->prepare("UPDATE villages SET `{$building}`=? WHERE id=?")->execute([$level,$village_id]);
                    $pts_map=['main'=>10,'wood_level'=>4,'stone_level'=>4,'iron_level'=>4,'farm'=>5,'storage'=>5,
                        'barracks'=>6,'stable'=>8,'smith'=>6,'garage'=>7,'wall'=>6,'hide'=>3];
                    $pts=($pts_map[$building]??4)*$level;
                    $this->db->prepare("UPDATE villages SET points=points+? WHERE id=?")->execute([$pts,$village_id]);

                    try {
                        $stmt2=$this->db->prepare("SELECT userid FROM villages WHERE id=?");
                        $stmt2->execute([$village_id]);
                        $v=$stmt2->fetch();
                        if ($v) {
                            $this->db->prepare("INSERT INTO player_stats (user_id,buildings_built) VALUES (?,1)
                                ON DUPLICATE KEY UPDATE buildings_built=buildings_built+1")->execute([$v['userid']]);
                            QuestController::trigger($this->db,$v['userid'],'build_1',1);
                            QuestController::trigger($this->db,$v['userid'],'build_3',1);
                            QuestController::trigger($this->db,$v['userid'],'build_10',1);
                            QuestController::trigger($this->db,$v['userid'],'first_build',1);
                            AchievementController::check($this->db,$v['userid']);
                        }
                    } catch (Exception $e) {}

                    $this->db->prepare("UPDATE build_queue SET status='done' WHERE id=?")->execute([$item['id']]);
                    $this->db->prepare("UPDATE build_queue SET position=position-1
                        WHERE village_id=? AND status!='done' AND position>?")->execute([$village_id,$item['position']]);
                    $changed=true;
                }
            }

            if ($changed) {
                try {
                    $stmt3=$this->db->prepare("SELECT userid FROM villages WHERE id=?");
                    $stmt3->execute([$village_id]);
                    $v=$stmt3->fetch();
                    if ($v) {
                        $this->db->prepare("UPDATE users SET points=(SELECT COALESCE(SUM(points),0) FROM villages WHERE userid=?) WHERE id=?")
                            ->execute([$v['userid'],$v['userid']]);
                        // Обновляем ранг
                        try {
                            $stmt_pts=$this->db->prepare("SELECT points FROM users WHERE id=?");
                            $stmt_pts->execute([$v['userid']]);
                            $pts_row=$stmt_pts->fetch();
                            if ($pts_row) {
                                $rc=new RankController($this->db);
                                $rc->updateRank($v['userid'],(int)$pts_row['points']);
                            }
                        } catch (Exception $e) {}
                    }
                } catch (Exception $e) {}
            }
        } catch (Exception $e) { error_log("processBuildQueue: ".$e->getMessage()); }
    }

    private function cancelBuild($village_id,$build_id) {
        $stmt=$this->db->prepare("SELECT * FROM build_queue WHERE id=? AND village_id=? AND status!='done'");
        $stmt->execute([$build_id,$village_id]);
        $item=$stmt->fetch();
        if (!$item) { $_SESSION['error']="Строительство не найдено!"; return; }

        $refund=$item['status']==='building'?0.5:1.0;
        $this->db->prepare("UPDATE villages SET r_wood=r_wood+?,r_stone=r_stone+?,r_iron=r_iron+? WHERE id=?")
            ->execute([(int)($item['wood_cost']*$refund),(int)($item['stone_cost']*$refund),(int)($item['iron_cost']*$refund),$village_id]);

        $this->db->prepare("DELETE FROM build_queue WHERE id=?")->execute([$build_id]);

        $stmt2=$this->db->prepare("SELECT * FROM build_queue WHERE village_id=? AND status!='done' ORDER BY position ASC");
        $stmt2->execute([$village_id]);
        foreach ($stmt2->fetchAll() as $i=>$r) {
            $this->db->prepare("UPDATE build_queue SET position=? WHERE id=?")->execute([$i+1,$r['id']]);
        }
        $_SESSION['success']="Строительство отменено. Возврат: ".($item['status']==='building'?'50%':'100%');
    }

    // =========================================================
    // УЛУЧШЕНИЕ ЗДАНИЯ
    // =========================================================
    private function startUpgrade($village_id,$building) {
        $allowed=['main','wood_level','stone_level','iron_level','farm','storage',
                  'barracks','stable','smith','garage','wall','hide'];
        if (!in_array($building,$allowed)) { $_SESSION['error']="Неизвестное здание!"; return; }

        $stmt=$this->db->prepare("SELECT * FROM villages WHERE id=?");
        $stmt->execute([$village_id]);
        $village=$stmt->fetch();
        if (!$village) { $_SESSION['error']="Деревня не найдена."; return; }

        $queue_levels=$this->getQueuedLevels($village_id);
        $current_level=(int)($village[$building]??0)+($queue_levels[$building]??0);
        $new_level=$current_level+1;

        $max_levels=['wall'=>20,'hide'=>10];
        $max_level=$max_levels[$building]??30;
        if ($new_level>$max_level) { $_SESSION['error']="Здание уже на максимальном уровне!"; return; }

        $queue=$this->getBuildQueue($village_id);
        if (count($queue)>=self::MAX_QUEUE) { $_SESSION['error']="Очередь заполнена (макс. ".self::MAX_QUEUE.")!"; return; }

        $main_level=(int)($village['main']??1);
        $requirements=['barracks'=>3,'stable'=>5,'smith'=>5,'garage'=>5];
        if (isset($requirements[$building])&&$main_level<$requirements[$building]) {
            $_SESSION['error']="Нужно Главное здание ур. {$requirements[$building]}!"; return;
        }

        $wood_cost=$new_level*120; $stone_cost=$new_level*100; $iron_cost=$new_level*80;

        $reserved=$this->getReservedResources($village_id);
        $avail_wood=($village['r_wood']??0)-$reserved['wood'];
        $avail_stone=($village['r_stone']??0)-$reserved['stone'];
        $avail_iron=($village['r_iron']??0)-$reserved['iron'];

        if ($avail_wood<$wood_cost||$avail_stone<$stone_cost||$avail_iron<$iron_cost) {
            $_SESSION['error']="Недостаточно ресурсов! Нужно: 🪵{$wood_cost} 🪨{$stone_cost} ⛏{$iron_cost}"; return;
        }

        $speed_bonus=max(0.5,1-($main_level*0.05));
        $base_time=($new_level*60)+30;
        $build_time=max(10,round($base_time*$speed_bonus));

        // Бонус события
        try {
            $bonuses=EventController::getEventBonuses($this->db);
            if ($bonuses['build']>0) $build_time=max(5,(int)ceil($build_time*(1-$bonuses['build']/100)));
        } catch (Exception $e) {}

        $position=count($queue)+1;
        if ($position===1) { $start_time=time(); $end_time=time()+$build_time; $status='building'; }
        else { $last=end($queue); $start_time=$last['end_time']; $end_time=$last['end_time']+$build_time; $status='pending'; }

        $this->db->prepare("UPDATE villages SET r_wood=r_wood-?,r_stone=r_stone-?,r_iron=r_iron-? WHERE id=?")
            ->execute([$wood_cost,$stone_cost,$iron_cost,$village_id]);
        $this->db->prepare("INSERT INTO build_queue
            (village_id,building,level,wood_cost,stone_cost,iron_cost,start_time,end_time,status,position)
            VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$village_id,$building,$new_level,$wood_cost,$stone_cost,$iron_cost,$start_time,$end_time,$status,$position]);

        $bnames=['main'=>'Главное здание','wood_level'=>'Лесопилка','stone_level'=>'Каменоломня',
            'iron_level'=>'Шахта','farm'=>'Ферма','storage'=>'Склад','barracks'=>'Казармы',
            'stable'=>'Конюшня','smith'=>'Кузница','garage'=>'Мастерская','wall'=>'Стена','hide'=>'Тайник'];
        $bname=$bnames[$building]??$building;
        $bonus=round((1-$speed_bonus)*100);
        $mins=floor($build_time/60); $secs=$build_time%60;
        $pos_str=$position>1?" (позиция {$position})":'';

        $_SESSION['success']="🔨 {$bname} → уровень {$new_level}{$pos_str}<br>⏱ {$mins}м {$secs}с (бонус ГЗ: {$bonus}%)";
    }

    // =========================================================
    // ТРЕНИРОВКА (с дворянином)
    // =========================================================
    private function processTrainingQueue($village_id) {
        $stmt=$this->db->prepare("SELECT train_queue,train_end_time FROM villages WHERE id=?");
        $stmt->execute([$village_id]);
        $row=$stmt->fetch();
        if (!$row||empty($row['train_queue'])||$row['train_end_time']>time()) return;

        list($unit_type,$amount)=explode(':',$row['train_queue'].':0');
        $amount=(int)$amount;

        $allowed=['spear','sword','axe','scout','light','heavy','ram','catapult','nobleman'];
        if (!in_array($unit_type,$allowed)) {
            $this->db->prepare("UPDATE villages SET train_queue=NULL,train_end_time=0 WHERE id=?")->execute([$village_id]);
            return;
        }

        $check=$this->db->prepare("SELECT id FROM unit_place WHERE villages_to_id=?");
        $check->execute([$village_id]);
        if ($check->fetch()) {
            $this->db->prepare("UPDATE unit_place SET `{$unit_type}`=`{$unit_type}`+? WHERE villages_to_id=?")
                ->execute([$amount,$village_id]);
        } else {
            $this->db->prepare("INSERT INTO unit_place (villages_to_id,`{$unit_type}`,created_at) VALUES (?,?,UNIX_TIMESTAMP())")
                ->execute([$village_id,$amount]);
        }

        $this->db->prepare("UPDATE villages SET train_queue=NULL,train_end_time=0 WHERE id=?")->execute([$village_id]);

        try {
            $stmt2=$this->db->prepare("SELECT userid FROM villages WHERE id=?");
            $stmt2->execute([$village_id]);
            $v=$stmt2->fetch();
            if ($v) {
                $this->db->prepare("INSERT INTO player_stats (user_id,units_trained) VALUES (?,?)
                    ON DUPLICATE KEY UPDATE units_trained=units_trained+VALUES(units_trained)")
                    ->execute([$v['userid'],$amount]);
                AchievementController::check($this->db,$v['userid']);
            }
        } catch (Exception $e) {}
    }

    private function trainUnits($village_id,$post) {
        $unit_type=$post['unit_type']??'';
        $amount=max(1,(int)($post['amount']??0));

        $costs=[
            'spear'    =>['wood'=>50,   'stone'=>30,   'iron'=>10,    'points'=>2,   'pop'=>1],
            'sword'    =>['wood'=>30,   'stone'=>50,   'iron'=>20,    'points'=>3,   'pop'=>1],
            'axe'      =>['wood'=>60,   'stone'=>30,   'iron'=>40,    'points'=>4,   'pop'=>1],
            'scout'    =>['wood'=>80,   'stone'=>40,   'iron'=>30,    'points'=>5,   'pop'=>2],
            'light'    =>['wood'=>100,  'stone'=>130,  'iron'=>160,   'points'=>8,   'pop'=>4],
            'heavy'    =>['wood'=>150,  'stone'=>200,  'iron'=>250,   'points'=>12,  'pop'=>6],
            'ram'      =>['wood'=>300,  'stone'=>200,  'iron'=>200,   'points'=>15,  'pop'=>5],
            'catapult' =>['wood'=>320,  'stone'=>400,  'iron'=>100,   'points'=>20,  'pop'=>8],
            'nobleman' =>['wood'=>50000,'stone'=>40000,'iron'=>50000, 'points'=>100, 'pop'=>20],
        ];

        if (!isset($costs[$unit_type])) { $_SESSION['error']="Неизвестный тип юнита!"; return; }

        $cost=$costs[$unit_type];
        $total_wood=$cost['wood']*$amount; $total_stone=$cost['stone']*$amount;
        $total_iron=$cost['iron']*$amount; $points_add=$cost['points']*$amount;
        $pop_cost=$cost['pop']*$amount;

        if (in_array($unit_type,['scout','light','heavy'])) $required_building='stable';
        elseif (in_array($unit_type,['ram','catapult','nobleman'])) $required_building='garage';
        else $required_building='barracks';

        $stmt=$this->db->prepare("SELECT r_wood,r_stone,r_iron,barracks,stable,garage,main,train_end_time,farm,population FROM villages WHERE id=?");
        $stmt->execute([$village_id]);
        $village=$stmt->fetch();
        if (!$village) { $_SESSION['error']="Деревня не найдена!"; return; }

        // Дворянин — нужно ГЗ ур.20
        if ($unit_type==='nobleman'&&(int)($village['main']??0)<20) {
            $_SESSION['error']="Дворянин требует Главное здание уровня 20!"; return;
        }

        if ((int)($village[$required_building]??0)<1) {
            $bnames=['stable'=>'Конюшню','garage'=>'Мастерскую','barracks'=>'Казармы'];
            $_SESSION['error']="Сначала постройте ".($bnames[$required_building]??$required_building)."!"; return;
        }

        if ($village['r_wood']<$total_wood||$village['r_stone']<$total_stone||$village['r_iron']<$total_iron) {
            $_SESSION['error']="Недостаточно ресурсов! Нужно: 🪵{$total_wood} 🪨{$total_stone} ⛏{$total_iron}"; return;
        }

        if (!empty($village['train_end_time'])&&$village['train_end_time']>time()) {
            $rem=$village['train_end_time']-time(); $m=floor($rem/60); $s=$rem%60;
            $_SESSION['error']="Уже идёт тренировка! Осталось: {$m}м {$s}с"; return;
        }

        $rm=new ResourceManager($this->db);
        $max_pop=$rm->getMaxPopulation($village);
        $curr_pop=(int)($village['population']??0);
        if ($curr_pop+$pop_cost>$max_pop) { $_SESSION['error']="Недостаточно места! Свободно: ".max(0,$max_pop-$curr_pop); return; }

        $train_key='train_time_'.$unit_type;
        $stmt_t=$this->db->prepare("SELECT value FROM game_config WHERE `key`=?");
        $stmt_t->execute([$train_key]);
        $base_time=(int)($stmt_t->fetch()['value']??($unit_type==='nobleman'?86400:60));

        $building_level=(int)($village[$required_building]??1);
        $time_per_unit=max(10,$base_time-($building_level*2));

        try {
            $bonuses=EventController::getEventBonuses($this->db);
            if ($bonuses['build']>0) $time_per_unit=max(5,(int)ceil($time_per_unit*(1-$bonuses['build']/100)));
        } catch (Exception $e) {}

        $total_time=$time_per_unit*$amount;

        $this->db->prepare("UPDATE villages SET
            r_wood=r_wood-?,r_stone=r_stone-?,r_iron=r_iron-?,
            population=population+?,points=points+? WHERE id=?")
            ->execute([$total_wood,$total_stone,$total_iron,$pop_cost,$points_add,$village_id]);

        $this->db->prepare("UPDATE villages SET train_queue=?,train_end_time=? WHERE id=?")
            ->execute([$unit_type.':'.$amount,time()+$total_time,$village_id]);

        try {
            $stmt_u=$this->db->prepare("SELECT userid FROM villages WHERE id=?");
            $stmt_u->execute([$village_id]);
            $v=$stmt_u->fetch();
            if ($v) {
                $this->db->prepare("UPDATE users SET points=(SELECT COALESCE(SUM(points),0) FROM villages WHERE userid=?) WHERE id=?")
                    ->execute([$v['userid'],$v['userid']]);
                if ($unit_type!=='nobleman') {
                    QuestController::trigger($this->db,$v['userid'],'train_50',$amount);
                    QuestController::trigger($this->db,$v['userid'],'train_200',$amount);
                    QuestController::trigger($this->db,$v['userid'],'train_500',$amount);
                    QuestController::trigger($this->db,$v['userid'],'first_train',1);
                }
                AchievementController::check($this->db,$v['userid']);
            }
        } catch (Exception $e) {}

        $unit_names=['spear'=>'Копейщики','sword'=>'Мечники','axe'=>'Топорщики',
            'scout'=>'Разведчики','light'=>'Лёгкая кав.','heavy'=>'Тяжёлая кав.',
            'ram'=>'Тараны','catapult'=>'Катапульты','nobleman'=>'Дворяне'];
        $mins=floor($total_time/60); $secs=$total_time%60;
        $_SESSION['success']="⚔ Тренировка начата!<br>+{$amount} ".($unit_names[$unit_type]??$unit_type)."<br>⏱ {$mins}м {$secs}с";
    }

    // =========================================================
    // ВСПОМОГАТЕЛЬНЫЕ
    // =========================================================
    private function getQueuedLevels($village_id) {
        try {
            $stmt=$this->db->prepare("SELECT building,COUNT(*) as cnt FROM build_queue WHERE village_id=? AND status!='done' GROUP BY building");
            $stmt->execute([$village_id]);
            $result=[];
            foreach ($stmt->fetchAll() as $row) $result[$row['building']]=$row['cnt'];
            return $result;
        } catch (Exception $e) { return []; }
    }

    private function getReservedResources($village_id) {
        try {
            $stmt=$this->db->prepare("SELECT COALESCE(SUM(wood_cost),0) as wood,COALESCE(SUM(stone_cost),0) as stone,COALESCE(SUM(iron_cost),0) as iron
                FROM build_queue WHERE village_id=? AND status!='done'");
            $stmt->execute([$village_id]);
            return $stmt->fetch()?:['wood'=>0,'stone'=>0,'iron'=>0];
        } catch (Exception $e) { return ['wood'=>0,'stone'=>0,'iron'=>0]; }
    }
}