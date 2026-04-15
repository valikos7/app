<?php
// controllers/AllianceController.php

class AllianceController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $stmt = $this->db->query("SELECT a.*,u.username as leader_name FROM alliances a
            LEFT JOIN users u ON a.leader_id=u.id ORDER BY a.points DESC LIMIT 50");
        $alliances = $stmt->fetchAll();

        $my_alliance = null;
        $stmt = $this->db->prepare("SELECT a.* FROM alliances a
            JOIN alliance_members am ON a.id=am.alliance_id WHERE am.user_id=?");
        $stmt->execute([$_SESSION['user_id']]);
        $my_alliance = $stmt->fetch();

        $db = $this->db;
        require_once __DIR__ . '/../templates/alliances.php';
    }

    public function view() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $alliance_id = (int)($_GET['id'] ?? 0);
        if ($alliance_id <= 0) { header("Location: ?page=alliances"); exit; }

        $stmt = $this->db->prepare("SELECT a.*,u.username as leader_name FROM alliances a
            LEFT JOIN users u ON a.leader_id=u.id WHERE a.id=?");
        $stmt->execute([$alliance_id]);
        $alliance = $stmt->fetch();
        if (!$alliance) { $_SESSION['error']="Альянс не найден."; header("Location: ?page=alliances"); exit; }

        $stmt = $this->db->prepare("SELECT am.*,u.username,u.points,u.villages,u.last_activity
            FROM alliance_members am JOIN users u ON am.user_id=u.id
            WHERE am.alliance_id=? ORDER BY u.points DESC");
        $stmt->execute([$alliance_id]);
        $members = $stmt->fetchAll();

        $my_role = null;
        foreach ($members as $m) {
            if ($m['user_id']==$_SESSION['user_id']) { $my_role=$m['role']; break; }
        }

        $db = $this->db;
        require_once __DIR__ . '/../templates/alliance_view.php';
    }

    public function create() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $user_id = $_SESSION['user_id'];

        $stmt = $this->db->prepare("SELECT id FROM alliance_members WHERE user_id=?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) { $_SESSION['error']="Вы уже состоите в альянсе!"; header("Location: ?page=alliances"); exit; }

        $db = $this->db;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require_once __DIR__ . '/../templates/alliance_create.php';
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $tag  = strtoupper(trim($_POST['tag'] ?? ''));
        $desc = trim($_POST['description'] ?? '');

        if (empty($name)||empty($tag)) { $_SESSION['error']="Название и тег обязательны!"; require_once __DIR__.'/../templates/alliance_create.php'; return; }
        if (strlen($name)<3||strlen($name)>64) { $_SESSION['error']="Название: от 3 до 64 символов!"; require_once __DIR__.'/../templates/alliance_create.php'; return; }
        if (strlen($tag)<2||strlen($tag)>8) { $_SESSION['error']="Тег: от 2 до 8 символов!"; require_once __DIR__.'/../templates/alliance_create.php'; return; }

        $stmt = $this->db->prepare("SELECT id FROM alliances WHERE name=? OR tag=?");
        $stmt->execute([$name,$tag]);
        if ($stmt->fetch()) { $_SESSION['error']="Альянс с таким именем или тегом уже существует!"; require_once __DIR__.'/../templates/alliance_create.php'; return; }

        $this->db->prepare("INSERT INTO alliances (name,tag,description,leader_id,created_at,members_count) VALUES (?,?,?,?,?,1)")
            ->execute([$name,$tag,$desc,$user_id,time()]);
        $alliance_id = $this->db->lastInsertId();

        $this->db->prepare("INSERT INTO alliance_members (alliance_id,user_id,role,joined_at) VALUES (?,?,'leader',?)")
            ->execute([$alliance_id,$user_id,time()]);
        $this->db->prepare("UPDATE users SET alliance_id=?,alliance_role='leader' WHERE id=?")
            ->execute([$alliance_id,$user_id]);

        // Квест
        try { QuestController::trigger($this->db,$user_id,'first_ally',1); } catch (Exception $e) {}

        $_SESSION['success']="Альянс [{$tag}] {$name} создан!";
        header("Location: ?page=alliance&id={$alliance_id}"); exit;
    }

    public function join() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $user_id     = $_SESSION['user_id'];
        $alliance_id = (int)($_GET['id'] ?? 0);

        $stmt = $this->db->prepare("SELECT id FROM alliance_members WHERE user_id=?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) { $_SESSION['error']="Вы уже состоите в альянсе!"; header("Location: ?page=alliances"); exit; }

        $stmt = $this->db->prepare("SELECT * FROM alliances WHERE id=?");
        $stmt->execute([$alliance_id]);
        $alliance = $stmt->fetch();
        if (!$alliance) { $_SESSION['error']="Альянс не найден."; header("Location: ?page=alliances"); exit; }

        $this->db->prepare("INSERT INTO alliance_members (alliance_id,user_id,role,joined_at) VALUES (?,?,'member',?)")
            ->execute([$alliance_id,$user_id,time()]);
        $this->db->prepare("UPDATE alliances SET members_count=members_count+1 WHERE id=?")->execute([$alliance_id]);
        $this->db->prepare("UPDATE users SET alliance_id=?,alliance_role='member' WHERE id=?")->execute([$alliance_id,$user_id]);

        // Квест
        try { QuestController::trigger($this->db,$user_id,'first_ally',1); } catch (Exception $e) {}

        $_SESSION['success']="Вы вступили в [{$alliance['tag']}] {$alliance['name']}!";
        header("Location: ?page=alliance&id={$alliance_id}"); exit;
    }

    public function leave() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $user_id = $_SESSION['user_id'];

        $stmt = $this->db->prepare("SELECT am.*,a.leader_id FROM alliance_members am
            JOIN alliances a ON am.alliance_id=a.id WHERE am.user_id=?");
        $stmt->execute([$user_id]);
        $membership = $stmt->fetch();

        if (!$membership) { $_SESSION['error']="Вы не состоите в альянсе."; header("Location: ?page=alliances"); exit; }
        if ($membership['leader_id']==$user_id) { $_SESSION['error']="Лидер не может покинуть альянс! Передайте лидерство."; header("Location: ?page=alliance&id={$membership['alliance_id']}"); exit; }

        $this->db->prepare("DELETE FROM alliance_members WHERE user_id=?")->execute([$user_id]);
        $this->db->prepare("UPDATE alliances SET members_count=GREATEST(0,members_count-1) WHERE id=?")->execute([$membership['alliance_id']]);
        $this->db->prepare("UPDATE users SET alliance_id=NULL,alliance_role=NULL WHERE id=?")->execute([$user_id]);

        $_SESSION['success']="Вы покинули альянс.";
        header("Location: ?page=alliances"); exit;
    }

    public function kick() {
        if (!isset($_SESSION['user_id'])) { header("Location: ?page=login"); exit; }

        $user_id     = $_SESSION['user_id'];
        $kick_id     = (int)($_GET['kick'] ?? 0);
        $alliance_id = (int)($_GET['id']   ?? 0);

        $stmt = $this->db->prepare("SELECT role FROM alliance_members WHERE user_id=? AND alliance_id=?");
        $stmt->execute([$user_id,$alliance_id]);
        $my = $stmt->fetch();

        if (!$my||$my['role']==='member') { $_SESSION['error']="Недостаточно прав!"; header("Location: ?page=alliance&id={$alliance_id}"); exit; }
        if ($kick_id===$user_id) { $_SESSION['error']="Нельзя исключить себя!"; header("Location: ?page=alliance&id={$alliance_id}"); exit; }

        $this->db->prepare("DELETE FROM alliance_members WHERE user_id=? AND alliance_id=?")->execute([$kick_id,$alliance_id]);
        $this->db->prepare("UPDATE alliances SET members_count=GREATEST(0,members_count-1) WHERE id=?")->execute([$alliance_id]);
        $this->db->prepare("UPDATE users SET alliance_id=NULL,alliance_role=NULL WHERE id=?")->execute([$kick_id]);

        $_SESSION['success']="Игрок исключён из альянса.";
        header("Location: ?page=alliance&id={$alliance_id}"); exit;
    }

    public function updatePoints($alliance_id) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(u.points),0) as total
            FROM alliance_members am JOIN users u ON am.user_id=u.id WHERE am.alliance_id=?");
        $stmt->execute([$alliance_id]);
        $total = $stmt->fetch()['total'] ?? 0;
        $this->db->prepare("UPDATE alliances SET points=? WHERE id=?")->execute([$total,$alliance_id]);
    }
}