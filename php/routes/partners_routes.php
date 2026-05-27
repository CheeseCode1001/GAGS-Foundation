<?php
/**
 * GAGS Foundation - Partners Routes
 * 
 * POST   /api/partners      — Submit partnership application (public)
 * GET    /api/partners       — List all partner applications (auth required)
 * DELETE /api/partners/:id   — Delete partner application (auth required)
 */

function handlePartnersRoute($method, $id) {
    switch ($method) {
        case 'POST':
            createPartner();
            break;
        case 'GET':
            requireAuth();
            getPartners();
            break;
        case 'DELETE':
            requireAuth();
            if (!$id) jsonError('Partner ID required', 400);
            deletePartner($id);
            break;
        default:
            jsonError('Method Not Allowed', 405);
    }
}

// ============ POST /api/partners ============
function createPartner() {
    try {
        $data = getJsonBody();
        
        $orgName = sanitizeString($data['org_name'] ?? '', 255);
        $email = validateEmail($data['email'] ?? '');
        
        if (empty($orgName) || empty($email)) {
            jsonError('Valid organization name and email required', 400);
        }
        
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO partners (org_name, contact_name, email, phone, partnership_type, message) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $orgName,
            sanitizeString($data['contact_name'] ?? '', 255),
            $email,
            sanitizeString($data['phone'] ?? '', 50),
            sanitizeString($data['partnership_type'] ?? '', 100),
            sanitizeString($data['message'] ?? '', 2000),
        ]);
        
        jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        error_log('Create partner error: ' . $e->getMessage());
        jsonError('Failed to submit partnership application', 500);
    }
}

// ============ GET /api/partners ============
function getPartners() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT * FROM partners ORDER BY created_at DESC');
        $partners = $stmt->fetchAll();
        jsonResponse($partners);
    } catch (PDOException $e) {
        error_log('Get partners error: ' . $e->getMessage());
        jsonError('Failed to fetch partners', 500);
    }
}

// ============ DELETE /api/partners/:id ============
function deletePartner($id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM partners WHERE id = ?');
        $stmt->execute([(int)$id]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Delete partner error: ' . $e->getMessage());
        jsonError('Failed to delete partner', 500);
    }
}
