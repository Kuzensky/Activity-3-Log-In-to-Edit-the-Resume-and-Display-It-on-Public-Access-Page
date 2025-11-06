<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once 'db.php';

// Handle remove request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    $resume_id = $_POST['resume_id'] ?? null;

    if (!$resume_id) {
        echo json_encode(['success' => false, 'message' => 'Resume ID required']);
        exit;
    }

    // Get current picture path
    $stmt = $pdo->prepare("SELECT profile_picture FROM resume_profile WHERE resume_id = ?");
    $stmt->execute([$resume_id]);
    $picture_path = $stmt->fetchColumn();

    // Delete file if exists
    if ($picture_path && file_exists(__DIR__ . '/' . $picture_path)) {
        unlink(__DIR__ . '/' . $picture_path);
    }

    // Update database to remove picture
    $stmt = $pdo->prepare("UPDATE resume_profile SET profile_picture = NULL, updated_at = CURRENT_TIMESTAMP WHERE resume_id = ?");

    if ($stmt->execute([$resume_id])) {
        echo json_encode(['success' => true, 'message' => 'Profile picture removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $resume_id = $_POST['resume_id'] ?? null;

    if (!$resume_id) {
        echo json_encode(['success' => false, 'message' => 'Resume ID required']);
        exit;
    }

    $file = $_FILES['profile_picture'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
        exit;
    }

    // Validate file type (only images)
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed']);
        exit;
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
        exit;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $resume_id . '_' . time() . '.' . $extension;

    // Ensure uploads directory exists
    $upload_dir = __DIR__ . '/uploads';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $upload_path = $upload_dir . '/' . $filename;

    // Delete old profile picture if exists
    $stmt = $pdo->prepare("SELECT profile_picture FROM resume_profile WHERE resume_id = ?");
    $stmt->execute([$resume_id]);
    $old_picture = $stmt->fetchColumn();

    if ($old_picture && file_exists(__DIR__ . '/' . $old_picture)) {
        unlink(__DIR__ . '/' . $old_picture);
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Update database - insert if not exists, update if exists
        $relative_path = 'uploads/' . $filename;
        $stmt = $pdo->prepare("
            INSERT INTO resume_profile (resume_id, profile_picture, updated_at, created_at)
            VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ON CONFLICT (resume_id) DO UPDATE SET
                profile_picture = EXCLUDED.profile_picture,
                updated_at = CURRENT_TIMESTAMP
        ");

        if ($stmt->execute([$resume_id, $relative_path])) {
            echo json_encode([
                'success' => true,
                'message' => 'Profile picture uploaded successfully',
                'path' => $relative_path
            ]);
        } else {
            // Delete uploaded file if database update fails
            unlink($upload_path);
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}
?>
