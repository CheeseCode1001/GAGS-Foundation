<?php
/**
 * GAGS Foundation - Donations Routes
 * 
 * GET    /api/donations      — List all donations (auth required)
 * POST   /api/donations      — Create donation (public)
 * DELETE /api/donations/:id  — Delete donation (auth required)
 */

function handleDonationsRoute($method, $id) {
    switch ($method) {
        case 'GET':
            requireAuth();
            getDonations();
            break;
        case 'POST':
            createDonation();
            break;
        case 'DELETE':
            requireAuth();
            if (!$id) jsonError('Donation ID required', 400);
            deleteDonation($id);
            break;
        default:
            jsonError('Method Not Allowed', 405);
    }
}

// ============ GET /api/donations ============
function getDonations() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT * FROM donations ORDER BY created_at DESC');
        $donations = $stmt->fetchAll();
        jsonResponse($donations);
    } catch (PDOException $e) {
        error_log('Get donations error: ' . $e->getMessage());
        jsonError('Failed to fetch donations', 500);
    }
}

// ============ POST /api/donations ============
function createDonation() {
    try {
        $data = getJsonBody();
        
        $donorName = trim($data['donor_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $amount = $data['amount'] ?? null;
        
        if (empty($donorName) || empty($email) || empty($amount)) {
            jsonError('Missing required fields', 400);
        }
        
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO donations (donor_name, email, amount, project_id, message) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $donorName,
            $email,
            (float)$amount,
            !empty($data['project_id']) ? (int)$data['project_id'] : null,
            $data['message'] ?? null,
        ]);
        
        jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        error_log('Create donation error: ' . $e->getMessage());
        jsonError('Failed to create donation', 500);
    }
}

// ============ DELETE /api/donations/:id ============
function deleteDonation($id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM donations WHERE id = ?');
        $stmt->execute([(int)$id]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Delete donation error: ' . $e->getMessage());
        jsonError('Failed to delete donation', 500);
    }
}
