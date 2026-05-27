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
    
    $username = sanitizeString($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonError('Username and password required', 400);
    }
    
    // Basic rate limiting: lockout for 30 seconds after 5 failed attempts
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_login_attempt'] = time();
    }
    if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_login_attempt']) < 30) {
        jsonError('Too many failed login attempts. Please wait 30 seconds.', 429);
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if (!$admin || !password_verify($password, $admin['password'])) {
            $_SESSION['login_attempts']++;
            $_SESSION['last_login_attempt'] = time();
            jsonError('Invalid credentials', 401);
        }
        
        // Reset attempts and prevent session fixation
        $_SESSION['login_attempts'] = 0;
        session_regenerate_id(true);
        
        // Set session
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        
        jsonResponse([
            'success' => true, 
            'message' => 'Login successful',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
        
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
            'username' => $_SESSION['admin_username'] ?? '',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    } else {
        jsonResponse(['authenticated' => false]);
    }
}
