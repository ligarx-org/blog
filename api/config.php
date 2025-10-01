<?php
// Database configuration and utility functions
class Database {
    private $host = 'localhost';
    private $dbname = 'stacknro_blog';
    private $username = 'stacknro_blog';
    private $password = 'admin-2025';
    private $pdo;
    
    public function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            error_log("Database connection successful: " . date('Y-m-d H:i:s'));
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// Utility functions
function sanitizeInput($data) {
    if ($data === null) {
        return '';
    }
    if ($data === '') {
        return '';
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && str_ends_with(strtolower($email), '@gmail.com');
}

function validateCaptcha($provided, $expected) {
    return !empty($provided) && !empty($expected) && strtolower($provided) === strtolower($expected);
}

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Logging functions
function logSuccess($message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => 'SUCCESS',
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    error_log(json_encode($logEntry));
}

function logError($message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => 'ERROR',
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    error_log(json_encode($logEntry));
}

function logInfo($message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => 'INFO',
        'message' => $message,
        'context' => $context
    ];
    error_log(json_encode($logEntry));
}
?>