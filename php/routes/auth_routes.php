<?php
/**
 * GAGS Foundation - Authentication Routes
 * 
 * POST /api/login       — Admin login
 * POST /api/logout      — Admin logout
 * GET  /api/auth-status — Check authentication status
 */

function handleAuthRoute($resource, $method) {
    switch ($resource) {
        case 'login':
            if ($method !== 'POST') {
                jsonError('Method Not Allowed', 405);
            }
            handleLogin();
            break;
            
        case 'logout':
            if ($method !== 'POST') {
                jsonError('Method Not Allowed', 405);
            }
            handleLogout();
            break;
            
        case 'auth-status':
            if ($method !== 'GET') {
                jsonError('Method Not Allowed', 405);
            }
            handleAuthStatus();
            break;
            
        default:
            jsonError('Not Found', 404);
    }
}

// ============ POST /api/login ============
function handleLogin() {
    $data = getJsonBody();
    
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonError('Username and password required', 400);
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            jsonError('Invalid credentials', 401);
        }
        
        if (!password_verify($password, $admin['password'])) {
            jsonError('Invalid credentials', 401);
        }
        
        // Set session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        
        jsonResponse(['success' => true, 'message' => 'Login successful']);
        
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        jsonError('Login failed', 500);
    }
}

// ============ POST /api/logout ============
function handleLogout() {
    $_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    
    session_destroy();
    
    jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
}

// ============ GET /api/auth-status ============
function handleAuthStatus() {
    if (!empty($_SESSION['admin_id'])) {
        jsonResponse([
            'authenticated' => true,
            'username' => $_SESSION['admin_username'] ?? ''
        ]);
    } else {
        jsonResponse(['authenticated' => false]);
    }
}
