<?php
/**
 * GAGS Foundation - Configuration & Database Connection
 * Works with XAMPP + MariaDB/MySQL
 */

// ===============================
// DATABASE CONFIGURATION
// ===============================
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gags_foundation');

// ===============================
// APP CONFIGURATION
// ===============================
define('SESSION_SECRET', 'gags_foundation_secret_key_2025');
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// ===============================
// BACKWARD COMPATIBILITY HELPER
// ===============================
if (!function_exists('getEnv')) {
    function getEnv($key, $default = null)
    {
        if (defined($key)) {
            return constant($key);
        }

        return $default;
    }
}

// ===============================
// DATABASE CONNECTION
// ===============================
if (!function_exists('getDB')) {

    function getDB()
    {
        static $pdo = null;

        // Reuse existing connection
        if ($pdo !== null) {
            return $pdo;
        }

        try {
            // STEP 1: Connect WITH database
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

            $pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

        } catch (PDOException $e) {
            // Fallback: If database doesn't exist, create it
            if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                    $pdo = new PDO(
                        $dsn,
                        DB_USER,
                        DB_PASS,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]
                    );

                    $pdo->exec("
                        CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`
                        CHARACTER SET utf8mb4
                        COLLATE utf8mb4_unicode_ci
                    ");

                    $pdo->exec("USE `" . DB_NAME . "`");
                } catch (PDOException $e2) {
                    http_response_code(500);
                    die(json_encode([
                        'success' => false,
                        'error' => 'Database creation failed',
                        'message' => $e2->getMessage()
                    ]));
                }
            } else {
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'error' => 'Database connection failed',
                    'message' => $e->getMessage()
                ]));
            }
        }

        return $pdo;
    }
}

// ===============================
// JSON RESPONSE HELPERS
// ===============================
if (!function_exists('jsonResponse')) {

    function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        exit;
    }
}

if (!function_exists('jsonError')) {

    function jsonError($message, $statusCode = 500)
    {
        jsonResponse([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }
}

// ===============================
// GET JSON REQUEST BODY
// ===============================
if (!function_exists('getJsonBody')) {

    function getJsonBody()
    {
        $raw = file_get_contents('php://input');

        if (!$raw) {
            return [];
        }

        $data = json_decode($raw, true);

        // If invalid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $data;
    }
}