<?php require_once __DIR__ . '/../config/config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Написать сообщение — <?= APP_NAME ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial; background:#1a1a0e; color:#ddd; }
        .container { max-width:700px; margin:20px auto; padding:0 15px; }
        .card {
            background:#2a2a1a; border:2px solid #5a4a20;
            border-radius:8px; overflow:hidden;
        }
        .card-header {
            background:#3a2c10; padding:12px 15px;
            font-weight:bold; color:#d4a843; font-size:15px;
        }
        .card-body { padding:20px; }

        .form-group { margin-bottom:15px; }
        label {
            display:block; color:#888; font-size:13px; margin-bottom:6px;
        }
        input[type="text"], textarea {
            width:100%; padding:11px 15px; background:#1a1a0a;
            color:#ddd; border:2px solid #444; border-radius:6px;
            font-size:14px; transition:0.2s; font-family:'Segoe UI',Arial;
        }
        input[type="text"]:focus, textarea:focus {
            border-color:#8b6914; outline:none;
        }
        textarea { height:180px; resize:vertical; }

        .btn {
            padding:10px 25px; border-radius:6px;
            font-size:14px; cursor:pointer; transition:0.2s;
            text-decoration:none; display:inline-block; border:none;
        }
        .btn-send { background:#1a5a1a; color:#fff; border:1px solid #4a8a4a; }
        .btn-send:hover { background:#2a7a2a; }
        .btn-back { background:#5a4a1a; color:#ddd; border:1px solid #8b6914; }
        .btn-back:hover { background:#7a6a2a; }

        .alert-error {
            background:#3a1a1a; border:1px solid #a44;
            border-radius:6px; padding:12px; margin-bottom:15px; color:#f66;
        }

        /* Если это ответ */
        .reply-context {
            background:#1a1a0a; border:1px solid #444;
            border-radius:6px; padding:12px; margin-bottom:15px;
        }
        .reply-context-header {
            font-size:12px; color:#888; margin-bottom:8px;
        }
        .reply-context-text {
            font-size:12px; color:#aaa; line-height:1.6;
            max-height:100px; overflow:hidden;
            border-left:3px solid #5a4a20; padding-left:10px;
        }

        /* Автодополнение */
        .autocomplete-wrap { position:relative; }
        .autocomplete-list {
            position:absolute; top:100%; left:0; right:0;
            background:#2a2a1a; border:1px solid #8b6914;
            border-radius:0 0 6px 6px; z-index:100;
            max-height:150px; overflow-y:auto;
        }
        .autocomplete-item {
            padding:8px 12px; cursor:pointer; font-size:13px;
        }
        .autocomplete-item:hover { background:#3a2c10; color:#d4a843; }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<div class="container">

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-error"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            ✉ <?= $reply_data ? 'Ответить на сообщение' : 'Написать сообщение' ?>
        </div>
        <div class="card-body">

            <!-- Контекст ответа -->
            <?php if ($reply_data): ?>
            <div class="reply-context">
                <div class="reply-context-header">
                    Ответ на: «<?= htmlspecialchars($reply_data['subject']) ?>»
                    от <?= htmlspecialchars($reply_data['from_name'] ?? '?') ?>
                </div>
                <div class="reply-context-text">
                    <?= nl2br(htmlspecialchars(mb_substr($reply_data['content'], 0, 200))) ?>
                    <?= mb_strlen($reply_data['content']) > 200 ? '...' : '' ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="?page=messages&action=compose">
                <?php if ($reply_data): ?>
                    <input type="hidden" name="reply_to" value="<?= $reply_data['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>👤 Получатель:</label>
                    <div class="autocomplete-wrap">
                        <input type="text" name="to_username" id="toUsername"
                               value="<?= htmlspecialchars($to_username ?? '') ?>"
                               placeholder="Имя игрока"
                               required autocomplete="off"
                               oninput="searchUsers(this.value)">
                        <div class="autocomplete-list" id="autocompleteList"
                             style="display:none;"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>📝 Тема:</label>
                    <input type="text" name="subject"
                           value="<?= htmlspecialchars(
                               $reply_data
                                   ? 'Re: ' . preg_replace('/^(Re:\s*)+/i', '', $reply_data['subject'])
                                   : ($_POST['subject'] ?? '')
                           ) ?>"
                           placeholder="Тема сообщения"
                           required maxlength="128">
                </div>

                <div class="form-group">
                    <label>💬 Сообщение:</label>
                    <textarea name="content"
                              placeholder="Текст сообщения..."
                              required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </div>

                <div style="display:flex; gap:10px;">
                    <button type="submit" class="btn btn-send">
                        ✉ Отправить
                    </button>
                    <a href="?page=messages" class="btn btn-back">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let searchTimer = null;

function searchUsers(query) {
    clearTimeout(searchTimer);
    const list = document.getElementById('autocompleteList');

    if (query.length < 2) {
        list.style.display = 'none';
        return;
    }

    searchTimer = setTimeout(() => {
        fetch('?page=api&action=search_users&q=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(users => {
                list.innerHTML = '';
                if (users.length === 0) {
                    list.style.display = 'none';
                    return;
                }
                users.forEach(u => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-item';
                    div.textContent = u.username;
                    div.onclick = () => {
                        document.getElementById('toUsername').value = u.username;
                        list.style.display = 'none';
                    };
                    list.appendChild(div);
                });
                list.style.display = 'block';
            })
            .catch(() => { list.style.display = 'none'; });
    }, 300);
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.autocomplete-wrap')) {
        document.getElementById('autocompleteList').style.display = 'none';
    }
});
</script>

</body>
</html>