<?php
/**
 * GAGS Foundation - Projects Routes
 * 
 * GET    /api/projects      — List all projects (public)
 * POST   /api/projects      — Create project (auth required)
 * PUT    /api/projects/:id  — Update project (auth required)
 * DELETE /api/projects/:id  — Delete project (auth required)
 */

function handleProjectsRoute($method, $id) {
    switch ($method) {
        case 'GET':
            getProjects();
            break;
        case 'POST':
            requireAuth();
            createProject();
            break;
        case 'PUT':
            requireAuth();
            if (!$id) jsonError('Project ID required', 400);
            updateProject($id);
            break;
        case 'DELETE':
            requireAuth();
            if (!$id) jsonError('Project ID required', 400);
            deleteProject($id);
            break;
        default:
            jsonError('Method Not Allowed', 405);
    }
}

// ============ GET /api/projects ============
function getProjects() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT * FROM projects ORDER BY created_at DESC');
        $projects = $stmt->fetchAll();
        jsonResponse($projects);
    } catch (PDOException $e) {
        error_log('Get projects error: ' . $e->getMessage());
        jsonError('Failed to fetch projects', 500);
    }
}

// ============ POST /api/projects ============
function createProject() {
    try {
        $data = getJsonBody();
        
        $title = sanitizeString($data['title'] ?? '', 255);
        if (empty($title)) {
            jsonError('Project title is required', 400);
        }
        
        $goal_amount = max(0, (float)($data['goal_amount'] ?? 0));
        $raised_amount = max(0, (float)($data['raised_amount'] ?? 0));
        $status = validateStatus($data['status'] ?? 'active');
        $image = validateUrl($data['image'] ?? '');
        
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO projects (title, description, goal_amount, raised_amount, status, image) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $title,
            sanitizeString($data['description'] ?? '', 5000),
            $goal_amount,
            $raised_amount,
            $status,
            $image,
        ]);
        
        jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        error_log('Create project error: ' . $e->getMessage());
        jsonError('Failed to create project', 500);
    }
}

// ============ PUT /api/projects/:id ============
function updateProject($id) {
    try {
        $data = getJsonBody();
        
        $title = sanitizeString($data['title'] ?? '', 255);
        if (empty($title)) {
            jsonError('Project title is required', 400);
        }
        
        $goal_amount = max(0, (float)($data['goal_amount'] ?? 0));
        $raised_amount = max(0, (float)($data['raised_amount'] ?? 0));
        $status = validateStatus($data['status'] ?? 'active');
        $image = validateUrl($data['image'] ?? '');
        
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'UPDATE projects SET title = ?, description = ?, goal_amount = ?, raised_amount = ?, status = ?, image = ? WHERE id = ?'
        );
        $stmt->execute([
            $title,
            sanitizeString($data['description'] ?? '', 5000),
            $goal_amount,
            $raised_amount,
            $status,
            $image,
            (int)$id,
        ]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Update project error: ' . $e->getMessage());
        jsonError('Failed to update project', 500);
    }
}

// ============ DELETE /api/projects/:id ============
function deleteProject($id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM projects WHERE id = ?');
        $stmt->execute([(int)$id]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Delete project error: ' . $e->getMessage());
        jsonError('Failed to delete project', 500);
    }
}
