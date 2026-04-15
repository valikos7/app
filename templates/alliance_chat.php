<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Чат альянса — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }

        .container {
            max-width:1100px; margin:20px auto; padding:0 15px;
            display:grid; grid-template-columns:1fr 240px; gap:15px;
            height:calc(100vh - 120px);
        }

        /* Чат */
        .chat-card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:10px; overflow:hidden;
            display:flex; flex-direction:column;
        }
        .chat-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
            display:flex; justify-content:space-between; align-items:center;
            flex-shrink:0;
        }
        .chat-messages {
            flex:1; overflow-y:auto; padding:15px;
            display:flex; flex-direction:column; gap:8px;
        }
        .chat-messages::-webkit-scrollbar { width:6px; }
        .chat-messages::-webkit-scrollbar-track { background:#1a1a0a; }
        .chat-messages::-webkit-scrollbar-thumb { background:#5a4a20; border-radius:3px; }

        .msg {
            display:flex; gap:10px; align-items:flex-start;
            max-width:85%;
        }
        .msg.mine { align-self:flex-end; flex-direction:row-reverse; }

        .msg-avatar {
            width:32px; height:32px; border-radius:50%;
            background:#3a2c10; border:2px solid #8b6914;
            display:flex; align-items:center; justify-content:center;
            font-size:14px; flex-shrink:0;
        }
        .msg.mine .msg-avatar { border-color:#4a8a4a; background:#1a3a1a; }

        .msg-bubble {
            background:#1a1a0a; border:1px solid #444;
            border-radius:10px; padding:8px 12px;
            max-width:100%;
        }
        .msg.mine .msg-bubble {
            background:#1a2a1a; border-color:#4a6a4a;
        }

        .msg-username {
            font-size:11px; font-weight:bold;
            color:#d4a843; margin-bottom:3px;
        }
        .msg.mine .msg-username { color:#4f4; text-align:right; }

        .msg-text { font-size:13px; color:#ccc; line-height:1.4; word-break:break-word; }
        .msg-time { font-size:10px; color:#666; margin-top:3px; }
        .msg.mine .msg-time { text-align:right; }

        /* Системные сообщения */
        .msg-system {
            text-align:center; font-size:11px; color:#666;
            padding:5px; align-self:center;
        }

        /* Форма отправки */
        .chat-form {
            padding:12px 15px; background:#3a2c10;
            border-top:1px solid #5a4a20;
            display:flex; gap:10px; flex-shrink:0;
        }
        .chat-input {
            flex:1; padding:10px 14px; background:#1a1a0a;
            color:#ddd; border:2px solid #444; border-radius:20px;
            font-size:13px; outline:none; transition:0.2s;
            font-family:'Segoe UI',Arial;
            resize:none; height:40px; max-height:100px;
        }
        .chat-input:focus { border-color:#8b6914; }
        .chat-send {
            width:40px; height:40px; background:#5a4a1a;
            border:1px solid #8b6914; border-radius:50%;
            color:#d4a843; font-size:18px; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            transition:0.2s; flex-shrink:0;
        }
        .chat-send:hover { background:#7a6a2a; }

        /* Боковая панель */
        .side-panel {
            display:flex; flex-direction:column; gap:12px;
        }
        .side-card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:10px; overflow:hidden;
        }
        .side-header {
            background:#3a2c10; padding:10px 14px;
            font-weight:bold; color:#d4a843; font-size:13px;
        }
        .side-body { padding:12px; }

        .member-item {
            display:flex; align-items:center; gap:8px;
            padding:6px 0; border-bottom:1px solid #333;
            font-size:12px;
        }
        .member-item:last-child { border-bottom:none; }
        .member-status {
            width:8px; height:8px; border-radius:50%; flex-shrink:0;
        }
        .status-online  { background:#0f0; }
        .status-offline { background:#555; }
        .member-name { flex:1; color:#ddd; }
        .member-name a {
            color:#ddd; text-decoration:none;
        }
        .member-name a:hover { color:#d4a843; }

        /* Счётчик символов */
        .char-counter { font-size:10px; color:#666; text-align:right; margin-top:4px; }

        @media(max-width:768px) {
            .container { grid-template-columns:1fr; height:auto; }
            .chat-card  { height:60vh; }
            .side-panel { display:none; }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <!-- Чат -->
    <div class="chat-card">
        <div class="chat-header">
            💬 Чат альянса [<?= htmlspecialchars($alliance['tag']) ?>]
            <?= htmlspecialchars($alliance['name']) ?>
            <span style="font-size:12px; color:#888;" id="onlineCount">
                <?= count(array_filter($members, fn($m) => $m['last_activity'] >= time()-300)) ?>
                онлайн
            </span>
        </div>

        <div class="chat-messages" id="chatMessages">
            <?php foreach ($messages as $msg):
                $is_mine = ($msg['user_id'] == $_SESSION['user_id']);
                $time_str = date('H:i', $msg['time']);
            ?>
            <div class="msg <?= $is_mine ? 'mine' : '' ?>"
                 data-id="<?= $msg['id'] ?>">
                <div class="msg-avatar">
                    <?= mb_substr($msg['username'], 0, 1) ?>
                </div>
                <div>
                    <div class="msg-bubble">
                        <?php if (!$is_mine): ?>
                        <div class="msg-username">
                            <?= htmlspecialchars($msg['username']) ?>
                        </div>
                        <?php endif; ?>
                        <div class="msg-text">
                            <?= htmlspecialchars($msg['message']) ?>
                        </div>
                    </div>
                    <div class="msg-time"><?= $time_str ?></div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($messages)): ?>
            <div class="msg-system">
                Начните общение с союзниками! 🏰
            </div>
            <?php endif; ?>
        </div>

        <div class="chat-form">
            <textarea class="chat-input" id="chatInput"
                      placeholder="Написать сообщение... (Enter — отправить)"
                      maxlength="500" rows="1"></textarea>
            <button class="chat-send" id="sendBtn" title="Отправить">
                ➤
            </button>
        </div>
    </div>

    <!-- Боковая панель -->
    <div class="side-panel">

        <!-- Участники альянса -->
        <div class="side-card">
            <div class="side-header">
                👥 Участники (<?= count($members) ?>)
            </div>
            <div class="side-body">
                <?php foreach ($members as $m):
                    $is_online = ($m['last_activity'] >= time() - 300);
                ?>
                <div class="member-item">
                    <div class="member-status <?= $is_online ? 'status-online' : 'status-offline' ?>">
                    </div>
                    <div class="member-name">
                        <a href="?page=player&id=<?= $m['id'] ?>">
                            <?= htmlspecialchars($m['username']) ?>
                        </a>
                    </div>
                    <?php if (!$is_online): ?>
                    <span style="font-size:10px; color:#555;">
                        <?= date('H:i', $m['last_activity']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Быстрые ссылки -->
        <div class="side-card">
            <div class="side-header">⚡ Быстрые действия</div>
            <div class="side-body">
                <?php
                $alliance_id_link = $alliance['id'];
                $links = [
                    ["?page=alliance&id={$alliance_id_link}", '🏰', 'Управление альянсом'],
                    ["?page=map",    '🗺', 'Карта мира'],
                    ["?page=ranking&tab=alliances", '🏆', 'Рейтинг альянсов'],
                    ["?page=messages", '✉', 'Личные сообщения'],
                ];
                foreach ($links as $l): ?>
                <a href="<?= $l[0] ?>"
                   style="display:block; padding:7px 0; color:#aaa;
                          text-decoration:none; font-size:12px;
                          border-bottom:1px solid #333; transition:0.2s;"
                   onmouseover="this.style.color='#d4a843'"
                   onmouseout="this.style.color='#aaa'">
                    <?= $l[1] ?> <?= $l[2] ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<script>
const myUserId   = <?= (int)$_SESSION['user_id'] ?>;
const allianceId = <?= (int)$alliance['id'] ?>;
let lastId       = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;
let isPolling    = true;

// Скролл вниз
function scrollToBottom() {
    const el = document.getElementById('chatMessages');
    el.scrollTop = el.scrollHeight;
}
scrollToBottom();

// Добавить сообщение в чат
function appendMessage(msg) {
    const el    = document.getElementById('chatMessages');
    const isMine = (parseInt(msg.uid) === myUserId);

    const time = new Date(msg.time * 1000);
    const timeStr = time.getHours().toString().padStart(2,'0') + ':' +
                    time.getMinutes().toString().padStart(2,'0');

    const div = document.createElement('div');
    div.className = 'msg' + (isMine ? ' mine' : '');
    div.dataset.id = msg.id;
    div.innerHTML = `
        <div class="msg-avatar">${msg.username.charAt(0)}</div>
        <div>
            <div class="msg-bubble">
                ${!isMine ? `<div class="msg-username">${escHtml(msg.username)}</div>` : ''}
                <div class="msg-text">${escHtml(msg.message)}</div>
            </div>
            <div class="msg-time">${timeStr}</div>
        </div>
    `;
    el.appendChild(div);

    // Удаляем заглушку если есть
    const placeholder = el.querySelector('.msg-system');
    if (placeholder) placeholder.remove();
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;')
              .replace(/</g,'&lt;')
              .replace(/>/g,'&gt;')
              .replace(/"/g,'&quot;');
}

// Опрос новых сообщений (long polling)
async function pollMessages() {
    while (isPolling) {
        try {
            const resp = await fetch(
                `?page=alliance_chat&action=get&since=${lastId}`
            );
            const data = await resp.json();

            if (data.messages && data.messages.length > 0) {
                const el = document.getElementById('chatMessages');
                const wasAtBottom = (
                    el.scrollHeight - el.scrollTop - el.clientHeight < 50
                );

                data.messages.forEach(msg => {
                    if (parseInt(msg.id) > lastId) {
                        appendMessage(msg);
                        lastId = parseInt(msg.id);
                    }
                });

                if (wasAtBottom) scrollToBottom();
            }
        } catch (e) {
            console.error('Ошибка polling:', e);
        }

        await new Promise(r => setTimeout(r, 2000));
    }
}

// Отправка сообщения
async function sendMessage() {
    const input = document.getElementById('chatInput');
    const msg   = input.value.trim();

    if (!msg) return;

    input.value = '';
    input.style.height = '40px';

    try {
        const form = new FormData();
        form.append('message', msg);

        const resp = await fetch('?page=alliance_chat&action=send', {
            method: 'POST',
            body:   form
        });
        const data = await resp.json();

        if (data.error) {
            alert(data.error);
            input.value = msg;
        }
    } catch (e) {
        alert('Ошибка отправки');
        input.value = msg;
    }
}

// Кнопка отправки
document.getElementById('sendBtn').addEventListener('click', sendMessage);

// Enter для отправки (Shift+Enter — перенос строки)
document.getElementById('chatInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Автовысота textarea
document.getElementById('chatInput').addEventListener('input', function() {
    this.style.height = '40px';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});

// Запускаем polling
pollMessages();

// Остановить при уходе со страницы
window.addEventListener('beforeunload', () => { isPolling = false; });
</script>

</body>
</html>