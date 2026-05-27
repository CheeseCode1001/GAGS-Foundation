<?php
/**
 * GAGS Foundation - Authentication Helper
 * 
 * Provides session-based authentication check equivalent
 * to the Express requireAuth middleware.
 */

require_once __DIR__ . '/config.php';

/**
 * Check if the current session is authenticated as admin.
 * If not, sends 401 JSON response and exits.
 */
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['admin_id'])) {
        jsonError('Unauthorized', 401);
    }
}

/**
 * Check if the user is currently authenticated (non-blocking).
 * Returns true/false without sending a response.
 */
function isAuthenticated() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return !empty($_SESSION['admin_id']);
}
