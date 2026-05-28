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
                    error_log('Database creation failed: ' . $e2->getMessage());
                    http_response_code(500);
                    die(json_encode([
                        'success' => false,
                        'error' => 'Database creation failed'
                    ]));
                }
            } else {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'error' => 'Database connection failed'
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

// ===============================
// INPUT VALIDATION & SANITIZATION
// ===============================
if (!function_exists('sanitizeString')) {

    /**
     * Trim whitespace and cap string length.
     */
    function sanitizeString($value, $maxLength = 500)
    {
        if ($value === null) return null;
        $value = trim((string)$value);
        if ($maxLength > 0 && mb_strlen($value) > $maxLength) {
            $value = mb_substr($value, 0, $maxLength);
        }
        return $value;
    }
}

if (!function_exists('validateEmail')) {

    /**
     * Returns the sanitized email or false if invalid.
     */
    function validateEmail($email)
    {
        $email = trim((string)$email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }
}

if (!function_exists('validateUrl')) {

    /**
     * Validate a URL: must be http(s) or a relative path.
     * Blocks javascript:, data:, vbscript: URIs.
     */
    function validateUrl($url)
    {
        if ($url === null || $url === '') return null;
        $url = trim((string)$url);

        // Block dangerous URI schemes
        $lower = strtolower($url);
        $dangerousSchemes = ['javascript:', 'data:', 'vbscript:', 'blob:'];
        foreach ($dangerousSchemes as $scheme) {
            if (strpos($lower, $scheme) === 0) {
                return null;
            }
        }

        // Allow relative paths (start with / or alphanumeric) or valid http(s) URLs
        if (preg_match('#^https?://#i', $url) || preg_match('#^[a-zA-Z0-9./]#', $url)) {
            return $url;
        }

        return null;
    }
}

if (!function_exists('validateStatus')) {

    /**
     * Validate project status against allowed values.
     */
    function validateStatus($status, $allowed = ['active', 'completed', 'upcoming'])
    {
        $status = strtolower(trim((string)$status));
        return in_array($status, $allowed, true) ? $status : $allowed[0];
    }
}

// ===============================
// SMTP / EMAIL CONFIGURATION
// ===============================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'gagsfoundation@gmail.com');
define('SMTP_PASS', 'YOUR_GMAIL_APP_PASSWORD'); // TODO: Replace this with your 16-character App Password
define('SMTP_FROM', 'gagsfoundation@gmail.com');
define('SMTP_FROM_NAME', 'GAGS Foundation');

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if (!function_exists('sendEmail')) {
    /**
     * Send an email using SMTP via PHPMailer
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content
     * @param string $plainBody Optional plain text fallback
     * @param array $attachments Optional array of attachments (each: ['path', 'name', 'cid', 'disposition'])
     * @return bool True on success, false on failure
     */
    function sendEmail($to, $subject, $htmlBody, $plainBody = '', $attachments = [])
    {
        // Require PHPMailer files manually
        require_once __DIR__ . '/libs/PHPMailer/Exception.php';
        require_once __DIR__ . '/libs/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/libs/PHPMailer/SMTP.php';

        // Check if the password is still the default placeholder
        if (SMTP_PASS === '' || SMTP_PASS === 'YOUR_GMAIL_APP_PASSWORD') {
            error_log("SMTP Error: Gmail App Password is not configured in config.php. Skipping email send.");
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            // Timeout settings (email sending shouldn't hang forever)
            $mail->Timeout    = 15;

            // Recipients
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);

            // Attachments & Inline Images
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $name = $attachment['name'] ?? '';
                        $encoding = $attachment['encoding'] ?? 'base64';
                        $type = $attachment['type'] ?? '';
                        $disposition = $attachment['disposition'] ?? 'attachment';
                        
                        if ($disposition === 'inline' && isset($attachment['cid'])) {
                            $mail->addEmbeddedImage($attachment['path'], $attachment['cid'], $name, $encoding, $type);
                        } else {
                            $mail->addAttachment($attachment['path'], $name, $encoding, $type);
                        }
                    }
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody ?: strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail sending failed to $to: " . $mail->ErrorInfo);
            return false;
        }
    }
}