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
