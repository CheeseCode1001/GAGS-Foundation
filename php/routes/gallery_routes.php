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
        // Check for uploaded file
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            jsonError('No image uploaded', 400);
        }
        
        $file = $_FILES['image'];
        $caption = $_POST['caption'] ?? '';
        $category = $_POST['category'] ?? 'general';
        
        // Create uploads directory if it doesn't exist
        $uploadsDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        // Generate unique filename (matches Node.js multer naming)
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = time() . '-' . mt_rand(100000000, 999999999) . '.' . $ext;
        $destPath = $uploadsDir . DIRECTORY_SEPARATOR . $uniqueName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            jsonError('Failed to save uploaded file', 500);
        }
        
        // Store relative path (matches Node.js format)
        $imagePath = '/assets/images/uploads/' . $uniqueName;
        
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
        
        if ($item) {
            // Delete the physical file
            $fullPath = dirname(__DIR__, 2) . $item['image'];
            // Normalize path separators for Windows
            $fullPath = str_replace('/', DIRECTORY_SEPARATOR, $fullPath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
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
