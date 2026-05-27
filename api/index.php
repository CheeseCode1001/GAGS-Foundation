<?php
/**
 * GAGS Foundation - API Front-Controller
 * 
 * Single entry point for all /api/* requests.
 * Parses the URI and routes to the appropriate handler.
 */

// ============ SESSION START ============
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours (matches Express config)
        'path'     => '/',
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
    session_start();
}

// ============ LOAD CORE ============
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/init_db.php';

// Initialize database schema and default admin
initializeDatabase();

// ============ CORS & HEADERS ============
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');


// ============ PARSE REQUEST ============
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove query string
$uri = strtok($uri, '?');

// Remove trailing slash
$uri = rtrim($uri, '/');

// Normalize: extract path after /api
// Handle both /api/programs and /api/programs/5
if (preg_match('#/api(?:/index\.php)?(/.*)?$#', $uri, $matches)) {
    $path = $matches[1] ?? '';
} else {
    jsonError('Not Found', 404);
}

// ============ ROUTE MATCHING ============
// Parse path segments: /resource or /resource/id
$segments = explode('/', trim($path, '/'));
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

// ============ CSRF PROTECTION ============
// Generate a CSRF token if one doesn't exist in the session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validate CSRF token on state-changing requests (POST/PUT/DELETE)
// Exempt public endpoints: donations (POST), partners (POST), login (POST)
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    $csrfExempt = (
        ($resource === 'donations' && $method === 'POST') ||
        ($resource === 'partners' && $method === 'POST') ||
        ($resource === 'login' && $method === 'POST')
    );

    if (!$csrfExempt) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            jsonError('Invalid or missing CSRF token', 403);
        }
    }
}

// ============ LOAD ROUTE HANDLERS ============
$routesDir = __DIR__ . '/../php/routes';

switch ($resource) {
    case 'login':
    case 'logout':
    case 'auth-status':
        require_once $routesDir . '/auth_routes.php';
        handleAuthRoute($resource, $method);
        break;
        
    case 'programs':
        require_once $routesDir . '/programs_routes.php';
        handleProgramsRoute($method, $id);
        break;
        
    case 'projects':
        require_once $routesDir . '/projects_routes.php';
        handleProjectsRoute($method, $id);
        break;
        
    case 'donations':
        require_once $routesDir . '/donations_routes.php';
        handleDonationsRoute($method, $id);
        break;
        
    case 'gallery':
        require_once $routesDir . '/gallery_routes.php';
        handleGalleryRoute($method, $id);
        break;
        
    case 'partners':
        require_once $routesDir . '/partners_routes.php';
        handlePartnersRoute($method, $id);
        break;
        
    default:
        jsonError('Not Found', 404);
}
