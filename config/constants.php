<?php

if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
}

if (!defined('ROLE_SUPER_ADMIN')) {
    define('ROLE_SUPER_ADMIN', 'super-admin');
}

if (!defined('ROLE_PARENT')) {
    define('ROLE_PARENT', 'parent');
}

if (!defined('ROLE_TEACHER')) {
    define('ROLE_TEACHER', 'teacher');
}

if (!defined('ROLE_STUDENT')) {
    define('ROLE_STUDENT', 'student');
}

if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 15);
}

if (!defined('BASE_URL')) {
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $appRoot = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';

    if ($documentRoot !== '' && strpos($appRoot, $documentRoot) === 0) {
        $basePath = substr($appRoot, strlen($documentRoot));
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = '/' . ltrim($basePath, '/');
    } else {
        $basePath = '/spms';
    }

    define('BASE_URL', rtrim($basePath, '/') . '/');
}

if (!function_exists('get_role_home_url')) {
    function get_role_home_url($role)
    {
        switch ($role) {
            case ROLE_SUPER_ADMIN:
                return BASE_URL . 'pages/super_admin/dashboard.php';
            case ROLE_PARENT:
                return BASE_URL . 'pages/parents/dashboard.php';
            case ROLE_TEACHER:
                return BASE_URL . 'pages/teachers/dashboard.php';
            case ROLE_STUDENT:
                return BASE_URL . 'pages/students/dashboard.php';
            case ROLE_ADMIN:
            default:
                return BASE_URL . 'pages/dashboard.php';
        }
    }
}
