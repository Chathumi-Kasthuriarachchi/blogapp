<?php
require_once '../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Not logged in']);
  exit;
}

if (!isset($_POST['post_id'])) {
  echo json_encode(['success' => false, 'message' => 'No post ID']);
  exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);

// Check if already liked
$check = $conn->prepare("SELECT * FROM likes WHERE post_id=? AND user_id=?");
$check->execute([$post_id, $user_id]);

if ($check->rowCount() > 0) {
  // Unlike
  $conn->prepare("DELETE FROM likes WHERE post_id=? AND user_id=?")->execute([$post_id, $user_id]);
  $liked = false;
} else {
  // Like
  $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
  $liked = true;
}

// Return updated like count
$countStmt = $conn->prepare("SELECT COUNT(*) AS totalLikes FROM likes WHERE post_id=?");
$countStmt->execute([$post_id]);
$totalLikes = $countStmt->fetch(PDO::FETCH_ASSOC)['totalLikes'];

echo json_encode([
  'success' => true,
  'liked' => $liked,
  'totalLikes' => $totalLikes
]);
?>
