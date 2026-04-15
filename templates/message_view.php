<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($message['subject']) ?> — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:800px; margin:20px auto; padding:0 15px; }

        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden; margin-bottom:15px;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843;
            display:flex; justify-content:space-between; align-items:center;
        }

        /* Цепочка сообщений */
        .thread-msg {
            border-bottom:1px solid #333; padding:0;
        }
        .thread-msg:last-child { border-bottom:none; }

        .thread-header {
            display:flex; justify-content:space-between;
            align-items:center; padding:12px 15px;
            cursor:pointer; transition:0.2s;
        }
        .thread-header:hover { background:#2a2010; }

        .thread-header.mine {
            background:#1a2a1a;
            border-left:3px solid #4a8a4a;
        }
        .thread-header.other {
            background:#1a1a2a;
            border-left:3px solid #4a4a8a;
        }

        .thread-from {
            display:flex; align-items:center; gap:10px;
        }
        .thread-avatar {
            width:36px; height:36px;
            background:#3a2c10; border:2px solid #8b6914;
            border-radius:50%; display:flex;
            align-items:center; justify-content:center;
            font-size:16px;
        }
        .thread-username {
            font-weight:bold; color:#d4a843; font-size:14px;
        }
        .thread-time {
            font-size:11px; color:#666;
        }

        .thread-body {
            padding:15px 20px 20px;
            font-size:13px; line-height:1.8;
            color:#ccc; white-space:pre-wrap;
            background:#1a1a0a;
            border-top:1px solid #333;
        }

        /* Форма ответа */
        .reply-form {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; padding:20px; margin-bottom:15px;
        }
        .reply-form h3 {
            color:#d4a843; margin-bottom:15px; font-size:15px;
        }
        .reply-form textarea {
            width:100%; padding:12px; background:#1a1a0a;
            color:#ddd; border:2px solid #444; border-radius:6px;
            font-size:13px; height:120px; resize:vertical;
            font-family:'Segoe UI',Arial; transition:0.2s;
            margin-bottom:12px;
        }
        .reply-form textarea:focus {
            border-color:#8b6914; outline:none;
        }

        .btn {
            display:inline-block; padding:8px 20px;
            border-radius:4px; font-size:13px;
            text-decoration:none; border:none;
            cursor:pointer; transition:0.2s;
        }
        .btn-send {
            background:#1a5a1a; color:#fff;
            border:1px solid #4a8a4a;
        }
        .btn-send:hover { background:#2a7a2a; }
        .btn-back {
            background:#5a4a1a; color:#ddd;
            border:1px solid #8b6914;
        }
        .btn-back:hover { background:#7a6a2a; }
        .btn-delete {
            background:#5a1a1a; color:#f66;
            border:1px solid #8a1a1a;
        }
        .btn-delete:hover { background:#7a2a2a; }

        .subject-line {
            font-size:18px; font-weight:bold;
            color:#d4a843; margin-bottom:15px;
            padding-bottom:10px; border-bottom:1px solid #444;
        }

        .alert-success {
            background:#1a3a1a; border:1px solid #4a4;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#0f0;
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Навигация -->
    <div style="margin-bottom:15px; display:flex; gap:8px;">
        <a href="?page=messages" class="btn btn-back">← Входящие</a>
        <a href="?page=messages&folder=sent" class="btn btn-back">📤 Отправленные</a>
    </div>

    <!-- Тема переписки -->
    <div class="subject-line">
        ✉ <?= htmlspecialchars(preg_replace('/^(Re:\s*)+/i', '', $message['subject'])) ?>
    </div>

    <!-- Цепочка сообщений -->
    <div class="card">
        <div class="card-header">
            💬 Переписка
            <span style="font-size:12px; color:#aaa;">
                <?= count($thread) ?> сообщений
            </span>
        </div>

        <?php foreach ($thread as $i => $t):
            $is_mine = ($t['from_id'] == $_SESSION['user_id']);
            $author  = $is_mine ? 'Вы' : htmlspecialchars($t['from_name'] ?? '?');
            $cls     = $is_mine ? 'mine' : 'other';
            $avatar  = $is_mine ? '👤' : '🗡';
            $time_diff = time() - $t['time'];
            if ($time_diff < 60)        $time_str = "только что";
            elseif ($time_diff < 3600)  $time_str = floor($time_diff/60) . " мин. назад";
            elseif ($time_diff < 86400) $time_str = floor($time_diff/3600) . " ч. назад";
            else                        $time_str = date('d.m.Y H:i', $t['time']);
        ?>
        <div class="thread-msg">
            <div class="thread-header <?= $cls ?>"
                 onclick="toggleMsg(<?= $i ?>)">
                <div class="thread-from">
                    <div class="thread-avatar"><?= $avatar ?></div>
                    <div>
                        <div class="thread-username">
                            <?= $is_mine ? 'Вы' : htmlspecialchars($t['from_name'] ?? '?') ?>
                            → <?= $is_mine ? htmlspecialchars($t['to_name'] ?? '?') : 'Вам' ?>
                        </div>
                        <div class="thread-time"><?= $time_str ?></div>
                    </div>
                </div>
                <div style="color:#666; font-size:12px;">
                    <?= $i === count($thread) - 1 ? '▼' : '▶' ?>
                </div>
            </div>
            <div class="thread-body" id="msg_<?= $i ?>"
                 style="display:<?= $i === count($thread) - 1 ? 'block' : 'none' ?>;">
                <?= nl2br(htmlspecialchars($t['content'])) ?>

                <!-- Кнопка удаления -->
                <div style="margin-top:15px; text-align:right;">
                    <a href="?page=messages&action=delete&msg_id=<?= $t['id'] ?>&folder=inbox"
                       class="btn btn-delete" style="font-size:11px; padding:4px 10px;"
                       onclick="return confirm('Удалить это сообщение?')">
                        🗑 Удалить
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Форма ответа -->
    <?php
    $reply_to_user = $message['from_id'] == $_SESSION['user_id']
        ? ($message['to_name'] ?? '')
        : ($message['from_name'] ?? '');
    $base_subject = preg_replace('/^(Re:\s*)+/i', '', $message['subject']);
    $reply_subject = 'Re: ' . $base_subject;
    ?>
    <div class="reply-form">
        <h3>
            ↩ Ответить
            <span style="color:#888; font-size:13px; font-weight:normal;">
                → <?= htmlspecialchars($reply_to_user) ?>
            </span>
        </h3>
        <form method="POST" action="?page=messages&action=compose">
            <input type="hidden" name="to_username"
                   value="<?= htmlspecialchars($reply_to_user) ?>">
            <input type="hidden" name="subject"
                   value="<?= htmlspecialchars($reply_subject) ?>">
            <input type="hidden" name="reply_to" value="<?= $message['id'] ?>">
            <textarea name="content"
                      placeholder="Напишите ответ..."
                      required></textarea>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-send">
                    ✉ Отправить ответ
                </button>
                <a href="?page=messages" class="btn btn-back">Отмена</a>
            </div>
        </form>
    </div>

</div>

<script>
function toggleMsg(i) {
    const el = document.getElementById('msg_' + i);
    if (el) {
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
}
</script>

</body>
</html>