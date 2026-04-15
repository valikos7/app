<?php
// core/Security.php

class Security {

    // ==============================
    // CSRF Токены
    // ==============================

    /**
     * Генерация CSRF токена
     */
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Проверка CSRF токена
     */
    public static function checkCsrf() {
        $token = $_POST['csrf_token']
              ?? $_GET['csrf_token']
              ?? $_SERVER['HTTP_X_CSRF_TOKEN']
              ?? '';

        if (empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            die("
            <!DOCTYPE html>
            <html><head><meta charset='UTF-8'><title>Ошибка безопасности</title>
            <style>
                body{font-family:Arial;background:#1a1a0e;color:#ddd;
                     display:flex;align-items:center;justify-content:center;
                     height:100vh;text-align:center;}
                h2{color:#f44;} a{color:#d4a843;}
            </style></head><body>
            <div>
                <h2>⚠ Ошибка безопасности</h2>
                <p>Недействительный токен запроса.</p>
                <a href='javascript:history.back()'>← Назад</a>
            </div></body></html>");
        }

        // Обновляем токен после проверки
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * HTML поле с CSRF токеном
     */
    public static function csrfField() {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' .
               htmlspecialchars($token) . '">';
    }

    /**
     * Мета-тег для AJAX
     */
    public static function csrfMeta() {
        $token = self::generateCsrfToken();
        return '<meta name="csrf-token" content="' .
               htmlspecialchars($token) . '">';
    }

    // ==============================
    // Rate Limiting
    // ==============================

    /**
     * Проверка лимита запросов
     */
    public static function rateLimit($action, $max_attempts = 5, $window = 300) {
        $key = 'rate_' . $action . '_' . self::getClientIp();

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first' => time()];
        }

        $data = &$_SESSION[$key];

        // Сбрасываем если окно истекло
        if (time() - $data['first'] > $window) {
            $data = ['count' => 0, 'first' => time()];
        }

        $data['count']++;

        if ($data['count'] > $max_attempts) {
            $wait = $window - (time() - $data['first']);
            return [
                'allowed' => false,
                'wait'    => $wait,
                'message' => "Слишком много попыток. Подождите " .
                             ceil($wait / 60) . " мин."
            ];
        }

        return ['allowed' => true, 'count' => $data['count']];
    }

    /**
     * Сброс лимита
     */
    public static function resetRateLimit($action) {
        $key = 'rate_' . $action . '_' . self::getClientIp();
        unset($_SESSION[$key]);
    }

    // ==============================
    // Валидация
    // ==============================

    /**
     * Безопасная строка
     */
    public static function sanitizeString($str, $max_len = 255) {
        $str = trim($str);
        $str = strip_tags($str);
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        return mb_substr($str, 0, $max_len);
    }

    /**
     * Безопасное целое число
     */
    public static function sanitizeInt($val, $min = 0, $max = PHP_INT_MAX) {
        $val = (int)$val;
        return max($min, min($max, $val));
    }

    /**
     * Валидация email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Валидация имени пользователя
     */
    public static function validateUsername($username) {
        if (strlen($username) < 3 || strlen($username) > 32) {
            return 'Имя должно быть от 3 до 32 символов';
        }
        if (!preg_match('/^[a-zA-Zа-яёА-ЯЁ0-9_\- ]+$/u', $username)) {
            return 'Имя содержит недопустимые символы';
        }
        return true;
    }

    /**
     * Валидация пароля
     */
    public static function validatePassword($password) {
        if (strlen($password) < 6) {
            return 'Пароль должен быть минимум 6 символов';
        }
        if (strlen($password) > 128) {
            return 'Пароль слишком длинный';
        }
        return true;
    }

    // ==============================
    // IP адрес
    // ==============================

    public static function getClientIp() {
        $keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    // ==============================
    // XSS защита
    // ==============================

    /**
     * Безопасный вывод
     */
    public static function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Безопасный вывод с переносами строк
     */
    public static function nl($str) {
        return nl2br(self::e($str));
    }

    // ==============================
    // Логирование подозрительных запросов
    // ==============================

    public static function logSuspicious($reason, $db = null) {
        $ip   = self::getClientIp();
        $page = $_SERVER['REQUEST_URI'] ?? '';
        $user = $_SESSION['user_id'] ?? 0;

        $msg = "[SECURITY] IP:{$ip} User:{$user} Page:{$page} Reason:{$reason}";
        error_log($msg);

        if ($db && $user) {
            try {
                $db->prepare("INSERT INTO activity_log
                    (user_id, action, details, time)
                    VALUES (?, 'security', ?, ?)")
                    ->execute([$user, $reason, time()]);
            } catch (Exception $e) {}
        }
    }
}