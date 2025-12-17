<?php
/**
 * 3WAY Car Service - Download Media File
 */
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('غير مصرح');
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    exit('معرف غير صالح');
}

$db = Database::getInstance()->getConnection();

// Get media info
$stmt = $db->prepare("SELECT op.*, j.created_by, j.branch FROM order_photos op JOIN job_orders j ON op.order_id = j.id WHERE op.id = ?");
$stmt->execute([$id]);
$media = $stmt->fetch();

if (!$media) {
    http_response_code(404);
    exit('الملف غير موجود');
}

// Check access
if (isReception()) {
    if ($media['created_by'] != $_SESSION['user_id']) {
        http_response_code(403);
        exit('غير مصرح');
    }
} elseif (!isAdmin()) {
    $userBranch = getUserBranch();
    if ($userBranch && $media['branch'] !== $userBranch) {
        http_response_code(403);
        exit('غير مصرح');
    }
}

$filePath = __DIR__ . '/../' . $media['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('الملف غير موجود');
}

// Get file info
$fileName = $media['file_name'];
$fileSize = filesize($filePath);
$mimeType = mime_content_type($filePath);

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . $fileSize);
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Clear output buffer
ob_clean();
flush();

// Output file
readfile($filePath);
exit;
