<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$message_id = $_GET['id'] ?? 0;

if (!$message_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
    exit();
}

$db = Database::getInstance()->getConnection();

// جلب الرسالة
$stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
$stmt->execute([$message_id]);
$message = $stmt->fetch();

if (!$message) {
    echo json_encode(['success' => false, 'error' => 'Message not found']);
    exit();
}

// تعليم الرسالة كمقروءة
$stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
$stmt->execute([$message_id]);

// تنسيق التاريخ
$message['created_at'] = date('Y-m-d H:i', strtotime($message['created_at']));

echo json_encode([
    'success' => true,
    'message' => $message
]);
