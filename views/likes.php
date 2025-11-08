<?php
// Handle like/unlike requests
require_once '../config/db.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'] ?? null;

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success'=>false, 'error'=>'not_logged_in']);
  exit;
}

$user_id = $_SESSION['user_id'];

// Check if user already liked
$check = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
$check->execute([$user_id, $post_id]);
$like = $check->fetch();

if ($like) {
  // Unlike
  $delete = $conn->prepare("DELETE FROM likes WHERE id = ?");
  $delete->execute([$like['id']]);
} else {
  // Like
  $insert = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
  $insert->execute([$user_id, $post_id]);
}

// Return updated count
$count = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$count->execute([$post_id]);
$total = $count->fetchColumn();

echo json_encode(['success'=>true, 'likes'=>$total]);
exit;
