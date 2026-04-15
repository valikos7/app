<?php
// templates/admin/sidebar.php
$current_section = $_GET['section'] ?? '';
$current_action  = $_GET['action']  ?? '';
$current_page    = $_GET['page']    ?? '';
?>
<div class="admin-sidebar">
    <div class="admin-logo">
        ⚙ Админ панель
    </div>
    <nav class="admin-nav">
        <div class="nav-section">Основное</div>
        <a href="?page=admin"
           class="<?= ($current_page==='admin'&&!$current_section)?'active':'' ?>">
            📊 Дашборд
        </a>
        <a href="?page=home" target="_blank">
            🎮 Открыть игру
        </a>

        <div class="nav-divider"></div>
        <div class="nav-section">Управление</div>

        <a href="?page=admin&section=players"
           class="<?= $current_section==='players'?'active':'' ?>">
            👥 Игроки
        </a>
        <a href="?page=admin&section=announcements"
           class="<?= $current_section==='announcements'?'active':'' ?>">
            📢 Объявления
        </a>
        <a href="?page=admin&section=events"
           class="<?= $current_section==='events'?'active':'' ?>">
            🌍 События
        </a>
        <a href="?page=admin&section=units"
           class="<?= $current_section==='units'?'active':'' ?>">
            ⚔ Юниты
        </a>
        <a href="?page=admin&section=settings"
           class="<?= $current_section==='settings'?'active':'' ?>">
            ⚙ Настройки
        </a>

        <div class="nav-divider"></div>
        <div class="nav-section">Статистика</div>

        <a href="?page=ranking">🏆 Рейтинг</a>
        <a href="?page=reports">📋 Отчёты</a>
        <a href="?page=map">🗺 Карта</a>

        <div class="nav-divider"></div>
        <div class="nav-section">Аккаунт</div>
        <a href="?page=profile">👤 Мой профиль</a>
        <a href="?page=logout" style="color:#f66;">🚪 Выйти</a>
    </nav>
</div>