<?php
/**
 * GAGS Foundation - Gallery Routes
 * 
 * GET    /api/gallery      — List all gallery items (public)
 * POST   /api/gallery      — Upload image to gallery (auth required, multipart form)
 * DELETE /api/gallery/:id  — Delete gallery item + file (auth required)
 */

function handleGalleryRoute($method, $id) {
    switch ($method) {
        case 'GET':
            getGallery();
            break;
        case 'POST':
            requireAuth();
            createGalleryItem();
            break;
        case 'DELETE':
            requireAuth();
            if (!$id) jsonError('Gallery ID required', 400);
            deleteGalleryItem($id);
            break;
        default:
            jsonError('Method Not Allowed', 405);
    }
}

// ============ GET /api/gallery ============
function getGallery() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query('SELECT * FROM gallery ORDER BY created_at DESC');
        $gallery = $stmt->fetchAll();
        jsonResponse($gallery);
    } catch (PDOException $e) {
        error_log('Get gallery error: ' . $e->getMessage());
        jsonError('Failed to fetch gallery', 500);
    }
}

// ============ POST /api/gallery ============
function createGalleryItem() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $imagePath = validateUrl($input['image'] ?? '');
        if (empty($imagePath)) {
            jsonError('Valid image URL provided is required', 400);
        }
        
        $caption = sanitizeString($input['caption'] ?? '', 255);
        $category = sanitizeString($input['category'] ?? 'general', 100);
        
        $pdo = getDB();
        $stmt = $pdo->prepare(
            'INSERT INTO gallery (image, caption, category) VALUES (?, ?, ?)'
        );
        $stmt->execute([$imagePath, $caption, $category]);
        
        jsonResponse([
            'success' => true,
            'id' => (int)$pdo->lastInsertId(),
            'image' => $imagePath,
        ]);
    } catch (PDOException $e) {
        error_log('Create gallery error: ' . $e->getMessage());
        jsonError('Failed to create gallery item', 500);
    }
}

// ============ DELETE /api/gallery/:id ============
function deleteGalleryItem($id) {
    try {
        $pdo = getDB();
        
        // Get image path before deleting record
        $stmt = $pdo->prepare('SELECT image FROM gallery WHERE id = ?');
        $stmt->execute([(int)$id]);
        $item = $stmt->fetch();
        
        if ($item && !empty($item['image'])) {
            // Fix path traversal: make sure it's within the upload directory or at least realpath matches
            $fullPath = dirname(__DIR__, 2) . '/' . ltrim($item['image'], '/');
            $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
            
            if (file_exists($fullPath)) {
                $realPath = realpath($fullPath);
                $basePath = realpath(dirname(__DIR__, 2));
                
                // Only delete if it exists and is inside the project directory
                if ($realPath && strpos($realPath, $basePath) === 0) {
                    unlink($realPath);
                }
            }
        }
        
        // Delete database record
        $stmt = $pdo->prepare('DELETE FROM gallery WHERE id = ?');
        $stmt->execute([(int)$id]);
        
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        error_log('Delete gallery error: ' . $e->getMessage());
        jsonError('Failed to delete gallery item', 500);
    }
}
