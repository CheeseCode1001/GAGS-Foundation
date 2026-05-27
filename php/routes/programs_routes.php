<?php
/**
 * GAGS Foundation - Programs Routes
 * 
 * GET    /api/programs      — List all programs (public)
 * POST   /api/programs      — Create program (auth required)
 * PUT    /api/programs/:id  — Update program (auth required)
 * DELETE /api/programs/:id  — Delete program (auth required)
 */

function handleProgramsRoute($method, $id) {
    switch ($method) {
        case 'GET':
            getPrograms();
            break;
        case 'POST':
            requireAuth();
            createProgram();
            break;
        case 'PUT':
            requireAuth();
            if (!$id) jsonError('Program ID required', 400);
            updateProgram($id);
            break;
        case 'DELETE':
            requireAuth();
            if (!$id) jsonError('Program ID required', 400);
            deleteProgram($id);
            break;
        default:
            jsonError('Method Not Allowed', 405);
    }
}

// ============ GET /api/programs ============
function getPrograms() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT * FROM programs ORDER BY created_at DESC');
        $programs = $stmt->fetchAll();
        jsonResponse($programs);
    } catch (PDOException $e) {
        error_log('Get programs error: ' . $e->getMessage());
        jsonError('Failed to fetch programs', 500);
    }
}

// ============ POST /api/programs ============
function createProgram() {
    try {
        $data = getJsonBody();
        
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO programs (title, tag, description, image) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'] ?? '',
            $data['tag'] ?? null,
            $data['description'] ?? null,
            $data['image'] ?? null,
        ]);
        
        jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        error_log('Create program error: ' . $e->getMessage());
        jsonError('Failed to create program', 500);
    }
}

// ============ PUT /api/programs/:id ============
function updateProgram($id) {
    try {
        $data = getJsonBody();
        
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'UPDATE programs SET title = ?, tag = ?, description = ?, image = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['title'] ?? '',
            $data['tag'] ?? null,
            $data['description'] ?? null,
            $data['image'] ?? null,
            (int)$id,
        ]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Update program error: ' . $e->getMessage());
        jsonError('Failed to update program', 500);
    }
}

// ============ DELETE /api/programs/:id ============
function deleteProgram($id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM programs WHERE id = ?');
        $stmt->execute([(int)$id]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Delete program error: ' . $e->getMessage());
        jsonError('Failed to delete program', 500);
    }
}
